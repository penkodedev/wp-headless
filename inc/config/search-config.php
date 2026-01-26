<?php
/**
 * ===================================================================
 * SEARCH CONFIGURATION
 * ===================================================================
 * Configure which Custom Post Types should be excluded from search results.
 * This affects both the REST API search endpoint and frontend search.
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get CPTs that should be excluded from search
 * 
 * @return array List of CPT slugs to exclude
 */
function get_search_excluded_cpts() {
    /**
     * Default excluded CPTs:
     * - modales: Popup content (should not appear in search)
     * - hero: Hero slides (should not appear in search)
     * 
     * Add more CPTs here if needed
     */
    $excluded_cpts = [
        'modales',
        'hero',
        // Add more here if needed:
        // 'custom_cpt_slug',
    ];
    
    /**
     * Filter: Allow plugins/themes to modify excluded CPTs
     * 
     * @param array $excluded_cpts Current list of excluded CPTs
     */
    return apply_filters('next_wp_kit_search_excluded_cpts', $excluded_cpts);
}

/**
 * Get CPTs that should be included in search (inverse of excluded)
 * 
 * @return array List of CPT slugs to include
 */
function get_search_included_cpts() {
    $all_public_cpts = get_post_types(['public' => true]);
    $excluded_cpts = get_search_excluded_cpts();
    
    return array_diff($all_public_cpts, $excluded_cpts);
}

/**
 * Check if a specific CPT should be searchable
 * 
 * @param string $post_type CPT slug to check
 * @return bool True if searchable, false if excluded
 */
function is_cpt_searchable($post_type) {
    $excluded_cpts = get_search_excluded_cpts();
    return !in_array($post_type, $excluded_cpts);
}

/**
 * Display admin notice showing which CPTs are excluded from search
 * (Only visible to administrators)
 */
function search_config_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $excluded = get_search_excluded_cpts();
    
    if (empty($excluded)) {
        return;
    }
    
    $screen = get_current_screen();
    if ($screen && $screen->id === 'dashboard') {
        ?>
        <div class="notice notice-info">
            <p>
                <strong>🔍 Search Configuration:</strong> 
                The following CPTs are excluded from search: 
                <code><?php echo implode('</code>, <code>', $excluded); ?></code>
            </p>
            <p>
                <small>
                    To modify this, edit: 
                    <code>/inc/config/search-config.php</code>
                </small>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'search_config_admin_notice');
