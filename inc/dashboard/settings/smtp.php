<?php
/**
 * SMTP Settings for WordPress
 * 
 * Configura el envío de correos vía SMTP en lugar de mail() nativo
 */

// =============================================================================
// REGISTER SETTINGS
// =============================================================================
add_action('admin_init', function() {
    // Register SMTP settings
    register_setting('custom-settings', 'smtp_enabled', [
        'type' => 'boolean',
        'default' => false,
    ]);
    register_setting('custom-settings', 'smtp_host', [
        'default' => '',
    ]);
    register_setting('custom-settings', 'smtp_port', [
        'default' => 587,
    ]);
    register_setting('custom-settings', 'smtp_username', [
        'default' => '',
    ]);
    register_setting('custom-settings', 'smtp_password', [
        'default' => '',
    ]);
    register_setting('custom-settings', 'smtp_secure', [
        'default' => 'tls',
    ]);
    register_setting('custom-settings', 'smtp_from_email', [
        'default' => '',
    ]);
    register_setting('custom-settings', 'smtp_from_name', [
        'default' => '',
    ]);
    register_setting('custom-settings', 'smtp_debug', [
        'type' => 'boolean',
        'default' => false,
    ]);

    // Add settings section
    add_settings_section(
        'pk_smtp_section',
        '',
        '__return_false',
        'custom-settings-smtp'
    );

    // Enable/Disable
    add_settings_field(
        'smtp_enabled',
        __('Enable SMTP', 'penkode-headless'),
        function() {
            $enabled = get_option('smtp_enabled', false);
            ?>
            <label>
                <input type="checkbox" name="smtp_enabled" value="1" <?php checked(true, $enabled); ?>>
                <?php esc_html_e('Enable SMTP sending', 'penkode-headless'); ?>
            </label>
            <?php
        },
        'custom-settings-smtp',
        'pk_smtp_section'
    );

    // Host
    add_settings_field(
        'smtp_host',
        __('SMTP Server', 'penkode-headless'),
        function() {
            $value = get_option('smtp_host', '');
            ?>
            <input type="text" name="smtp_host" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="mail.example.com">
            <p class="description"><?php esc_html_e('SMTP server hostname (e.g., mail.penkode.com, smtp.gmail.com)', 'penkode-headless'); ?></p>
            <?php
        },
        'custom-settings-smtp',
        'pk_smtp_section'
    );

    // Port
    add_settings_field(
        'smtp_port',
        __('SMTP Port', 'penkode-headless'),
        function() {
            $value = get_option('smtp_port', 587);
            ?>
            <input type="number" name="smtp_port" value="<?php echo esc_attr($value); ?>" class="small-text" min="1" max="65535">
            <p class="description"><?php esc_html_e('Common ports: 587 (TLS/STARTTLS), 465 (SSL), 25 (default)', 'penkode-headless'); ?></p>
            <p class="description" style="color: #d63638;"><strong><?php esc_html_e('Note: Use SSL for port 465, TLS for port 587', 'penkode-headless'); ?></strong></p>
            <?php
        },
        'custom-settings-smtp',
        'pk_smtp_section'
    );

    // Username
    add_settings_field(
        'smtp_username',
        __('SMTP Username', 'penkode-headless'),
        function() {
            $value = get_option('smtp_username', '');
            ?>
            <input type="text" name="smtp_username" value="<?php echo esc_attr($value); ?>" class="regular-text" autocomplete="username">
            <p class="description"><?php esc_html_e('Usually your full email address', 'penkode-headless'); ?></p>
            <?php
        },
        'custom-settings-smtp',
        'pk_smtp_section'
    );

    // Password
    add_settings_field(
        'smtp_password',
        __('SMTP Password', 'penkode-headless'),
        function() {
            $value = get_option('smtp_password', '');
            ?>
            <input type="password" name="smtp_password" value="<?php echo esc_attr($value); ?>" class="regular-text" autocomplete="current-password">
            <p class="description"><?php esc_html_e('Your SMTP password or app-specific password', 'penkode-headless'); ?></p>
            <?php
        },
        'custom-settings-smtp',
        'pk_smtp_section'
    );

    // Security
    add_settings_field(
        'smtp_secure',
        __('Encryption', 'penkode-headless'),
        function() {
            $value = get_option('smtp_secure', 'tls');
            ?>
            <select name="smtp_secure">
                <option value="tls" <?php selected('tls', $value); ?>><?php esc_html_e('TLS (recommended)', 'penkode-headless'); ?></option>
                <option value="ssl" <?php selected('ssl', $value); ?>><?php esc_html_e('SSL', 'penkode-headless'); ?></option>
                <option value="" <?php selected('', $value); ?>><?php esc_html_e('None', 'penkode-headless'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('TLS is recommended for most servers', 'penkode-headless'); ?></p>
            <?php
        },
        'custom-settings-smtp',
        'pk_smtp_section'
    );

    // From Email
    add_settings_field(
        'smtp_from_email',
        __('From Email', 'penkode-headless'),
        function() {
            $value = get_option('smtp_from_email', '');
            ?>
            <input type="email" name="smtp_from_email" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="noreply@example.com">
            <p class="description"><?php esc_html_e('Email address used as sender', 'penkode-headless'); ?></p>
            <?php
        },
        'custom-settings-smtp',
        'pk_smtp_section'
    );

    // From Name
    add_settings_field(
        'smtp_from_name',
        __('From Name', 'penkode-headless'),
        function() {
            $value = get_option('smtp_from_name', '');
            ?>
            <input type="text" name="smtp_from_name" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
            <p class="description"><?php esc_html_e('Name displayed as sender', 'penkode-headless'); ?></p>
            <?php
        },
        'custom-settings-smtp',
        'pk_smtp_section'
    );

    // Debug
    add_settings_field(
        'smtp_debug',
        __('Debug Mode', 'penkode-headless'),
        function() {
            $debug = get_option('smtp_debug', false);
            ?>
            <label>
                <input type="checkbox" name="smtp_debug" value="1" <?php checked(true, $debug); ?>>
                <?php esc_html_e('Enable debug logging', 'penkode-headless'); ?>
            </label>
            <p class="description"><?php esc_html_e('Check this to see SMTP communication in the email sent', 'penkode-headless'); ?></p>
            <?php
        },
        'custom-settings-smtp',
        'pk_smtp_section'
    );
});


// =============================================================================
// APPLY SMTP SETTINGS
// =============================================================================
add_action('phpmailer_init', function($phpmailer) {
    $smtp_enabled = get_option('smtp_enabled', false);
    if (empty($smtp_enabled)) {
        return;
    }

    $host = get_option('smtp_host', '');
    if (empty($host)) {
        return;
    }

    $port = get_option('smtp_port', 587);
    $secure = get_option('smtp_secure', 'tls');
    $username = get_option('smtp_username', '');

    // Port 465 = implicit TLS → must use 'ssl'
    // Port 587 = STARTTLS   → must use 'tls'
    if ($port == 465) {
        $secure = 'ssl';
    } elseif ($port == 587) {
        $secure = 'tls';
    }

    $phpmailer->isSMTP();
    $phpmailer->Host = $host;
    $phpmailer->Port = $port;
    $phpmailer->Username = $username;
    $phpmailer->Password = get_option('smtp_password', '');
    $phpmailer->SMTPSecure = $secure;
    $phpmailer->SMTPAuth = true;

    $phpmailer->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $from_email = get_option('smtp_from_email', '');
    if (!empty($from_email)) {
        $phpmailer->From = $from_email;
    }

    $from_name = get_option('smtp_from_name', '');
    if (!empty($from_name)) {
        $phpmailer->FromName = $from_name;
    }

    // Send debug output to error_log, never 'html' (which echoes to PHP output
    // and corrupts REST API / CF7 JSON responses).
    // The test-email handler (priority 99) overrides with its own callback.
    if (get_option('smtp_debug', false)) {
        $phpmailer->SMTPDebug = 2;
        $phpmailer->Debugoutput = 'error_log';
    }
});


// =============================================================================
// SMTP DIAGNOSTIC (no email send — just connectivity check)
// =============================================================================
add_action('wp_ajax_pk_smtp_diagnose', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $diag = [];
    $diag['smtp_enabled'] = get_option('smtp_enabled', false);

    $host = get_option('smtp_host', '');
    $port = (int) get_option('smtp_port', 587);
    $secure = get_option('smtp_secure', 'tls');

    $diag['host'] = $host;
    $diag['port'] = $port;
    $diag['secure'] = $secure;
    $diag['has_password'] = !empty(get_option('smtp_password', ''));
    $diag['from_email'] = get_option('smtp_from_email', '');
    $diag['from_name'] = get_option('smtp_from_name', '');
    $diag['php_version'] = PHP_VERSION;
    $diag['openssl_loaded'] = extension_loaded('openssl');

    if (!empty($host)) {
        $dns_start = microtime(true);
        $ip = @gethostbyname($host);
        $diag['dns_resolved'] = ($ip !== $host);
        $diag['dns_ip'] = $ip;
        $diag['dns_ms'] = round((microtime(true) - $dns_start) * 1000);
    }

    // Port 465 (implicit SSL): connect with ssl:// wrapper
    // Port 587 (STARTTLS): connect plain, read banner
    if (!empty($host)) {
        $use_ssl_wrapper = ($port == 465);
        $target = $use_ssl_wrapper ? "ssl://{$host}" : $host;

        $tcp_start = microtime(true);
        $sock = @fsockopen($target, $port, $errno, $errstr, 5);
        $diag['tcp_ms'] = round((microtime(true) - $tcp_start) * 1000);

        if ($sock) {
            $diag['tcp_reachable'] = true;
            stream_set_timeout($sock, 2);
            $diag['tcp_banner'] = trim(@fgets($sock, 512) ?: '');
            fclose($sock);
        } else {
            $diag['tcp_reachable'] = false;
            $diag['tcp_errno'] = $errno;
            $diag['tcp_error'] = $errstr;
        }
    }

    $diag['timestamp'] = time();
    wp_send_json_success(['message' => 'Diagnostic complete', 'diagnostic' => $diag]);
});

// =============================================================================
// TEST EMAIL FUNCTION
// =============================================================================
add_action('wp_ajax_pk_send_test_email', function() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pk_smtp_test')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $smtp_enabled = get_option('smtp_enabled', false);
    if (empty($smtp_enabled)) {
        wp_send_json_error(['message' => 'SMTP is not enabled. Please check "Enable SMTP" and save settings.']);
    }

    $host = get_option('smtp_host', '');
    if (empty($host)) {
        wp_send_json_error(['message' => 'SMTP Host is not configured.']);
    }

    $to = sanitize_email($_POST['email'] ?? '');
    if (empty($to)) {
        wp_send_json_error(['message' => 'Email address required']);
    }

    $port = (int) get_option('smtp_port', 587);
    $secure = get_option('smtp_secure', 'tls');

    if ($port == 465) {
        $secure = 'ssl';
    } elseif ($port == 587) {
        $secure = 'tls';
    }

    // Pre-flight SMTP connectivity check (5s timeout)
    $sock_target = ($secure === 'ssl') ? "ssl://{$host}" : $host;
    $sock_err = 0;
    $sock_errstr = '';
    $sock = @fsockopen($sock_target, $port, $sock_err, $sock_errstr, 5);
    if ($sock) {
        fclose($sock);
    } else {
        wp_send_json_error([
            'message' => "SMTP server unreachable: {$host}:{$port} ({$sock_errstr}). The server may block outbound SMTP or the host/port is wrong.",
        ]);
    }

    @set_time_limit(30);

    $subject = __('Test Email from ', 'penkode-headless') . get_bloginfo('name');
    $message = __('This is a test email to verify your SMTP configuration is working correctly.', 'penkode-headless');

    // Capture SMTP transcript for the test
    $smtp_log = [];
    add_action('phpmailer_init', function($phpmailer) use (&$smtp_log) {
        $phpmailer->SMTPDebug = 3;
        $phpmailer->Debugoutput = function($str, $level) use (&$smtp_log) {
            $smtp_log[] = trim($str);
        };
        $phpmailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
    }, 99);

    $result = wp_mail($to, $subject, $message);

    if ($result) {
        wp_send_json_success(['message' => 'Test email sent successfully!']);
    } else {
        $error_msg = 'Failed to send test email.';
        global $phpmailer;
        if (isset($phpmailer->ErrorInfo) && !empty($phpmailer->ErrorInfo)) {
            $error_msg .= ' Error: ' . $phpmailer->ErrorInfo;
        }
        wp_send_json_error([
            'message' => $error_msg,
            'smtp_log' => array_slice($smtp_log, 0, 15),
        ]);
    }
});
