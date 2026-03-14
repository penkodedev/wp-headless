<?php
/**
 * register.php — Register Lucide Icon Gutenberg block
 */

add_action( 'init', function() {
    // Register the block type
    register_block_type( get_template_directory() . '/blocks/lucide-icons' );
});

add_action( 'enqueue_block_editor_assets', function() {
    // Path to the SVG icons folder
    $svg_path = get_template_directory() . '/blocks/lucide-icons/svg/';
    $icons = [];

    // Generate array of icon names
    foreach ( glob( $svg_path . '*.svg' ) as $file ) {
        $icons[] = basename( $file, '.svg' );
    }

    // Register and enqueue editor JS
    wp_enqueue_script(
        'custom-lucide-icon-editor',
        get_template_directory_uri() . '/blocks/lucide-icons/index.js',
        [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor' ],
        filemtime( get_template_directory() . '/blocks/lucide-icons/index.js' )
    );

    // Pass the icons array AND the base path to JS
    wp_localize_script(
        'custom-lucide-icon-editor',
        'LucideIconsData',
        array(
            'icons' => $icons,
            'basePath' => get_template_directory_uri() . '/blocks/lucide-icons/svg/'
        )
    );

    // Enqueue editor CSS (optional)
    $css_file_path = get_template_directory() . '/blocks/lucide-icons/editor.css';
    if ( file_exists( $css_file_path ) ) {
        wp_enqueue_style(
            'custom-lucide-icon-editor-style',
            get_template_directory_uri() . '/blocks/lucide-icons/editor.css',
            [],
            filemtime( $css_file_path )
        );
    }
});