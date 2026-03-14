<?php
/**
 * Home/Welcome Section for Custom Settings
 */

/**
 * Register Home section
 */
function pk_add_home_section() {
    add_settings_section(
        'pk_home_section',
        __('', 'penkode-headless'),
        'pk_render_home_section',
        'custom-settings-home'
    );
}
add_action('admin_init', 'pk_add_home_section');

/**
 * Render Home section with welcome message and documentation links
 */
function pk_render_home_section() {
    $api_endpoints_url = admin_url('admin.php?page=custom-settings#api');
    ?>
    <div class="pk-home-section">
        <div class="pk-welcome-box">
            <h2><?php _e('Penkode Headless', 'penkode-headless'); ?></h2>
            <p class="pk-welcome-description">
                <?php _e('This is the administration panel for your headless WordPress site. All content and configurations are served via the REST API to be consumed by your React frontend.', 'penkode-headless'); ?>
            </p>
        </div>
        
        <div class="pk-features-grid">
            <div class="pk-feature-card">
                <div class="pk-feature-icon">
                    <span class="dashicons dashicons-rest-api"></span>
                </div>
                <h3><?php _e('REST API', 'penkode-headless'); ?></h3>
                <p><?php _e('All content is available through our custom REST API endpoints.', 'penkode-headless'); ?></p>
            </div>
            
            <div class="pk-feature-card">
                <div class="pk-feature-icon">
                    <span class="dashicons dashicons-admin-customizer"></span>
                </div>
                <h3><?php _e('Settings', 'penkode-headless'); ?></h3>
                <p><?php _e('Configure site logos, analytics, languages, and more.', 'penkode-headless'); ?></p>
            </div>
            
            <div class="pk-feature-card">
                <div class="pk-feature-icon">
                    <span class="dashicons dashicons-admin-post"></span>
                </div>
                <h3><?php _e('Custom Post Types', 'penkode-headless'); ?></h3>
                <p><?php _e('Manage your custom content types and their visibility.', 'penkode-headless'); ?></p>
            </div>
            
            <div class="pk-feature-card">
                <div class="pk-feature-icon">
                    <span class="dashicons dashicons-translation"></span>
                </div>
                <h3><?php _e('Multilingual', 'penkode-headless'); ?></h3>
                <p><?php _e('Full WPML support for multilingual content.', 'penkode-headless'); ?></p>
            </div>
        </div>
        
        <div class="pk-info-grid">
            <div class="pk-tech-stack">
                <h3><?php _e('Technology Stack', 'penkode-headless'); ?></h3>
                <ul class="pk-tech-list">
                    <li><strong>WordPress:</strong> <?php _e('Headless CMS', 'penkode-headless'); ?></li>
                    <li><strong>Frontend:</strong> React / Next.js</li>
                    <li><strong>API:</strong> WordPress REST API (custom endpoints)</li>
                    <li><strong>Languages:</strong> WPML</li>
                </ul>
            </div>

            <div class="pk-dev-notes">
                <h3><?php _e('Developer Notes', 'penkode-headless'); ?></h3>
                <ul class="pk-tech-list">
                    <li><strong><code>npm run dev</code></strong> — <?php _e('Watch + live reload (Dart SASS expanded CSS)', 'penkode-headless'); ?></li>
                    <li><strong><code>npm run build</code></strong> — <?php _e('Production build (Dart SASS compressed CSS)', 'penkode-headless'); ?></li>
                    <li><?php _e('Live reload via', 'penkode-headless'); ?> <code>https://localhost:4200</code></li>
                    <li><?php _e('Styles source:', 'penkode-headless'); ?> <code>scss/</code> → <code>style.css</code></li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php
}
