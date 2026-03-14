<?php

/**
 * Audio Post Settings - Checkbox to enable/disable audio generation per post
 */

// Add meta box for audio settings
add_action('add_meta_boxes', 'add_audio_settings_meta_box');
function add_audio_settings_meta_box() {
    $post_types = ['noticias', 'recursos'];
    foreach ($post_types as $pt) {
        add_meta_box(
            'audio_settings_meta_box',
            'Audio',
            'render_audio_settings_meta_box',
            $pt,
            'side',
            'default'
        );
    }
}

// Render the meta box
function render_audio_settings_meta_box($post) {
    $generate_audio = get_post_meta($post->ID, '_generate_audio', true);
    // Default to true for new posts or if not set
    $checked = ($generate_audio !== '0');
    wp_nonce_field('audio_settings_nonce', 'audio_settings_nonce');
    ?>
    <label for="generate_audio">
        <input type="checkbox" id="generate_audio" name="generate_audio" value="1" <?php checked($checked); ?> />
        Generar audio para este post
    </label>
    <p class="description">Si está marcado, se generará un archivo de audio y aparecerá el Player al guardar el post.</p>
    <?php
}

// Save the meta value
add_action('save_post', 'save_audio_settings_meta', 10, 2);
function save_audio_settings_meta($post_id, $post) {
    if (!in_array($post->post_type, ['noticias', 'recursos'])) return;

    if (!isset($_POST['audio_settings_nonce']) || !wp_verify_nonce($_POST['audio_settings_nonce'], 'audio_settings_nonce')) return;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $generate_audio = isset($_POST['generate_audio']) ? '1' : '0';
    update_post_meta($post_id, '_generate_audio', $generate_audio);

    // If unchecked, delete existing audio
    if ($generate_audio === '0') {
        $audio_id = get_post_meta($post_id, '_recurso_audio_id', true);
        if ($audio_id) {
            wp_delete_attachment($audio_id, true);
            delete_post_meta($post_id, '_recurso_audio_id');
        }
        // Also delete the physical file
        $upload_dir = wp_upload_dir();
        $file_name = sanitize_title($post->post_title) . '-' . $post_id . '.mp3';
        $file_path = $upload_dir['path'] . '/' . $file_name;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
}

