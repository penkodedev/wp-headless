<?php

//*-------------------------------------------------
//*            NOTICIAS Custom Post Type
//*-------------------------------------------------

function noticias_post_type()
{

  $supports = array(
    'title',          // Post Title
    'editor',         // Content
    'thumbnail',      // Featured Image
    'revisions',      // Allow Post Revisions
  );

  $labels = array(
    'name' => __('Noticias', 'textdomain'),
    'singular_name' => __('Noticia', 'textdomain'),
    'menu_name' => __('Noticias', 'textdomain'),
    'add_new' => __('Añadir Noticia', 'textdomain'),
    'add_new_item' => __('Añadir Nueva Noticia', 'textdomain'),
    'edit_item' => __('Editar Noticia', 'textdomain'),
    'new_item' => __('Nueva Noticia', 'textdomain'),
    'view_item' => __('Ver Noticia', 'textdomain'),
    'search_items' => __('Buscar Noticias', 'textdomain'),
    'not_found' => __('No se encontraron Noticias', 'textdomain'),
    'not_found_in_trash' => __('No se encontraron Noticias en la papelera', 'textdomain'),
    'parent_item_colon' => '',
    'all_items' => __('Todas las Noticias', 'textdomain')
  );

  $args = array(
    'supports' => $supports,
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,
    'menu_icon' => 'dashicons-media-text', // Icono para Noticias
    'exclude_from_search' => false,
    'rewrite' => array('slug' => 'noticias'), // URL slug -> /news/
    'show_in_rest' => true, // Habilita Gutenberg y la REST API
    'show_in_graphql' => true,
    'graphql_single_name' => 'noticia',
    'graphql_plural_name' => 'noticias',
  );

  register_post_type('noticias', $args);
}
add_action('init', 'noticias_post_type');



//*-------------------------------------------------
//*            HERO Custom Post Type
//*-------------------------------------------------

function hero_post_type()
{

  $supports = array(
    'title',          // Post Title
    //'editor',         // Content
    'thumbnail',      // Featured Image
    'revisions',      // Allow Post Revisions
  );

$labels = array(
    'name' => __('Hero', 'textdomain'),
    'singular_name' => __('Hero', 'textdomain'),
    'menu_name' => __('Hero', 'textdomain'),
    'add_new' => __('Añadir Hero', 'textdomain'),
    'add_new_item' => __('Añadir Nuevo Hero', 'textdomain'),
    'edit_item' => __('Editar Hero', 'textdomain'),
    'new_item' => __('Nuevo Hero', 'textdomain'),
    'view_item' => __('Ver Hero', 'textdomain'),
    'search_items' => __('Buscar Heroes', 'textdomain'),
    'not_found' => __('No se encontraron Heroes', 'textdomain'),
    'not_found_in_trash' => __('No se encontraron Heroes en la papelera', 'textdomain'),
    'parent_item_colon' => '',
    'all_items' => __('Todos los Heroes', 'textdomain')
);

  $args = array(
    'supports' => $supports,
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,
    'menu_icon' => 'dashicons-star-filled',
    'exclude_from_search' => false,
    'rewrite' => array('slug' => 'hero'), // URL slug -> /hero/
    'show_in_rest' => false, // ⚠️ Desactivado: usamos classic editor para custom meta boxes
    'show_in_graphql' => true,
    'graphql_single_name' => 'hero',
    'graphql_plural_name' => 'heroes',
  );

  register_post_type('hero', $args);
}
add_action('init', 'hero_post_type');


//*-------------------------------------------------
//*            RECURSOS Custom Post Type
//*-------------------------------------------------

function recursos_post_type()
{

  $supports = array(
    'title',          // Post Title
    'editor',         // Content -> Habilita un editor.
    'thumbnail',      // Featured Image
    //'excerpt',        // Field for Excerpt
    //'custom-fields',  // Native WordPress Custom fields
    //'author',         // Author of the Post
    //'trackbacks',     // Allow Trackbacks
    'revisions',      // Allow Post Revisions
    //'post-formats',   // Allow Post Formats
    //'page-attributes',// Allow Page Atributes
  );


  $labels = array(
    'name' => __('Recursos', 'textdomain'),
    'singular_name' => __('Recurso', 'textdomain'),
    'menu_name' => __('Recursos', 'textdomain'),
    'add_new' => __('Añadir Recurso', 'textdomain'),
    'add_new_item' => __('Añadir Nuevo Recurso', 'textdomain'),
    'edit_item' => __('Editar Recurso', 'textdomain'),
    'new_item' => __('Nuevo Recurso', 'textdomain'),
    'view_item' => __('Ver Recurso', 'textdomain'),
    'search_items' => __('Buscar Recursos', 'textdomain'),
    'not_found' => __('No se encontraron Recursos', 'textdomain'),
    'not_found_in_trash' => __('No se encontraron Recursos en la papelera', 'textdomain'),
    'parent_item_colon' => '',
    'all_items' => __('Todos los Recursos', 'textdomain')
  );


  $args = array(
    'supports' => $supports,
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,
    'menu_icon' => 'dashicons-megaphone', // The icon that appears in the WordPress admin menu
    'exclude_from_search' => false, // Asegura que el CPT sea incluido en las búsquedas
    'rewrite' => array('slug' => 'recursos'), // URL slug
    'show_in_rest' => true, // Enable Gutenberg editor for this post type
    'show_in_graphql' => true,
    'graphql_single_name' => 'recurso',
    'graphql_plural_name' => 'recursos',
  );

  register_post_type('recursos', $args);
}
add_action('init', 'recursos_post_type');



//*-------------------------------------------------
//*            MODALS Custom Post Type
//*-------------------------------------------------

function modales_post_type() {

  $supports = array(
    'title',
    'editor',
    'thumbnail',
    'revisions',
  );

  $labels = array(
    'name' => __('Modales', 'textdomain'),
    'singular_name' => __('Modal', 'textdomain'),
    'menu_name' => __('Modales', 'textdomain'),
    'add_new' => __('Añadir Modal', 'textdomain'),
    'add_new_item' => __('Añadir Nuevo Modal', 'textdomain'),
    'edit_item' => __('Editar Modal', 'textdomain'),
    'new_item' => __('Nuevo Modal', 'textdomain'),
    'view_item' => __('Ver Modal', 'textdomain'),
    'search_items' => __('Buscar Modales', 'textdomain'),
    'not_found' => __('No se encontraron Modales', 'textdomain'),
    'not_found_in_trash' => __('No se encontraron Modales en la papelera', 'textdomain'),
    'parent_item_colon' => '',
    'all_items' => __('Todos los Modales', 'textdomain'),
  );

  $args = array(
    'supports' => $supports,
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,
    'menu_icon' => 'dashicons-format-gallery',
    'exclude_from_search' => false,
    'rewrite' => array('slug' => 'modales'),
    'show_in_rest' => true,
    'show_in_graphql' => true,
    'graphql_single_name' => 'modal',
    'graphql_plural_name' => 'modals',
  );

  register_post_type('modales', $args);
}
add_action('init', 'modales_post_type');
