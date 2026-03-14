<?php

// =========================================================================================
// 📌 PREVENIR wptexturize EN SHORTCODES (OPTIMIZADO)
// =========================================================================================
function prevent_wptexturize_on_shortcodes($content) {
    // Solo procesar si el contenido contiene shortcodes
    if (strpos($content, '[') === false) {
        return $content;
    }

    // Restaurar comillas normales dentro de shortcodes
    $content = preg_replace_callback('/\[([^\]]+)\]/', function($matches) {
        return str_replace(['»', '«', '"', '"'], ['"', '"', '"', '"'], $matches[0]);
    }, $content);
    return $content;
}

// Aplicar DESPUÉS de wptexturize (prioridad 12 vs 11 de wptexturize)
add_filter('the_content', 'prevent_wptexturize_on_shortcodes', 12);
add_filter('widget_text', 'prevent_wptexturize_on_shortcodes', 12);
add_filter('term_description', 'prevent_wptexturize_on_shortcodes', 12);


// =========================================================================================
// GENERAL POST SHORTCODE 
// Crear el shortcode dinámico basado en el nombre del post type
// =========================================================================================
function dynamic_post_type_shortcode($atts) {
    // Obtener el nombre del shortcode (tipo de post) desde el contexto global
    global $shortcode_tag;
    $post_type = $shortcode_tag;

    // Extraer los atributos ID y size del shortcode
    $atts = shortcode_atts(
        array(
            'id' => '',
            'size' => 'thumbnail', // Tamaño de la imagen por defecto
        ),
        $atts,
        $post_type
    );

    // Obtener el post correspondiente al ID
    $post = get_post($atts['id']);

    // Verificar si el post existe
    if (!$post) {
        return 'Elemento no encontrado';
    }

    // Obtener el contenido del post (incluyendo imágenes)
    $contenido = apply_filters('the_content', $post->post_content);

    // Devolver solo el contenido sin la imagen destacada ni el título
    return $contenido;
}

// Registrar el shortcode dinámico
function register_dynamic_post_type_shortcodes() {
    // Obtener todos los tipos de post públicos
    $post_types = get_post_types(['public' => true], 'names');

    foreach ($post_types as $post_type) {
        // Registrar el shortcode para cada tipo de post
        add_shortcode($post_type, 'dynamic_post_type_shortcode');
    }
}
add_action('init', 'register_dynamic_post_type_shortcodes', 20); // Mayor prioridad para asegurar que se ejecute después de los CPTs

// Añadir la columna del shortcode para todos los post types
function agregar_columna_shortcode_todos($columns) {
    $columns['shortcode'] = 'Shortcode';
    return $columns;
}

// Rellenar la columna con el shortcode dinámico
function mostrar_columna_shortcode_todos($column, $post_id) {
    if ($column == 'shortcode') {
        $post_type = get_post_type($post_id);
        // The sliders CPT uses [slider] (singular) shortcode
        $shortcode_name = ($post_type === 'sliders') ? 'slider' : $post_type;
        echo '[' . $shortcode_name . ' id="' . $post_id . '"]';
    }
}

// Hacer la columna del shortcode ordenable (opcional)
function hacer_columna_shortcode_ordenable_todos($columns) {
    $columns['shortcode'] = 'shortcode';
    return $columns;
}

// Hook for all post types with UI (public + non-public CPTs like hero, modales, sliders)
function aplicar_shortcodes_a_todos_los_post_types() {
    $post_types = get_post_types(['show_ui' => true], 'names');
    
    foreach ($post_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'agregar_columna_shortcode_todos');
        add_action("manage_{$post_type}_posts_custom_column", 'mostrar_columna_shortcode_todos', 10, 2);
        add_filter("manage_edit-{$post_type}_sortable_columns", 'hacer_columna_shortcode_ordenable_todos');
    }
}
add_action('admin_init', 'aplicar_shortcodes_a_todos_los_post_types');

//******************** ANY WIDGET SHORTCODE [widget="Widget Name"] **************************
// Function to display a widget by name
function display_widget_by_name($atts) {
  // Extract the widget name from the shortcode attributes
  $widget_name = isset($atts[0]) ? sanitize_text_field($atts[0]) : '';

  // Check if the widget name is provided
  if (empty($widget_name)) {
      return 'Widget name is required.';
  }

  // Get the sidebar ID based on the widget name
  $sidebar_id = sanitize_title($widget_name);

  // Check if the sidebar with the provided ID exists
  if (is_active_sidebar($sidebar_id)) {
      ob_start();
      dynamic_sidebar($sidebar_id);
      $widget_content = ob_get_clean();
      return $widget_content;
  } else {
      return 'It seems like Widget do not exists or is do not have any content. Check your widget please in Appearance > Widgets';
  }
}

// Add a shortcode to display widgets by name
add_shortcode('widget', 'display_widget_by_name'); // SHORTCODE USAGE [widget="Widget Name"]


// =========================================================================================
// SLIDER SHORTCODE [slider id="X"]
// Outputs an HTML marker that Next.js replaces with a <SliderRenderer> component.
// =========================================================================================
add_shortcode('slider', function ($atts) {
    $atts = shortcode_atts(['id' => ''], $atts, 'slider');
    $id = absint($atts['id']);

    if (!$id) {
        return '';
    }

    $post = get_post($id);
    if (!$post || $post->post_type !== 'sliders' || $post->post_status !== 'publish') {
        return '';
    }

    return '<div data-component="slider" data-slider-id="' . esc_attr($id) . '"></div>';
});


// =========================================================================================
// STATS SHORTCODE [stats id="X"]
// Outputs an HTML marker that Next.js replaces with a <CounterStats> component.
// X is the 1-based index of the group in Custom Settings > Counter Stats.
// =========================================================================================
add_shortcode('stats', function ($atts) {
    $atts = shortcode_atts(['id' => ''], $atts, 'stats');
    $id = absint($atts['id']);

    if (!$id) {
        return '';
    }

    $groups = get_option('counter_stats_data', []);
    if (!is_array($groups) || !isset($groups[$id - 1])) {
        return '';
    }

    return '<div data-component="stats" data-stats-id="' . esc_attr($id) . '"></div>';
});

