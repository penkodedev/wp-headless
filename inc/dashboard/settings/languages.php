<?php
/**
 * Languages/i18n Settings for Custom Settings
 * 
 * Register and render internationalization configuration fields
 */

add_action('admin_init', function() {
    // Add settings section
    add_settings_section(
        'pk_languages_section',
        '',
        '__return_false',
        'custom-settings-languages'
    );
    
    // Add settings field
    add_settings_field(
        'i18n_settings',
        __('Languages', 'penkode-headless'),
        'i18n_settings_field',
        'custom-settings-languages',
        'pk_languages_section'
    );
    
    // Register setting
    register_setting('custom-settings', 'i18n_settings', 'sanitize_i18n_settings');
});

/**
 * Campo para i18n_settings
 */
function i18n_settings_field() {
    $i18n = get_language_option('i18n_settings') ?: ['default_locale' => 'es', 'locales' => ['es']];
    ?>
    <div class="i18n-settings">
        <div class="form-group">
            <label for="default_locale">Default Locale:</label>
            <input type="text" name="i18n_settings[default_locale]" id="default_locale" value="<?php echo esc_attr($i18n['default_locale'] ?? 'es'); ?>" placeholder="es" />
        </div>
        <div class="form-group">
            <label for="available_locales">Available Locales (comma-separated):</label>
            <input type="text" name="i18n_settings[locales]" id="available_locales" value="<?php echo esc_attr(implode(', ', $i18n['locales'] ?? ['es'])); ?>" placeholder="es, en, fr" />
        </div>
    </div>
    <?php
}

/**
 * Sanitiza los i18n_settings
 */
function sanitize_i18n_settings($settings) {
    if (!is_array($settings)) return ['default_locale' => 'es', 'locales' => ['es']];
    
    $locales_string = $settings['locales'] ?? 'es';
    
    // Handle case where locales is already an array
    if (is_array($locales_string)) {
        $locales = $locales_string;
    } else {
        $locales = array_map('trim', explode(',', $locales_string));
    }
    
    $locales = array_filter($locales, function($locale) {
        return preg_match('/^[a-z]{2,3}(-[a-z]{2,4})?$/i', $locale);
    });
    
    return [
        'default_locale' => sanitize_text_field($settings['default_locale'] ?? 'es'),
        'locales' => $locales ?: ['es']
    ];
}
