<?php
//************************* INCLUDE JS FUNCTIONS **************************************
  // En setup headless, solo mantenemos scripts esenciales para admin


// Frontend scripts
function enqueue_scripts()
{
  wp_enqueue_script(
    'secure-login',
    get_template_directory_uri() . '/js/secure-login.js',
    array(), // Sin dependencias
    '', // Version
    true  // Load in footer
  );

}
add_action('wp_enqueue_scripts', 'enqueue_scripts');

// Admin scripts
function enqueue_admin_scripts($hook) {
    // Media uploader for settings pages
    if (strpos($hook, 'custom-settings') !== false || strpos($hook, 'settings_page_') !== false) {
        wp_enqueue_script(
            'admin-media-uploader',
            get_template_directory_uri() . '/js/admin-media-uploader.js',
            array('jquery', 'wp-util'),
            '1.0.0',
            true
        );
    }

    // Slider metabox scripts (CPT edit screens)
    if (in_array($hook, ['post.php', 'post-new.php'], true)) {
        global $post_type;
        if ('sliders' === $post_type) {
            wp_enqueue_media();
            wp_enqueue_script(
                'admin-slider-metabox',
                get_template_directory_uri() . '/js/admin-slider-metabox.js',
                array('jquery'),
                '1.0.0',
                true
            );
        }
        if ('maps' === $post_type) {
            $mapbox_token = defined('MAPBOX_ACCESS_TOKEN') && MAPBOX_ACCESS_TOKEN ? MAPBOX_ACCESS_TOKEN : '';
            wp_enqueue_script(
                'admin-maps-metabox',
                get_template_directory_uri() . '/js/admin-maps-metabox.js',
                array('jquery'),
                '1.0.0',
                true
            );
            wp_localize_script('admin-maps-metabox', 'pkMaps', [
                'mapboxToken' => $mapbox_token,
            ]);
        }
    }
}
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');
