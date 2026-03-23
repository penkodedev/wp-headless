<?php
/**
 * ===================================================================
 * MAP CPT — META BOX (each post = one location/pin)
 * ===================================================================
 *
 * One map shows all locations. Each post = one pin with tooltip.
 *
 * Meta fields (React-friendly):
 *   _map_location_lat   (float)  – Latitude
 *   _map_location_lng   (float)  – Longitude
 *   _map_location_addr  (string) – Address (for display, optional)
 *   _map_location_desc  (string) – Tooltip content (HTML allowed)
 *
 * Post title = pin title.
 *
 * Related:
 *   maps-save.php, map.php (settings), api-callbacks, shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

const PK_MAP_META_LAT   = '_map_location_lat';
const PK_MAP_META_LNG   = '_map_location_lng';
const PK_MAP_META_ADDR  = '_map_location_addr';
const PK_MAP_META_DESC  = '_map_location_desc';

require_once __DIR__ . '/maps-save.php';


function pk_map_register_meta_boxes() {
    add_meta_box(
        'pk_map_location',
        __('Location', 'penkode-headless'),
        'pk_map_render_location_metabox',
        'maps',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'pk_map_register_meta_boxes');


function pk_map_render_location_metabox($post) {
    wp_nonce_field('pk_map_save', 'pk_map_nonce');

    $lat  = get_post_meta($post->ID, PK_MAP_META_LAT, true);
    $lng  = get_post_meta($post->ID, PK_MAP_META_LNG, true);
    $addr = get_post_meta($post->ID, PK_MAP_META_ADDR, true);
    $desc = get_post_meta($post->ID, PK_MAP_META_DESC, true);
    ?>

    <div class="pk-map-config">
        <div class="pk-map-address-row">
            <label for="pk_map_location_address"><?php esc_html_e('Address', 'penkode-headless'); ?></label>
            <p class="description pk-map-address-help">
                <?php esc_html_e('First search for the street in the dropdown, then add the number to complete the address. Example: Calle Fray Diego De Cádiz 12, 41003 Sevilla, Seville, Spain', 'penkode-headless'); ?>
            </p>
            <input type="text" id="pk_map_location_address" name="pk_map_location_address" value="<?php echo esc_attr($addr); ?>"
                   class="pk-map-address-autocomplete widefat"
                   placeholder="<?php esc_attr_e('Start typing to search...', 'penkode-headless'); ?>"
                   data-fill-target="#pk_map_location_lat,#pk_map_location_lng" autocomplete="off">
        </div>

        <div class="pk-map-grid">
            <div>
                <label for="pk_map_location_lat"><?php esc_html_e('Latitude', 'penkode-headless'); ?></label>
                <input type="text" id="pk_map_location_lat" name="pk_map_location_lat" value="<?php echo esc_attr($lat); ?>"
                       placeholder="40.4168" class="widefat">
            </div>
            <div>
                <label for="pk_map_location_lng"><?php esc_html_e('Longitude', 'penkode-headless'); ?></label>
                <input type="text" id="pk_map_location_lng" name="pk_map_location_lng" value="<?php echo esc_attr($lng); ?>"
                       placeholder="-3.7038" class="widefat">
            </div>
        </div>

        <div class="pk-map-field">
            <label for="pk_map_location_desc"><?php esc_html_e('Tooltip', 'penkode-headless'); ?></label>
            <textarea id="pk_map_location_desc" name="pk_map_location_desc" class="widefat" rows="4"
                      placeholder="<?php esc_attr_e('Content shown when hovering/clicking the pin...', 'penkode-headless'); ?>"><?php echo esc_textarea($desc); ?></textarea>
            <p class="description"><?php esc_html_e('HTML allowed. Shown in the pin popup.', 'penkode-headless'); ?></p>
        </div>
    </div>
    <?php
}
