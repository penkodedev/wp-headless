<?php

// ===================================================================================================
//   Añade campos personalizados a los ajustes generales de WordPress con configuraciones independientes por idioma.
// ===================================================================================================
add_action('admin_init', function () {
    add_settings_section(
        'custom_settings_section',
        '', // Sin título para evitar el <h2>
        function () {
            echo '<br></br>';
            echo '<h1>Custom Settings</h1>';
        },
        'general'
    );
    

    $fields = [
        ['site_logo_light', 'Light Logo', 'site_logo_upload_field'],
        ['site_logo_dark', 'Dark Logo', 'site_logo_upload_field'],
        ['headless_front_url', 'Headless Front URL', 'headless_front_url_field'],
        ['site_links', 'Site Links', 'site_links_field'],
        ['social_networks', 'Social Networks', 'social_networks_field'],
        ['contact_info', 'Contact Information', 'contact_info_field'],
        ['analytics_settings', 'Analytics Configuration', 'analytics_settings_field'],
        ['i18n_settings', 'Internationalization', 'i18n_settings_field'],
    ];

    foreach ($fields as [$id, $label, $callback]) {
        add_settings_field($id, $label, $callback, 'general', 'custom_settings_section', $id);
    }

    register_setting('general', 'site_logo_light', 'absint');
    register_setting('general', 'site_logo_dark', 'absint');
    register_setting('general', 'headless_front_url', 'esc_url_raw');
    register_setting('general', 'site_links', 'sanitize_site_links');
    register_setting('general', 'social_networks', 'sanitize_social_networks');
    register_setting('general', 'contact_info', 'sanitize_contact_info');
    register_setting('general', 'analytics_settings', 'sanitize_analytics_settings');
    register_setting('general', 'i18n_settings', 'sanitize_i18n_settings');
});

/**
 * Helpers para obtener y guardar opciones (funciona con o sin WPML)
 */
function get_language_option($option_name) {
    // Check if WPML is active and properly configured
    if (class_exists('SitePress')) {
        global $sitepress;
        if ($sitepress && method_exists($sitepress, 'get_current_language')) {
            try {
                $current_lang = $sitepress->get_current_language();
                if ($current_lang && $current_lang !== 'all') {
                    $option_with_lang = get_option($option_name . '_' . $current_lang);
                    if ($option_with_lang !== false) {
                        return $option_with_lang;
                    }
                }
            } catch (Exception $e) {
                // WPML not properly configured, fall back to default
            }
        }
    }

    // Fallback to default option without language suffix
    return get_option($option_name);
}

function update_language_option($option_name, $value) {
    // Check if WPML is active and properly configured
    if (class_exists('SitePress')) {
        global $sitepress;
        if ($sitepress && method_exists($sitepress, 'get_current_language')) {
            try {
                $current_lang = $sitepress->get_current_language();
                if ($current_lang && $current_lang !== 'all') {
                    update_option($option_name . '_' . $current_lang, $value);
                    return;
                }
            } catch (Exception $e) {
                // WPML not properly configured, fall back to default
            }
        }
    }

    // Fallback to default option without language suffix
    update_option($option_name, $value);
}


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
    <button type="button" class="button button-secondary upload_logo_button" data-target="<?php echo esc_attr($option_name); ?>">Upload/Change Logo</button>
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

    <script>
    jQuery(function ($) {
        let siteLinkIndex = <?php echo count($links) ?: 1; ?>;

        $('#add_site_link_row').on('click', function () {
            $('#site_links_container').append(`
                <div class="site_link_row">
                    <input type="url" name="site_links[${siteLinkIndex}][wpurl]" placeholder="WP URL" />
                    <input type="url" name="site_links[${siteLinkIndex}][fronturl]" placeholder="Front URL" />
                    <button type="button" class="button remove_site_link_row">Borrar</button>
                </div>
            `);
            siteLinkIndex++;
        });

        $(document).on('click', '.remove_site_link_row', function () {
            $(this).closest('.site_link_row').remove();
        });

        $(document).on('click', '.upload_logo_button', function (e) {
            e.preventDefault();
            const target = $(this).data('target');
            let mediaUploader = wp.media({
                title: 'Select Logo',
                button: { text: 'Use this logo' },
                multiple: false
            });

            mediaUploader.on('select', function () {
                const attachment = mediaUploader.state().get('selection').first().toJSON();

                // Allow SVG, JPG, PNG, GIF
                const allowedTypes = ['image/svg+xml', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(attachment.mime)) {
                    alert('Please select a valid image file (SVG, JPG, PNG, GIF).');
                    return;
                }

                $('#' + target).val(attachment.id);
                $('.' + target + '_preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
            });

            mediaUploader.open();
        });
    });
    </script>
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
 * Campo para contact_info (repite filas dinámicas)
 */
function contact_info_field() {
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
 * Campo para analytics_settings
 */
function analytics_settings_field() {
    $analytics = get_language_option('analytics_settings') ?: [];
    ?>
    <div class="analytics-settings">
        <div class="form-group">
            <label for="google_analytics_id">Google Analytics ID:</label>
            <input type="text" name="analytics_settings[google_analytics_id]" id="google_analytics_id" value="<?php echo esc_attr($analytics['google_analytics_id'] ?? ''); ?>" placeholder="GA-XXXXXXXXX" />
        </div>
        <div class="form-group">
            <label for="facebook_pixel_id">Facebook Pixel ID:</label>
            <input type="text" name="analytics_settings[facebook_pixel_id]" id="facebook_pixel_id" value="<?php echo esc_attr($analytics['facebook_pixel_id'] ?? ''); ?>" placeholder="123456789012345" />
        </div>
        <div class="form-group">
            <label for="gtm_id">Google Tag Manager ID:</label>
            <input type="text" name="analytics_settings[gtm_id]" id="gtm_id" value="<?php echo esc_attr($analytics['gtm_id'] ?? ''); ?>" placeholder="GTM-XXXXXXX" />
        </div>
        <div class="form-group">
            <label for="twitter_pixel_id">Twitter Pixel ID:</label>
            <input type="text" name="analytics_settings[twitter_pixel_id]" id="twitter_pixel_id" value="<?php echo esc_attr($analytics['twitter_pixel_id'] ?? ''); ?>" placeholder="abcd1234" />
        </div>
    </div>
    <p class="description">Configure analytics tracking IDs for headless frontend</p>
    <?php
}

/**
 * Campo para i18n_settings
 */
function i18n_settings_field() {
    $i18n = get_language_option('i18n_settings') ?: ['default_locale' => 'es', 'locales' => ['es']];
    ?>
    <div class="i18n-settings">
        <div class="form-group">
            <label for="default_locale">Default Locale:</label>
            <input type="text" name="i18n_settings[default_locale]" id="default_locale" value="<?php echo esc_attr($i18n['default_locale'] ?? 'es'); ?>" placeholder="es" />
        </div>
        <div class="form-group">
            <label for="available_locales">Available Locales (comma-separated):</label>
            <input type="text" name="i18n_settings[locales]" id="available_locales" value="<?php echo esc_attr(implode(', ', $i18n['locales'] ?? ['es'])); ?>" placeholder="es, en, fr" />
        </div>
    </div>
    <p class="description">Configure internationalization settings for headless frontend</p>
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
 * Sanitiza los contact_info
 */
function sanitize_contact_info($contacts) {
    if (!is_array($contacts)) return [];
    return array_map(function ($contact) {
        return [
            'type' => sanitize_text_field($contact['type'] ?? ''),
            'value' => wp_kses_post($contact['value'] ?? '') // Allow HTML for rich text
        ];
    }, $contacts);
}

/**
 * Sanitiza los analytics_settings
 */
function sanitize_analytics_settings($settings) {
    if (!is_array($settings)) return [];

    return [
        'google_analytics_id' => sanitize_text_field($settings['google_analytics_id'] ?? ''),
        'facebook_pixel_id' => sanitize_text_field($settings['facebook_pixel_id'] ?? ''),
        'gtm_id' => sanitize_text_field($settings['gtm_id'] ?? ''),
        'twitter_pixel_id' => sanitize_text_field($settings['twitter_pixel_id'] ?? ''),
    ];
}

/**
 * Sanitiza los i18n_settings
 */
function sanitize_i18n_settings($settings) {
    if (!is_array($settings)) return ['default_locale' => 'es', 'locales' => ['es']];

    $locales_string = $settings['locales'] ?? 'es';

    // Handle case where locales is already an array
    if (is_array($locales_string)) {
        $locales = $locales_string;
    } else {
        $locales = array_map('trim', explode(',', $locales_string));
    }

    $locales = array_filter($locales, function($locale) {
        return preg_match('/^[a-z]{2,3}(-[a-z]{2,4})?$/i', $locale);
    });

    return [
        'default_locale' => sanitize_text_field($settings['default_locale'] ?? 'es'),
        'locales' => $locales ?: ['es']
    ];
}

/**
 * Intercepta el guardado para registrar las opciones por idioma
 */
add_action('admin_init', function () {
    if (isset($_POST['option_page']) && $_POST['option_page'] === 'general') {
        update_language_option('site_logo_light', absint($_POST['site_logo_light'] ?? 0));
        update_language_option('site_logo_dark', absint($_POST['site_logo_dark'] ?? 0));
        update_language_option('headless_front_url', esc_url_raw($_POST['headless_front_url'] ?? ''));
        update_language_option('site_links', sanitize_site_links($_POST['site_links'] ?? []));
        update_language_option('social_networks', sanitize_social_networks($_POST['social_networks'] ?? []));
        update_language_option('contact_info', sanitize_contact_info($_POST['contact_info'] ?? []));
        update_language_option('analytics_settings', sanitize_analytics_settings($_POST['analytics_settings'] ?? []));
        update_language_option('i18n_settings', sanitize_i18n_settings($_POST['i18n_settings'] ?? []));
    }
});



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
