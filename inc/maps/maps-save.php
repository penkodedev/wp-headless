<?php
/**
 * MAP CPT — SAVE LOCATION META
 */

if (!defined('ABSPATH')) {
    exit;
}

function pk_map_save_meta($post_id) {
    if (!isset($_POST['pk_map_nonce']) || !wp_verify_nonce($_POST['pk_map_nonce'], 'pk_map_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['post_type']) || 'maps' !== $_POST['post_type']) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $lat  = pk_map_sanitize_coord($_POST['pk_map_location_lat'] ?? '', 'lat');
    $lng  = pk_map_sanitize_coord($_POST['pk_map_location_lng'] ?? '', 'lng');
    $addr = sanitize_text_field($_POST['pk_map_location_address'] ?? '');
    $desc = wp_kses_post($_POST['pk_map_location_desc'] ?? '');

    update_post_meta($post_id, PK_MAP_META_LAT, $lat);
    update_post_meta($post_id, PK_MAP_META_LNG, $lng);
    update_post_meta($post_id, PK_MAP_META_ADDR, $addr);
    update_post_meta($post_id, PK_MAP_META_DESC, $desc);
}
add_action('save_post', 'pk_map_save_meta');


function pk_map_sanitize_coord($value, $type = 'lat') {
    $value = sanitize_text_field($value);
    if ($value === '') return '';
    $num = floatval(str_replace(',', '.', $value));
    if ($type === 'lat') {
        return $num >= -90 && $num <= 90 ? (string) $num : '';
    }
    return $num >= -180 && $num <= 180 ? (string) $num : '';
}
