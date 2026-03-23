<?php
/**
 * Appearance Settings for Custom Settings
 * 
 * Global visual configuration (not language-dependent).
 * Dark mode, UI component toggles, and default color scheme.
 *
 * NOTE: We intentionally omit 'default' from register_setting().
 * WordPress's registered default makes get_option() return the default
 * instead of false when the row is missing, which causes update_option()
 * to attempt UPDATE (0 rows) instead of INSERT — silently losing the value.
 * We seed the rows with add_option() and pass explicit defaults to get_option().
 */

$pk_appearance_components = [
    'appearance_breadcrumbs_enabled'    => ['label' => 'Breadcrumbs',      'description' => 'Navigation breadcrumbs on inner pages',           'default' => '1'],
    'appearance_mega_menu_enabled'      => ['label' => 'Mega Menu',        'description' => 'Mega menú expandido en desktop (solo mainnav)',     'default' => '0'],
    'appearance_copylink_enabled'       => ['label' => 'Copy Link',        'description' => 'Button to copy the page URL to clipboard',       'default' => '1'],
    'appearance_darkmode_enabled'       => ['label' => 'Dark Mode Toggle', 'description' => 'Show dark mode toggle on the frontend',          'default' => '1'],
    'appearance_default_mode'           => ['label' => 'Default Color Mode','description' => '',                                               'default' => 'light', 'type' => 'select'],
    'appearance_lightbox_enabled'       => ['label' => 'Lightbox',         'description' => 'Fullscreen zoom on images',                       'default' => '1'],
    'appearance_like_enabled'           => ['label' => 'Like Button',      'description' => 'Like/heart button on posts',                      'default' => '1'],
    'appearance_loading_enabled'        => ['label' => 'Loading Spinner',  'description' => 'Loading indicator between page navigations',      'default' => '1'],
    'appearance_popups_enabled'         => ['label' => 'Popups/Modals',    'description' => 'Display popup modals configured in Modales CPT',  'default' => '1'],
    'appearance_scrollprogress_enabled' => ['label' => 'Scroll Progress',  'description' => 'Reading progress bar at the top of the page',    'default' => '0'],
    'appearance_scrolltop_enabled'      => ['label' => 'Scroll to Top',    'description' => 'Floating button to scroll back to top',           'default' => '1'],
    'appearance_share_enabled'          => ['label' => 'Share Button',     'description' => 'Social share button on posts',                    'default' => '1'],
    'appearance_smoothscroll_enabled'   => ['label' => 'Smooth Scroll',    'description' => 'Smooth scrolling behavior for anchor links',      'default' => '1'],
];


foreach ($pk_appearance_components as $key => $cfg) {
    if (!isset($cfg['type'])) {
        add_option($key, $cfg['default']);
    }
}
add_option('appearance_default_mode', 'light');

add_action('admin_init', function() use ($pk_appearance_components) {

    $checkbox_sanitize = function($v) { return $v ? '1' : '0'; };

    foreach ($pk_appearance_components as $key => $cfg) {
        if (isset($cfg['type']) && $cfg['type'] === 'select') {
            register_setting('custom-settings', $key, [
                'type' => 'string',
                'sanitize_callback' => function($v) {
                    return in_array($v, ['light', 'dark', 'system'], true) ? $v : 'light';
                },
            ]);
        } else {
            register_setting('custom-settings', $key, [
                'type' => 'string',
                'sanitize_callback' => $checkbox_sanitize,
            ]);
        }
    }

    add_settings_section(
        'pk_appearance_section',
        '',
        'pk_render_appearance_section',
        'custom-settings-appearance'
    );
});

function pk_render_appearance_section() {
    global $pk_appearance_components;
    ?>
    <div class="pk-appearance-section">

        <table class="widefat striped pk-appearance-table">
            <thead>
                <tr>
                    <th><?php _e('Component', 'penkode-headless'); ?></th>
                    <th><?php _e('Description', 'penkode-headless'); ?></th>
                    <th><?php _e('Enabled', 'penkode-headless'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pk_appearance_components as $key => $cfg) :
                    if (isset($cfg['type']) && $cfg['type'] === 'select') :
                        $mode = get_option($key, $cfg['default']);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html__($cfg['label'], 'penkode-headless'); ?></strong></td>
                            <td class="pk-appearance-desc"><?php _e('Default color mode for first-time visitors', 'penkode-headless'); ?></td>
                            <td>
                                <select name="<?php echo esc_attr($key); ?>">
                                    <option value="light" <?php selected($mode, 'light'); ?>><?php _e('Light', 'penkode-headless'); ?></option>
                                    <option value="dark" <?php selected($mode, 'dark'); ?>><?php _e('Dark', 'penkode-headless'); ?></option>
                                    <option value="system" <?php selected($mode, 'system'); ?>><?php _e('System', 'penkode-headless'); ?></option>
                                </select>
                            </td>
                        </tr>
                    <?php else :
                        $enabled = get_option($key, $cfg['default']);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html__($cfg['label'], 'penkode-headless'); ?></strong></td>
                            <td class="pk-appearance-desc"><?php echo esc_html__($cfg['description'], 'penkode-headless'); ?></td>
                            <td>
                                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="0">
                                <label class="pk-switch">
                                    <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($enabled, '1'); ?>>
                                    <span class="pk-slider"></span>
                                </label>
                            </td>
                        </tr>
                    <?php endif;
                endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
