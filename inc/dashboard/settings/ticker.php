<?php
/**
 * Ticker Settings Fields
 * Only contains settings fields for the Custom Settings page.
 * The REST API endpoint is registered in /inc/api/api-endpoints.php
 * with callback in /inc/api/api-callbacks.php.
 */

/**
 * Registrar campos en la página de Custom Settings
 */
function next_wp_kit_register_ticker_settings() {
    // Simple register settings without custom callbacks to avoid infinite loops
    register_setting('custom-settings', 'ticker_enabled');
    register_setting('custom-settings', 'ticker_text');
    register_setting('custom-settings', 'ticker_pages');
    register_setting('custom-settings', 'ticker_link');
    register_setting('custom-settings', 'ticker_speed');
    register_setting('custom-settings', 'ticker_size');
    register_setting('custom-settings', 'ticker_no_animate');
    register_setting('custom-settings', 'ticker_pause_hover');
}
add_action('admin_init', 'next_wp_kit_register_ticker_settings');

function next_wp_kit_render_ticker_settings() {
    $pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'ASC']);
    $selected_pages = (array) get_language_option('ticker_pages', []);

    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">Activar Ticker</th>
            <td>
                <?php $enabled = get_language_option('ticker_enabled', false); ?>
                <label>
                    <input type="checkbox" name="ticker_enabled" value="1" <?php checked($enabled, true); ?>>
                    Activar el ticker en las páginas seleccionadas
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">Texto del ticker</th>
            <td>
                <?php 
                $content = get_language_option('ticker_text', '');
                wp_editor($content, 'ticker_text', [
                    'textarea_name' => 'ticker_text',
                    'media_buttons' => false,
                    'textarea_rows' => 4,
                    'quicktags' => false,
                    'tinymce' => [
                        'toolbar1' => 'bold,italic,underline,link',
                        'toolbar2' => '',
                    ],
                ]);
                ?>
                <p class="description">Texto que se mostrará en el ticker. Usa el editor para dar formato.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">Enlace (opcional)</th>
            <td>
                <?php $value = get_language_option('ticker_link', ''); ?>
                <input type="url" name="ticker_link" value="<?php echo esc_attr($value); ?>" style="width: 100%; max-width: 400px;" placeholder="https://...">
                <p class="description">URL a la que directedirá el ticker (opcional)</p>
            </td>
        </tr>
        <tr>
            <th scope="row">Mostrar en páginas</th>
            <td>
                <?php $value = get_language_option('ticker_pages', []); ?>
                <fieldset>
                    <legend class="screen-reader-text">Seleccionar páginas</legend>
                    <p class="description" style="margin-bottom: 10px;">Selecciona las páginas donde quieres que aparezca el ticker. Si no seleccionas ninguna, aparecerá en todas.</p>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #c3c4c7; padding: 10px; background: #f6f7f7;">
                        <?php foreach ($pages as $page): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="ticker_pages[]" value="<?php echo esc_attr($page->ID); ?>" <?php checked(in_array($page->ID, $selected_pages), true); ?>>
                                <?php echo esc_html($page->post_title); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            </td>
        </tr>
        <tr>
            <th scope="row">Velocidad</th>
            <td>
                <?php $value = get_language_option('ticker_speed', 50); ?>
                <input type="range" name="ticker_speed" value="<?php echo esc_attr($value); ?>" min="10" max="100" step="5" style="width: 200px;">
                <span id="ticker_speed_display"><?php echo esc_html($value); ?></span>
                <p class="description">10 = muy lento, 100 = muy rápido</p>
                <script>
                    document.querySelector('input[name="ticker_speed"]')?.addEventListener('input', function(e) {
                        document.getElementById('ticker_speed_display').textContent = e.target.value;
                    });
                </script>
            </td>
        </tr>
        <tr>
            <th scope="row">Tamaño</th>
            <td>
                <?php $value = get_language_option('ticker_size', 'medium'); ?>
                <select name="ticker_size">
                    <option value="small" <?php selected($value, 'small'); ?>>Small</option>
                    <option value="medium" <?php selected($value, 'medium'); ?>>Medium</option>
                    <option value="big" <?php selected($value, 'big'); ?>>Big</option>
                    <option value="extra-big" <?php selected($value, 'extra-big'); ?>>Extra Big</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">Sin movimiento</th>
            <td>
                <?php $value = get_language_option('ticker_no_animate', false); ?>
                <label>
                    <input type="checkbox" name="ticker_no_animate" value="1" <?php checked($value, true); ?>>
                    Mostrar texto estático sin animación
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">Pausar al hover</th>
            <td>
                <?php $value = get_language_option('ticker_pause_hover', true); ?>
                <label>
                    <input type="checkbox" name="ticker_pause_hover" value="1" <?php checked($value, true); ?>>
                    Pausar animación al pasar el ratón
                </label>
            </td>
        </tr>
    </table>
    <?php
}
