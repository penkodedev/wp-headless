<?php
/**
 * Header template for headless WordPress theme.
 * This file is minimal since the frontend is handled by React/Next.js.
 * Only includes essential WordPress hooks and basic HTML for admin previews.
 */

// Load theme textdomain
load_theme_textdomain('penkode-headless');

// Custom header setup (kept for compatibility)
function penkode_custom_header_setup() {
    $args = array(
        'default-image'      => get_template_directory_uri() . 'img/default-image.jpg',
        'default-text-color' => '000',
        'width'              => 1000,
        'height'             => 250,
        'flex-width'         => true,
        'flex-height'        => true,
    );
    add_theme_support('custom-header', $args);
}
add_action('after_setup_theme', 'penkode_custom_header_setup');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title(''); ?></title>
    <?php if (is_page_template('login.php')): ?>
    <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
    <?php endif; ?>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php wp_footer(); ?>
</body>
</html>