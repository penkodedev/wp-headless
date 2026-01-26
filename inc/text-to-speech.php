<?php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

// ===================================================================================================
//
//                      🔊 FUNCIONALIDAD DE TEXTO A VOZ PARA RECURSOS
//
// ===================================================================================================

use Stichoza\GoogleTranslate\GoogleTranslate;

/**
 * Se activa cuando se guarda un post del tipo 'recursos'.
 * **NOTA: Esta función ahora se llama vía AJAX, no con save_post.**
 * Genera un archivo de audio a partir del contenido del post.
 *
 * @param int     $post_id El ID del post que se está guardando.
 * @param WP_Post $post    El objeto del post.
 */
function xamle_generate_audio_on_save($post_id, $post) {
    // --- Verificaciones iniciales ---

    // --- Verificaciones ---
    // 1. Solo actuar sobre el CPT 'recursos'.
    if ($post->post_type !== 'recursos') {
        return;
    }
    
    // 2. No actuar en autoguardados.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // 3. No actuar en revisiones.
    if (wp_is_post_revision($post_id)) {
        return;
    }

    // 4. Verificar que el post está publicado.
    if ($post->post_status !== 'publish') {
        return;
    }

    // --- Preparación del contenido ---
    
    // 5. Unir título y contenido para el audio.
    $title = $post->post_title;
    // --- Método definitivo: Múltiples enfoques para extraer TODO el texto ---
    $content = $post->post_content;
    $text_content = '';

    if (!empty($content)) {
        // MÉTODO 1: Usar strip_tags directamente (más confiable)
        $text_content = strip_tags($content);
        $text_content = preg_replace('/\s+/', ' ', trim($text_content));
        
        // MÉTODO 2: Respaldo con DOMDocument si strip_tags falla
        if (empty($text_content)) {
            $dom = new DOMDocument();
            // Configurar para UTF-8
            $dom->encoding = 'utf-8';
            // Usamos @ para suprimir warnings de HTML mal formado
            @$dom->loadHTML('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $content . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            $body = $dom->getElementsByTagName('body')->item(0);
            if ($body) {
                $text_content = $body->textContent;
                $text_content = preg_replace('/\s+/', ' ', trim($text_content));
            }
        }
        
        // MÉTODO 3: Respaldo final con regex si todo falla
        if (empty($text_content)) {
            $text_content = preg_replace('/<[^>]*>/', ' ', $content);
            $text_content = html_entity_decode($text_content, ENT_QUOTES, 'UTF-8');
            $text_content = preg_replace('/\s+/', ' ', trim($text_content));
        }
        
        // DEBUG: Log para ver qué contenido se está extrayendo
        // error_log('DEBUG TTS - Post ID: ' . $post_id . ' - Longitud del contenido extraído: ' . mb_strlen($text_content, 'UTF-8') . ' caracteres');
        // error_log('DEBUG TTS - Primeros 200 caracteres: ' . mb_substr($text_content, 0, 200, 'UTF-8'));
    }
    $text_to_convert = $title . ". \n\n" . $text_content;

    // DEBUG: Información completa del texto
    // error_log('DEBUG TTS - Título: ' . $title);
    // error_log('DEBUG TTS - Contenido extraído completo (' . mb_strlen($text_content, 'UTF-8') . ' chars): ' . $text_content);
    // error_log('DEBUG TTS - Texto final a convertir (' . mb_strlen($text_to_convert, 'UTF-8') . ' chars): ' . $text_to_convert);

    // Si no hay texto, no hacemos nada.
    if (empty(trim($text_to_convert))) {
        // error_log('DEBUG TTS - ERROR: Texto vacío, saliendo...');
        return;
    }

    // --- Generación del audio ---

    try {
        // --- Enfoque final y más robusto: Petición cURL manual por fragmentos ---

        // Check if the Composer class exists before using it.
        if (!class_exists('Stichoza\GoogleTranslate\GoogleTranslate')) {
            error_log('XAMLE TTS Error: La clase GoogleTranslate no se encuentra. ¿Se instalaron las dependencias de Composer y se subieron al servidor?');
            return;
        }

        // 1. Dividir el texto en fragmentos más pequeños (máx 100 caracteres para máxima compatibilidad).
        $text_chunks = xamle_split_text_for_tts($text_to_convert, 150);
        // error_log('DEBUG TTS - Número de chunks generados: ' . count($text_chunks));
        // error_log('DEBUG TTS - Chunks: ' . print_r($text_chunks, true));
        $audio_content = '';

        // --- Detección de idioma ---
        $terms = get_the_terms($post_id, 'idioma');
        $lang_code = 'es-ES'; // Español por defecto

        if (!empty($terms) && !is_wp_error($terms)) {
            $term_name = $terms[0]->name;
            // Mapeo de nombres de término a códigos de idioma para la API de Google
            $lang_map = [
                'Español'            => 'es-ES',
                'Portugués (Brasil)' => 'pt-BR',
                'Portugués (Portugal)' => 'pt-PT',
                'Gallego' => 'gl-ES',
                'Euskera' => 'eu-ES',
                'Catalán' => 'ca-ES',
                'Inglés' => 'en-US',
                'Francés' => 'fr-FR',
                'Italiano' => 'it-IT',
                'Alemán' => 'de-DE',
            ];
            if (isset($lang_map[$term_name])) {
                $lang_code = $lang_map[$term_name];
            }
        }

        // error_log('DEBUG TTS - Idioma detectado: ' . $lang_code);

        foreach ($text_chunks as $index => $chunk) {
            // error_log('DEBUG TTS - Procesando chunk ' . ($index + 1) . ' (' . mb_strlen($chunk, 'UTF-8') . ' chars): ' . $chunk);
            
            // Limpiar el chunk de caracteres problemáticos
            $chunk_clean = str_replace([':', '¡', '¿'], [',', '', ''], $chunk);
            $chunk_clean = preg_replace('/[^\p{L}\p{N}\s.,;!?()-]/u', '', $chunk_clean);
            $chunk_clean = trim($chunk_clean);
            
            if (empty($chunk_clean)) {
                // error_log('DEBUG TTS - Chunk ' . ($index + 1) . ' vacío después de limpieza, saltando...');
                continue;
            }
            
            // 2. Construimos la URL para la API de TTS de Google Translate para cada fragmento.
            $google_tts_url = 'https://translate.google.com/translate_tts';
            $query_params = [
                'ie'        => 'UTF-8',
                'q'         => $chunk_clean,
                'tl'        => $lang_code, // Usamos el código de idioma detectado
                'client'    => 'tw-ob', // Cliente que espera la API
            ];
            $request_url = $google_tts_url . '?' . http_build_query($query_params);
            
            // error_log('DEBUG TTS - URL generada (' . strlen($request_url) . ' chars): ' . $request_url);

            // 3. Usamos wp_remote_get, que es la forma recomendada por WordPress para hacer peticiones.
            $response = wp_remote_get($request_url, [
                'timeout'     => 20,
                'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36',
                'sslverify'   => false,
            ]);

            $http_code = wp_remote_retrieve_response_code($response);
            $chunk_audio = wp_remote_retrieve_body($response);

            if (is_wp_error($response) || $http_code !== 200 || empty($chunk_audio)) {
                $error_message = is_wp_error($response) ? $response->get_error_message() : 'Respuesta vacía o código HTTP no es 200.';
                error_log('XAMLE TTS Error (wp_remote_get): Fallo al obtener audio para el post ' . $post_id . '. Código HTTP: ' . $http_code . '. Mensaje: ' . $error_message);
                continue; // Saltar al siguiente fragmento
            }

            // error_log('DEBUG TTS - Chunk ' . ($index + 1) . ' procesado exitosamente. Tamaño audio: ' . strlen($chunk_audio) . ' bytes');

            // 4. Concatenamos el audio del fragmento al contenido total.
            $audio_content .= $chunk_audio;

            // Pequeña pausa para no saturar la API.
            usleep(250000); // 0.25 segundos
        }

        // error_log('DEBUG TTS - Audio final generado. Tamaño total: ' . strlen($audio_content) . ' bytes');

        // Si al final no tenemos contenido de audio, salimos.
        if (empty($audio_content)) {
            error_log('XAMLE TTS Error: No se pudo generar contenido de audio para el post ' . $post_id . '. Todos los fragmentos fallaron o el texto estaba vacío.');
            return;
        }

        // --- Guardado en la Biblioteca de Medios ---

        // 7. Cambiamos el directorio de subida a /uploads/mp3/
        add_filter('upload_dir', 'xamle_custom_audio_upload_dir');

        // 8. Preparamos el nombre del archivo.
        $file_name = sanitize_title($title) . '-' . $post_id . '.mp3';

        // 9. Subimos el archivo a la carpeta personalizada.
        // wp_upload_bits ahora usará el directorio que hemos definido en el filtro.
        $upload = wp_upload_bits($file_name, null, $audio_content);

        // 10. Eliminamos el filtro para no afectar a otras subidas de archivos en WordPress.
        remove_filter('upload_dir', 'xamle_custom_audio_upload_dir');


        if ($upload['error']) {
            error_log('XAMLE TTS Error (wp_upload_bits): ' . $upload['error'] . ' para el post ' . $post_id . '. Verifica los permisos de la carpeta /wp-content/uploads/mp3/.');
            return;
        }

        // 11. Preparamos los datos para insertar el archivo en la Biblioteca de Medios.
        $attachment = [
            'guid'           => $upload['url'],
            'post_mime_type' => 'audio/mpeg',
            'post_title'     => 'Audio del recurso: ' . $title,
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        // 12. Insertamos el archivo como un adjunto y obtenemos su ID.
        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);

        if (is_wp_error($attachment_id)) {
            error_log('XAMLE TTS Error (wp_insert_attachment): ' . $attachment_id->get_error_message() . ' para el post ' . $post_id);
            return;
        }

        // 13. Generamos los metadatos del adjunto (importante para que WordPress lo reconozca bien).
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        wp_generate_attachment_metadata($attachment_id, $upload['file']);

        // 14. --- Asociación con el post ---

        // 12. Guardamos el ID del nuevo archivo de audio en un campo personalizado del recurso.
        // Primero, borramos el audio anterior si existía para no dejar huérfanos.
        $old_audio_id = get_post_meta($post_id, '_recurso_audio_id', true);
        if (!empty($old_audio_id)) {
            wp_delete_attachment($old_audio_id, true);
        }
        
        // Guardamos el ID del nuevo audio.
        update_post_meta($post_id, '_recurso_audio_id', $attachment_id);

    } catch (Exception $e) {
        // Capturamos cualquier error de la librería y lo registramos.
        error_log('XAMLE TTS Exception: ' . $e->getMessage() . ' para el post ' . $post_id);
        // Añadimos un mensaje de error para que el editor lo vea (opcional, pero útil).
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Error al generar el audio:</strong> <?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
}

// Enganchamos nuestra función a la acción 'save_post_recursos'.
add_action('save_post_recursos', 'xamle_generate_audio_on_save', 10, 2);



/**
 * Divide un texto largo en fragmentos más pequeños, respetando los finales de oración.
 *
 * @param string $text El texto a dividir.
 * @param int $max_length La longitud máxima de cada fragmento.
 * @return array Un array de fragmentos de texto.
 */
function xamle_split_text_for_tts($text, $max_length = 100) {
    // Limpia y normaliza el texto.
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text, 'UTF-8') <= $max_length) {
        return [$text];
    }

    $chunks = [];
    $current_chunk = '';

    // Divide el texto por palabras para tener un control más granular.
    $words = explode(' ', $text);

    foreach ($words as $word) {
        if (empty($word)) continue;

        // Comprueba si añadir la siguiente palabra superaría el límite.
        if (mb_strlen($current_chunk, 'UTF-8') + mb_strlen($word, 'UTF-8') + 1 > $max_length) {
            // Si el chunk actual no está vacío, lo guardamos.
            if (!empty($current_chunk)) {
                $chunks[] = $current_chunk;
            }
            // La palabra actual inicia un nuevo chunk.
            $current_chunk = $word;
        } else {
            // Si no supera el límite, añade la palabra al chunk actual.
            $current_chunk .= (empty($current_chunk) ? '' : ' ') . $word;
        }
    }

    // No olvides guardar el último chunk que se estaba construyendo.
    if (!empty($current_chunk)) {
        $chunks[] = $current_chunk;
    }

    return $chunks;
}


/**
 * Filtro para cambiar el directorio de subida de los audios.
 *
 * @param array $dirs Rutas de subida de WordPress.
 * @return array Rutas modificadas.
 */
function xamle_custom_audio_upload_dir($dirs) {
    // Define el subdirectorio personalizado
    $custom_dir = 'mp3';

    // Cambia las rutas para que apunten a /uploads/mp3/
    $dirs['subdir'] = '/' . $custom_dir;
    $dirs['path'] = $dirs['basedir'] . '/' . $custom_dir;
    $dirs['url'] = $dirs['baseurl'] . '/' . $custom_dir;

    return $dirs;
}