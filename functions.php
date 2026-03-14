<?php

load_theme_textdomain('penkode-headless', get_template_directory() . '/assets/languages');

//******************** Enqueue necesary files from /inc folder **************************
require_once get_template_directory() . '/inc/api/api-endpoints.php';
require_once get_template_directory() . '/inc/api/api-callbacks.php';
require_once get_template_directory() . '/inc/api/api-utils.php';
require_once get_template_directory() . '/inc/api/revalidation.php';

require_once get_template_directory() . '/inc/custom-meta/custom-fields.php';
require_once get_template_directory() . '/inc/custom-meta/hero.php';
require_once get_template_directory() . '/inc/custom-meta/sliders.php';

require_once get_template_directory() . '/inc/custom-posts/post-types.php';
require_once get_template_directory() . '/inc/custom-posts/utils.php';

require_once get_template_directory() . '/inc/custom-taxonomies/tax.php';

require_once get_template_directory() . '/inc/dashboard/admin/users.php';
require_once get_template_directory() . '/inc/dashboard/admin/wp-admin.php';

require_once get_template_directory() . '/inc/dashboard/editor/preview.php';
require_once get_template_directory() . '/inc/dashboard/editor/shortcodes.php';

require_once get_template_directory() . '/inc/dashboard/settings/analytics.php';
require_once get_template_directory() . '/inc/dashboard/settings/api-endpoints.php';
require_once get_template_directory() . '/inc/dashboard/settings/chat-bot.php';
require_once get_template_directory() . '/inc/dashboard/settings/home.php';
require_once get_template_directory() . '/inc/dashboard/settings/languages.php';
require_once get_template_directory() . '/inc/dashboard/settings/posts.php';
require_once get_template_directory() . '/inc/dashboard/settings/search.php';
require_once get_template_directory() . '/inc/dashboard/settings/site-info.php';
require_once get_template_directory() . '/inc/dashboard/settings/ticker.php';
require_once get_template_directory() . '/inc/dashboard/settings/tooltips.php';
require_once get_template_directory() . '/inc/dashboard/settings/appearance.php';
require_once get_template_directory() . '/inc/dashboard/settings/counter-stats.php';
require_once get_template_directory() . '/inc/dashboard/settings/smtp.php';
require_once get_template_directory() . '/inc/dashboard/settings/settings-page.php';

require_once get_template_directory() . '/inc/media/audio-post.php';
require_once get_template_directory() . '/inc/media/text-to-speech.php';
require_once get_template_directory() . '/inc/media/utils.php';

require_once get_template_directory() . '/inc/register/register-nav-menus.php';

require_once get_template_directory() . '/inc/utils/include-js.php';

$secrets = get_template_directory() . '/inc/security/secrets.php';
if (file_exists($secrets)) {
    require_once $secrets;
}
require_once get_template_directory() . '/inc/security/security.php';

require_once get_template_directory() . '/blocks/lucide-icons/register.php';
require_once get_template_directory() . '/vendor/autoload.php'; // Autoloader de Composer