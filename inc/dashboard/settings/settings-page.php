<?php
/**
 * Custom Settings Page with Tabs
 * 
 * @package Penkode_Headless
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue Admin Scripts and Styles
 */
// Settings page styles are in scss/_dashboard.scss

/**
 * Add Custom Settings Menu Item
 */
function pk_add_custom_settings_menu() {
    add_menu_page(
        __('Custom Settings', 'penkode-headless'),
        __('Custom Settings', 'penkode-headless'),
        'manage_options',
        'custom-settings',
        'pk_render_custom_settings_page',
        'dashicons-admin-generic',
        99
    );

    add_submenu_page(
        'custom-settings',
        __('Settings', 'penkode-headless'),
        __('Settings', 'penkode-headless'),
        'manage_options',
        'custom-settings',
        'pk_render_custom_settings_page'
    );
}
add_action('admin_menu', 'pk_add_custom_settings_menu');

/**
 * Reorder Custom Settings submenu: Settings first, then CPTs alphabetically.
 */
function pk_reorder_custom_settings_submenu() {
    global $submenu;
    if (empty($submenu['custom-settings'])) {
        return;
    }

    $settings_item = null;
    $cpt_items = [];

    foreach ($submenu['custom-settings'] as $item) {
        if ($item[2] === 'custom-settings') {
            $settings_item = $item;
        } else {
            $cpt_items[] = $item;
        }
    }

    usort($cpt_items, function ($a, $b) {
        return strcasecmp($a[0], $b[0]);
    });

    $submenu['custom-settings'] = array_merge(
        $settings_item ? [$settings_item] : [],
        $cpt_items
    );
}
add_action('admin_menu', 'pk_reorder_custom_settings_submenu', 999);

/**
 * Force WPML language when viewing Custom Settings with ?lang= so the form
 * loads and displays the correct language's data (get_language_option uses wpml_current_language).
 * Without this, the admin request may still have default language and show wrong data on the tab.
 */
add_action('admin_init', function() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'custom-settings' || empty($_GET['lang'])) {
        return;
    }
    $lang = sanitize_text_field($_GET['lang']);
    $active = function_exists('apply_filters') ? apply_filters('wpml_active_languages', []) : [];
    if (!empty($active[$lang])) {
        do_action('wpml_switch_language', $lang);
    }
}, 1);

/**
 * Preserve active tab after saving settings
 */
add_action('admin_init', function() {
    if (isset($_POST['pk_active_tab'])) {
        add_filter('wp_redirect', function($location) {
            if (strpos($location, 'page=custom-settings') !== false) {
                $tab = sanitize_text_field($_POST['pk_active_tab']);
                $location = remove_query_arg('settings-updated', $location);
                $location = add_query_arg('settings-updated', 'true', $location);
                if (!empty($_POST['pk_wpml_admin_lang'])) {
                    $location = add_query_arg('lang', sanitize_text_field($_POST['pk_wpml_admin_lang']), $location);
                }
                $location .= '#' . $tab;
            }
            return $location;
        });
    }
});

/**
 * Render Custom Settings Page with Tabs
 */
function pk_render_custom_settings_page() {
    // Forzar idioma desde la URL justo antes de pintar el formulario, para que
    // get_language_option() en Site Info (contact_info, site_links, etc.) use el idioma correcto.
    if (!empty($_GET['lang']) && isset($_GET['page']) && $_GET['page'] === 'custom-settings') {
        $lang = sanitize_text_field($_GET['lang']);
        $active = function_exists('apply_filters') ? apply_filters('wpml_active_languages', []) : [];
        if (!empty($active[$lang])) {
            do_action('wpml_switch_language', $lang);
        }
    }
    ?>
    <div class="wrap pk-settings-wrap">
        <h1 class="settings-title" ><?php _e('Custom Settings', 'penkode-headless'); ?></h1>
        
        <form method="post" action="options.php">
            <input type="hidden" id="pk-active-tab" name="pk_active_tab" value="home">
            <?php
            $pk_admin_lang = isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : apply_filters('wpml_current_language', '');
            if ($pk_admin_lang) {
                echo '<input type="hidden" name="pk_wpml_admin_lang" value="' . esc_attr($pk_admin_lang) . '">';
            }
            ?>
            <?php settings_fields('custom-settings'); ?>
            
            <div class="pk-tabs">
                <button type="button" class="pk-tab active" data-tab="home"><?php _e('Home', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="analytics"><?php _e('Analytics', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="api"><?php _e('API Rest', 'penkode-headless'); ?></button>              
                <button type="button" class="pk-tab" data-tab="chatbot"><?php _e('Chat Bot', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="appearance"><?php _e('Components', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="counter-stats"><?php _e('Counter Stats', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="languages"><?php _e('Languages', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="map"><?php _e('Map', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="posts"><?php _e('Posts', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="search"><?php _e('Search', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="site-info"><?php _e('Site Info', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="smtp"><?php _e('SMTP', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="ticker"><?php _e('Ticker', 'penkode-headless'); ?></button>
                <button type="button" class="pk-tab" data-tab="tooltips"><?php _e('Tooltips', 'penkode-headless'); ?></button>
            </div>

            <div class="pk-tab-content active" id="tab-home">
                <?php do_settings_sections('custom-settings-home'); ?>
            </div>

            <div class="pk-tab-content" id="tab-analytics">
                <h2 class="title-tab"><?php _e('Analytics Configuration', 'penkode-headless'); ?></h2>
                <p class="tab-description">Google Analytics, Tag Manager and third-party tracking scripts for the frontend.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php do_settings_sections('custom-settings-analytics'); ?>
            </div>

            <div class="pk-tab-content" id="tab-api">
                <h2 class="title-tab"><?php _e('API Rest endpoints', 'penkode-headless'); ?></h2>
                <p class="tab-description">A list of which custom REST API endpoints are active for the headless frontend under the namespace <code>custom/v1</code>.</p>
                
                <?php do_settings_sections('custom-settings-api'); ?>
            </div>

            <div class="pk-tab-content" id="tab-appearance">
                <h2 class="title-tab"><?php _e('Appearance Settings', 'penkode-headless'); ?></h2>
                <p class="tab-description">Here you can activate/deactivate the main <b>UI components</b> for the entire website.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php do_settings_sections('custom-settings-appearance'); ?>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
            </div>

            <div class="pk-tab-content" id="tab-chatbot">
                <h2 class="title-tab"><?php _e('Chat Bot Configuration', 'penkode-headless'); ?></h2>
                <p class="tab-description">AI chatbot settings, API keys and behaviour for the frontend assistant.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php do_settings_sections('custom-settings-chatbot'); ?>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
            </div>

            <div class="pk-tab-content" id="tab-counter-stats">
                <h2 class="title-tab"><?php _e('Counter Stats', 'penkode-headless'); ?></h2>
                <p class="tab-description">Animated number counters. Create groups and insert them via shortcode.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php pk_render_counter_stats_settings(); ?>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
            </div>

            <div class="pk-tab-content" id="tab-languages">
                <h2 class="title-tab"><?php _e('Language Settings', 'penkode-headless'); ?></h2>
                <p class="tab-description">WPML integration, default language and multilingual routing options.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php do_settings_sections('custom-settings-languages'); ?>
            </div>

            <div class="pk-tab-content" id="tab-map">
                <h2 class="title-tab"><?php _e('Map', 'penkode-headless'); ?></h2>
                <p class="tab-description"><?php _e('Configure your map here. Default center and zoom and more. To add locations go to Custom Settings → Map.', 'penkode-headless'); ?></p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php do_settings_sections('custom-settings-map'); ?>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
            </div>

            <div class="pk-tab-content" id="tab-posts">
                <h2 class="title-tab"><?php _e('Custom Post Type Settings', 'penkode-headless'); ?></h2>
                <p class="tab-description">Here you can enable/disable the custom post types registered in the theme by default.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php do_settings_sections('custom-settings-posts'); ?>
            </div>

            <div class="pk-tab-content" id="tab-search">
                <h2 class="title-tab"><?php _e('Search Exclusions', 'penkode-headless'); ?></h2>
                <p class="tab-description">Choose which post types and pages to exclude from search results.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php do_settings_sections('custom-settings-search'); ?>
            </div>

            <div class="pk-tab-content" id="tab-site-info">
                <h2 class="title-tab"><?php _e('Site Information', 'penkode-headless'); ?></h2>
                <p class="tab-description">Contact details, social links and general site information for the frontend.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php do_settings_sections('custom-settings-site-info'); ?>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
            </div>

            <div class="pk-tab-content" id="tab-ticker">
                <h2 class="title-tab"><?php _e('Ticker Configuration', 'penkode-headless'); ?></h2>
                <p class="tab-description">Scrolling text ticker: content, speed, visibility and page assignment.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php next_wp_kit_render_ticker_settings(); ?>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
            </div>

            <div class="pk-tab-content" id="tab-tooltips">
                <h2 class="title-tab"><?php _e('Tooltips Configuration', 'penkode-headless'); ?></h2>
                <p class="tab-description">Rich-text tooltips. Link them in Gutenberg using the generated anchor URLs.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php pk_render_tooltip_settings(); ?>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
            </div>
            
            <div class="pk-tab-content" id="tab-smtp">
                <h2 class="title-tab"><?php _e('SMTP Configuration', 'penkode-headless'); ?></h2>
                <p class="tab-description">Outgoing email server settings, authentication and test tools.</p>
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
                <?php do_settings_sections('custom-settings-smtp'); ?>
                <p class="description" style="margin-top: 20px;">
                    <button type="button" class="button" id="pk-test-smtp"><?php _e('Send Test Email', 'penkode-headless'); ?></button>
                    <button type="button" class="button" id="pk-diagnose-smtp" style="margin-left:8px;"><?php _e('Diagnose SMTP', 'penkode-headless'); ?></button>
                    <span id="pk-test-smtp-result" style="margin-left: 10px;"></span>
                </p>

                <script>
                jQuery(document).ready(function($) {
                    $('#pk-diagnose-smtp').on('click', function() {
                        $('#pk-test-smtp-result').text('Diagnosing...').css('color', '#666');
                        $.post(ajaxurl, {
                            action: 'pk_smtp_diagnose',
                        }, function(response) {
                            if (response.success) {
                                var d = response.data.diagnostic;
                                var summary = 'Host: ' + d.host + ':' + d.port + ' | TCP: ' + (d.tcp_reachable ? 'OK (' + d.tcp_ms + 'ms)' : 'FAIL: ' + d.tcp_error) + ' | OpenSSL: ' + d.openssl_loaded;
                                $('#pk-test-smtp-result').text(summary).css('color', d.tcp_reachable ? 'green' : 'red');
                            } else {
                                $('#pk-test-smtp-result').text(response.data.message).css('color', 'red');
                            }
                        }).fail(function(xhr) {
                            $('#pk-test-smtp-result').text('Diagnose failed: HTTP ' + xhr.status).css('color', 'red');
                        });
                    });

                    $('#pk-test-smtp').on('click', function() {
                        var email = prompt('<?php echo esc_js(__("Enter email address to send test:", "penkode-headless")); ?>');
                        if (!email) return;
                        
                        $('#pk-test-smtp-result').text('<?php echo esc_js(__("Sending...", "penkode-headless")); ?>').css('color', '#666');
                        
                        $.post(ajaxurl, {
                            action: 'pk_send_test_email',
                            nonce: '<?php echo wp_create_nonce("pk_smtp_test"); ?>',
                            email: email
                        }, function(response) {
                            if (response.success) {
                                $('#pk-test-smtp-result').text(response.data.message).css('color', 'green');
                            } else {
                                $('#pk-test-smtp-result').text(response.data.message).css('color', 'red');
                            }
                        }).fail(function(xhr, status, error) {
                            $('#pk-test-smtp-result').text('Error: ' + error + ' (Status: ' + status + ', HTTP: ' + xhr.status + ')').css('color', 'red');
                        });
                    });
                });
                </script>
                
                <input type="submit" class="save-changes" value="<?php esc_attr_e('Save Changes', 'penkode-headless'); ?>">
            </div>

        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var pkSavingLabel = <?php echo json_encode(__('Saving...', 'penkode-headless')); ?>;
        // Get current tab from hash or hidden field
        function pk_getCurrentTab() {
            var hash = window.location.hash.substring(1);
            if (hash) {
                return hash;
            }
            // Check if there's a saved active tab
            var savedTab = $('#pk-active-tab').val();
            if (savedTab) {
                return savedTab;
            }
            return 'home';
        }
        
        // Set active tab
        function pk_setActiveTab(tabId) {
            $('.pk-tab').removeClass('active');
            $('.pk-tab-content').removeClass('active');
            $('.pk-tab[data-tab="' + tabId + '"]').addClass('active');
            $('#tab-' + tabId).addClass('active');
            $('#pk-active-tab').val(tabId);
            
            // Update URL hash without triggering reload
            if (window.location.hash.substring(1) !== tabId) {
                history.replaceState(null, null, '#' + tabId);
            }
            
            pk_toggleSaveButtons();
        }
        
        // Toggle Save buttons based on active tab
        function pk_toggleSaveButtons() {
            var activeTab = pk_getCurrentTab();
            
            if (activeTab === 'home') {
                $('.toplevel_page_custom-settings p.submit').hide();
            } else {
                $('.toplevel_page_custom-settings p.submit').show();
            }
        }
        
        // Initialize on load - set the correct tab
        var initialTab = pk_getCurrentTab();
        pk_setActiveTab(initialTab);
        pk_toggleSaveButtons();
        
        // Tab click handler
        $('.pk-tab').on('click', function() {
            var tabId = $(this).data('tab');
            pk_setActiveTab(tabId);
        });
        
        // Update active tab and sync TinyMCE before form submission (editors store content in iframe, must copy to textarea).
        // Guardar explícitamente cada editor de Contact Info por ID para no perder filas (teléfono, dirección, etc.).
        var pkFormSyncDone = false;
        $('form').on('submit', function(e) {
            var currentTab = pk_getCurrentTab();
            $('#pk-active-tab').val(currentTab);
            if (pkFormSyncDone) return;
            if (typeof tinymce !== 'undefined') {
                try {
                    tinymce.triggerSave();
                    if (tinymce.editors && tinymce.editors.length > 0) {
                        tinymce.editors.forEach(function(ed) { if (ed.initialized) ed.save(); });
                    }
                    $('#contact_info_container').find('textarea[id^="contact_info_"][id$="_value"]').each(function() {
                        var ed = tinymce.get(this.id);
                        if (ed && ed.initialized) ed.save();
                    });
                } catch (err) {}
                e.preventDefault();
                pkFormSyncDone = true;
                var form = this;
                var $btns = $(form).find('input[type="submit"]');
                $btns.prop('disabled', true).each(function() {
                    var t = $(this);
                    if (!t.data('pk-original-val')) t.data('pk-original-val', this.value);
                    this.value = pkSavingLabel;
                });
                setTimeout(function() {
                    $btns.prop('disabled', false);
                    $btns.first()[0].click();
                }, 300);
            }
        });
    });
    </script>
    <?php
}
