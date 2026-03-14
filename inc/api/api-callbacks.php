<?php

/**
 * ===================================================================
 *   CUSTOM FIELDS SCHEMA ENDPOINT CALLBACK
 *   Devuelve el schema de todos los custom fields para el frontend React.
 * ===================================================================
 */
function custom_fields_schema_callback($request) {
    $schema_file = get_template_directory() . '/inc/custom-meta/custom-fields.php';
    
    // Verify the file exists before including it
    if (!file_exists($schema_file)) {
        return new WP_REST_Response([
            'error' => 'Schema file not found',
            'path' => $schema_file,
            'message' => 'The custom-fields.php file is missing or the path is incorrect'
        ], 500);
    }
    
    // Include the file where the schema is defined
    require_once $schema_file;
    global $custom_fields_schema;
    
    // Verify the global variable exists and is not empty
    if (!isset($GLOBALS['custom_fields_schema']) || empty($GLOBALS['custom_fields_schema'])) {
        return new WP_REST_Response([
            'error' => 'Schema not defined',
            'message' => 'The custom_fields_schema global variable is empty or not defined'
        ], 500);
    }
    
    return new WP_REST_Response($GLOBALS['custom_fields_schema'], 200);
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
    $lang = $request->get_param('lang'); // Get language from request parameter
    
    // Si se pasa el parámetro lang, usarlo para WPML
    if ($lang && function_exists('wpml_object_id')) {
        do_action('wpml_switch_language', $lang);
    }
    
    $current_lang = $lang ?: apply_filters('wpml_current_language', NULL);

    // If an identifier is specified, return only that menu.
    if ($slug || $location) {
        $menu_items = null;
        if ($location) {
            // WPML: Get translated locations using icl_object_id
            $locations = get_nav_menu_locations();
            
            if (isset($locations[$location])) {
                $menu_id = $locations[$location];
                
                // 🌐 WPML: Translate menu ID to current language
                if ($current_lang) {
                    $translated_menu_id = apply_filters('wpml_object_id', $menu_id, 'nav_menu', false, $current_lang);
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

        $cleaned_menu = clean_menu_items($menu_items, $lang);
        return new WP_REST_Response(build_menu_tree($cleaned_menu), 200);
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

function clean_menu_items($menu_items, $lang = null) {
    $cleaned_items = [];
    
    // Detectar el path base de WordPress automáticamente
    // rest_url('/wp-json') devuelve algo como 'https://penkode.com/headless/wp-json'
    // Extraemos solo el path: '/headless/wp-json'
    $rest_path = parse_url(rest_url('/wp-json'), PHP_URL_PATH); // '/headless/wp-json'
    $base_path = str_replace('/wp-json', '', $rest_path);       // '/headless'
    
    // Obtener el idioma por defecto de WPML
    $default_lang = apply_filters('wpml_default_language', null);
    
    // Determinar si necesitamos añadir prefijo de idioma
    $needs_prefix = false;
    if ($lang && $lang !== $default_lang) {
        $needs_prefix = true;
        $lang_prefix = '/' . $lang;
    }
    
    foreach ($menu_items as $item) {
        // Quitar el dominio y el path base de WordPress
        $url = str_replace(home_url(), '', $item->url);
        $url = str_replace($base_path, '', $url);
        
        // Normalizar slashes duplicados
        $url = preg_replace('#/+#', '/', $url);
        
        // Si queda vacío, usar /
        if (empty($url)) $url = '/';
        
        // NO modificar URLs que son solo '#' (enlaces internos para submenús)
        if ($url !== '#') {
            // AÑADIR PREFIJO DE IDIOMA si no es el idioma por defecto
            if ($needs_prefix && !str_starts_with($url, $lang_prefix)) {
                $url = $lang_prefix . $url;
            }
        }
        
        $cleaned_items[] = [
            'id' => $item->ID,
            'parent' => $item->menu_item_parent,
            'title' => $item->title,
            'url' => $url,
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
            
            // QUITAR EL PREFIJO DE WORDPRESS (/headless) de la URL
            $wp_base_path = '/headless';
            if (str_starts_with($relative_url, $wp_base_path)) {
                $relative_url = substr($relative_url, strlen($wp_base_path));
            }

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
function get_global_site_info_callback($request) {
    // Language from REST request param (reliable) or fallback to GET for non-REST calls
    $lang_param = $request ? $request->get_param('lang') : null;
    if ($lang_param === null && isset($_GET['lang'])) {
        $lang_param = sanitize_text_field($_GET['lang']);
    }

    $original_lang = apply_filters('wpml_current_language', null);

    if ($lang_param && $lang_param !== $original_lang) {
        do_action('wpml_switch_language', $lang_param);
    }

    $site_links = get_language_option('site_links') ?: [];
    $primary_link = !empty($site_links) ? $site_links[0] : null;
    $front_url = ($primary_link && !empty($primary_link['fronturl'])) ? $primary_link['fronturl'] : null;
    if ($front_url === null) {
        $front_url = get_language_option('headless_front_url');
    }
    if ($front_url === false || $front_url === '') {
        $front_url = home_url();
    }

    $site_info = [
        'title'         => get_bloginfo('name'),
        'description'   => get_bloginfo('description'),
        'back_url'      => site_url(),
        'front_url'     => $front_url,
        'light_logo'    => get_logo_url('site_logo_light'),
        'dark_logo'     => get_logo_url('site_logo_dark'),
        'favicons'      => [
            'icon_32'   => get_site_icon_url(32),
            'icon_180'  => get_site_icon_url(180),
            'icon_192'  => get_site_icon_url(192),
            'icon_512'  => get_site_icon_url(512),
        ],
        'date_format'   => get_option('date_format'),
        'language'      => get_bloginfo('language'),
        'social'        => get_language_option('social_networks') ?: [],
        'contact'       => array_values(array_map(function ($item) {
            return is_array($item) ? array_merge(['type' => '', 'value' => ''], $item) : ['type' => '', 'value' => ''];
        }, get_language_option('contact_info') ?: [])),
        'analytics'     => get_language_option('analytics_settings') ?: [],
        'i18n'          => get_language_option('i18n_settings') ?: ['default_locale' => 'es', 'locales' => ['es']],
    ];

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
 * 🎠 SLIDER ENDPOINT CALLBACK
 * ===================================================================
 * Returns the full slider data for a given slider ID.
 * For type=cpt, fetches actual posts and returns them with embedded media.
 * For other types, returns the stored slides with resolved image URLs.
 */

function pk_slider_resolve_images($slides) {
    foreach ($slides as &$slide) {
        if (!empty($slide['image_id'])) {
            $full  = wp_get_attachment_image_url($slide['image_id'], 'large');
            $thumb = wp_get_attachment_image_url($slide['image_id'], 'thumbnail');
            $slide['image_url']   = $full ?: '';
            $slide['image_thumb'] = $thumb ?: '';
        }
    }
    return $slides;
}

function get_slider_data_callback($request) {
    $id   = absint($request['id']);
    $lang = $request->get_param('lang');
    $post = get_post($id);

    if (!$post || $post->post_type !== 'sliders' || $post->post_status !== 'publish') {
        return new WP_Error('slider_not_found', __('Slider not found', 'penkode-headless'), ['status' => 404]);
    }

    $type   = get_post_meta($id, '_slider_type', true) ?: 'cpt';
    $config = get_post_meta($id, '_slider_config', true) ?: [];

    $response = [
        'id'     => $id,
        'title'  => $post->post_title,
        'type'   => $type,
        'config' => $config,
    ];

    if ($type === 'cpt') {
        $source = get_post_meta($id, '_slider_source', true) ?: [];
        $response['source'] = $source;

        // Switch WPML language if requested
        $original_lang = apply_filters('wpml_current_language', null);
        if ($lang && $lang !== $original_lang) {
            do_action('wpml_switch_language', $lang);
        }

        $query_args = [
            'post_type'        => sanitize_text_field($source['postType'] ?? 'post'),
            'posts_per_page'   => max(1, min(50, intval($source['perPage'] ?? 6))),
            'post_status'      => 'publish',
            'suppress_filters' => false,
            '_embed'           => true,
        ];

        $order = $source['order'] ?? 'desc';
        if ($order === 'rand') {
            $query_args['orderby'] = 'rand';
        } else {
            $query_args['orderby'] = 'date';
            $query_args['order']   = strtoupper($order);
        }

        $query = new WP_Query($query_args);
        $controller = new WP_REST_Posts_Controller($source['postType'] ?? 'post');
        $rest_request = new WP_REST_Request('GET');
        $rest_request->set_param('_embed', true);
        $server = rest_get_server();

        $posts = [];
        foreach ($query->posts as $p) {
            $item = $controller->prepare_item_for_response($p, $rest_request);
            if (!is_wp_error($item)) {
                $posts[] = $server->response_to_data($item, true);
            }
        }

        $response['posts'] = $posts;

        // Restore original language
        if ($lang && $lang !== $original_lang) {
            do_action('wpml_switch_language', $original_lang);
        }
    } else {
        $raw_slides = get_post_meta($id, '_slider_slides', true) ?: [];
        $response['slides'] = pk_slider_resolve_images($raw_slides);
    }

    return rest_ensure_response($response);
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
        'post_type'         => 'hero',
        'posts_per_page'    => 1,
        'post_status'       => 'publish',
        'suppress_filters'  => false, // Necesario para que WPML filtre por idioma
        'meta_query'        => [
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
    
    // QUITAR EL PREFIJO DE WORDPRESS (/headless) de la URL
    $wp_base_path = '/headless';
    if (str_starts_with($relative_url, $wp_base_path)) {
        $relative_url = substr($relative_url, strlen($wp_base_path));
    }
    
    // Si la URL ya tiene prefijo de idioma (WPML lo añade), no duplicar
    $lang_prefixes = ['/en/', '/es/', '/pt-br/'];
    $has_lang_prefix = false;
    foreach ($lang_prefixes as $prefix) {
        if (str_starts_with($relative_url, $prefix)) {
            $has_lang_prefix = true;
            break;
        }
    }
    
    // Obtener el idioma por defecto de WPML
    $wpml_default_lang = apply_filters('wpml_default_language', null);
    
    if (!$has_lang_prefix && $target_lang !== $wpml_default_lang) {
        $relative_url = '/' . $target_lang . $relative_url;
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


/**
 * =============================================================================
 * 🤖 CHATBOT CONFIG CALLBACK
 * =============================================================================
 * Returns chatbot configuration for the frontend.
 */
function get_chatbot_config_callback() {
    $enabled = get_language_option('chatbot_enabled', false);
    
    // If chatbot is disabled, still return config but with enabled: false
    $config = [
        'enabled' => (bool) $enabled,
        'name' => get_language_option('chatbot_name', 'Tiko'),
        'welcome' => get_language_option('chatbot_welcome', ''),
        'avatar' => get_language_option('chatbot_avatar', ''),
        'systemPrompt' => get_language_option('chatbot_system_prompt', ''),
        'placeholder' => get_language_option('chatbot_placeholder', ''),
        'position' => get_language_option('chatbot_position', 'bottom-right'),
        'color' => get_language_option('chatbot_color', '#000000'),
    ];
    
    return new WP_REST_Response($config, 200);
}


/**
 * =============================================================================
 * 💬 CHATBOT MESSAGE CALLBACK
 * =============================================================================
 * Receives user message, processes with AI, returns response.
 * Uses GROQ_API_KEY from wp-config.php
 * Fetches README files from GitHub to enhance system prompt.
 */
function post_chatbot_message_callback($request) {
    // Sanitización básica
    $message = trim($request->get_param('message'));
    $max_length = 500;
    
    if (empty($message)) {
        return new WP_Error('missing_message', 'Message is required', ['status' => 400]);
    }
    
    if (strlen($message) > $max_length) {
        return new WP_Error('message_too_long', 'Message exceeds maximum length', ['status' => 400]);
    }
    
    // Check if chatbot is enabled
    $enabled = get_language_option('chatbot_enabled', false);
    if (!$enabled) {
        return new WP_Error('chatbot_disabled', 'Chatbot is not enabled', ['status' => 403]);
    }
    
    // Get bot name
    $bot_name = get_language_option('chatbot_name', 'Tiko');
    
    // System prompt: admin config OR default interno
    $system_prompt = get_language_option('chatbot_system_prompt', 
        'Eres Tiko, un asistente amable y profesional. Ayudas a los usuarios con información sobre este sitio web de forma clara y concisa.');
    
    // Fetch README files from GitHub
    $readme_content = fetch_github_readmes();
    
    if (!empty($readme_content)) {
        $system_prompt .= "\n\n=== INFORMACIÓN DEL PROYECTO (del README de GitHub) ===\n" . $readme_content;
    }
    
    // Add contact info
    $system_prompt .= "\n\n=== INFORMACIÓN DE CONTACTO ===\n";
    $system_prompt .= "- Teléfono: +34 676666854\n";
    $system_prompt .= "- Email: email@penkode.com\n";
    $system_prompt .= "- LinkedIn: https://www.linkedin.com/in/pauloramalho/\n";
    
    // Get API key - check constant and environment variable
    $api_key = '';
    
    if (defined('GROQ_API_KEY')) {
        $api_key = GROQ_API_KEY;
    }
    
    if (empty($api_key)) {
        $api_key = getenv('GROQ_API_KEY');
    }
    
    if (empty($api_key)) {
        return new WP_REST_Response([
            'reply' => 'Lo siento, el asistente no está disponible en este momento.'
        ], 200);
    }
    
    // Prepare messages for GROQ API
    $messages = [
        [
            'role' => 'system',
            'content' => $system_prompt
        ],
        [
            'role' => 'user',
            'content' => $message
        ]
    ];
    
    // Call GROQ API (using Llama model for free tier)
    $api_url = 'https://api.groq.com/openai/v1/chat/completions';
    
    $body = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 1000,
    ];
    
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($body),
        'timeout' => 30,
    ];
    
    $response = wp_remote_post($api_url, $args);
    
    if (is_wp_error($response)) {
        return new WP_REST_Response([
            'reply' => 'Lo siento, hubo un error. Por favor, inténtalo de nuevo.'
        ], 200);
    }
    
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($response_body['choices'][0]['message']['content'])) {
        $reply = trim($response_body['choices'][0]['message']['content']);
        return new WP_REST_Response(['reply' => $reply], 200);
    }
    
    if (isset($response_body['error'])) {
        return new WP_REST_Response([
            'reply' => 'Error: ' . $response_body['error']['message']
        ], 200);
    }
    
    return new WP_REST_Response([
        'reply' => 'Lo siento, no pude generar una respuesta.'
    ], 200);
}

/**
 * Fetch README files from GitHub repositories
 */
function fetch_github_readmes() {
    $repos = [
        'https://raw.githubusercontent.com/penkodedev/next-wp-kit/main/README.md',
        'https://raw.githubusercontent.com/penkodedev/wp-headless/main/README.md',
    ];
    
    $all_content = '';
    
    foreach ($repos as $repo_url) {
        $response = wp_remote_get($repo_url, ['timeout' => 10]);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $content = wp_remote_retrieve_body($response);
            $content = strip_tags($content);
            // Limit to first 3000 characters to avoid token limits
            $content = substr($content, 0, 3000);
            $all_content .= $content . "\n\n---\n\n";
        }
    }
    
    return $all_content;
}

/**
 * =============================================================================
 * 📰 TICKER SETTINGS CALLBACK
 * =============================================================================
 * Returns ticker configuration for the frontend.
 * Si se pasa ?page_id=123, incluye showOnThisPage: true/false para respetar "Mostrar en páginas".
 */
function ticker_settings($request) {
    $enabled = get_language_option('ticker_enabled', false);
    $text = wp_kses_post(get_language_option('ticker_text', ''));
    $link = esc_url_raw(get_language_option('ticker_link', ''));
    $pages_option = get_language_option('ticker_pages', []);
    $pages = is_array($pages_option) ? array_map('intval', $pages_option) : [];
    $speed = (int) get_language_option('ticker_speed', 50);
    $size = sanitize_text_field(get_language_option('ticker_size', 'medium'));
    $no_animate = get_language_option('ticker_no_animate', false);
    $pauseOnHover = get_language_option('ticker_pause_hover', true);

    $front_page_id = (int) get_option('page_on_front');

    $page_slugs = [];
    if (!empty($pages)) {
        foreach ($pages as $pid) {
            if ($pid === $front_page_id) {
                $page_slugs[] = '/';
            } else {
                $p = get_post($pid);
                if ($p) {
                    $page_slugs[] = $p->post_name;
                }
            }
        }
    }

    $data = [
        'enabled' => $enabled,
        'text' => $text,
        'link' => $link,
        'pages' => $page_slugs,
        'speed' => $speed,
        'size' => $size,
        'noAnimate' => $no_animate,
        'pauseOnHover' => $pauseOnHover,
    ];

    return rest_ensure_response($data);
}

/**
 * =============================================================================
 * 🎨 APPEARANCE SETTINGS CALLBACK
 * =============================================================================
 * Returns appearance and UI component configuration for the frontend.
 */
function get_appearance_settings_callback($request) {
    $default_mode = get_option('appearance_default_mode', 'light');

    return rest_ensure_response([
        'darkModeEnabled' => get_option('appearance_darkmode_enabled', '1') === '1',
        'defaultMode'     => in_array($default_mode, ['light', 'dark', 'system'], true) ? $default_mode : 'light',
        'scrollToTop'     => get_option('appearance_scrolltop_enabled', '1') === '1',
        'breadcrumbs'     => get_option('appearance_breadcrumbs_enabled', '1') === '1',
        'loading'         => get_option('appearance_loading_enabled', '1') === '1',
        'scrollProgress'  => get_option('appearance_scrollprogress_enabled', '0') === '1',
        'lightbox'        => get_option('appearance_lightbox_enabled', '1') === '1',
        'smoothScroll'    => get_option('appearance_smoothscroll_enabled', '1') === '1',
        'popups'          => get_option('appearance_popups_enabled', '1') === '1',
        'copyLink'        => get_option('appearance_copylink_enabled', '1') === '1',
        'likeButton'      => get_option('appearance_like_enabled', '1') === '1',
        'shareButton'     => get_option('appearance_share_enabled', '1') === '1',
    ]);
}

/**
 * =============================================================================
 * 💬 TOOLTIPS CALLBACK
 * =============================================================================
 * Returns all tooltips configured in Custom Settings.
 */
function get_tooltips_callback() {
    $tooltips = get_option('tooltips_data', []);
    
    if (!is_array($tooltips)) {
        $tooltips = [];
    }
    
    return rest_ensure_response($tooltips);
}


/**
 * ===================================================================
 *   📊 COUNTER STATS ENDPOINT CALLBACK
 * ===================================================================
 * Returns a single counter stats group by 1-based ID.
 */
function get_counter_stats_callback($request) {
    $id = intval($request['id']);
    $groups = get_option('counter_stats_data', []);

    if (!is_array($groups) || empty($groups)) {
        return new WP_REST_Response(['error' => 'No counter stats configured'], 404);
    }

    $index = $id - 1;
    if (!isset($groups[$index])) {
        return new WP_REST_Response(['error' => 'Counter stats group not found', 'id' => $id], 404);
    }

    $group = $groups[$index];

    return rest_ensure_response([
        'title'    => $group['title'] ?? '',
        'duration' => intval($group['duration'] ?? 2000),
        'items'    => $group['items'] ?? [],
    ]);
}
