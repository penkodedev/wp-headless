<?php

// =============================================================
//   REGISTRO DE CAMPOS PERSONALIZADOS EN LA REST API
// =============================================================
add_action('init', function() {
    // Autoría
    register_post_meta('recursos', 'recurso_autoria', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => '__return_true',
        'default' => '',
    ]);
    // Web del recurso
    register_post_meta('recursos', 'recurso_web_url', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => '__return_true',
        'default' => '',
    ]);
    // PDF externo
    register_post_meta('recursos', 'recurso_pdf_url', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => '__return_true',
        'default' => '',
    ]);
    // PDF (media)
    register_post_meta('recursos', 'recurso_pdf_id', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => '__return_true',
        'default' => '',
    ]);
});

// =============================================================
//   EXPOSICIÓN EXPLÍCITA VÍA register_rest_field (FALLBACK)
// =============================================================
add_action('rest_api_init', function() {
    $meta_fields = ['recurso_autoria', 'recurso_web_url', 'recurso_pdf_url', 'recurso_pdf_id'];
    
    foreach ($meta_fields as $field) {
        register_rest_field('recursos', $field, [
            'get_callback' => function($object) use ($field) {
                $value = get_post_meta($object['id'], $field, true);
                
                // Si es el campo PDF ID, devolver la URL del attachment en lugar del ID
                if ($field === 'recurso_pdf_id' && !empty($value)) {
                    $attachment_url = wp_get_attachment_url($value);
                    // Si el attachment no existe, devolver vacío
                    if (!$attachment_url) {
                        return '';
                    }
                    // Devolver la URL en lugar del ID
                    return $attachment_url;
                }
                
                return $value;
            },
            'update_callback' => function($value, $object) use ($field) {
                return update_post_meta($object->ID, $field, $value);
            },
            'schema' => [
                'type' => 'string',
                'context' => ['view', 'edit'],
            ],
        ]);
    }
});

// =============================================================
//   EXPOSE LIKES COUNT IN REST API FOR ALL POST TYPES
// =============================================================
add_action('rest_api_init', function() {
    // Get all public post types
    $post_types = get_post_types(['public' => true], 'names');
    
    foreach ($post_types as $post_type) {
        register_rest_field($post_type, 'likes', [
            'get_callback' => function($object) {
                return (int) get_post_meta($object['id'], '_post_likes', true);
            },
            'update_callback' => null, // Read-only in REST API, use custom endpoint to increment
            'schema' => [
                'type' => 'integer',
                'context' => ['view', 'edit'],
                'readonly' => true,
            ],
        ]);
    }
});


// =============================================================
//   SCHEMA DE CAMPOS PERSONALIZADOS PARA EL ENDPOINT
// =============================================================
// Este array será expuesto por el endpoint /custom/v1/custom-fields-schema
// y consumido por el frontend para renderizado dinámico.

$custom_fields_schema = [
    [
        'id' => 'recurso_autoria',
        'label' => [ 'es' => 'Autoría', 'en' => 'Author' ],
        'type' => 'text',
        'cpts' => ['recursos'],
        'required' => false,
        'placeholder' => [ 'es' => 'Nombre del autor o la organización', 'en' => 'Author or organization name' ],
    ],
    [
        'id' => 'recurso_web_url',
        'label' => [ 'es' => 'Web del Recurso', 'en' => 'Resource Website' ],
        'type' => 'url',
        'cpts' => ['recursos'],
        'required' => false,
        'placeholder' => [ 'es' => 'https://ejemplo.com/recurso', 'en' => 'https://example.com/resource' ],
    ],
    [
        'id' => 'recurso_pdf_url',
        'label' => [ 'es' => 'PDF (externo)', 'en' => 'PDF (external)' ],
        'type' => 'url',
        'cpts' => ['recursos'],
        'required' => false,
        'placeholder' => [ 'es' => 'https://ejemplo.com/documento.pdf', 'en' => 'https://example.com/document.pdf' ],
    ],
    [
        'id' => 'recurso_pdf_id',
        'label' => [ 'es' => 'PDF (media)', 'en' => 'PDF (media)' ],
        'type' => 'file',
        'cpts' => ['recursos'],
        'required' => false,
        'placeholder' => [ 'es' => 'ID de adjunto', 'en' => 'Attachment ID' ],
    ],
];



// ===================================================================================================
//
//             Desactiva el editor de bloques (Gutenberg) para tipos de post específicos.
//
// ===================================================================================================

function theme_prefix_disable_gutenberg_for_cpt( $use_block_editor, $post_type ) {
    // Añade aquí los CPTs donde quieres desactivar Gutenberg.
    $disabled_post_types = array( 'recursos' ); // Comentado o vaciado para permitir Gutenberg en 'recursos'

    if ( in_array( $post_type, $disabled_post_types, true ) ) {
        return false;
    }

    return $use_block_editor;
}
add_filter( 'use_block_editor_for_post_type', 'theme_prefix_disable_gutenberg_for_cpt', 10, 2 );



// ===================================================================================================
//
//                     Registra los meta boxes para el CPT 'recursos'.
//
// ===================================================================================================

function theme_prefix_register_recursos_meta_boxes() {
    add_meta_box(
        'recursos_pdf_metabox',                 // ID único del meta box.
        'Información Adicional',                // Título del meta box.
        'theme_prefix_render_recursos_metabox', // Función de callback para renderizar el contenido.
        'recursos',                             // El CPT donde se mostrará.
        'advanced',                             // Contexto (advanced, normal, side).
        'high'                                  // Prioridad (high, core, default, low).
    );
}
add_action( 'add_meta_boxes', 'theme_prefix_register_recursos_meta_boxes' );


/**
 * Renderiza el contenido del meta box para los campos de PDF.
 *
 * @param WP_Post $post El objeto del post actual.
 */
function theme_prefix_render_recursos_metabox( $post ) {
    // Añadir un nonce para verificación de seguridad.
    wp_nonce_field( 'recursos_pdf_nonce_action', 'recursos_pdf_nonce' );

    // Obtener valores guardados.
    $autoria = get_post_meta( $post->ID, 'recurso_autoria', true );
    $web_url = get_post_meta( $post->ID, 'recurso_web_url', true );
    $pdf_url = get_post_meta( $post->ID, 'recurso_pdf_url', true );
    $pdf_id  = get_post_meta( $post->ID, 'recurso_pdf_id', true );
    $pdf_src = wp_get_attachment_url( $pdf_id );
    ?>
    <style>
        .recurso-field { margin-bottom: 20px; }
        .recurso-field label { display: block; font-weight: bold; margin-bottom: 5px; }
        .recurso-field input[type="url"],
        .recurso-field input[type="text"] { width: 100%; }
        .recurso-field .upload-description { font-size: 0.9em; color: #666; }
    </style>

    <div class="recurso-field">
        <label for="recurso_autoria">Autoría</label>
        <input type="text" id="recurso_autoria" name="recurso_autoria" value="<?php echo esc_attr( $autoria ); ?>" placeholder="Nombre del autor o la organización" />
    </div>

    <div class="recurso-field">
        <label for="recurso_web_url">Web del Recurso</label>
        <input type="url" id="recurso_web_url" name="recurso_web_url" value="<?php echo esc_url( $web_url ); ?>" placeholder="https://ejemplo.com/recurso" />
    </div>

    <div class="recurso-field">
        <label for="recurso_pdf_url">URL del PDF (externo)</label>
        <input type="url" id="recurso_pdf_url" name="recurso_pdf_url" value="<?php echo esc_url( $pdf_url ); ?>" placeholder="https://ejemplo.com/documento.pdf" />
    </div>

    <div class="recurso-field">
        <label for="recurso_pdf_upload">Subir PDF (desde tu PC)</label>
        <input type="hidden" name="recurso_pdf_id" id="recurso_pdf_id" value="<?php echo esc_attr( $pdf_id ); ?>" />
        <button type="button" class="button" id="upload_pdf_button">Seleccionar o Subir PDF</button>
        <button type="button" class="button" id="remove_pdf_button" style="<?php echo ( $pdf_id ? '' : 'display:none;' ); ?>">Quitar PDF</button>
        <p class="upload-description">Sube un archivo PDF o selecciónalo de la biblioteca de medios.</p>
        <div id="pdf-preview-container">
            <?php if ( $pdf_src ) : ?>
                <a href="<?php echo esc_url( $pdf_src ); ?>" target="_blank">Ver PDF actual</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        var mediaUploader;

        $('#upload_pdf_button').click(function(e) {
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Seleccionar un PDF',
                button: { text: 'Usar este PDF' },
                library: { type: 'application/pdf' }, // Limitar a archivos PDF
                multiple: false
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#recurso_pdf_id').val(attachment.id);
                $('#pdf-preview-container').html('<a href="' + attachment.url + '" target="_blank">Ver PDF actual</a>');
                $('#remove_pdf_button').show();
            });

            mediaUploader.open();
        });

        $('#remove_pdf_button').click(function(e) {
            e.preventDefault();
            $('#recurso_pdf_id').val('');
            $('#pdf-preview-container').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}


/**
 * Guarda los datos de los meta boxes al guardar el post.
 *
 * @param int $post_id El ID del post que se está guardando.
 */
function theme_prefix_save_recursos_metadata( $post_id ) {
    // Verificar el nonce.
    if ( ! isset( $_POST['recursos_pdf_nonce'] ) || ! wp_verify_nonce( $_POST['recursos_pdf_nonce'], 'recursos_pdf_nonce_action' ) ) {
        return;
    }

    // No guardar en autoguardado.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Verificar permisos del usuario.
    if ( isset( $_POST['post_type'] ) && 'recursos' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    // Guardar campo Autoría.
    if ( isset( $_POST['recurso_autoria'] ) ) {
        $autoria = sanitize_text_field( $_POST['recurso_autoria'] );
        update_post_meta( $post_id, 'recurso_autoria', $autoria );
    }

    // Guardar campo Web del Recurso.
    if ( isset( $_POST['recurso_web_url'] ) ) {
        $web_url = esc_url_raw( $_POST['recurso_web_url'] );
        update_post_meta( $post_id, 'recurso_web_url', $web_url );
    }

    // Guardar campo URL.
    if ( isset( $_POST['recurso_pdf_url'] ) ) {
        $url = esc_url_raw( $_POST['recurso_pdf_url'] ); // Usar esc_url_raw para URLs
        update_post_meta( $post_id, 'recurso_pdf_url', $url );
    }

    // Guardar campo de subida de archivo.
    if ( isset( $_POST['recurso_pdf_id'] ) ) {
        $id = intval( $_POST['recurso_pdf_id'] ); // El ID es un número entero
        update_post_meta( $post_id, 'recurso_pdf_id', $id );
    }
}
add_action( 'save_post', 'theme_prefix_save_recursos_metadata' );



 // ===================================================================================================
 //
 //                                     💥 POPUP MODAL
 //
 // ===================================================================================================
 
 /**
  * 1. Registra el meta box para la configuración del Popup en el CPT 'modales'.
  */
 function agregar_meta_box_popup_config() {
     add_meta_box(
         'popup_config_metabox',          // ID único
         'Configuración de Popup',        // Título del meta box
         'mostrar_meta_box_popup_config', // Callback para renderizar
         'modales',                       // CPT donde se mostrará
         'side',
         'high'
     );
 }
 add_action('add_meta_boxes', 'agregar_meta_box_popup_config');
 
 /**
  * 2. Renderiza el contenido del meta box de configuración del Popup.
  */
 function mostrar_meta_box_popup_config($post) {
     // Nonce para seguridad
     wp_nonce_field('guardar_popup_config', 'popup_config_nonce');
 
     // Obtener valores guardados
     $is_popup = get_post_meta($post->ID, '_is_popup', true);
    $saved_slugs_str = get_post_meta($post->ID, '_popup_pages', true);
    $popup_frequency = get_post_meta($post->ID, '_popup_frequency', true);
    $popup_delay = get_post_meta($post->ID, '_popup_delay', true);
    if (empty($popup_frequency)) {
        $popup_frequency = 'once'; // Default value
    }

    $saved_slugs = !empty($saved_slugs_str) ? explode(',', $saved_slugs_str) : [];

    // Obtener todas las páginas publicadas para el dropdown
    $all_pages = get_pages(['post_status' => 'publish', 'sort_column' => 'post_title', 'sort_order' => 'ASC']);
     ?>
     <p>
         <label for="is_popup_checkbox">
             <input type="checkbox" id="is_popup_checkbox" name="is_popup_checkbox" value="1" <?php checked($is_popup, '1'); ?>>
             <strong>Activar como Popup</strong>
         </label>
        <small>Marca esta casilla para que este modal se comporte como un popup automático.</small>
     </p>
     <hr>
     <p>
        <label for="popup_pages_select"><strong>Mostrar en Páginas:</strong></label>
        <select id="popup_pages_select" name="popup_pages_select[]" multiple style="width:100%; height: 150px;">
            <?php foreach ($all_pages as $page) : ?>
                <?php
                    // Comprobar si esta página es la página de inicio configurada en WordPress
                    $is_front_page = (get_option('page_on_front') == $page->ID);
                    // El slug para la página de inicio es '/', para las demás es su ruta.
                    $page_slug = $is_front_page ? '/' : '/' . get_page_uri($page->ID);
                ?>
                <option value="<?php echo esc_attr($page_slug); ?>" <?php selected(in_array($page_slug, $saved_slugs)); ?>>
                    <?php echo esc_html($page->post_title); ?> <?php echo $is_front_page ? '(Página de Inicio)' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
         <small>Selecciona una o varias páginas (mantén Ctrl/Cmd para selección múltiple).</small>
     </p>
     <hr>
     <p>
        <label for="popup_frequency_select"><strong>Frecuencia de Muestra:</strong></label>
        <select id="popup_frequency_select" name="popup_frequency_select" style="width:100%;">
            <option value="once" <?php selected($popup_frequency, 'once'); ?>>Una vez por página, por sesión</option>
            <option value="always" <?php selected($popup_frequency, 'always'); ?>>Siempre en cada carga de página</option>
            <option value="2" <?php selected($popup_frequency, '2'); ?>>2 veces por página, por sesión</option>
            <option value="3" <?php selected($popup_frequency, '3'); ?>>3 veces por página, por sesión</option>
        </select>
        <small>Elige cuántas veces se mostrará el popup al usuario.</small>
     </p>
     <hr>
     <p>
        <label for="popup_delay_input"><strong>Tiempo de espera (segundos):</strong></label>
        <input type="number" id="popup_delay_input" name="popup_delay_input" value="<?php echo esc_attr($popup_delay ?: 2); ?>" min="0" style="width:100%;">
        <small>Segundos a esperar antes de mostrar el popup. Por defecto: 2.</small>
     </p>
     <?php
 }
 
 /**
  * 3. Guarda los datos del meta box al guardar el post.
  */
 function guardar_meta_box_popup_config($post_id) {
     // Verificar nonce
     if (!isset($_POST['popup_config_nonce']) || !wp_verify_nonce($_POST['popup_config_nonce'], 'guardar_popup_config')) {
         return;
     }
 
     // No guardar en autoguardado
     if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
         return;
     }
 
     // Verificar permisos
     if (isset($_POST['post_type']) && 'modales' === $_POST['post_type']) {
         if (!current_user_can('edit_post', $post_id)) {
             return;
         }
     } else {
         return;
     }
 
     // Guardar el checkbox "Activar como Popup"
     if (isset($_POST['is_popup_checkbox'])) {
         update_post_meta($post_id, '_is_popup', '1');
     } else {
         delete_post_meta($post_id, '_is_popup');
     }
 
     // Guardar las páginas donde se mostrará
    if (isset($_POST['popup_pages_select']) && is_array($_POST['popup_pages_select'])) {
        // Sanitizamos cada slug individualmente para asegurar que no haya caracteres extraños.
        $selected_slugs = array_map('sanitize_text_field', $_POST['popup_pages_select']);
        $pages_string = implode(',', $selected_slugs);
        update_post_meta($post_id, '_popup_pages', $pages_string);
     } else {
        // Si no se selecciona ninguna página, borramos el meta para que no quede guardado un valor vacío.
         delete_post_meta($post_id, '_popup_pages');
     }

     // Guardar la frecuencia de muestra
     if (isset($_POST['popup_frequency_select'])) {
        update_post_meta($post_id, '_popup_frequency', sanitize_text_field($_POST['popup_frequency_select']));
     } else {
        delete_post_meta($post_id, '_popup_frequency');
     }

     // Guardar el tiempo de espera
     if (isset($_POST['popup_delay_input']) && is_numeric($_POST['popup_delay_input'])) {
        update_post_meta($post_id, '_popup_delay', intval($_POST['popup_delay_input']));
     } else {
        delete_post_meta($post_id, '_popup_delay');
     }
 }
 add_action('save_post', 'guardar_meta_box_popup_config');
 
 /**
  * 4. Registra los campos del popup en la API REST para el CPT 'modales'.
  */
 function agregar_campos_popup_a_api_rest() {
     register_rest_field('modales', 'popup_settings', array(
         'get_callback' => function($post_arr) {
             $is_popup = get_post_meta($post_arr['id'], '_is_popup', true) === '1';
             $pages_str = get_post_meta($post_arr['id'], '_popup_pages', true);
             $delay_seconds = get_post_meta($post_arr['id'], '_popup_delay', true);
             $frequency = get_post_meta($post_arr['id'], '_popup_frequency', true) ?: 'once'; // Default to 'once'
             
             // Convertir el string de páginas en un array limpio
             $display_pages = !empty($pages_str) ? array_map('trim', explode(',', $pages_str)) : [];
 
             return [
                 'is_popup' => $is_popup,
                 'delay' => is_numeric($delay_seconds) ? intval($delay_seconds) * 1000 : 2000, // Convert to ms, default 2s
                 'display_pages' => $display_pages,
                 'frequency' => $frequency,
             ];
         },
         'update_callback' => null,
         'schema' => array(
             'description' => 'Configuración para mostrar el modal como un popup.',
             'type'        => 'object',
             'properties'  => [
                 'is_popup' => ['type' => 'boolean'],
                 'delay' => ['type' => 'integer'],
                 'frequency' => ['type' => 'string'],
                 'display_pages' => ['type' => 'array', 'items' => ['type' => 'string']],
             ],
             'context'     => array('view', 'edit'),
         ),
     ));
 }
 add_action('rest_api_init', 'agregar_campos_popup_a_api_rest');


// =============================================================
//   MIGRACIÓN DE CAMPOS CON PREFIJO _ A SIN PREFIJO
// =============================================================
// Este hook se ejecuta una sola vez para migrar los datos antiguos
function migrate_recursos_meta_fields() {
    // Verifica si ya se ejecutó la migración
    if (get_option('recursos_meta_migrated')) {
        return;
    }

    $field_mappings = [
        '_recurso_autoria' => 'recurso_autoria',
        '_recurso_web_url' => 'recurso_web_url',
        '_recurso_pdf_url' => 'recurso_pdf_url',
        '_recurso_pdf_id' => 'recurso_pdf_id',
    ];

    $recursos = get_posts([
        'post_type' => 'recursos',
        'posts_per_page' => -1,
        'post_status' => 'any',
    ]);

    foreach ($recursos as $recurso) {
        foreach ($field_mappings as $old_key => $new_key) {
            $old_value = get_post_meta($recurso->ID, $old_key, true);
            
            if ($old_value !== '' && $old_value !== false) {
                // Copia el valor al nuevo campo
                update_post_meta($recurso->ID, $new_key, $old_value);
                // Opcionalmente elimina el campo antiguo
                // delete_post_meta($recurso->ID, $old_key);
            }
        }
    }

    // Marca la migración como completada
    update_option('recursos_meta_migrated', true);
}
add_action('admin_init', 'migrate_recursos_meta_fields');
