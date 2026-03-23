<?php
/**
 * Map Settings — Default center and zoom for the single map.
 * All locations (maps CPT) appear on one map.
 */

if (!defined('ABSPATH')) {
    exit;
}

function pk_add_map_section() {
    add_settings_section(
        'pk_map_section',
        '',
        'pk_render_map_section',
        'custom-settings-map'
    );
}
add_action('admin_init', 'pk_add_map_section');

function pk_register_map_settings() {
    register_setting('custom-settings', 'pk_map_center_lat', [
        'sanitize_callback' => function ($v) {
            $v = sanitize_text_field($v);
            if ($v === '') return '';
            $n = floatval(str_replace(',', '.', $v));
            return $n >= -90 && $n <= 90 ? (string) $n : '';
        },
    ]);
    register_setting('custom-settings', 'pk_map_center_lng', [
        'sanitize_callback' => function ($v) {
            $v = sanitize_text_field($v);
            if ($v === '') return '';
            $n = floatval(str_replace(',', '.', $v));
            return $n >= -180 && $n <= 180 ? (string) $n : '';
        },
    ]);
    register_setting('custom-settings', 'pk_map_zoom', [
        'sanitize_callback' => function ($v) {
            $n = (int) $v;
            return (string) max(1, min(20, $n ?: 12));
        },
    ]);
    register_setting('custom-settings', 'pk_map_clustering', [
        'sanitize_callback' => function ($v) { return !empty($v) ? '1' : '0'; },
    ]);
    register_setting('custom-settings', 'pk_map_style', [
        'sanitize_callback' => function ($v) {
            $allowed = ['streets', 'light', 'dark', 'outdoors', 'satellite'];
            return in_array($v, $allowed, true) ? $v : 'streets';
        },
    ]);
    register_setting('custom-settings', 'pk_map_height', [
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting('custom-settings', 'pk_map_tooltip_trigger', [
        'sanitize_callback' => function ($v) {
            return in_array($v, ['hover', 'click'], true) ? $v : 'click';
        },
    ]);
    register_setting('custom-settings', 'pk_map_zoom_controls', [
        'sanitize_callback' => function ($v) { return !empty($v) ? '1' : '0'; },
    ]);
}
add_action('admin_init', 'pk_register_map_settings');

function pk_render_map_section() {
    $lat   = get_option('pk_map_center_lat', '');
    $lng   = get_option('pk_map_center_lng', '');
    $zoom  = get_option('pk_map_zoom', '12');
    $zoom  = $zoom ?: '12';
    $zoom  = max(1, min(20, (int) $zoom));
    $cluster = get_option('pk_map_clustering', '0');
    $style   = get_option('pk_map_style', 'streets');
    $height  = get_option('pk_map_height', '400px');
    $tooltip = get_option('pk_map_tooltip_trigger', 'click');
    $zoomCtrls = get_option('pk_map_zoom_controls', '1');
    ?>
    <p class="description"><?php esc_html_e('Default view for the map. If empty, the map will fit all pins.', 'penkode-headless'); ?></p>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e('Center Latitude', 'penkode-headless'); ?></th>
            <td>
                <input type="text" name="pk_map_center_lat" value="<?php echo esc_attr($lat); ?>" class="regular-text" placeholder="40.4168">
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Center Longitude', 'penkode-headless'); ?></th>
            <td>
                <input type="text" name="pk_map_center_lng" value="<?php echo esc_attr($lng); ?>" class="regular-text" placeholder="-3.7038">
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Default Zoom', 'penkode-headless'); ?></th>
            <td>
                <input type="number" name="pk_map_zoom" value="<?php echo esc_attr($zoom); ?>" min="1" max="20" style="width:80px;">
                <span class="description">1–20</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Clustering', 'penkode-headless'); ?></th>
            <td>
                <input type="hidden" name="pk_map_clustering" value="0">
                <label>
                    <input type="checkbox" name="pk_map_clustering" value="1" <?php checked($cluster, '1'); ?>>
                    <?php esc_html_e('Group nearby pins (recommended for 50+ locations)', 'penkode-headless'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Map Style', 'penkode-headless'); ?></th>
            <td>
                <select name="pk_map_style">
                    <option value="streets" <?php selected($style, 'streets'); ?>><?php esc_html_e('Streets', 'penkode-headless'); ?></option>
                    <option value="light" <?php selected($style, 'light'); ?>><?php esc_html_e('Light', 'penkode-headless'); ?></option>
                    <option value="dark" <?php selected($style, 'dark'); ?>><?php esc_html_e('Dark', 'penkode-headless'); ?></option>
                    <option value="outdoors" <?php selected($style, 'outdoors'); ?>><?php esc_html_e('Outdoors', 'penkode-headless'); ?></option>
                    <option value="satellite" <?php selected($style, 'satellite'); ?>><?php esc_html_e('Satellite', 'penkode-headless'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Map Height', 'penkode-headless'); ?></th>
            <td>
                <input type="text" name="pk_map_height" value="<?php echo esc_attr($height); ?>" class="regular-text" placeholder="400px">
                <p class="description"><?php esc_html_e('e.g. 400px, 50vh, 100vh', 'penkode-headless'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Tooltip', 'penkode-headless'); ?></th>
            <td>
                <select name="pk_map_tooltip_trigger">
                    <option value="click" <?php selected($tooltip, 'click'); ?>><?php esc_html_e('On click', 'penkode-headless'); ?></option>
                    <option value="hover" <?php selected($tooltip, 'hover'); ?>><?php esc_html_e('On hover', 'penkode-headless'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('When to show the pin popup', 'penkode-headless'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Zoom controls', 'penkode-headless'); ?></th>
            <td>
                <input type="hidden" name="pk_map_zoom_controls" value="0">
                <label>
                    <input type="checkbox" name="pk_map_zoom_controls" value="1" <?php checked($zoomCtrls, '1'); ?>>
                    <?php esc_html_e('Show zoom (+/-) buttons on the map', 'penkode-headless'); ?>
                </label>
            </td>
        </tr>
    </table>
    <?php
}
