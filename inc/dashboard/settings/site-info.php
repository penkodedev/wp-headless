<?php

// ===================================================================================================
//   Añade campos personalizados a Custom Settings y mantiene algunos en Ajustes Generales
// ===================================================================================================
add_action('admin_init', function () {
    // Encolar scripts para el Media Uploader
    wp_enqueue_media();

    // Site Info Section - Custom Settings
    add_settings_section(
        'pk_site_info_section',
        '',
        '__return_false',
        'custom-settings-site-info'
    );

    $fields_custom = [
        ['site_logo_light', 'Light Logo', 'site_logo_upload_field'],
        ['site_logo_dark', 'Dark Logo', 'site_logo_upload_field'],
        ['headless_front_url', 'Headless Front URL', 'headless_front_url_field'],
        ['site_links', 'Site Links', 'site_links_field'],
        ['social_networks', 'Social Networks', 'social_networks_field'],
        ['contact_info', 'Contact Information', 'contact_info_field'],
    ];

    foreach ($fields_custom as [$id, $label, $callback]) {
        add_settings_field($id, $label, $callback, 'custom-settings-site-info', 'pk_site_info_section', $id);
    }

    // Register settings with sanitize_callback so the form value is passed to pre_update_option
    register_setting('custom-settings', 'site_logo_light', ['sanitize_callback' => function ($v) { return absint($v); }]);
    register_setting('custom-settings', 'site_logo_dark', ['sanitize_callback' => function ($v) { return absint($v); }]);
    register_setting('custom-settings', 'headless_front_url', ['sanitize_callback' => function ($v) { return esc_url_raw($v); }]);
    register_setting('custom-settings', 'site_links', ['sanitize_callback' => 'sanitize_site_links']);
    register_setting('custom-settings', 'social_networks', ['sanitize_callback' => 'sanitize_social_networks']);
    register_setting('custom-settings', 'contact_info', ['sanitize_callback' => 'sanitize_contact_info']);
});

/**
 * Opciones de Site Info que deben guardarse por idioma (WPML).
 * WPML guarda con sufijo (_es, _en, etc.) cuando están registradas.
 * Incluye también Ticker, Chatbot e i18n para que no se mezclen entre idiomas.
 */
const PK_SITE_INFO_LANGUAGE_OPTIONS = [
    'site_logo_light',
    'site_logo_dark',
    'headless_front_url',
    'site_links',
    'social_networks',
    'contact_info',
    // Ticker
    'ticker_enabled',
    'ticker_text',
    'ticker_pages',
    'ticker_link',
    'ticker_speed',
    'ticker_size',
    'ticker_no_animate',
    'ticker_pause_hover',
    // Chatbot
    'chatbot_enabled',
    'chatbot_name',
    'chatbot_welcome',
    'chatbot_avatar',
    'chatbot_system_prompt',
    'chatbot_placeholder',
    'chatbot_position',
    'chatbot_color',
    'chatbot_margin',
    'chatbot_show_avatar',
    // Analytics
    'analytics_settings',
    // i18n
    'i18n_settings',
];

// No registrar estas opciones en wpml_multilingual_options: el guardado por idioma
// lo hacemos nosotros en pre_update_option_* (claves _es, _en). Si WPML las registra,
// puede redirigir update_option(opcion_base, $old_value) a opcion_en y pisar nuestro valor.

/**
 * Devuelve el valor de una opción en el idioma actual (WPML) o la opción base.
 * En admin: idioma seleccionado en el conmutador de WPML.
 * En API: idioma pasado por ?lang= o wpml_switch_language.
 */
function get_language_option($option_name, $default = false) {
    $lang = apply_filters('wpml_current_language', null);
    if ($lang && in_array($option_name, PK_SITE_INFO_LANGUAGE_OPTIONS, true)) {
        $sentinel = new stdClass();
        $per_lang = get_option($option_name . '_' . $lang, $sentinel);
        if ($per_lang !== $sentinel) {
            return $per_lang;
        }
        $default_lang = apply_filters('wpml_default_language', 'es');
        if ($lang !== $default_lang) {
            $fallback = get_option($option_name . '_' . $default_lang, $sentinel);
            if ($fallback !== $sentinel) {
                return $fallback;
            }
        }
        $base = get_option($option_name, $sentinel);
        if ($base !== $sentinel) {
            return $base;
        }
    }
    return get_option($option_name, $default);
}

/**
 * Guarda una opción para el idioma actual (WPML). Evitar usar en sanitize callbacks
 * para no provocar bucles; el guardado normal del formulario lo hace WordPress + WPML.
 */
function update_language_option($option_name, $value) {
    global $pk_in_sanitize;
    if (!empty($pk_in_sanitize)) {
        return;
    }
    $lang = apply_filters('wpml_current_language', null);
    if ($lang && in_array($option_name, PK_SITE_INFO_LANGUAGE_OPTIONS, true)) {
        $pk_in_sanitize = true;
        update_option($option_name . '_' . $lang, $value);
        $pk_in_sanitize = false;
        return;
    }
    $pk_in_sanitize = true;
    update_option($option_name, $value);
    $pk_in_sanitize = false;
}

// Bandera para evitar bucle infinito en sanitización
static $pk_in_sanitize = false;


/**
 * Campo de subida de logos (light y dark)
 */
function site_logo_upload_field($option_name) {
    $logo_id = get_language_option($option_name);
    $logo_url = $logo_id ? wp_get_attachment_image_src($logo_id, 'full')[0] : '';
    ?>
    <input type="hidden" name="<?php echo esc_attr($option_name); ?>" id="<?php echo esc_attr($option_name); ?>" value="<?php echo esc_attr($logo_id); ?>" />
    <div class="<?php echo esc_attr($option_name); ?>_preview">
        <?php if ($logo_url): ?>
            <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 100px; height: auto; background:#dddddd; padding:5px">
        <?php endif; ?>
    </div>
    <button type="button" class="button button-secondary media-upload-button" 
            data-target="<?php echo esc_attr($option_name); ?>" 
            data-preview="<?php echo esc_attr($option_name); ?>_preview"
            data-allowed-types="image/svg+xml,image/jpeg,image/png,image/gif"
            data-title="Select Logo"
            data-button-text="Use this logo">
            Upload/Change Logo
        </button>
    <?php
}

/**
 * Campo para headless_front_url
 */
function headless_front_url_field() {
    $url = get_language_option('headless_front_url');
    echo '<input type="url" name="headless_front_url" value="' . esc_url($url) . '" />
        <p class="description">For upload cache Webhook</p>';
}

/**
 * Campo para site_links (repite filas dinámicas)
 */
function site_links_field() {
    $links = get_language_option('site_links') ?: [];

    ?>
    <div id="site_links_container">
        <div class="col_headers">
            <span>WP URL</span>
            <span>Front URL</span>
        </div>
        <?php
        if (!empty($links)) {
            foreach ($links as $index => $link) {
                render_site_link_row($index, $link);
            }
        } else {
            render_site_link_row(0, ['wpurl' => '', 'fronturl' => '']);
        }
        ?>
    </div>
    <p class="description">Para remplazo de rutas absolutas</p>
    <button type="button" id="add_site_link_row" class="button">Añadir enlaces</button>
    <?php
}

/**
 * Render de cada fila de site_links
 */
function render_site_link_row($index, $link) {
    ?>
    <div class="site_link_row">
        <input type="url" name="site_links[<?php echo esc_attr($index); ?>][wpurl]" value="<?php echo esc_url($link['wpurl'] ?? ''); ?>" placeholder="WP URL" />
        <input type="url" name="site_links[<?php echo esc_attr($index); ?>][fronturl]" value="<?php echo esc_url($link['fronturl'] ?? ''); ?>" placeholder="Front URL" />
        <button type="button" class="button remove_site_link_row">Borrar</button>
    </div>
    <?php
}

/**
 * Sanitiza los site_links
 */
function sanitize_site_links($links) {
    if (!is_array($links)) return [];
    return array_map(function ($link) {
        return [
            'wpurl' => esc_url_raw($link['wpurl'] ?? ''),
            'fronturl' => esc_url_raw($link['fronturl'] ?? '')
        ];
    }, $links);
}

/**
 * Campo para social_networks (repite filas dinámicas)
 */
function social_networks_field() {
    $networks = get_language_option('social_networks') ?: [];

    ?>
    <div id="social_networks_container">
        <div class="col_headers">
            <span>Network Name</span>
            <span>URL</span>
        </div>
        <?php
        if (!empty($networks)) {
            foreach ($networks as $index => $network) {
                render_social_network_row($index, $network);
            }
        } else {
            render_social_network_row(0, ['name' => '', 'url' => '']);
        }
        ?>
    </div>
    <p class="description">Add social media networks for footer/header links</p>
    <button type="button" id="add_social_network_row" class="button">Add Social Network</button>

    <script>
    jQuery(function ($) {
        let socialNetworkIndex = <?php echo count($networks) ?: 1; ?>;

        $('#add_social_network_row').on('click', function () {
            $('#social_networks_container').append(`
                <div class="social_network_row">
                    <input type="text" name="social_networks[${socialNetworkIndex}][name]" placeholder="Network Name (e.g., Facebook)" />
                    <input type="url" name="social_networks[${socialNetworkIndex}][url]" placeholder="https://..." />
                    <button type="button" class="button remove_social_network_row">Remove</button>
                </div>
            `);
            socialNetworkIndex++;
        });

        $(document).on('click', '.remove_social_network_row', function () {
            $(this).closest('.social_network_row').remove();
        });
    });
    </script>
    <?php
}

/**
 * Campo para contact_info (repite filas dinámicas).
 * Asegura idioma desde URL para que en lang=en se carguen los contactos en inglés.
 */
function contact_info_field() {
    if (!empty($_GET['lang']) && isset($_GET['page']) && $_GET['page'] === 'custom-settings') {
        $lang = sanitize_text_field($_GET['lang']);
        $active = function_exists('apply_filters') ? apply_filters('wpml_active_languages', []) : [];
        if (!empty($active[$lang])) {
            do_action('wpml_switch_language', $lang);
        }
    }
    $contacts = get_language_option('contact_info') ?: [];

    ?>
    <div id="contact_info_container" style="align-items: flex-start;">
        <div class="col_headers" style="margin-bottom: 10px; font-weight: bold; color: #23282d;">
            <span style="flex: 1;">Contact Type</span>
            <span style="flex: 2;">Value</span>
        </div>

        <?php
        if (!empty($contacts)) {
            foreach ($contacts as $index => $contact) {
                render_contact_info_row($index, $contact);
            }
        } else {
            render_contact_info_row(0, ['type' => '', 'value' => '']);
        }
        ?>
    </div>
    <p class="description">Add contact information (email, phone, address, etc.)</p>
    <button type="button" id="add_contact_info_row" class="button">Add Contact Info</button>

    <script>
    jQuery(function ($) {
        let contactInfoIndex = <?php echo count($contacts) ?: 1; ?>;

        $('#add_contact_info_row').on('click', function () {
            const editorId = 'contact_info_' + contactInfoIndex + '_value';
            const rowHtml = `
                <div class="contact_info_row" style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                    <input type="text" name="contact_info[${contactInfoIndex}][type]" placeholder="Type (e.g., Email, Phone)" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
                    <div style="flex: 2;">
                        <textarea id="${editorId}" name="contact_info[${contactInfoIndex}][value]" rows="3"></textarea>
                    </div>
                    <button type="button" class="button remove_contact_info_row" style="background: #dc3232; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">Remove</button>
                </div>
            `;
            $('#contact_info_container').append(rowHtml);

            // Initialize TinyMCE for the new textarea
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: '#' + editorId,
                    menubar: false,
                    statusbar: false,
                    toolbar: 'bold italic underline strikethrough | bullist numlist | link unlink | undo redo',
                    height: 80,
                    forced_root_block: 'div',
                    force_br_newlines: false,
                    force_p_newlines: false,
                    convert_newlines_to_brs: true,
                    remove_linebreaks: false,
                    wpautop: false,
                    element_format: 'html',
                    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }'
                });
            }

            contactInfoIndex++;
        });

        $(document).on('click', '.remove_contact_info_row', function () {
            $(this).closest('.contact_info_row').remove();
        });
    });
    </script>
    <?php
}



/**
 * Render de cada fila de social_networks
 */
function render_social_network_row($index, $network) {
    ?>
    <div class="social_network_row">
        <input type="text" name="social_networks[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($network['name'] ?? ''); ?>" placeholder="Network Name" />
        <input type="url" name="social_networks[<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_url($network['url'] ?? ''); ?>" placeholder="https://" />
        <button type="button" class="button remove_social_network_row">Remove</button>
    </div>
    <?php
}

/**
 * Render de cada fila de contact_info
 */
function render_contact_info_row($index, $contact) {
    $editor_id = 'contact_info_' . $index . '_value';
    $content = $contact['value'] ?? '';
    ?>
    <div class="contact_info_row" style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
        <input type="text" name="contact_info[<?php echo esc_attr($index); ?>][type]" value="<?php echo esc_attr($contact['type'] ?? ''); ?>" placeholder="Type (e.g., Email, Phone)" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
        <div style="flex: 2;">
            <?php
            wp_editor(
                $content,
                $editor_id,
                [
                    'textarea_name' => 'contact_info[' . $index . '][value]',
                    'textarea_rows' => 3,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => true,
                    'tinymce' => [
                        'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,undo,redo',
                        'toolbar2' => '',
                        'menubar' => false,
                        'statusbar' => false,
                        'forced_root_block' => 'div',
                        'force_br_newlines' => false,
                        'force_p_newlines' => false,
                        'convert_newlines_to_brs' => true,
                        'remove_linebreaks' => false,
                        'wpautop' => false,
                        'element_format' => 'html',
                    ],
                ]
            );
            ?>
        </div>
        <button type="button" class="button remove_contact_info_row" style="background: #dc3232; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">Remove</button>
    </div>
    <?php
}

/**
 * Sanitiza los social_networks
 */
function sanitize_social_networks($networks) {
    if (!is_array($networks)) return [];
    return array_map(function ($network) {
        return [
            'name' => sanitize_text_field($network['name'] ?? ''),
            'url' => esc_url_raw($network['url'] ?? '')
        ];
    }, $networks);
}

/**
 * Sanitiza los contact_info. array_values() reindexa a 0,1,2... para no perder filas
 * ni desordenar al guardar (p. ej. teléfono y dirección en inglés).
 */
function sanitize_contact_info($contacts) {
    if (!is_array($contacts)) return [];
    $out = [];
    foreach ($contacts as $contact) {
        $out[] = [
            'type' => sanitize_text_field($contact['type'] ?? ''),
            'value' => wp_kses_post($contact['value'] ?? '')
        ];
    }
    return $out;
}



/**
 * Redirigir guardado a la opción con sufijo de idioma.
 * Así, al guardar desde Custom Settings, se escribe en ej. headless_front_url_es
 * y se evita escribir en la clave sin sufijo (evita que todos los idiomas compartan el mismo valor).
 */
$pk_site_info_sanitizers = [
    'site_logo_light'       => function ($v) { return absint($v); },
    'site_logo_dark'        => function ($v) { return absint($v); },
    'headless_front_url'    => function ($v) { return esc_url_raw($v); },
    'site_links'            => 'sanitize_site_links',
    'social_networks'       => 'sanitize_social_networks',
    'contact_info'          => 'sanitize_contact_info',
    // Ticker
    'ticker_enabled'        => function ($v) { return $v ? '1' : ''; },
    'ticker_text'           => function ($v) { return wp_kses_post($v); },
    'ticker_pages'          => function ($v) { return is_array($v) ? array_map('absint', $v) : []; },
    'ticker_link'           => function ($v) { return esc_url_raw($v); },
    'ticker_speed'          => function ($v) { return absint($v); },
    'ticker_size'           => function ($v) { return sanitize_text_field($v); },
    'ticker_no_animate'     => function ($v) { return $v ? '1' : ''; },
    'ticker_pause_hover'    => function ($v) { return $v ? '1' : ''; },
    // Chatbot
    'chatbot_enabled'       => function ($v) { return $v ? '1' : ''; },
    'chatbot_name'          => function ($v) { return sanitize_text_field($v); },
    'chatbot_welcome'       => function ($v) { return wp_kses_post($v); },
    'chatbot_avatar'        => function ($v) { return esc_url_raw($v); },
    'chatbot_system_prompt' => function ($v) { return wp_kses_post($v); },
    'chatbot_placeholder'   => function ($v) { return sanitize_text_field($v); },
    'chatbot_position'      => function ($v) { return sanitize_text_field($v); },
    'chatbot_color'         => function ($v) { return sanitize_text_field($v); },
    'chatbot_margin'        => function ($v) { return sanitize_text_field($v); },
    'chatbot_show_avatar'   => function ($v) { return $v ? '1' : ''; },
    // Analytics
    'analytics_settings'   => 'sanitize_analytics_settings',
    // i18n (sanitizer defined in languages.php)
    'i18n_settings'        => function ($v) { return function_exists('sanitize_i18n_settings') ? sanitize_i18n_settings($v) : (is_array($v) ? $v : []); },
];

// Guardar aquí el valor por idioma; se escribe en updated_option para que no lo pise WPML u otros hooks
global $pk_pending_lang_saves;
if (!isset($pk_pending_lang_saves)) {
    $pk_pending_lang_saves = [];
}

foreach (array_keys($pk_site_info_sanitizers) as $opt) {
    add_filter('pre_update_option_' . $opt, function ($value, $old_value, $option) use ($pk_site_info_sanitizers) {
        global $pk_pending_lang_saves;
        $lang = null;
        $posted_lang = !empty($_POST['pk_wpml_admin_lang']) ? sanitize_text_field($_POST['pk_wpml_admin_lang']) : null;
        if ($posted_lang) {
            $active = apply_filters('wpml_active_languages', []);
            if (isset($active[$posted_lang])) {
                $lang = $posted_lang;
            }
        }
        if (!$lang) {
            $lang = apply_filters('wpml_current_language', null);
        }
        if (!$lang || !in_array($option, PK_SITE_INFO_LANGUAGE_OPTIONS, true)) {
            return $value;
        }
        $sanitize = $pk_site_info_sanitizers[$option];
        $value = is_callable($sanitize) ? call_user_func($sanitize, $value) : $value;
        $key = $option . '_' . $lang;
        update_option($key, $value);
        $pk_pending_lang_saves[$option] = ['lang' => $lang, 'value' => $value];
        return $old_value;
    }, 999, 3); // Prioridad tardía para ejecutar después de otros hooks y no ser pisados
}

// Escribir en la clave _lang DESPUÉS de que WordPress/WPML procesen la opción base (evita que nos pisen)
add_action('updated_option', function ($option, $old_value, $value) {
    global $pk_pending_lang_saves;
    if (empty($pk_pending_lang_saves[$option])) {
        return;
    }
    $pending = $pk_pending_lang_saves[$option];
    $key = $option . '_' . $pending['lang'];
    update_option($key, $pending['value']);
    unset($pk_pending_lang_saves[$option]);
}, 999, 3);



// ===================================================================================================
//   Reemplaza todas las URLs de visualización con la del front headless
// ===================================================================================================
add_filter('post_type_link', 'replace_frontend_urls_with_full_path', 99, 2);
add_filter('page_link', 'replace_frontend_urls_with_full_path', 99, 2);
add_filter('post_link', 'replace_frontend_urls_with_full_path', 99, 2);
add_filter('preview_post_link', 'replace_frontend_urls_with_full_path', 99, 2);
add_filter('get_preview_post_link', 'replace_frontend_urls_with_full_path', 99, 2);
add_filter('preview_page_link', 'replace_frontend_urls_with_full_path', 99, 2);

function replace_frontend_urls_with_full_path($url, $post = null) {
    $front_url = get_language_option('headless_front_url');
    if (!$front_url || !$url) {
        return $url;
    }

    // Extrae la ruta completa de WordPress
    $path = wp_parse_url($url, PHP_URL_PATH);
    $query = wp_parse_url($url, PHP_URL_QUERY);

    $new_url = rtrim($front_url, '/') . $path;

    if ($query) {
        $new_url .= '?' . $query;
    }

    return $new_url;
}

// También reescribe la URL en el admin (enlace permanente de ejemplo)
add_filter('get_sample_permalink_html', 'replace_sample_permalink_in_admin_full_path', 10, 5);
function replace_sample_permalink_in_admin_full_path($html, $post_id, $new_title, $new_slug, $post) {
    $front_url = get_language_option('headless_front_url');
    if (!$front_url || !$post instanceof WP_Post) {
        return $html;
    }

    // Usa la URL original de muestra de WordPress
    $sample_permalink = get_sample_permalink($post_id);
    $original_url = $sample_permalink[0];

    // Reemplaza solo el dominio por el del front
    $path = wp_parse_url($original_url, PHP_URL_PATH);
    $new_url = rtrim($front_url, '/') . $path;

    return preg_replace(
        '#<a[^>]*href="[^"]+"[^>]*>[^<]+</a>#',
        '<a href="' . esc_url($new_url) . '" target="_blank">' . esc_html($new_url) . '</a>',
        $html
    );
}
