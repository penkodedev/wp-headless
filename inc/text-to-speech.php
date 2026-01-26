<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// ===================================================================================================
//
//                      🔊 TEXT-TO-SPEECH FUNCTIONALITY FOR CONFIGURED CPTs
//
// ===================================================================================================

use Stichoza\GoogleTranslate\GoogleTranslate;

/**
 * Triggered when saving a post of configured types.
 * Generates an audio file from the post content.
 *
 * @param int     $post_id The ID of the post being saved.
 * @param WP_Post $post    The post object.
 */
function xamle_generate_audio_on_save($post_id, $post) {
    // --- Initial checks ---

    // 1. Only act on configured CPTs for audio.
    $allowed_cpts = ['recursos', 'noticias']; // Add more CPTs here if needed
    if (!in_array($post->post_type, $allowed_cpts)) {
        return;
    }

    // 2. Don't act on autosaves.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // 3. Don't act on revisions.
    if (wp_is_post_revision($post_id)) {
        return;
    }

    // 4. Verify the post is published.
    if ($post->post_status !== 'publish') {
        return;
    }

    // --- Content preparation ---

    // 5. Combine title and content for audio.
    $title = $post->post_title;
    $content = $post->post_content;
    $text_content = '';

    if (!empty($content)) {
        // METHOD 1: Use strip_tags directly (more reliable)
        $text_content = strip_tags($content);
        $text_content = preg_replace('/\s+/', ' ', trim($text_content));

        // METHOD 2: Fallback with DOMDocument if strip_tags fails
        if (empty($text_content)) {
            $dom = new DOMDocument();
            $dom->encoding = 'utf-8';
            @$dom->loadHTML('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $content . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $body = $dom->getElementsByTagName('body')->item(0);
            if ($body) {
                $text_content = $body->textContent;
                $text_content = preg_replace('/\s+/', ' ', trim($text_content));
            }
        }

        // METHOD 3: Final fallback with regex if everything fails
        if (empty($text_content)) {
            $text_content = preg_replace('/<[^>]*>/', ' ', $content);
            $text_content = html_entity_decode($text_content, ENT_QUOTES, 'UTF-8');
            $text_content = preg_replace('/\s+/', ' ', trim($text_content));
        }
    }
    $text_to_convert = $title . ". \n\n" . $text_content;

    // If no text, do nothing.
    if (empty(trim($text_to_convert))) {
        return;
    }

    // --- Audio generation ---

    try {
        // --- Enfoque final y más robusto: Petición cURL manual por fragmentos ---

        // Check if the Composer class exists before using it.
        if (!class_exists('Stichoza\GoogleTranslate\GoogleTranslate')) {
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
            error_log('XAMLE TTS: Llamando a Google API para chunk ' . ($index + 1) . ' - URL: ' . substr($request_url, 0, 100) . '...');
            $response = wp_remote_get($request_url, [
                'timeout'     => 20,
                'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36',
                'sslverify'   => false,
            ]);

            $http_code = wp_remote_retrieve_response_code($response);
            $chunk_audio = wp_remote_retrieve_body($response);

            if (is_wp_error($response) || $http_code !== 200 || empty($chunk_audio)) {
                $error_message = is_wp_error($response) ? $response->get_error_message() : 'Respuesta vacía o código HTTP no es 200.';
                error_log('XAMLE TTS Error (wp_remote_get): Fallo al obtener audio para el post ' . $post_id . '. Código HTTP: ' . $http_code . '. Mensaje: ' . $error_message . '. URL: ' . substr($request_url, 0, 150));
                continue; // Saltar al siguiente fragmento
            }

            error_log('XAMLE TTS: Chunk ' . ($index + 1) . ' exitoso - Tamaño: ' . strlen($chunk_audio) . ' bytes');

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
        // Catch any library errors and log them.
        error_log('XAMLE TTS Exception: ' . $e->getMessage() . ' for post ' . $post_id);
        // Add error message for editor to see (optional but useful).
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Error generating audio:</strong> <?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
}
// Hook our function to save post actions for allowed CPTs
$allowed_cpts = ['recursos', 'noticias'];
foreach ($allowed_cpts as $cpt) {
    add_action('save_post_' . $cpt, 'xamle_generate_audio_on_save', 10, 2);
}



/**
 * Split long text into smaller chunks, respecting sentence endings.
 *
 * @param string $text The text to split.
 * @param int $max_length The maximum length of each chunk.
 * @return array An array of text chunks.
 */
function xamle_split_text_for_tts($text, $max_length = 100) {
    // Clean and normalize the text.
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text, 'UTF-8') <= $max_length) {
        return [$text];
    }

    $chunks = [];
    $current_chunk = '';

    // Split text by words for more granular control.
    $words = explode(' ', $text);

    foreach ($words as $word) {
        if (empty($word)) continue;

        // Check if adding the next word would exceed the limit.
        if (mb_strlen($current_chunk, 'UTF-8') + mb_strlen($word, 'UTF-8') + 1 > $max_length) {
            // If current chunk is not empty, save it.
            if (!empty($current_chunk)) {
                $chunks[] = $current_chunk;
            }
            // Current word starts a new chunk.
            $current_chunk = $word;
        } else {
            // If it doesn't exceed, add word to current chunk.
            $current_chunk .= (empty($current_chunk) ? '' : ' ') . $word;
        }
    }

    // Don't forget to save the last chunk being built.
    if (!empty($current_chunk)) {
        $chunks[] = $current_chunk;
    }

    return $chunks;
}



/**
 * Filter to change the upload directory for audio files.
 *
 * @param array $dirs WordPress upload paths.
 * @return array Modified paths.
 */
function xamle_custom_audio_upload_dir($dirs) {
    // Define custom subdirectory
    $custom_dir = 'mp3';

    // Change paths to point to /uploads/mp3/
    $dirs['subdir'] = '/' . $custom_dir;
    $dirs['path'] = $dirs['basedir'] . '/' . $custom_dir;
    $dirs['url'] = $dirs['baseurl'] . '/' . $custom_dir;

    return $dirs;
}