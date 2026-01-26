<?php

/**
 * ===================================================================
 *   CUSTOM FIELDS SCHEMA ENDPOINT CALLBACK
 *   Devuelve el schema de todos los custom fields para el frontend React.
 * ===================================================================
 */
function custom_fields_schema_callback($request) {
    // Include the file where the schema is defined
    require_once get_template_directory() . '/inc/custom-meta/custom-fields-types.php';
    global $custom_fields_schema;
    return new WP_REST_Response($custom_fields_schema, 200);
}

/**
 * =============================================================================
 *   🍔 UNIFIED MENUS ENDPOINT CALLBACK
 * =============================================================================
 * - Si se llama sin parámetros (/menus), devuelve todos los menús registrados.
 * - Si se llama con ?location=..., devuelve el menú de esa ubicación.
 * - Si se llama con ?slug=..., devuelve el menú con ese slug.
 */
function menus_endpoint_callback($request) {
    $slug = $request->get_param('slug');
    $location = $request->get_param('location');
    $current_lang = apply_filters('wpml_current_language', NULL);

    // If an identifier is specified, return only that menu.
    if ($slug || $location) {
        $menu_items = null;
        if ($location) {
            // WPML: Get translated locations using icl_object_id
            $locations = get_nav_menu_locations();
            
            if (isset($locations[$location])) {
                $menu_id = $locations[$location];
                
                // 🌐 WPML: Translate menu ID to current language
                if (function_exists('icl_object_id') && $current_lang) {
                    $translated_menu_id = icl_object_id($menu_id, 'nav_menu', false, $current_lang);
                    if ($translated_menu_id) {
                        $menu_id = $translated_menu_id;
                    }
                }
                
                $menu_items = wp_get_nav_menu_items($menu_id);
            }
        } elseif ($slug) {
            $menu_items = wp_get_nav_menu_items($slug);
        }

        if (empty($menu_items)) {
            return new WP_Error('no_menu_found', 'No se encontró ningún menú con el identificador proporcionado.', ['status' => 404]);
        }

        return new WP_REST_Response(build_menu_tree(clean_menu_items($menu_items)), 200);
    }

    // If no parameters, return all menus.
    $all_menus_data = get_all_menus_data();
    return new WP_REST_Response($all_menus_data, 200);
}

/**
 * Helper: Obtiene y estructura los datos de todos los menús registrados.
 */
function get_all_menus_data() {
    $all_menus = [];
    $registered_menus = get_terms('nav_menu', ['hide_empty' => true]);
    $locations = get_nav_menu_locations();

    foreach ($registered_menus as $menu) {
        $menu_items = wp_get_nav_menu_items($menu->term_id);
        $menu_location = array_search($menu->term_id, $locations);

        $all_menus[$menu->slug] = [
            'slug' => $menu->slug,
            'name' => $menu->name,
            'location' => $menu_location ?: null,
            'items' => build_menu_tree(clean_menu_items($menu_items))
        ];
    }
    return $all_menus;
}

function clean_menu_items($menu_items) {
    $cleaned_items = [];
    foreach ($menu_items as $item) {
        $cleaned_items[] = [
            'id' => $item->ID,
            'parent' => $item->menu_item_parent,
            'title' => $item->title,
            'url' => $item->url,
            'target' => $item->target,
            'classes' => $item->classes,
        ];
    }
    return $cleaned_items;
}

/**
 * Función de ayuda para construir una estructura de árbol a partir de una lista plana.
 */
function build_menu_tree(array &$elements, $parentId = 0) {
    $branch = [];
    foreach ($elements as $key => &$element) {
        if ($element['parent'] == $parentId) {
            $children = build_menu_tree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
            // No need to remove the element, as we pass by reference
            // and each element is only processed once as a child.
        }
    }
    return $branch;
}

/**
 * ===================================================================
 * 🔎 Callback for CUSTOM SEARCH ENDPOINT
 * ===================================================================
 * Executes a direct SQL query for full control, avoiding conflicts
 * with WP_Query when CPTs have the same slug as the search term.
 * 
 * Excludes specific CPTs from search results (configured in /inc/config/search-config.php)
 */
function custom_search_callback($request) {
    global $wpdb;
    $search_term = sanitize_text_field($request['term']);

    if (empty($search_term)) {
        return new WP_REST_Response([], 200);
    }

    $like_term = '%' . $wpdb->esc_like($search_term) . '%';

    // Get searchable post types (excludes CPTs defined in search-config.php)
    $post_types = get_search_included_cpts();
    
    // If no post types remain after exclusion, return empty
    if (empty($post_types)) {
        return new WP_REST_Response([], 200);
    }
    
    $post_type_placeholders = implode(', ', array_fill(0, count($post_types), '%s'));

    // FINAL SOLUTION: Separate search in title/content, meta, and taxonomies to avoid LEFT JOIN issues.

    // 1. Search in title and content
    $title_content_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($post_type_placeholders) AND (post_title LIKE %s OR post_content LIKE %s)",
        array_merge(
            array_values($post_types),
            [$like_term, $like_term]
        )
    ));

    // 2. Search in custom fields (postmeta)
    $meta_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
        $like_term
    ));

    // 3. Search in taxonomy names (terms)
    $term_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT tr.object_id FROM {$wpdb->term_relationships} AS tr
         INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
         INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
         WHERE t.name LIKE %s",
        $like_term
    ));

    // 4. Merge all IDs and remove duplicates
    $all_ids = array_merge($title_content_ids, $meta_ids, $term_ids);
    $post_ids = array_unique(array_map('intval', $all_ids));

    if (empty($post_ids)) { // Early check in case there are no IDs
        return new WP_REST_Response([], 200);
    }

    // Final filter to ensure all IDs correspond to published posts of the correct types.
    // This is crucial because searching meta and terms can return IDs of non-public posts.
    $post_ids_placeholders = implode(',', array_fill(0, count($post_ids), '%d')); // placeholders for IDs
    $final_query_args = array_merge($post_ids, array_values($post_types));
    
    $post_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE ID IN ($post_ids_placeholders) AND post_status = 'publish' AND post_type IN ($post_type_placeholders)",
        $final_query_args
    ));

    if (empty($post_ids)) {
        return new WP_REST_Response([], 200);
    }

    // Use WP_Query now that we have the IDs, it's the safe way to get post data.
    $query = new WP_Query([
        'post__in' => $post_ids,
        'post_type' => 'any',
        'posts_per_page' => 20,
        'orderby' => 'post__in',
    ]);

    $results = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'url' => get_permalink(),
                'type' => get_post_type(),
                'subtype' => get_post_type(),
                '_embedded' => ['self' => [['excerpt' => ['rendered' => get_the_excerpt()]]]],
            ];
        }
    }
    wp_reset_postdata();

    return new WP_REST_Response($results, 200);
}

/**
 * ===================================================================
 * 🚀 [NUEVO] Callback para el Endpoint de Traducciones Universal
 * =================================================================== 
 * Utiliza las funciones de WPML para obtener las URLs de todas las
 * traducciones de un contenido específico (página, post, CPT, o archivo de CPT).
 */
function get_universal_translations_callback($request) {
    $post_type = $request->get_param('post_type');
    $slug = $request->get_param('slug');
    // The 'lang' parameter is no longer needed to find the post, WPML will handle it.
    
    // We do not check 'function_exists' here. We trust that the 'rest_api_loaded' middleware
    // has already been executed. If WPML is not active, the 'apply_filters' functions will simply
    // devolverán el valor de entrada, lo que resultará en un error 404 más adelante si no se encuentra nada,
    // lo cual es un comportamiento más predecible que un error 500.

    $active_languages = apply_filters('wpml_active_languages', NULL, 'orderby=id&order=desc');
    $default_lang = apply_filters('wpml_default_language', NULL);
    $urls = [];

    $element_id = 0;
    $element_type = 'post_' . $post_type;

    // Determinar si es un archivo de CPT o un post individual
    if ($slug === $post_type) {
        // Es un archivo de CPT (ej. /noticias)
        foreach ($active_languages as $code => $lang_info) {
            $translated_cpt_slug = apply_filters('wpml_get_translated_slug', $post_type, $post_type, $code);
            if ($code === $default_lang) {
                $urls[$code] = '/' . $translated_cpt_slug;
            } else {
                $urls[$code] = '/' . $code . '/' . $translated_cpt_slug;
            }
        }
        return new WP_REST_Response($urls, 200);
    } else {
        // Es un post individual o una página
        // El slug puede estar en cualquier idioma, así que intentamos buscar en TODOS los idiomas.
        
        $post = null;
        
        // Primero, intentar buscar en el idioma actual
        $post = get_page_by_path($slug, OBJECT, $post_type);
        if (!$post) {
            $posts = get_posts(['name' => $slug, 'post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => 1]);
            if (!empty($posts)) {
                $post = $posts[0];
            }
        }

        // Si aún no encontramos, intentar en todos los idiomas disponibles
        if (!$post && function_exists('wpml_get_active_languages')) {
            $active_languages = apply_filters('wpml_active_languages', NULL, 'orderby=id&order=desc');
            
            foreach ($active_languages as $lang_code => $lang_info) {
                // Cambiar temporalmente a ese idioma para buscar
                do_action('wpml_switch_language', $lang_code);
                
                // Intentar buscar el post en este idioma
                $post = get_page_by_path($slug, OBJECT, $post_type);
                if (!$post) {
                    $posts = get_posts(['name' => $slug, 'post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => 1]);
                    if (!empty($posts)) {
                        $post = $posts[0];
                        break; // Encontramos, salir del loop
                    }
                } else {
                    break; // Encontramos, salir del loop
                }
            }
        }

        if (!$post) {
            return new WP_Error('not_found', "Content not found for slug '{$slug}' and post_type '{$post_type}' in any language.", ['status' => 404]);
        }

        $element_id = $post->ID;
        
        // Ahora que tenemos el ID, obtenemos el tipo de elemento correcto para WPML.
        // Esto es importante porque para las páginas es 'post_page' y para los posts es 'post_post'.
        $element_type = apply_filters('wpml_element_type', $post->post_type);
    }

    // Obtener el TRID (Translation Group ID)
    $trid = apply_filters('wpml_element_trid', NULL, $element_id, $element_type);

    if (!$trid) {
        return new WP_Error('no_trid', 'Could not find translation group for the element.', ['status' => 404]);
    }

    // Obtener todas las traducciones de ese grupo
    $translations = apply_filters('wpml_get_element_translations', NULL, $trid, $element_type);

    if (empty($translations)) {
        return new WP_Error('no_translations', 'No translations found.', ['status' => 404]);
    }

    // Construir el mapa de URLs
    foreach ($translations as $code => $translation) {
        $translated_post = get_post($translation->element_id);
        if ($translated_post) {
            $permalink = get_permalink($translated_post->ID);
            // Limpiar la URL para que sea relativa al frontend
            $relative_url = str_replace(home_url(), '', $permalink);

            // Asegurarse de que el idioma por defecto no tenga prefijo
            if ($code === $default_lang) {
                // Eliminar cualquier prefijo de idioma que WPML pueda añadir por error
                $relative_url = preg_replace('/^\/' . $code . '\//', '/', $relative_url);
            }
            
            // Para la home, la URL debe ser '/' para el idioma por defecto
            if ($translated_post->post_name === 'inicio' && $code === $default_lang) {
                $relative_url = '/';
            }

            $urls[$code] = $relative_url;
        }
    }

    return new WP_REST_Response($urls, 200);
}


/**
 * ===================================================================
 * ↔️ Callback para el endpoint de Navegación de Posts
 * ===================================================================
 * Devuelve el post anterior y siguiente para un post_id dado.
 */
function get_post_navigation_callback($request) {
    $post_id = $request->get_param('post_id');
    $post_type = $request->get_param('post_type');
    $lang = $request->get_param('lang') ?? 'es';
    
    // Get current post
    $current_post = get_post($post_id);
    if (!$current_post) {
        return array('previous' => null, 'next' => null);
    }
    
    // Switch WPML language context
    if (function_exists('icl_object_id')) {
        global $sitepress;
        if ($sitepress) {
            $sitepress->switch_lang($lang);
        }
    }
    
    // Base query args for posts in the same language
    $args = array(
        'post_type' => $post_type,
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'suppress_filters' => false, // CRITICAL: Allows WPML to filter results
        'orderby' => 'date',
    );
    
    // Get PREVIOUS post (older than current)
    $prev_args = array_merge($args, array(
        'order' => 'DESC',
        'date_query' => array(
            array(
                'before' => $current_post->post_date,
                'inclusive' => false
            )
        )
    ));
    $prev_query = new WP_Query($prev_args);
    $prev_post = $prev_query->have_posts() ? $prev_query->posts[0] : null;
    wp_reset_postdata();
    
    // Get NEXT post (newer than current)
    $next_args = array_merge($args, array(
        'order' => 'ASC',
        'date_query' => array(
            array(
                'after' => $current_post->post_date,
                'inclusive' => false
            )
        )
    ));
    $next_query = new WP_Query($next_args);
    $next_post = $next_query->have_posts() ? $next_query->posts[0] : null;
    wp_reset_postdata();
    
    // Format post data for response
    $format_post = function($post) {
        if (!$post) return null;
        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name
        );
    };
    
    return array(
        'previous' => $format_post($prev_post),
        'next' => $format_post($next_post)
    );
}


/**
 * ===================================================================
 * ⚙️ Callback para el endpoint de Información Global del Sitio
 * ===================================================================
 * Recopila y devuelve datos clave de la configuración de WordPress.
 */
function get_global_site_info_callback() {
    // Detect language from query parameter
    $lang_param = isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : null;
    
    // Store original language to restore at the end
    $original_lang = apply_filters('wpml_current_language', null);
    
    // Switch to requested language BEFORE fetching any data
    // This ensures ALL language-dependent data (strings, options, etc.) are in the correct language
    if ($lang_param && $lang_param !== $original_lang) {
        do_action('wpml_switch_language', $lang_param);
    }
    
    // Now fetch all data - it will be in the switched language
    $site_links = get_language_option('site_links') ?: [];
    $primary_link = !empty($site_links) ? $site_links[0] : null;

    $site_info = [
        'title'         => get_bloginfo('name'),           // Translatable via WPML String Translation
        'description'   => get_bloginfo('description'),    // Translatable via WPML String Translation
        'back_url'      => site_url(),
        'front_url'     => $primary_link ? $primary_link['fronturl'] : home_url(),
        'light_logo'    => get_logo_url('site_logo_light'), // Language-specific if configured
        'dark_logo'     => get_logo_url('site_logo_dark'),  // Language-specific if configured
        'favicons'      => [
            'icon_32'   => get_site_icon_url(32),
            'icon_180'  => get_site_icon_url(180),
            'icon_192'  => get_site_icon_url(192),
            'icon_512'  => get_site_icon_url(512),
        ],
        'date_format'   => get_option('date_format'),
        'language'      => get_bloginfo('language'),        // Will reflect the switched language
        'social'        => get_language_option('social_networks') ?: [],    // Already language-specific
        'contact'       => get_language_option('contact_info') ?: [],       // Already language-specific
        'analytics'     => get_language_option('analytics_settings') ?: [], // Already language-specific
        'i18n'          => get_language_option('i18n_settings') ?: ['default_locale' => 'es', 'locales' => ['es']],
    ];
    
    // CRITICAL: Restore original language to avoid affecting other requests
    if ($lang_param && $lang_param !== $original_lang) {
        do_action('wpml_switch_language', $original_lang);
    }

    return new WP_REST_Response($site_info, 200);
}

/**
 * ===================================================================
 * 🎨 Callback para el endpoint de Configuración del Sitio (Headless)
 * ===================================================================
 * Devuelve configuración completa del sitio para frontend headless.
 */
function get_site_config_callback() {
    // Incluir las funciones helper desde site-info.php
    require_once get_template_directory() . '/inc/dashboard/site-info.php';

    $config = [
        'site' => [
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => get_site_url(),
            'headless_url' => get_language_option('headless_front_url'),
            'language' => get_locale(),
        ],
        'logos' => [
            'light' => get_logo_url('site_logo_light'),
            'dark' => get_logo_url('site_logo_dark'),
        ],
        'links' => get_language_option('site_links') ?: [],
        'social' => get_language_option('social_networks') ?: [],
        'contact' => get_language_option('contact_info') ?: [],
        'analytics' => get_language_option('analytics_settings') ?: [],
        'i18n' => get_language_option('i18n_settings') ?: ['default_locale' => 'es', 'locales' => ['es']],
        'navigation' => get_navigation_menus(),
        'timestamp' => current_time('timestamp'),
    ];

    return new WP_REST_Response($config, 200);
}

/**
 * ===================================================================
 * 💥 Callback para el endpoint de Popups Activos
 * ===================================================================
 * Busca todos los 'modales' que tienen el meta '_is_popup' = '1'.
 * La respuesta ya incluye el campo 'popup_settings' gracias al
 * 'register_rest_field' que definimos en custom-fields-types.php.
 */
function get_active_popups_callback() {
     $args = [
         'post_type'      => 'modales',
         'posts_per_page' => -1, // Obtener todos
         'post_status'    => 'publish',
         'meta_query'     => [
             [
                 'key'     => '_is_popup',
                 'value'   => '1',
                 'compare' => '=',
             ],
         ],
     ];
 
     $query = new WP_Query($args);
     $controller = new WP_REST_Posts_Controller('modales');
     $response = array_map(function($post) use ($controller) {
         return $controller->prepare_item_for_response($post, new WP_REST_Request())->get_data();
     }, $query->posts);
 
     return new WP_REST_Response($response, 200);
}

/**
 * ===================================================================
 * 🎬 HERO ENDPOINT CALLBACK
 * ===================================================================
 * Busca heroes activos para una posición específica (home/page/archive/custom)
 * Devuelve configuración y slides del hero en el idioma solicitado.
 * 
 * Parámetros:
 * - position: home|page|archive|custom (obligatorio)
 * - lang: es|en|pt-br (opcional, usa idioma actual por defecto)
 * 
 * Ejemplo: /wp-json/custom/v1/hero?position=home&lang=es
 */
function get_hero_data_callback($request) {
    $position = $request->get_param('position');
    $lang_param = $request->get_param('lang');
    
    // Validar parámetro position
    if (!$position) {
        return new WP_Error('missing_position', 'El parámetro "position" es obligatorio', ['status' => 400]);
    }
    
    $allowed_positions = ['home', 'page', 'archive', 'custom'];
    if (!in_array($position, $allowed_positions)) {
        return new WP_Error('invalid_position', 'Posición no válida. Usa: home, page, archive o custom', ['status' => 400]);
    }
    
    // Store original language to restore at the end
    $original_lang = apply_filters('wpml_current_language', null);
    
    // Switch to requested language BEFORE fetching any data
    if ($lang_param && $lang_param !== $original_lang) {
        do_action('wpml_switch_language', $lang_param);
    }
    
    // Buscar hero activo para la posición especificada
    $args = [
        'post_type'      => 'hero',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => '_hero_active',
                'value'   => '1',
                'compare' => '=',
            ],
            [
                'key'     => '_hero_position',
                'value'   => $position,
                'compare' => '=',
            ],
        ],
    ];
    
    $query = new WP_Query($args);
    
    // Si no hay hero activo para esta posición
    if (!$query->have_posts()) {
        // Restore language before returning
        if ($lang_param && $lang_param !== $original_lang) {
            do_action('wpml_switch_language', $original_lang);
        }
        
        return new WP_REST_Response([
            'active' => false,
            'position' => $position,
            'message' => 'No hay hero activo para esta posición',
        ], 200);
    }
    
    $post = $query->posts[0];
    
    // Obtener configuración del hero
    $settings = [
        'autoplay'      => get_post_meta($post->ID, '_hero_autoplay', true) === '1',
        'interval'      => (int) get_post_meta($post->ID, '_hero_interval', true) ?: 8000,
        'show_arrows'   => get_post_meta($post->ID, '_hero_show_arrows', true) === '1',
        'show_dots'     => get_post_meta($post->ID, '_hero_show_dots', true) === '1',
    ];
    
    // Obtener slides
    $slides_raw = get_post_meta($post->ID, '_hero_slides', true);
    $slides = [];
    
    if (is_array($slides_raw)) {
        foreach ($slides_raw as $slide) {
            $slides[] = [
                'title'               => $slide['title'] ?? '',
                'title_align'         => $slide['title_align'] ?? 'left',
                'subtitle'            => $slide['subtitle'] ?? '',
                'content_position'    => $slide['content_position'] ?? 'center',
                'content_align'       => $slide['content_align'] ?? 'center',
                'overlay_opacity'     => (float) ($slide['overlay_opacity'] ?? 0.3),
                'ken_burns'           => (int) ($slide['ken_burns'] ?? 0),
                'button_text'         => $slide['button_text'] ?? '',
                'button_link'         => $slide['button_link'] ?? '',
                'button_style'        => $slide['button_style'] ?? 'default',
                'background_type'     => $slide['background_type'] ?? 'image',
                'background_image'    => $slide['background_image'] ?? '',
                'background_video'    => $slide['background_video'] ?? '',
                'video_playback_rate' => (float) ($slide['video_playback_rate'] ?? 1),
                'gradient_color_1'    => $slide['gradient_color_1'] ?? '#6366f1',
                'gradient_color_2'    => $slide['gradient_color_2'] ?? '#8b5cf6',
                'gradient_direction'  => $slide['gradient_direction'] ?? 'to bottom',
            ];
        }
    }
    
    $response = [
        'active'   => true,
        'position' => $position,
        'hero_id'  => $post->ID,
        'title'    => $post->post_title,
        'settings' => $settings,
        'slides'   => $slides,
        'language' => apply_filters('wpml_current_language', null),
    ];
    
    // CRITICAL: Restore original language
    if ($lang_param && $lang_param !== $original_lang) {
        do_action('wpml_switch_language', $original_lang);
    }
    
    return new WP_REST_Response($response, 200);
}

/**
 * Helper para obtener URL del logo
 */
function get_logo_url($option_name) {
    $logo_id = get_language_option($option_name);
    if ($logo_id) {
        $logo_url = wp_get_attachment_image_src($logo_id, 'full');
        return $logo_url ? $logo_url[0] : null;
    }
    return null;
}

/**
 * Obtiene los menús de navegación registrados
 */
function get_navigation_menus() {
    $menus = [];
    $registered_menus = get_registered_nav_menus();

    foreach ($registered_menus as $location => $description) {
        $menu_id = get_nav_menu_locations()[$location] ?? null;
        if ($menu_id) {
            $menu_items = wp_get_nav_menu_items($menu_id);
            $menus[$location] = format_menu_items($menu_items);
        }
    }

    return $menus;
}

/**
 * Formatea los items del menú para el API
 */
function format_menu_items($menu_items) {
    if (!$menu_items) return [];

    $formatted_items = [];

    foreach ($menu_items as $item) {
        $formatted_items[] = [
            'id' => $item->ID,
            'title' => $item->title,
            'url' => $item->url,
            'target' => $item->target,
            'parent' => $item->menu_item_parent,
            'classes' => $item->classes,
            'type' => $item->type,
            'object' => $item->object,
            'object_id' => $item->object_id,
        ];
    }

    return build_menu_tree($formatted_items);
}

/**
 * ===================================================================
 * 🌐 Callback para obtener traducción por post_id (WPML)
 * ===================================================================
 * Usa wpml_object_id para obtener el ID traducido y retorna la URL
 */
function get_wpml_translation_by_id_callback($request) {
    $post_id = (int) $request['id'];
    $target_lang = $request['lang'] ?? 'en';
    
    // Get translated ID
    $post_type = get_post_type($post_id);
    $translated_id = apply_filters('wpml_object_id', $post_id, $post_type, false, $target_lang);
    
    if (!$translated_id) {
        return rest_ensure_response(['exists' => false]);
    }
    
    // SOLUCIÓN SIMPLE: Usar get_permalink() y limpiar la URL
    $permalink = get_permalink($translated_id);
    $site_url = get_site_url();
    $relative_url = str_replace($site_url, '', $permalink);
    
    // Si no tiene prefijo de idioma y el target es 'en', agregarlo
    if ($target_lang === 'en' && !str_starts_with($relative_url, '/en')) {
        $relative_url = '/en' . $relative_url;
    }
    
    return rest_ensure_response([
        'exists' => true,
        'original_id' => $post_id,
        'translated_id' => $translated_id,
        'url' => $relative_url,
        'title' => get_the_title($translated_id),
        'post_type' => get_post_type($translated_id)
    ]);
}


/**
 * ===================================================================
 * 🌐 Returns all active languages configured in WPML (WPML)
 * ===================================================================
 */
function get_wpml_languages_callback() {
    // Get active languages from WPML
    $languages = apply_filters('wpml_active_languages', null);
    
    if (empty($languages)) {
        return rest_ensure_response([
            'languages' => [],
            'default' => 'es',
            'message' => 'WPML not active or no languages configured'
        ]);
    }
    
    $formatted = [];
    $default_code = '';
    
    foreach ($languages as $lang) {
        // The default language is the one WITHOUT language code in URL path
        // Example: default has "http://site.com/", others have "http://site.com/en/"
        $parsed_url = parse_url($lang['url']);
        $path = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';
        
        // If path is empty or just the language code, check if it matches the base URL
        $is_default = empty($path) || $path === '';
        
        $formatted[] = [
            'code' => $lang['code'],
            'name' => $lang['translated_name'],
            'native_name' => $lang['native_name'],
            'is_default' => $is_default,
            'url' => $lang['url']
        ];
        
        if ($is_default) {
            $default_code = $lang['code'];
        }
    }
    
    // Fallback: if no default found, use first language
    if (empty($default_code) && !empty($formatted)) {
        $default_code = $formatted[0]['code'];
        $formatted[0]['is_default'] = true;
    }
    
    return rest_ensure_response([
        'languages' => $formatted,
        'default' => $default_code,
        'count' => count($formatted)
    ]);
}

/**
 * =============================================================================
 *   ❤️ POST LIKE ENDPOINT CALLBACK
 *   Increments the like count for a post and returns the new count.
 * =============================================================================
 */
function post_like_callback($request) {
    $post_id = (int) $request->get_param('id');
    
    // Verify post exists
    if (!get_post($post_id)) {
        return new WP_Error('post_not_found', 'Post not found', ['status' => 404]);
    }
    
    // Get current like count
    $current_likes = (int) get_post_meta($post_id, '_post_likes', true);
    
    // Increment
    $new_likes = $current_likes + 1;
    
    // Update post meta
    update_post_meta($post_id, '_post_likes', $new_likes);
    
    return rest_ensure_response([
        'success' => true,
        'post_id' => $post_id,
        'likes' => $new_likes,
        'previous_likes' => $current_likes
    ]);
}

/**
 * =============================================================================
 *   ❤️ GET POST LIKES COUNT CALLBACK
 *   Returns the current like count for a post.
 * =============================================================================
 */
function get_post_likes_callback($request) {
    $post_id = (int) $request->get_param('id');
    
    // Verify post exists
    if (!get_post($post_id)) {
        return new WP_Error('post_not_found', 'Post not found', ['status' => 404]);
    }
    
    // Get current like count
    $likes = (int) get_post_meta($post_id, '_post_likes', true);
    
    return rest_ensure_response([
        'post_id' => $post_id,
        'likes' => $likes
    ]);
}

/**
 * =============================================================================
 *   AUDIO URL FOR RECURSOS
 * =============================================================================
 * Añade audio_url a la respuesta de recursos si existe audio generado
 */
function add_audio_url_to_post_response($response, $post, $request) {
    $audio_id = get_post_meta($post->ID, '_recurso_audio_id', true);
    if ($audio_id) {
        $response->data['audio_url'] = wp_get_attachment_url($audio_id);
    }
    return $response;
}
add_filter('rest_prepare_recursos', 'add_audio_url_to_post_response', 10, 3);
add_filter('rest_prepare_noticias', 'add_audio_url_to_post_response', 10, 3);

