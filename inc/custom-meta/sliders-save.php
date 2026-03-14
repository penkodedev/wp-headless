<?php
/**
 * ===================================================================
 * SLIDERS CPT — SAVE META BOX DATA
 * ===================================================================
 */

if (!defined('ABSPATH')) {
    exit;
}

function pk_slider_save_meta($post_id) {
    if (!isset($_POST['pk_slider_nonce']) || !wp_verify_nonce($_POST['pk_slider_nonce'], 'pk_slider_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['post_type']) || 'sliders' !== $_POST['post_type']) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Type
    $type = sanitize_text_field($_POST['pk_slider_type'] ?? 'cpt');
    $allowed_types = ['cpt', 'testimonials', 'media', 'custom'];
    if (!in_array($type, $allowed_types, true)) {
        $type = 'cpt';
    }
    update_post_meta($post_id, '_slider_type', $type);

    // Swiper config
    $raw_config = $_POST['pk_slider_config'] ?? [];
    $display_mode = sanitize_text_field($raw_config['displayMode'] ?? 'contain');
    if (!in_array($display_mode, ['contain', 'cover'], true)) {
        $display_mode = 'contain';
    }

    $config = [
        'autoplay'     => isset($raw_config['autoplay']) ? 1 : 0,
        'speed'        => max(500, intval($raw_config['speed'] ?? 3000)),
        'perView'      => max(1, min(10, intval($raw_config['perView'] ?? 3))),
        'loop'         => isset($raw_config['loop']) ? 1 : 0,
        'navigation'   => isset($raw_config['navigation']) ? 1 : 0,
        'pagination'   => isset($raw_config['pagination']) ? 1 : 0,
        'fullWidth'    => isset($raw_config['fullWidth']) ? 1 : 0,
        'displayMode'  => $display_mode,
        'gap'          => max(0, min(100, intval($raw_config['gap'] ?? 20))),
        'grayscale'    => isset($raw_config['grayscale']) ? 1 : 0,
        'opacity'      => max(10, min(100, intval($raw_config['opacity'] ?? 100))),
    ];
    update_post_meta($post_id, '_slider_config', $config);

    // CPT source (only relevant for type=cpt)
    if ($type === 'cpt') {
        $raw_source = $_POST['pk_slider_source'] ?? [];
        $source = [
            'postType' => sanitize_text_field($raw_source['postType'] ?? 'post'),
            'perPage'  => max(1, min(50, intval($raw_source['perPage'] ?? 6))),
            'order'    => in_array($raw_source['order'] ?? 'desc', ['asc', 'desc', 'rand'], true) ? $raw_source['order'] : 'desc',
        ];
        update_post_meta($post_id, '_slider_source', $source);
        delete_post_meta($post_id, '_slider_slides');
    } else {
        $raw_slides = $_POST['pk_slider_slides'] ?? [];
        $slides = [];
        foreach ($raw_slides as $slide) {
            $clean = [];
            foreach ($slide as $key => $value) {
                if ($key === 'image_id') {
                    $clean[$key] = absint($value);
                } elseif ($key === 'link') {
                    $clean[$key] = esc_url_raw($value);
                } elseif ($key === 'text') {
                    $clean[$key] = wp_kses_post($value);
                } else {
                    $clean[$key] = sanitize_text_field($value);
                }
            }
            $has_content = false;
            foreach ($clean as $v) {
                if (!empty($v)) { $has_content = true; break; }
            }
            if ($has_content) {
                $slides[] = $clean;
            }
        }
        update_post_meta($post_id, '_slider_slides', $slides);
        delete_post_meta($post_id, '_slider_source');
    }
}
add_action('save_post', 'pk_slider_save_meta');
