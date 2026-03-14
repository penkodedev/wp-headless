<?php
/**
 * ChatBot Settings for WordPress
 * 
 * Uses register_setting() for options (not post meta)
 */

// GROQ_API_KEY is defined in inc/security/secrets.php

// Register ChatBot settings under Custom Settings
add_action('admin_init', function() {
    // Register settings - simple version without language hooks to avoid infinite loops
    register_setting('custom-settings', 'chatbot_enabled');
    register_setting('custom-settings', 'chatbot_name');
    register_setting('custom-settings', 'chatbot_welcome');
    register_setting('custom-settings', 'chatbot_avatar');
    register_setting('custom-settings', 'chatbot_system_prompt');
    register_setting('custom-settings', 'chatbot_placeholder');
    register_setting('custom-settings', 'chatbot_position');
    register_setting('custom-settings', 'chatbot_primary_color');
    register_setting('custom-settings', 'chatbot_secondary_color');
    register_setting('custom-settings', 'chatbot_color');
    register_setting('custom-settings', 'chatbot_margin');
    register_setting('custom-settings', 'chatbot_show_avatar');
    
    // Add settings section
    add_settings_section(
        'pk_chatbot_section',
        '',
        '__return_false',
        'custom-settings-chatbot'
    );
    
    // Enable
    add_settings_field(
        'chatbot_enabled',
        __('Enable ChatBot', 'penkode-headless'),
        function() {
            $enabled = get_language_option('chatbot_enabled', false);
            ?>
            <label>
                <input type="checkbox" name="chatbot_enabled" value="1" <?php checked(1, $enabled); ?>>
                <?php esc_html_e('Enable the ChatBot on your site', 'penkode-headless'); ?>
            </label>
            <?php
        },
        'custom-settings-chatbot',
        'pk_chatbot_section'
    );
    
    // Name
    add_settings_field(
        'chatbot_name',
        __('Bot Name', 'penkode-headless'),
        function() {
            $name = get_language_option('chatbot_name', 'Tiko');
            ?>
            <input type="text" name="chatbot_name" value="<?php echo esc_attr($name); ?>" class="regular-text">
            <?php
        },
        'custom-settings-chatbot',
        'pk_chatbot_section'
    );
    
    // Welcome Message
    add_settings_field(
        'chatbot_welcome',
        __('Welcome Message', 'penkode-headless'),
        function() {
            $welcome = get_language_option('chatbot_welcome', __('¡Hola! Soy Tiko, tu asistente. ¿En qué puedo ayudarte?', 'penkode-headless'));
            ?>
            <textarea name="chatbot_welcome" rows="3" class="large-text"><?php echo esc_textarea($welcome); ?></textarea>
            <?php
        },
        'custom-settings-chatbot',
        'pk_chatbot_section'
    );
    
    // System Prompt
    add_settings_field(
        'chatbot_system_prompt',
        __('System Prompt', 'penkode-headless'),
        function() {
            $prompt = get_language_option('chatbot_system_prompt', __("Eres Tiko, un asistente amable y profesional. Ayudas a los usuarios con información sobre este sitio web. Responde de forma clara y concisa.", 'penkode-headless'));
            ?>
            <textarea name="chatbot_system_prompt" rows="6" class="large-text"><?php echo esc_textarea($prompt); ?></textarea>
            <p class="description"><?php esc_html_e('Define la personalidad y comportamiento del bot.', 'penkode-headless'); ?></p>
            <?php
        },
        'custom-settings-chatbot',
        'pk_chatbot_section'
    );
    
    // Placeholder
    add_settings_field(
        'chatbot_placeholder',
        __('Input Placeholder', 'penkode-headless'),
        function() {
            $placeholder = get_language_option('chatbot_placeholder', __('Escribe tu mensaje...', 'penkode-headless'));
            ?>
            <input type="text" name="chatbot_placeholder" value="<?php echo esc_attr($placeholder); ?>" class="regular-text">
            <?php
        },
        'custom-settings-chatbot',
        'pk_chatbot_section'
    );
    
    // Position
    add_settings_field(
        'chatbot_position',
        __('Position', 'penkode-headless'),
        function() {
            $position = get_language_option('chatbot_position', 'bottom-right');
            ?>
            <select name="chatbot_position">
                <option value="bottom-right" <?php selected('bottom-right', $position); ?>><?php esc_html_e('Bottom Right', 'penkode-headless'); ?></option>
                <option value="bottom-left" <?php selected('bottom-left', $position); ?>><?php esc_html_e('Bottom Left', 'penkode-headless'); ?></option>
            </select>
            <?php
        },
        'custom-settings-chatbot',
        'pk_chatbot_section'
    );
    
    // Color
    add_settings_field(
        'chatbot_color',
        __('Primary Color', 'penkode-headless'),
        function() {
            $color = get_language_option('chatbot_color', '#000000');
            ?>
            <input type="color" name="chatbot_color" value="<?php echo esc_attr($color); ?>">
            <?php
        },
        'custom-settings-chatbot',
        'pk_chatbot_section'
    );
    
    // Avatar
    add_settings_field(
        'chatbot_avatar',
        __('Bot Avatar', 'penkode-headless'),
        function() {
            $avatar = get_language_option('chatbot_avatar', '');
            ?>
            <input type="text" name="chatbot_avatar" value="<?php echo esc_attr($avatar); ?>" class="regular-text">
            <p class="description"><?php esc_html_e('Enter avatar URL or upload via Media Library', 'penkode-headless'); ?></p>
            <?php
        },
        'custom-settings-chatbot',
        'pk_chatbot_section'
    );
});
