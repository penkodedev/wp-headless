
<?php
// ===================================================================================================
//                                 WORDPRESS ADMIN STYLES
// ===================================================================================================

function style_admin() {
  wp_enqueue_style(
      'mi-style-admin',
      get_stylesheet_directory_uri() . '/style.css',
      [],
      filemtime(get_stylesheet_directory() . '/style.css')
  );
}
add_action('admin_enqueue_scripts', 'style_admin');



// ===================================================================================================
//                                 DISABLE dashboard full screen mode
// ===================================================================================================
if (is_admin()) {
  function disable_editor_fullscreen_by_default()
  {
    $script = "jQuery( window ).load(function() { const isFullscreenMode = wp.data.select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' );
    if ( isFullscreenMode ) { wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'fullscreenMode' ); } });";
    wp_add_inline_script('wp-blocks', $script);
  }
  add_action('enqueue_block_editor_assets', 'disable_editor_fullscreen_by_default');
}

// ---------------------------------------------------------------------------------------------------
//                      ⭐ REMOVE certain PLUGINS UPDATE
// ---------------------------------------------------------------------------------------------------
function remove_update_notifications($value)
{

  if (isset($value) && is_object($value)) {
    unset($value->response['smart-slider-3/nextend-smart-slider3-pro.php']);
    unset($value->response['akismet/akismet.php']);
  }

  return $value;
}
add_filter('site_transient_update_plugins', 'remove_update_notifications');


// ===================================================================================================
//                                 REMOVE Dashboard menu items
// ===================================================================================================
function remove_menus()
{

  //remove_menu_page( 'index.php' ); //**************************//Dashboard Home and Updates
  remove_menu_page('edit.php'); //*****************************//Posts
  //remove_menu_page('edit.php?post_type=portfolio'); //*********//Portfolio CPT
  remove_menu_page('edit-comments.php'); //**********************//Comments
  //remove_menu_page( 'edit.php?post_type=page' ); //************//Pages
  //remove_menu_page( 'themes.php' ); //*************************//Appearance
  //remove_menu_page( 'plugins.php' ); //************************//Users
  //remove_menu_page( 'tools.php' ); //**************************//Tools
  //remove_menu_page( 'options-general.php' ); //****************//Settings
}
add_action('admin_menu', 'remove_menus');



// ===================================================================================================
//                      ⭐ COLUMNA "DESTACADO" CON QUICK TOGGLE PARA RECURSOS
// ===================================================================================================

/**
 * 1. Añade la columna "Destacado" al listado de Recursos.
 */
function xamle_add_destacado_column($columns) {
    // Añade la columna 'destacado' al final del array de columnas.
    $columns['destacado'] = '<span class="dashicons dashicons-star-filled" style="color: #f39c12; font-size: 16px; vertical-align: middle;" title="Destacado"></span>';
    return $columns;
}
add_filter('manage_recursos_posts_columns', 'xamle_add_destacado_column');

/**
 * 2. Muestra el contenido de la columna "Destacado" (el interruptor).
 */
function xamle_display_destacado_column($column, $post_id) {
    if ($column === 'destacado') {
        $is_destacado = get_post_meta($post_id, 'destacado', true) == '1';
        // Nonce para la seguridad de la petición AJAX
        $nonce = wp_create_nonce('xamle_toggle_destacado_nonce');
        ?>
        <label class="switch">
            <input type="checkbox" 
                   class="destacado-toggle" 
                   data-post-id="<?php echo $post_id; ?>" 
                   data-nonce="<?php echo $nonce; ?>"
                   <?php checked($is_destacado); ?>>
            <span class="slider round"></span>
        </label>
        <?php
    }
}
add_action('manage_recursos_posts_custom_column', 'xamle_display_destacado_column', 10, 2);

/**
 * 3. Encola el CSS y JS necesarios en el panel de administración.
 */
function xamle_admin_enqueue_scripts($hook) {
    // Solo cargar en páginas específicas donde se necesitan los toggles
    $allowed_pages = ['edit.php', 'post.php', 'post-new.php'];
    $allowed_post_types = ['recursos'];

    if (!in_array($hook, $allowed_pages) ||
        (isset($_GET['post_type']) && !in_array($_GET['post_type'], $allowed_post_types)) ||
        (isset($_GET['post']) && get_post_type($_GET['post']) !== 'recursos')) {
        return;
    }

    // CSS para el interruptor
    $css = "
        .switch { position: relative; display: inline-block; width: 34px; height: 20px; vertical-align: middle; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
        .slider:before { position: absolute; content: ''; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; }
        input:checked + .slider { background-color: #f39c12; }
        input:checked + .slider:before { transform: translateX(14px); }
        .slider.round { border-radius: 22px; }
        .slider.round:before { border-radius: 50%; }
        .column-destacado { width: 60px; text-align: center !important; }
    ";
    wp_add_inline_style('wp-admin', $css);

    // JavaScript para manejar el AJAX
    $js = "
        jQuery(document).ready(function($) {
            $('.destacado-toggle').on('change', function() {
                var checkbox = $(this);
                var post_id = checkbox.data('post-id');
                var nonce = checkbox.data('nonce');
                var is_checked = checkbox.is(':checked');

                // Deshabilitar temporalmente para evitar clics múltiples
                checkbox.prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'xamle_toggle_destacado',
                        post_id: post_id,
                        is_destacado: is_checked,
                        _ajax_nonce: nonce
                    },
                    success: function(response) {
                        // Reactivar el checkbox
                        checkbox.prop('disabled', false);
                        if (!response.success) {
                            // Si falla, revertir el estado visual
                            checkbox.prop('checked', !is_checked);
                            alert('Hubo un error al actualizar el estado.');
                        }
                    },
                    error: function() {
                        checkbox.prop('disabled', false);
                        checkbox.prop('checked', !is_checked);
                        alert('Error de conexión.');
                    }
                });
            });
        });
    ";
    wp_add_inline_script('jquery-core', $js);
}
add_action('admin_enqueue_scripts', 'xamle_admin_enqueue_scripts');

/**
 * 4. La función PHP que maneja la petición AJAX.
 */
function xamle_handle_toggle_destacado() {
    // Verificar nonce y permisos
    if (
        !check_ajax_referer('xamle_toggle_destacado_nonce', '_ajax_nonce', false) ||
        !current_user_can('edit_posts')
    ) {
        wp_send_json_error(['message' => 'Permiso denegado.'], 403);
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $is_destacado = isset($_POST['is_destacado']) && $_POST['is_destacado'] === 'true';

    if ($post_id > 0) {
        if ($is_destacado) {
            update_post_meta($post_id, 'destacado', '1');
        } else {
            delete_post_meta($post_id, 'destacado');
        }
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'ID de post no válido.'], 400);
    }
}
add_action('wp_ajax_xamle_toggle_destacado', 'xamle_handle_toggle_destacado');

/**
 * 5. (Opcional) Hacer la columna ordenable.
 */
function xamle_make_destacado_column_sortable($columns) {
    $columns['destacado'] = 'destacado';
    return $columns;
}
add_filter('manage_edit-recursos_sortable_columns', 'xamle_make_destacado_column_sortable');


// ===================================================================================================
//                    🔌 PÁGINA DE API CUSTOM ENDPOINTS
// ===================================================================================================

add_action('admin_menu', function () {
    add_menu_page(
        'Documentación de la API',          // Título de la página
        'API Endpoints',                    // Título del menú
        'manage_options',                   // Capacidad requerida para verla
        'api_docs_page',                    // Slug del menú
        'mostrar_documentacion_api_endpoints', // Función que muestra el contenido
        'dashicons-rest-api',               // Icono del menú (https://developer.wordpress.org/resource/dashicons/)
        40                                  // Posición en el menú
    );
});

function mostrar_documentacion_api_endpoints() {
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
        <h1><span class="dashicons-before dashicons-rest-api"></span> Documentación de Endpoints (custom/v1)</h1>
        <p>Esta página lista todos los <b>custom endpoints</b> de la API REST registrados en el namespace <code>custom/v1</code>.</p>

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

// ===================================================================================================
//                      ⭐ DUPLICATE POSTS/PAGES/CUSTOM POSTS on Dashboard
// ===================================================================================================

function duplicate_post_link($actions, $post) {
  if (current_user_can('edit_posts')) {
      if (post_type_supports($post->post_type, 'editor')) {
          $actions['duplicate'] = '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=duplicate_post&post=' . $post->ID), 'duplicate-post_' . $post->ID)) . '" title="Duplicate this item" rel="permalink">Duplicate</a>';
      }
  }
  return $actions;
}

function duplicate_post_action() {
  if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] == 'duplicate_post') {
      $post_id = absint($_GET['post']);
      if (current_user_can('edit_posts')) {
          $new_post_id = duplicate_post($post_id);
          if (!is_wp_error($new_post_id)) {
              wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
              exit;
          } else {
              wp_die('Error duplicating the post.');
          }
      }
  }
}

function duplicate_post($post_id) {
  $post = get_post($post_id);
  if (isset($post) && $post != null) {
      $args = array(
          'post_title' => $post->post_title,
          'post_content' => $post->post_content,
          'post_excerpt' => $post->post_excerpt,
          'post_status' => $post->post_status,
          'post_type' => $post->post_type,
          'post_author' => get_current_user_id(),
      );
      $new_post_id = wp_insert_post($args);
      if ($new_post_id) {
        
          // Duplicate post meta information (custom fields)
          $post_meta = get_post_custom($post_id);
          foreach ($post_meta as $key => $value) {
              if ($key != '_edit_lock' && $key != '_edit_last') {
                  foreach ($value as $meta_value) {
                      add_post_meta($new_post_id, $key, maybe_unserialize($meta_value));
                  }
              }
          }
          return $new_post_id;
      }
  }
  return new WP_Error('duplicate-error', 'Could not duplicate the post.');
}

add_filter('page_row_actions', 'duplicate_post_link', 10, 2);
add_filter('post_row_actions', 'duplicate_post_link', 10, 2);

// Solo añadir el filtro para CPTs personalizados si existen
$custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'names');
foreach ($custom_post_types as $cpt) {
    add_filter("{$cpt}_row_actions", 'duplicate_post_link', 10, 2);
}

add_action('admin_init', 'duplicate_post_action');


// ===================================================================================================
//                      ⭐ ADD CUSTOM POSTS to "At a Glance" Widget
// ===================================================================================================
function add_custom_post_types_to_at_a_glance($items) {
  $custom_post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');

  foreach ($custom_post_types as $post_type) {
      $num_posts = wp_count_posts($post_type->name);
      $num = number_format_i18n($num_posts->publish);
      $text = _n($post_type->labels->singular_name, $post_type->labels->name, intval($num_posts->publish));
      $items[] = sprintf('<a class="%1$s-count" href="edit.php?post_type=%1$s">%2$s %3$s</a>', $post_type->name, $num, $text);
  }

  return $items;
}

add_filter('dashboard_glance_items', 'add_custom_post_types_to_at_a_glance');


// La función 'search_news' y sus actions han sido eliminados.
// Era un remanente de una funcionalidad AJAX que entraba en conflicto
// con la búsqueda de la API REST, impidiendo que se encontraran los CPTs.

// ===================================================================================================
//                      ⭐ Change default POST NAME (when needed)
// ===================================================================================================
/*
add_filter( 'post_type_labels_post', 'change_post_labels' );
function change_post_labels( $args ) {
    foreach( $args as $key => $label ){
        $args->{$key} = str_replace( [ __( 'Posts' ), __( 'Post' ) ],
        __( 'Name' ), $label ); // change post name

    }
    return $args;
}*/