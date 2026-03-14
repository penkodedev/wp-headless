<?php
/**
 * =============================================================================
 *   SECURITY LAYER FOR HEADLESS WORDPRESS
 * =============================================================================
 *
 * Covers: frontend lockdown, wp-login/wp-admin protection, rate limiting,
 * REST API hardening, security headers, and general WP hardening.
 *
 * NOTE: Additional login security lives in /js and the login.php template.
 */


// =============================================================================
//  1. HEADLESS FRONTEND LOCKDOWN
// =============================================================================
// Redirects every front-end WP request to /login so the public never sees
// raw WordPress output. Whitelists: admin, AJAX, cron, REST API, /login page.

function pk_headless_redirect() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';

    if (strpos($uri, '/wp-json/') !== false || isset($_GET['rest_route'])) {
        return;
    }

    // Only exact /login page — not sub-paths like /login/foo
    $path = rtrim(strtok($uri, '?'), '/');
    if (substr($path, -6) === '/login') {
        return;
    }

    // wp-login.php is handled by pk_protect_wp_login() (logout, rate limiting)
    if (strpos($uri, 'wp-login.php') !== false) {
        return;
    }

    if (strpos($uri, 'wp-cron.php') !== false) {
        return;
    }

    wp_redirect(home_url('/login'));
    exit;
}
add_action('init', 'pk_headless_redirect');


// =============================================================================
//  2. WP-LOGIN / WP-ADMIN PROTECTION
// =============================================================================

function pk_login_url($login_url, $redirect) {
    return home_url('/login');
}
add_filter('login_url', 'pk_login_url', 10, 2);


// --- IP detection --------------------------------------------------------

function pk_get_client_ip(): string {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}


// --- Rate limiting -------------------------------------------------------

function pk_is_ip_locked(string $ip): bool {
    $key = sanitize_key('pk_lockout_' . md5($ip));
    $until = get_transient($key);

    if ($until && time() < $until) {
        return true;
    }

    if ($until) {
        delete_transient($key);
    }

    return false;
}

function pk_record_admin_attempt(string $ip): void {
    $key = sanitize_key('pk_attempts_' . md5($ip));
    $attempts = (int) get_transient($key) + 1;

    if ($attempts >= 5) {
        $lockout_key = sanitize_key('pk_lockout_' . md5($ip));
        set_transient($lockout_key, time() + 3600, 3600);
        delete_transient($key);
    } else {
        set_transient($key, $attempts, 3600);
    }
}


// --- Protect wp-login.php & wp-admin -------------------------------------

function pk_protect_wp_login() {
    $uri = sanitize_text_field($_SERVER['REQUEST_URI'] ?? '');

    // Early return — only act on wp-login.php and wp-admin
    $is_login = strpos($uri, '/wp-login.php') !== false;
    $is_admin = strpos($uri, '/wp-admin') !== false;
    if (!$is_login && !$is_admin) {
        return;
    }

    $ip = pk_get_client_ip();
    $login_url = home_url('/login');

    if (pk_is_ip_locked($ip)) {
        wp_die(
            __('Access temporarily blocked due to security policy.', 'penkode-headless'),
            __('Access Blocked', 'penkode-headless'),
            ['response' => 429]
        );
    }

    if ($is_login) {
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            return;
        }
        pk_record_admin_attempt($ip);
        pk_log_security_event('WP_LOGIN_ACCESS', "URI: $uri", $ip);
        wp_redirect($login_url);
        exit;
    }

    if ($is_admin && !is_user_logged_in()) {
        pk_record_admin_attempt($ip);
        pk_log_security_event('WP_ADMIN_ACCESS', "URI: $uri", $ip);
        wp_redirect($login_url);
        exit;
    }
}
add_action('init', 'pk_protect_wp_login', 1);


// --- Post-logout redirect ------------------------------------------------

add_action('wp_logout', function () {
    wp_redirect(home_url('/login'));
    exit;
});


// =============================================================================
//  3. DASHBOARD ROLE RESTRICTIONS
// =============================================================================

// Kick subscribers/contributors out of the dashboard
add_action('init', function () {
    if (is_admin() && !defined('DOING_AJAX') && (current_user_can('subscriber') || current_user_can('contributor'))) {
        wp_redirect(home_url('/login'));
        exit;
    }
});

// Prevent non-admins from modifying plugins/themes
add_filter('map_meta_cap', function ($caps, $cap, $user_id) {
    $restricted = [
        'activate_plugins', 'deactivate_plugins', 'edit_plugins', 'update_plugins',
        'edit_themes', 'update_themes', 'install_plugins', 'delete_plugins',
    ];
    if (in_array($cap, $restricted, true)) {
        $user = get_userdata($user_id);
        if (!$user || !in_array('administrator', $user->roles, true)) {
            $caps[] = 'do_not_allow';
        }
    }
    return $caps;
}, 10, 3);


// =============================================================================
//  4. REST API HARDENING
// =============================================================================

// Block user enumeration via /wp/v2/users (exposes usernames to anonymous)
add_filter('rest_endpoints', function ($endpoints) {
    if (!is_user_logged_in()) {
        unset($endpoints['/wp/v2/users']);
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
});

// Remove author query-string enumeration (?author=1 → redirects to /author/slug/)
add_action('init', function () {
    if (!is_admin() && isset($_GET['author'])) {
        wp_redirect(home_url('/login'), 301);
        exit;
    }
});


// =============================================================================
//  5. SECURITY HEADERS
// =============================================================================

add_action('send_headers', function () {
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    // REST API responses get their CORS headers from api-endpoints.php
    if (strpos($uri, '/wp-json/') !== false || isset($_GET['rest_route'])) {
        return;
    }

    if (!is_admin()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
});


// =============================================================================
//  6. GENERAL WP HARDENING
// =============================================================================

// Disable XML-RPC
add_filter('xmlrpc_enabled', '__return_false');

// Disable pingbacks
add_filter('pings_open', '__return_false');

// Disable unfiltered HTML for all users
if (!defined('DISALLOW_UNFILTERED_HTML')) {
    define('DISALLOW_UNFILTERED_HTML', true);
}

// Limit post revisions
if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', 3);
}

// Ensure uploads directory has an index.php (prevent directory listing)
add_action('admin_init', function () {
    $uploads = wp_upload_dir();
    $index = trailingslashit($uploads['basedir']) . 'index.php';
    if (!file_exists($index)) {
        @file_put_contents($index, "<?php // Silence is golden.");
    }
});

// Close comments/pingbacks on existing posts (runs once per admin load, only updates open ones)
add_action('admin_init', function () {
    global $wpdb;
    $wpdb->query(
        "UPDATE $wpdb->posts SET comment_status = 'closed', ping_status = 'closed'
         WHERE comment_status != 'closed' OR ping_status != 'closed'"
    );
});

// Ensure new posts get closed comments/pingbacks
add_filter('wp_insert_post_data', function ($data) {
    $data['comment_status'] = 'closed';
    $data['ping_status'] = 'closed';
    return $data;
});


// =============================================================================
//  7. SECURITY EVENT LOGGING (only when WP_DEBUG is true)
// =============================================================================

function pk_log_security_event(string $event, string $details = '', string $ip = null): void {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $ip = $ip ?? pk_get_client_ip();
    $entry = sprintf(
        "[%s] %s | IP: %s | %s | User: %s\n",
        current_time('Y-m-d H:i:s'),
        $event,
        $ip,
        $details,
        is_user_logged_in() ? wp_get_current_user()->user_login : 'anonymous'
    );

    @file_put_contents(WP_CONTENT_DIR . '/security-events.log', $entry, FILE_APPEND);
}

add_action('wp_login_failed', function ($username) {
    pk_log_security_event('FAILED_LOGIN', "Username: $username");
});

add_action('wp_login', function ($user_login) {
    pk_log_security_event('SUCCESSFUL_LOGIN', "Username: $user_login");
});
