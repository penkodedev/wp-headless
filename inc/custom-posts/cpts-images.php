<?php

//******************** Allow FEATURED IMAGES on CPTs  **************************
function custom_theme_features()  {
    // Add theme support for Featured Images
    add_theme_support( 'post-thumbnails' );
}
// Hook into the 'after_setup_theme' action
add_action( 'after_setup_theme', 'custom_theme_features' );