<?php


//******************** Allow FEATURED IMAGES on CPTs  **************************
function custom_theme_features()  {
    // Add theme support for Featured Images
    add_theme_support( 'post-thumbnails' );
}
// Hook into the 'after_setup_theme' action
add_action( 'after_setup_theme', 'custom_theme_features' );


//******************** Add Post Type & Post Name to Body Class **************************
add_filter('body_class', 'add_post_class');
function add_post_class($classes)
{
  global $post;
  if (isset($post)) {
    $classes[] = $post->post_type . ' ' . $post->post_name;
  }
  return $classes;
}
