<?php
/**
 * Search Exclusions Settings
 *
 * Allows admins to choose which CPTs are excluded from search results
 * via Custom Settings > Search tab.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the search exclusions setting
 */
function pk_register_search_exclusions_setting() {
    register_setting('custom-settings', 'pk_search_excluded_cpts', [
        'sanitize_callback' => 'pk_sanitize_search_exclusions',
        'default'           => ['modales', 'hero'],
    ]);

    add_settings_section(
        'pk_search_section',
        '',
        'pk_render_search_section',
        'custom-settings-search'
    );
}
add_action('admin_init', 'pk_register_search_exclusions_setting');

/**
 * Sanitize: only keep valid CPT slugs
 */
function pk_sanitize_search_exclusions($input) {
    if (!is_array($input)) {
        return [];
    }
    return array_map('sanitize_text_field', array_values($input));
}

/**
 * Render the Search section with checkboxes
 */
function pk_render_search_section() {
    $excluded = get_option('pk_search_excluded_cpts', ['modales', 'hero']);
    $own_cpts = pk_get_own_cpts();

    if (empty($own_cpts)) {
        echo '<p>' . __('No custom post types registered.', 'penkode-headless') . '</p>';
        return;
    }

    $all_types = array_merge(
        get_post_types(['public' => true,  '_builtin' => false], 'objects'),
        get_post_types(['public' => false, '_builtin' => false], 'objects')
    );
    ?>
    <div class="pk-search-section">

        <table class="widefat striped pk-search-table">
            <thead>
                <tr>
                    <th><?php _e('Post Type', 'penkode-headless'); ?></th>
                    <th><?php _e('Label', 'penkode-headless'); ?></th>
                    <th><?php _e('Exclude from Search', 'penkode-headless'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($own_cpts as $slug) :
                    $post_type = $all_types[$slug] ?? null;
                    if (!$post_type) continue;

                    $is_excluded = in_array($slug, $excluded, true);
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($slug); ?></code></td>
                        <td><?php echo esc_html($post_type->labels->singular_name); ?></td>
                        <td>
                            <label class="pk-switch">
                                <input type="checkbox"
                                       name="pk_search_excluded_cpts[]"
                                       value="<?php echo esc_attr($slug); ?>"
                                       <?php checked($is_excluded); ?>>
                                <span class="pk-slider"></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
}

// ── Helper functions (used by api-callbacks.php) ────────────

/**
 * Get CPTs excluded from search
 */
function get_search_excluded_cpts() {
    return get_option('pk_search_excluded_cpts', ['modales', 'hero']);
}

/**
 * Get CPTs included in search (inverse of excluded)
 */
function get_search_included_cpts() {
    $all_public_cpts = get_post_types(['public' => true]);
    $excluded_cpts = get_search_excluded_cpts();
    return array_diff($all_public_cpts, $excluded_cpts);
}

/**
 * Check if a specific CPT is searchable
 */
function is_cpt_searchable($post_type) {
    return !in_array($post_type, get_search_excluded_cpts(), true);
}
