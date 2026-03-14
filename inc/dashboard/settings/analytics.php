<?php
/**
 * Analytics Settings for Custom Settings
 * 
 * Register and render analytics configuration fields
 */

add_action('admin_init', function() {
    // Add settings section
    add_settings_section(
        'pk_analytics_section',
        '',
        '__return_false',
        'custom-settings-analytics'
    );
    
    // Add settings field
    add_settings_field(
        'analytics_settings',
        __('Analytics Configuration', 'penkode-headless'),
        'analytics_settings_field',
        'custom-settings-analytics',
        'pk_analytics_section'
    );
    
    // Register setting
    register_setting('custom-settings', 'analytics_settings', 'sanitize_analytics_settings');
});

/**
 * Campo para analytics_settings
 */
function analytics_settings_field() {
    $analytics = get_language_option('analytics_settings') ?: [];
    ?>
    <div class="analytics-settings">
        <div class="form-group">
            <label for="google_analytics_id">Google Analytics ID:</label>
            <input type="text" name="analytics_settings[google_analytics_id]" id="google_analytics_id" value="<?php echo esc_attr($analytics['google_analytics_id'] ?? ''); ?>" placeholder="GA-XXXXXXXXX" />
        </div>
        <div class="form-group">
            <label for="facebook_pixel_id">Facebook Pixel ID:</label>
            <input type="text" name="analytics_settings[facebook_pixel_id]" id="facebook_pixel_id" value="<?php echo esc_attr($analytics['facebook_pixel_id'] ?? ''); ?>" placeholder="123456789012345" />
        </div>
        <div class="form-group">
            <label for="gtm_id">Google Tag Manager ID:</label>
            <input type="text" name="analytics_settings[gtm_id]" id="gtm_id" value="<?php echo esc_attr($analytics['gtm_id'] ?? ''); ?>" placeholder="GTM-XXXXXXX" />
        </div>
        <div class="form-group">
            <label for="twitter_pixel_id">Twitter Pixel ID:</label>
            <input type="text" name="analytics_settings[twitter_pixel_id]" id="twitter_pixel_id" value="<?php echo esc_attr($analytics['twitter_pixel_id'] ?? ''); ?>" placeholder="abcd1234" />
        </div>
    </div>
    <?php
}

/**
 * Sanitiza los analytics_settings
 */
function sanitize_analytics_settings($settings) {
    if (!is_array($settings)) return [];
    
    return [
        'google_analytics_id' => sanitize_text_field($settings['google_analytics_id'] ?? ''),
        'facebook_pixel_id' => sanitize_text_field($settings['facebook_pixel_id'] ?? ''),
        'gtm_id' => sanitize_text_field($settings['gtm_id'] ?? ''),
        'twitter_pixel_id' => sanitize_text_field($settings['twitter_pixel_id'] ?? ''),
    ];
}
