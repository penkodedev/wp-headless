<?php
/**
 * ===================================================================
 * TOOLTIPS SETTINGS
 * ===================================================================
 * Manageable tooltip system from Custom Settings.
 * 
 * Structure:
 * - Tooltip Content (WYSIWYG)
 * - Link URL for use in Gutenberg
 * 
 * Usage in Gutenberg: Select text → Insert link → #tooltip-1
 * API: /wp-json/custom/v1/tooltips
 */

// ===================================================================
// REGISTER SETTINGS
// ===================================================================
add_action('admin_init', 'pk_register_tooltip_settings');

function pk_register_tooltip_settings() {
    register_setting('custom-settings', 'tooltips_data', [
        'type' => 'array',
        'sanitize_callback' => 'pk_sanitize_tooltips',
        'default' => []
    ]);
}

/**
 * Sanitize tooltip data
 */
function pk_sanitize_tooltips($value) {
    if (!is_array($value)) {
        return [];
    }
    
    $sanitized = [];
    foreach ($value as $index => $tooltip) {
        $tooltip_id = (string)($index + 1);
        
        if (!empty($tooltip['content'])) {
            $content = $tooltip['content'];
            
            // Allow specific HTML tags: b, i, u, strong, em, a, br, p
            $allowed_tags = wp_kses_allowed_html('post');
            $allowed_tags['b'] = [];
            $allowed_tags['i'] = [];
            $allowed_tags['u'] = [];
            $allowed_tags['strong'] = [];
            $allowed_tags['em'] = [];
            $allowed_tags['br'] = [];
            $allowed_tags['p'] = [];
            $allowed_tags['a'] = [
                'href' => true,
                'title' => true,
                'target' => true,
                'rel' => true,
            ];
            
            $sanitized[] = [
                'id' => $tooltip_id,
                'content' => wp_kses($content, $allowed_tags)
            ];
        }
    }
    return $sanitized;
}

// ===================================================================
// RENDER TOOLTIP SETTINGS
// ===================================================================
function pk_render_tooltip_settings() {
    $tooltips = get_option('tooltips_data', []);
    if (!is_array($tooltips)) {
        $tooltips = [];
    }
    
    $editor_id = 0;
    ?>
    
    <div id="pk-tooltips-container">
        <div id="pk-tooltips-list">
            <?php
            if (!empty($tooltips)) {
                foreach ($tooltips as $index => $tooltip) {
                    pk_render_tooltip_row($index, $tooltip);
                    $editor_id = $index + 1;
                }
            }
            ?>
        </div>
        
        <button type="button" class="button button-primary" id="pk-add-tooltip">
            + Add Tooltip
        </button>
    </div>
    
    <!-- HIDDEN TEMPLATE FOR NEW TOOLTIPS -->
    <script type="text/html" id="pk-tooltip-template">
        <div class="pk-tooltip-row" data-index="{{INDEX}}">
            <div class="pk-tooltip-actions">
                <button type="button" class="button button-small pk-remove-tooltip">✕ Remove</button>
            </div>
            
            <h4>Tooltip #<span class="tooltip-number">{{INDEX_PLUS_1}}</span></h4>
            
            <div class="pk-tooltip-field">
                <label>Link URL</label>
                <div class="pk-shortcode-display">
                    <code>#tooltip-{{INDEX_PLUS_1}}</code>
                    <button type="button" class="button button-small pk-copy-shortcode" data-shortcode='#tooltip-{{INDEX_PLUS_1}}'>Copy</button>
                </div>
                <p class="description">In Gutenberg: Select text → Insert link → Paste this URL</p>
            </div>
            
            <div class="pk-tooltip-field">
                <label>Tooltip Content</label>
                <div class="pk-tooltip-editor-container">
                    <textarea 
                        id="tooltip-editor-{{INDEX}}"
                        name="tooltips_data[{{INDEX}}][content]"
                        class="pk-tooltip-content-editor"
                        rows="4"
                        placeholder="Tooltip content..."></textarea>
                </div>
            </div>
        </div>
    </script>
    
    
    <script>
    jQuery(document).ready(function($) {
        let tooltipIndex = <?php echo count($tooltips); ?>;
        
        // Initialize editors for existing tooltips
        function initExistingEditors() {
            $('.pk-tooltip-row').each(function(index) {
                const $row = $(this);
                const content = $row.find('.pk-tooltip-content-editor').val();
                const textareaId = 'tooltip-editor-' + index;
                
                // Create WYSIWYG editor
                if (typeof wp !== 'undefined' && wp.editor) {
                    wp.editor.initialize(textareaId, {
                        tinymce: {
                            toolbar1: 'bold,italic,underline,link',
                            toolbar2: '',
                        },
                        quicktags: true,
                        media_buttons: false
                    });
                    
                    // Copy content to editor
                    if (content) {
                        const editor = tinyMCE.get(textareaId);
                        if (editor) {
                            editor.setContent(content);
                        }
                    }
                    
                    // Hide original textarea
                    $row.find('.pk-tooltip-content-editor').hide();
                }
            });
        }
        
        // Delay to ensure wp.editor is available
        setTimeout(initExistingEditors, 500);
        
        // Add new tooltip
        $('#pk-add-tooltip').on('click', function() {
            console.log('➕ Adding new tooltip, index:', tooltipIndex);
            
            // Get template
            const template = $('#pk-tooltip-template').html();
            
            // Replace placeholders
            let newTooltip = template.replace(/\{\{INDEX\}\}/g, tooltipIndex);
            newTooltip = newTooltip.replace(/\{\{INDEX_PLUS_1\}\}/g, tooltipIndex + 1);
            
            // Add to list
            const $newTooltip = $(newTooltip);
            $('#pk-tooltips-list').append($newTooltip);
            
            // Initialize editor for new tooltip
            const textareaId = 'tooltip-editor-' + tooltipIndex;
            
            if (typeof wp !== 'undefined' && wp.editor) {
                // Wait for DOM to update
                setTimeout(function() {
                    wp.editor.initialize(textareaId, {
                        tinymce: {
                            toolbar1: 'bold,italic,underline,link',
                            toolbar2: '',
                        },
                        quicktags: true,
                        media_buttons: false
                    });
                    
                    // Hide textarea original
                    $('#pk-tooltips-list .pk-tooltip-row[data-index="' + tooltipIndex + '"]')
                        .find('.pk-tooltip-content-editor').hide();
                }, 100);
            }
            
            tooltipIndex++;
            console.log('✅ Tooltip added, new index:', tooltipIndex);
        });
        
        // Remove tooltip
        $(document).on('click', '.pk-remove-tooltip', function() {
            if (confirm('Are you sure you want to remove this tooltip?')) {
                const $row = $(this).closest('.pk-tooltip-row');
                const index = $row.data('index');
                
                // Destroy editor if exists
                const editorId = 'tooltip-editor-' + index;
                if (typeof wp !== 'undefined' && wp.editor) {
                    wp.editor.remove(editorId);
                }
                
                $row.remove();
                console.log('🗑️ Tooltip removed');
            }
        });
        
        // Copy shortcode to clipboard
        $(document).on('click', '.pk-copy-shortcode', function() {
            const shortcode = $(this).data('shortcode');
            const $btn = $(this);
            
            // Create temp input to copy from
            const tempInput = document.createElement('input');
            tempInput.value = shortcode;
            tempInput.style.position = 'absolute';
            tempInput.style.left = '-9999px';
            document.body.appendChild(tempInput);
            tempInput.select();
            
            try {
                document.execCommand('copy');
                // Change button text temporarily
                const originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() {
                    $btn.text(originalText);
                }, 1500);
            } catch (err) {
                console.error('Copy failed:', err);
                // Fallback - show in prompt for manual copy
                prompt('Copy this URL:', shortcode);
            }
            
            document.body.removeChild(tempInput);
        });
        
        // Before saving, copy editor content to textareas
        $(document).on('submit', 'form', function() {
            $('.pk-tooltip-row').each(function(index) {
                const editorId = 'tooltip-editor-' + index;
                const $textarea = $(this).find('.pk-tooltip-content-editor');
                
                if (typeof tinyMCE !== 'undefined') {
                    const editor = tinyMCE.get(editorId);
                    if (editor) {
                        $textarea.val(editor.getContent());
                    }
                }
            });
        });
    });
    </script>
    
    <?php
}

/**
 * Render a tooltip row
 */
function pk_render_tooltip_row($index, $tooltip) {
    $editor_id = 'tooltip-editor-' . $index;
    $tooltip_id = (string)($index + 1);
    $content = isset($tooltip['content']) ? $tooltip['content'] : '';
    ?>
    <div class="pk-tooltip-row" data-index="<?php echo $index; ?>">
        <div class="pk-tooltip-actions">
            <button type="button" class="button button-small pk-remove-tooltip">✕ Remove</button>
        </div>
        
        <h4>Tooltip #<span class="tooltip-number"><?php echo $index + 1; ?></span></h4>
        
        <div class="pk-tooltip-field">
            <label>Link URL</label>
            <div class="pk-shortcode-display">
                <code>#tooltip-<?php echo $tooltip_id; ?></code>
                <button type="button" class="button button-small pk-copy-shortcode" data-shortcode='#tooltip-<?php echo $tooltip_id; ?>'>Copy</button>
            </div>
            <p class="description">In Gutenberg: Select text → Insert link → Paste this URL</p>
        </div>
        
        <div class="pk-tooltip-field">
            <?php
            wp_editor($content, $editor_id, [
                'textarea_name' => 'tooltips_data[' . $index . '][content]',
                'media_buttons' => false,
                'textarea_rows' => 4,
                'quicktags' => true,
                'tinymce' => [
                    'toolbar1' => 'bold,italic,underline,link',
                    'toolbar2' => '',
                ],
            ]);
            ?>
        </div>
    </div>
    <?php
}
