<?php
/**
 * On-Demand Cache Revalidation – WordPress → Next.js webhook.
 *
 * Fires wp_remote_post() to {headless_front_url}/api/revalidate
 * whenever content that affects the frontend cache is created, updated, or deleted.
 *
 * Requires:
 *   - REVALIDATE_SECRET defined in wp-config.php
 *   - headless_front_url option set in Custom Settings → Site Info
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Send a revalidation request to the Next.js frontend.
 *
 * @param string[]|null $tags  Cache tags to revalidate (null = all).
 * @param string|null   $path  Specific path to revalidate.
 */
function pk_revalidate_frontend(?array $tags = null, ?string $path = null): void {
    if (!defined('REVALIDATE_SECRET') || !REVALIDATE_SECRET) {
        return;
    }

    require_once get_template_directory() . '/inc/dashboard/settings/site-info.php';
    $front_url = get_language_option('headless_front_url');
    if (!$front_url) {
        return;
    }

    $endpoint = rtrim($front_url, '/') . '/api/revalidate';

    $body = ['secret' => REVALIDATE_SECRET];
    if ($tags) {
        $body['tags'] = $tags;
    }
    if ($path) {
        $body['path'] = $path;
    }

    wp_remote_post($endpoint, [
        'headers'   => ['Content-Type' => 'application/json'],
        'body'      => wp_json_encode($body),
        'timeout'   => 5,
        'blocking'  => false,
        'sslverify' => defined('WP_DEBUG') && WP_DEBUG ? false : true,
    ]);
}

/**
 * Map a WordPress post type to the Next.js cache tags that should be invalidated.
 */
function pk_post_type_to_tags(string $post_type): array {
    $map = [
        'post'  => ['all-posts'],
        'page'  => ['all-pages'],
        'hero'  => ['site-info'],
    ];

    return $map[$post_type] ?? ['all-posts'];
}

// ─── Hook: save_post (create / update any published post) ───────────────────
add_action('save_post', function (int $post_id, \WP_Post $post, bool $update): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if ($post->post_status !== 'publish') {
        return;
    }

    pk_revalidate_frontend(pk_post_type_to_tags($post->post_type));
}, 20, 3);

// ─── Hook: transition_post_status (publish → trash / draft, etc.) ───────────
add_action('transition_post_status', function (string $new, string $old, \WP_Post $post): void {
    if ($old === 'publish' && $new !== 'publish') {
        pk_revalidate_frontend(pk_post_type_to_tags($post->post_type));
    }
}, 20, 3);

// ─── Hook: wp_update_nav_menu (menu structure changes) ──────────────────────
add_action('wp_update_nav_menu', function (): void {
    pk_revalidate_frontend(['all-menus']);
});

// ─── Hook: Custom Settings changes that affect site-info ────────────────────
$pk_revalidate_options = [
    'site_logo_light',
    'site_logo_dark',
    'headless_front_url',
    'site_links',
    'social_networks',
    'contact_info',
    'appearance_darkmode_enabled',
    'appearance_default_mode',
    'blogname',
    'blogdescription',
];

foreach ($pk_revalidate_options as $opt) {
    add_action("update_option_{$opt}", function () {
        pk_revalidate_frontend(['site-info']);
    });
}
