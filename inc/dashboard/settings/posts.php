<?php
/**
 * Custom Post Types Settings
 * Muestra solo los CPTs registrados en post-types.php con toggle on/off
 */

/**
 * Devuelve los CPTs registrados por nuestro post-types.php.
 * El valor lo escribe el snippet añadido a ese archivo en cada carga de WP.
 */
function pk_get_own_cpts() {
    return get_option('pk_own_cpts', []);
}

/**
 * Register Posts Settings section
 */
function pk_add_posts_section() {
    add_settings_section(
        'pk_posts_section',
        __('', 'penkode-headless'),
        'pk_render_posts_section',
        'custom-settings-posts'
    );
}
add_action('admin_init', 'pk_add_posts_section');

/**
 * Sanitize CPT visibility settings
 *
 * Itera sobre nuestros CPTs propios y guarda 1 o 0 para cada uno.
 * Los CPTs desmarcados no llegan en $input, pero los guardamos igualmente como 0.
 */
function pk_sanitize_cpt_visibility($input) {
    $sanitized = [];
    $own_cpts  = pk_get_own_cpts();

    foreach ($own_cpts as $slug) {
        $sanitized[$slug] = (isset($input[$slug]) && $input[$slug] === '1') ? 1 : 0;
    }

    return $sanitized;
}

/**
 * Register CPT visibility setting
 */
function pk_register_cpt_visibility_setting() {
    register_setting('custom-settings', 'pk_cpt_visibility', [
        'sanitize_callback' => 'pk_sanitize_cpt_visibility',
        'default'           => [],
    ]);
}
add_action('admin_init', 'pk_register_cpt_visibility_setting');

/**
 * Aplica la visibilidad justo después de que cada CPT se registra.
 *
 * 'registered_post_type' se dispara inmediatamente tras cada register_post_type(),
 * así podemos modificar $wp_post_types sin tocar post-types.php.
 *
 * Al DESACTIVAR:
 *   - Desaparece del menú y UI del Admin
 *   - No se expone en la REST API
 *   - No se expone en WPGraphQL
 *   - Sin URLs públicas en el frontend
 */
function pk_apply_cpt_visibility($post_type, $post_type_object) {
    // Ignorar tipos nativos de WP
    if ($post_type_object->_builtin) {
        return;
    }

    // Ignorar CPTs que no son nuestros
    $own_cpts = pk_get_own_cpts();
    if (!in_array($post_type, $own_cpts, true)) {
        return;
    }

    $saved_settings = get_option('pk_cpt_visibility', []);

    // Sin setting guardado todavía → dejarlo como está
    if (!array_key_exists($post_type, $saved_settings)) {
        return;
    }

    $is_enabled = $saved_settings[$post_type] === 1;

    if (!$is_enabled) {
        global $wp_post_types;

        if (!isset($wp_post_types[$post_type])) {
            return;
        }

        // Ocultar del Admin
        $wp_post_types[$post_type]->show_ui           = false;
        $wp_post_types[$post_type]->show_in_menu      = false;
        $wp_post_types[$post_type]->show_in_nav_menus = false;
        $wp_post_types[$post_type]->show_in_admin_bar = false;

        // Quitar de la REST API
        $wp_post_types[$post_type]->show_in_rest      = false;

        // Quitar de WPGraphQL (si está activo)
        if (isset($wp_post_types[$post_type]->show_in_graphql)) {
            $wp_post_types[$post_type]->show_in_graphql = false;
        }

        // Sin URLs públicas ni búsqueda
        $wp_post_types[$post_type]->public              = false;
        $wp_post_types[$post_type]->publicly_queryable  = false;
        $wp_post_types[$post_type]->exclude_from_search = true;
    }
}
add_action('registered_post_type', 'pk_apply_cpt_visibility', 20, 2);

/**
 * Render Posts section con tabla de CPTs y toggles
 */
function pk_render_posts_section() {
    $own_cpts       = pk_get_own_cpts();
    $saved_settings = get_option('pk_cpt_visibility', []);

    if (empty($own_cpts)) {
        echo '<p>' . __('No custom post types registered.', 'penkode-headless') . '</p>';
        return;
    }

    // Construir array de objetos CPT (público o no, ya que algunos pueden estar desactivados)
    $all_types = array_merge(
        get_post_types(['public' => true,  '_builtin' => false], 'objects'),
        get_post_types(['public' => false, '_builtin' => false], 'objects')
    );
    ?>
    <div class="pk-posts-section">

        <table class="widefat striped pk-cpt-table">
            <thead>
                <tr>
                    <th><?php _e('Post Type', 'penkode-headless'); ?></th>
                    <th><?php _e('Label', 'penkode-headless'); ?></th>
                    <th><?php _e('Count', 'penkode-headless'); ?></th>
                    <th><?php _e('Enabled', 'penkode-headless'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($own_cpts as $slug) :
                    // Buscar el objeto CPT (puede estar en public o non-public según su estado)
                    $post_type = $all_types[$slug] ?? null;
                    if (!$post_type) continue;

                    $published = count(get_posts([
                        'post_type'   => $slug,
                        'post_status' => 'publish',
                        'numberposts' => -1,
                        'fields'      => 'ids',
                    ]));

                    // Por defecto activado si aún no hay setting guardado
                    $is_enabled = !array_key_exists($slug, $saved_settings)
                        || $saved_settings[$slug] === 1;
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($slug); ?></code></td>
                        <td><?php echo esc_html($post_type->labels->singular_name); ?></td>
                        <td>
                            <?php echo esc_html($published); ?>
                            <?php _e('published', 'penkode-headless'); ?>
                        </td>
                        <td>
                            <label class="pk-switch">
                                <input type="checkbox"
                                       name="pk_cpt_visibility[<?php echo esc_attr($slug); ?>]"
                                       value="1"
                                       <?php checked($is_enabled); ?>>
                                <span class="pk-slider"></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
}

/**
 * Helper: comprueba si un CPT propio está activado.
 *
 * Uso: if (pk_is_cpt_enabled('noticias')) { ... }
 */
function pk_is_cpt_enabled($post_type) {
    $saved_settings = get_option('pk_cpt_visibility', []);
    if (!array_key_exists($post_type, $saved_settings)) {
        return true; // por defecto activado
    }
    return $saved_settings[$post_type] === 1;
}