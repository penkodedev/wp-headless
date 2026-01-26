<?php
/*
* Template Name: Login Page
*/

// Start output buffering to prevent any premature output
ob_start();

// Handle redirects BEFORE any output
if (! defined('ABSPATH')) {
    exit;
}

$max_attempts = 3;
$delay_seconds = 130;

if (is_user_logged_in() && !isset($_POST['log'])) {
    wp_redirect(admin_url());
    exit;
}

get_header();
?>

<?php if (is_user_logged_in()) : ?>
    <div class="info-login">
        <?php
        echo __('You are already connected to the system, use one of the links below for the desired action.', 'foo') . '<br />';
        wp_loginout(home_url());
        echo ' | ';
        wp_register('', '');
        ?>
    </div>
<?php else : ?>

    <main class="grid-main animate fadeIn" id="main-container">
        <div class="login-container">

            <div class="login-info">
                <h1>CMS CONTROL PANEL</h1>
                <p>This is a headless CMS installation. Frontend is handled by React/Next.js.</p>
            </div>


            <?php
            $error_message = '';
            $login_attempts = get_transient('login_attempts') ?: 0;

            if ($login_attempts >= $max_attempts) {
                $last_attempt_time = get_transient('last_login_attempt');
                $remaining_time = $last_attempt_time ? max(0, ($last_attempt_time + $delay_seconds) - time()) : 0;
                if ($remaining_time > 0) {
                    $error_message = sprintf(__('Too many failed attempts. Please try again after <strong><span id="remaining-time">%d</span> seconds.</strong>', 'foo'), $remaining_time);
                } else {
                    delete_transient('login_attempts');
                    delete_transient('last_login_attempt');
                }
            } elseif (isset($_POST['log'])) {
                $login_data = array(
                    'user_login'    => $_POST['log'],
                    'user_password' => $_POST['pwd'],
                    'remember'      => isset($_POST['rememberme']) ? $_POST['rememberme'] : ''
                );

                $user = wp_signon($login_data);

                if (is_wp_error($user)) {
                    $error_codes = $user->get_error_codes();

                    if (in_array('invalid_username', $error_codes)) {
                        $error_message = __('The username entered is invalid.', 'foo');
                    } elseif (in_array('incorrect_password', $error_codes)) {
                        $error_message = __('The entered password is incorrect.', 'foo');
                    }

                    $login_attempts++;
                    set_transient('login_attempts', $login_attempts, $delay_seconds);
                    set_transient('last_login_attempt', time(), $delay_seconds);
                } else {
                    // Success - clean all output buffers and redirect
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    wp_redirect(admin_url());
                    exit;
                }
            }
            ?>

            <?php get_template_part('/template-parts/logo-container'); ?>

            <!-- FORM begin -->
            <form id="loginform" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
                <?php if ($error_message) : ?>
                    <div id="error-message" class="login-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if ($login_attempts > 0) : ?>
                    <div class="login-message">
                        <?php printf(__('You have %d attempts remaining before the first unsuccessful attempt.', 'foo'), $max_attempts - $login_attempts); ?>
                    </div>
                <?php endif; ?>

                <p><input type="text" placeholder="Username" name="log" id="user_login" class="input" value="" size="20" autocapitalize="off" /></p>
                <p><input type="password" placeholder="Password" name="pwd" id="user_pass" class="input" value="" size="20" /></p>



                <p class="submit">
                    <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php _e('Login', 'foo'); ?>" />
                </p>
            </form>
            <!-- FORM end -->

        </div>

        <div class="login-info">
                
                <small>Powered by Penkode</small>
            </div>
    </main>

<?php endif; ?>

<?php get_footer(); ?>