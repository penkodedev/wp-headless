<?php

/*************************************************************************************
REMEMBER you have more WORDPRESS LOGIN SECURITY measures in
JavaScript on /js folder and login.php Template
*************************************************************************************/


//************************* Protect WP-ADMIN and WP-LOGIN **************************************

// Change login URL
function custom_login_page($login_url, $redirect) {
    return home_url('/login'); // login page (change if necessary)
}
add_filter('login_url', 'custom_login_page', 10, 2);


// Enhanced IP detection for security
function get_client_ip_secure(): string {
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// Rate limiting functions for admin access protection
function get_admin_attempts_key(string $ip): string {
    return sanitize_key("admin_attempts_" . md5($ip));
}

function get_admin_lockout_key(string $ip): string {
    return sanitize_key("admin_lockout_" . md5($ip));
}

function is_admin_ip_locked(string $ip): bool {
    $lockout_key = get_admin_lockout_key($ip);
    $lockout_time = get_transient($lockout_key);

    if ($lockout_time && time() < $lockout_time) {
        return true;
    }

    if ($lockout_time) {
        delete_transient($lockout_key);
    }

    return false;
}

function record_admin_attempt(string $ip): void {
    $attempts_key = get_admin_attempts_key($ip);
    $current_attempts = (int) get_transient($attempts_key);
    $new_attempts = $current_attempts + 1;

    // Simple admin access rate limiting (5 attempts per hour)
    if ($new_attempts >= 5) {
        $lockout_key = get_admin_lockout_key($ip);
        set_transient($lockout_key, time() + 3600, 3600); // 1 hour lockout
        delete_transient($attempts_key);
    } else {
        set_transient($attempts_key, $new_attempts, 3600); // 1 hour window
    }
}

// Redirect unauthorized access to wp-login.php or wp-admin to the custom login page
function protect_wp_login() {
    // Only log in debug mode
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_file = WP_CONTENT_DIR . '/security-debug.log';
        $log_message = sprintf(
            "%s - URI: %s - Logged in: %s - Action: %s - IP: %s\n",
            current_time('Y-m-d H:i:s'),
            $_SERVER['REQUEST_URI'],
            (is_user_logged_in() ? 'YES' : 'NO'),
            ($_GET['action'] ?? 'none'),
            get_client_ip_secure()
        );
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }

    $custom_login_url = custom_login_page('', '');
    $requested_url = sanitize_text_field($_SERVER['REQUEST_URI']);
    $client_ip = get_client_ip_secure();

    // Check if IP is locked out for admin access
    if (is_admin_ip_locked($client_ip)) {
        wp_die(
            __('Access temporarily blocked due to security policy.', 'foo'),
            __('Access Blocked', 'foo'),
            ['response' => 429]
        );
    }

    // Allow logout action to process first, then redirect
    if (strpos($requested_url, '/wp-login.php') !== false) {
        // If it's a logout action, let WordPress process it first
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            return; // Don't redirect, let logout happen
        }

        // Record admin access attempt
        record_admin_attempt($client_ip);

        // For all other wp-login.php access, redirect
        wp_redirect($custom_login_url);
        exit;
    }

    // Redirect wp-admin for non-logged-in users
    if (!is_user_logged_in() && strpos($requested_url, '/wp-admin') !== false) {
        record_admin_attempt($client_ip);
        wp_redirect($custom_login_url);
        exit;
    }
}
add_action('init', 'protect_wp_login', 1); // Highest priority


// Redirect to custom login page after logout
function custom_logout_redirect() {
    wp_redirect(home_url('/login'));
    exit;
}
add_action('wp_logout', 'custom_logout_redirect');


//******************** Lock DASHBOARD to certain roles **************************
function dashboard_redirect()
{
  if (is_admin() && !defined('DOING_AJAX') && (current_user_can('subscriber') || current_user_can('contributor'))) {
    wp_redirect(home_url());
    exit;
  }
}
add_action('init', 'dashboard_redirect');
  

//******************** DISABLE Plugin And Theme Modifications on DASHBOARD from non admin users **************************

  function restrict_non_admin_plugin_theme_modifications($caps, $cap, $user_id, $args) {
    if (in_array($cap, array('activate_plugins', 'deactivate_plugins', 'edit_plugins', 'update_plugins', 'edit_themes', 'update_themes', 'install_plugins', 'delete_plugins'))) {
        $user = get_userdata($user_id);
        if (!$user || !in_array('administrator', $user->roles)) { // You can ADD USER ROLES
            $caps[] = 'do_not_allow';
        }
    }
    return $caps;
}

add_filter('map_meta_cap', 'restrict_non_admin_plugin_theme_modifications', 10, 4);


//******************** DISABLE Unfiltered HTML **************************
//define('DISALLOW_UNFILTERED_HTML', true);


//************************* REMOVE x-pingback-by header  **************************************
add_filter('pings_open', function() {
    return false;
});

//************************* REMOVE recent comments **************************************
function remove_recent_comments_style()
{
  global $wp_widget_factory;
  remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
}
add_action('widgets_init', 'remove_recent_comments_style');


//******************** DISABLE Directory Browsing (wp-content/uploads/) **************************
// If you're still seeing the directory listing in your browser after adding the code to functions.php,
//it's possible that the server configuration is not allowing the PHP code to take effect. In such cases,
//you should consider adding the following code to your site's root .htaccess file instead,
//as it's a more reliable way to disable directory browsing:

//# Disable directory browsing
//Options -Indexes

function disable_directory_browsing() {
    $uploads_dir = wp_upload_dir();
    $uploads_path = $uploads_dir['basedir'];

    if (is_dir($uploads_path)) {
        $index_file = trailingslashit($uploads_path) . 'index.php';

        if (!file_exists($index_file)) {
            $index_content = "<?php // Silence is golden.";
            file_put_contents($index_file, $index_content);
        }
    }
}

add_action('admin_init', 'disable_directory_browsing');
add_action('template_redirect', 'disable_directory_browsing');


//******************** REMOVES UNECESSARY INFORMATION FROM head **************************
add_action('init', function() {
    // Remove post and comment feed link
    remove_action('wp_head', 'feed_links', 2);

    // Remove post category links
    remove_action('wp_head', 'feed_links_extra', 3);

    // Remove link to the Really Simple Discovery service endpoint
    remove_action('wp_head', 'rsd_link');

    // Remove the link to the Windows Live Writer manifest file
    remove_action('wp_head', 'wlwmanifest_link');

    // Remove the XHTML generator that is generated on the wp_head hook, WP version
    remove_action('wp_head', 'wp_generator');

    // Remove start link
    remove_action('wp_head', 'start_post_rel_link');

    // Remove index link
    remove_action('wp_head', 'index_rel_link');

    // Remove previous link
    remove_action('wp_head', 'parent_post_rel_link', 10, 0);

    // Remove relational links for the posts adjacent to the current post
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

    // Remove relational links for the posts adjacent to the current post
    remove_action('wp_head', 'wp_oembed_add_discovery_links');

    // Remove REST API links
    remove_action('wp_head', 'rest_output_link_wp_head');

    // Remove Link header for REST API
    remove_action('template_redirect', 'rest_output_link_header', 11, 0);

    // Remove Link header for shortlink
    remove_action('template_redirect', 'wp_shortlink_header', 11, 0);

});

//******************** ENHANCED SECURITY HEADERS **************************
add_action('send_headers', function() {
    // Security headers
    if (!is_admin()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    // Content Security Policy for login page
    if (is_page_template('login.php')) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; frame-ancestors 'none';");
    }
});

//******************** SANITIZE AND VALIDATE USER INPUT **************************
add_filter('pre_user_login', function($username) {
    // Sanitize username input
    return sanitize_user($username, true);
});

//******************** LOG SECURITY EVENTS **************************
function log_security_event(string $event, string $details = '', string $ip = null): void {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $ip = $ip ?? get_client_ip_secure();
    $log_entry = sprintf(
        "[%s] SECURITY: %s | IP: %s | Details: %s | User: %s\n",
        current_time('Y-m-d H:i:s'),
        $event,
        $ip,
        $details,
        is_user_logged_in() ? wp_get_current_user()->user_login : 'anonymous'
    );

    $log_file = WP_CONTENT_DIR . '/security-events.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

//******************** MONITOR FAILED LOGIN ATTEMPTS **************************
// Note: Login rate limiting is now handled in login.php
add_action('wp_login_failed', function($username) {
    $ip = get_client_ip_secure();
    log_security_event('FAILED_LOGIN', "Username: $username", $ip);
});

//******************** MONITOR SUCCESSFUL LOGINS **************************
add_action('wp_login', function($user_login, $user) {
    $ip = get_client_ip_secure();
    log_security_event('SUCCESSFUL_LOGIN', "Username: $user_login", $ip);
}, 10, 2);


//******************** REMOVES wp-embed.js from loading **************************
/* If you need to call content from other websites comment this code */
//add_action( 'wp_footer', function() {
//    wp_deregister_script('wp-embed');
//});


//******************** DISABLE xmlrpc **************************
/* Disable only if your site does not require use of xmlrpc*/
add_filter('xmlrpc_enabled', function() {
    return false;
});


//******************** SET MAXIMUM revisions number **************************
if (!defined('WP_POST_REVISIONS')) define('WP_POST_REVISIONS', 3);


//******************** SET COMMENTS & PINGBACKS off by default when CREATE A POST **************************
// Disable comments and pingbacks on existing posts
function disable_comments_and_pingbacks_on_existing_posts() {
    global $wpdb;
    $wpdb->query("UPDATE $wpdb->posts SET comment_status = 'closed', ping_status = 'closed'");
}

// Disable comments and pingbacks on new posts
function disable_comments_and_pingbacks_on_new_posts($data) {
    $data['comment_status'] = 'closed';
    $data['ping_status'] = 'closed';
    return $data;
}

// Apply the functions
add_action('init', 'disable_comments_and_pingbacks_on_existing_posts');
add_filter('wp_insert_post_data', 'disable_comments_and_pingbacks_on_new_posts', 10, 2);