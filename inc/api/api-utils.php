<?php

// =========================================================================================
// 📌 PROCESAR SHORTCODES EN ENDPOINTS CUSTOM (HEADLESS)
// =========================================================================================
// Procesamos shortcodes en los endpoints que contienen shortcodes en su contenido
add_filter('rest_prepare_modales', 'process_shortcodes_in_rest_content', 10, 3);
add_filter('rest_prepare_recursos', 'process_shortcodes_in_rest_content', 10, 3);
add_filter('rest_prepare_post', 'process_shortcodes_in_rest_content', 10, 3);
add_filter('rest_prepare_page', 'process_shortcodes_in_rest_content', 10, 3);

function process_shortcodes_in_rest_content($response, $post, $request) {
    $data = $response->get_data();

    // Procesar shortcodes en el contenido si existe Y contiene shortcodes
    if (isset($data['content']['rendered']) && strpos($data['content']['rendered'], '[') !== false) {
        $data['content']['rendered'] = do_shortcode($data['content']['rendered']);
    }

    $response->set_data($data);
    return $response;
}

// =========================================================================================
// 📌 Decodifica entidades HTML en TODOS los endpoints REST de WP: CPTs, menús, y campos
// =========================================================================================
add_filter('rest_post_dispatch', 'decode_html_entities_rest_output', 15, 3);

function decode_html_entities_rest_output($response, $server, $request) {
    if (!($response instanceof WP_REST_Response)) {
        return $response;
    }

    // Asegurarse de que es una ruta custom nuestra o del contenido
    $route = $request->get_route();
    if (
        strpos($route, '/wp/v2/') === false &&
        strpos($route, '/custom/v2/') === false // tu namespace custom para menús u otros
    ) {
        return $response;
    }

    $data = $response->get_data();

    // Función recursiva para decodificar todo string
    $decode_recursive = function (&$item) use (&$decode_recursive) {
        if (is_string($item)) {
            $item = html_entity_decode($item, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } elseif (is_array($item)) {
            foreach ($item as &$value) {
                $decode_recursive($value);
            }
        } elseif (is_object($item)) {
            foreach ($item as $key => &$value) {
                $decode_recursive($value);
            }
        }
    };

    $decode_recursive($data);

    $response->set_data($data);
    return $response;
}

