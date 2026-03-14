<?php
/**
 * API Endpoints Documentation Section
 * Displays all custom/v1 REST API endpoints
 */

/**
 * Register API Endpoints section
 */
function pk_add_api_endpoints_section() {
    add_settings_section(
        'pk_api_endpoints_section',
        __('', 'penkode-headless'),
        'pk_render_api_endpoints_section',
        'custom-settings-api'
    );
}
add_action('admin_init', 'pk_add_api_endpoints_section');

function pk_render_api_endpoints_section() {
    // Comprobar si el usuario tiene permisos
    if (!current_user_can('manage_options')) {
        return;
    }

    // Obtener el servidor de la API REST y todas las rutas registradas
    $server = rest_get_server();
    $all_routes = $server->get_routes();

    // Filtrar solo las rutas del namespace 'custom/v1'
    $custom_routes = array_filter($all_routes, function ($route_key) {
        return strpos($route_key, '/custom/v1') === 0;
    }, ARRAY_FILTER_USE_KEY);

    ?>
    <div class="wrap">
        <?php if (empty($custom_routes)) : ?>
            <p>No se encontraron endpoints personalizados en el namespace <code>custom/v1</code>.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Método(s)</th>
                        <th>Ruta</th>
                        <th>Función (Callback)</th>
                        <th>Permisos (permission_callback)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_routes as $route => $handlers) : ?>
                        <?php foreach ($handlers as $handler) : ?>
                            <tr>
                                <td><strong><?php echo implode(', ', array_keys($handler['methods'])); ?></strong></td>
                                <td>
                                    <a href="<?php echo esc_url( home_url( '/wp-json' . $route ) ); ?>" target="_blank" rel="noopener noreferrer"><code><?php echo esc_html($route); ?></code></a>
                                </td>
                                <td>
                                    <?php
                                    if (is_string($handler['callback'])) {
                                        echo '<code>' . esc_html($handler['callback']) . '</code>';
                                    } elseif (is_array($handler['callback']) && is_string($handler['callback'][0]) && is_string($handler['callback'][1])) {
                                        echo '<code>' . esc_html($handler['callback'][0]) . '::' . esc_html($handler['callback'][1]) . '</code>';
                                    } else {
                                        echo '<em>Closure o callback no identificable</em>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo isset($handler['permission_callback']) ? '<code>' . esc_html(is_string($handler['permission_callback']) ? $handler['permission_callback'] : 'Callback personalizado') . '</code>' : '<strong style="color: red;">¡NINGUNO! (Público)</strong>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
