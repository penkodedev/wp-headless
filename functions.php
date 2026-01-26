<?php

//******************** Enqueue necesary files from /inc folder **************************
require_once get_template_directory() . '/inc/api/api-endpoints.php';
require_once get_template_directory() . '/inc/api/api-callbacks.php';
require_once get_template_directory() . '/inc/api/api-utils.php';

require_once get_template_directory() . '/inc/config/search-config.php';

require_once get_template_directory() . '/inc/dashboard/shortcodes.php';
require_once get_template_directory() . '/inc/dashboard/site-info.php';
require_once get_template_directory() . '/inc/dashboard/users.php';
require_once get_template_directory() . '/inc/dashboard/wp-admin.php';

require_once get_template_directory() . '/inc/media/utils.php';

require_once get_template_directory() . '/inc/custom-posts/cpts-images.php';
require_once get_template_directory() . '/inc/custom-posts/post-types.php';
require_once get_template_directory() . '/inc/custom-posts/utils.php';

require_once get_template_directory() . '/inc/custom-taxonomies/tax.php';

require_once get_template_directory() . '/inc/js/include-js.php';

require_once get_template_directory() . '/inc/custom-meta/custom-fields-types.php';
require_once get_template_directory() . '/inc/custom-meta/hero.php';

require_once get_template_directory() . '/inc/register/register-widgets.php';
require_once get_template_directory() . '/inc/register/register-nav-menus.php';

require_once get_template_directory() . '/inc/wp-core/wp-core-utils.php';

require_once get_template_directory() . '/inc/preview.php';

// require_once get_template_directory() . '/inc/security.php';

// require_once get_template_directory() . '/inc/wpml.php';
// require_once get_template_directory() . '/inc/wp-nav/wp-nav-utils.php';
// require_once get_template_directory() . '/vendor/autoload.php'; // Autoloader de Composer
// require_once get_template_directory() . '/inc/text-to-speech.php'; // Funcionalidad de Texto a Voz

