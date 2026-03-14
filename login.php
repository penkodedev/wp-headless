<?php
/**
 * Template Name: Login Page
 *
 * Self-contained login template for the headless CMS.
 * Does not rely on get_header()/get_footer() to keep HTML clean.
 *
 * Security: per-IP rate limiting, nonce, generic error messages.
 */

if (!defined('ABSPATH')) {
    exit;
}

// --- Config ---
$pk_max_attempts  = 3;
$pk_lockout_secs  = 130;

// --- Already logged in → dashboard ---
if (is_user_logged_in() && !isset($_POST['log'])) {
    wp_redirect(admin_url());
    exit;
}

// --- Per-IP rate limiting helpers ---
$pk_ip           = pk_get_client_ip();
$pk_attempts_key = 'pk_login_att_' . md5($pk_ip);
$pk_time_key     = 'pk_login_time_' . md5($pk_ip);
$pk_attempts     = (int) get_transient($pk_attempts_key);
$pk_error        = '';
$pk_remaining    = 0;

// --- Check lockout ---
if ($pk_attempts >= $pk_max_attempts) {
    $pk_last = (int) get_transient($pk_time_key);
    $pk_remaining = max(0, ($pk_last + $pk_lockout_secs) - time());
    if ($pk_remaining > 0) {
        $pk_error = sprintf(
            __('Too many failed attempts.<br>Try again in <strong><span id="remaining-time">%d</span> seconds.</strong>', 'penkode-headless'),
            $pk_remaining
        );
    } else {
        delete_transient($pk_attempts_key);
        delete_transient($pk_time_key);
        $pk_attempts = 0;
    }
}

// --- Handle form submission ---
if (empty($pk_error) && isset($_POST['log']) && wp_verify_nonce($_POST['_pk_login_nonce'] ?? '', 'pk_login')) {
    $pk_user = wp_signon([
        'user_login'    => sanitize_user($_POST['log']),
        'user_password' => $_POST['pwd'] ?? '',
        'remember'      => !empty($_POST['rememberme']),
    ]);

    if (is_wp_error($pk_user)) {
        $pk_error = __('Invalid username or password.', 'penkode-headless');
        $pk_attempts++;
        set_transient($pk_attempts_key, $pk_attempts, $pk_lockout_secs);
        set_transient($pk_time_key, time(), $pk_lockout_secs);
    } else {
        wp_redirect(admin_url());
        exit;
    }
} elseif (isset($_POST['log']) && empty($pk_error)) {
    $pk_error = __('Session expired. Please try again.', 'penkode-headless');
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(get_bloginfo('name')); ?> — <?php esc_html_e('Login', 'penkode-headless'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo esc_url(get_stylesheet_directory_uri() . '/style.css'); ?>">
    <link rel="stylesheet" id="sass-error-css" href="<?php echo esc_url(get_stylesheet_directory_uri() . '/sass-error.css'); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if (is_user_logged_in()) : ?>
    <main class="grid-main" id="main-container">
        <div class="login-container">
            <div class="login-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </div>
            <div class="login-header">
                <h1><?php esc_html_e('Already signed in', 'penkode-headless'); ?></h1>
                <p><?php esc_html_e('Choose an action below', 'penkode-headless'); ?></p>
            </div>
            <div class="login-actions">
                <a href="<?php echo esc_url(admin_url()); ?>" class="pk-btn pk-btn-primary"><?php esc_html_e('Go to Dashboard', 'penkode-headless'); ?></a>
                <a href="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>" class="pk-btn pk-btn-outline"><?php esc_html_e('Log out', 'penkode-headless'); ?></a>
            </div>
            <div class="login-footer">
                <small>Powered by</small>
                <a href="https://www.penkode.com" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-penkode-01.png'); ?>" alt="Penkode" class="login-logo"></a>
            </div>
        </div>
    </main>
<?php else : ?>

    <main class="grid-main animate fadeIn" id="main-container">
        <div class="login-container">

            <div class="login-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><circle cx="12" cy="16" r="1"/></svg>
            </div>
            <div class="login-header">
                <h1><?php esc_html_e('Control Panel Login', 'penkode-headless'); ?></h1>
                <p><?php esc_html_e('Sign in to access the CMS dashboard', 'penkode-headless'); ?></p>
            </div>

            <form id="loginform" action="<?php echo esc_url(home_url('/login')); ?>" method="post" autocomplete="on">
                <?php wp_nonce_field('pk_login', '_pk_login_nonce'); ?>

                <?php if ($pk_error) : ?>
                    <div id="error-message" class="login-error"><?php echo wp_kses_post($pk_error); ?></div>
                <?php endif; ?>

                <?php if ($pk_attempts > 0 && $pk_attempts < $pk_max_attempts) : ?>
                    <div class="login-message">
                        <?php printf(
                            esc_html__('%d of %d attempts used.', 'penkode-headless'),
                            $pk_attempts,
                            $pk_max_attempts
                        ); ?>
                    </div>
                <?php endif; ?>

                <p>
                    <input type="text" name="log" id="user_login" class="input"
                           placeholder="<?php esc_attr_e('Username', 'penkode-headless'); ?>"
                           value="" autocapitalize="off" autocomplete="username" required>
                </p>
                <p>
                    <input type="password" name="pwd" id="user_pass" class="input"
                           placeholder="<?php esc_attr_e('Password', 'penkode-headless'); ?>"
                           autocomplete="current-password" required>
                </p>

                <div class="login-options-container">
                    <p class="login-remember">
                        <label>
                            <input type="checkbox" name="rememberme" value="forever">
                            <?php esc_html_e('Remember me', 'penkode-headless'); ?>
                        </label>
                    </p>
                </div>

                <p class="submit">
                    <input type="submit" name="wp-submit" id="wp-submit"
                           class="button button-primary button-large"
                           value="<?php esc_attr_e('Login', 'penkode-headless'); ?>">
                </p>
            </form>

            <div class="login-footer">
                <small>Powered by</small>
                <a href="https://www.penkode.com" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-penkode-01.png'); ?>" alt="Penkode" class="login-logo"></a>
            </div>
        </div>
    </main>

    <?php if ($pk_remaining > 0) : ?>
    <script>
    (function() {
        var el = document.getElementById('remaining-time');
        if (!el) return;
        var secs = <?php echo (int) $pk_remaining; ?>;
        var timer = setInterval(function() {
            secs--;
            if (secs <= 0) {
                clearInterval(timer);
                location.reload();
                return;
            }
            el.textContent = secs;
        }, 1000);
    })();
    </script>
    <?php endif; ?>

<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
