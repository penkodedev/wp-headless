<?php
/**
 * ===================================================================
 * COUNTER STATS SETTINGS
 * ===================================================================
 * Repeatable groups of animated counters, managed from Custom Settings.
 *
 * Structure:
 * - Each group has a title (internal) and repeatable counter items
 * - Each item has: number + description
 * - Shortcode: [stats id="1"] (1-based index)
 *
 * API: /wp-json/custom/v1/stats/{id}
 */

if (!defined('ABSPATH')) {
    exit;
}

// ===================================================================
// REGISTER SETTINGS
// ===================================================================
add_action('admin_init', 'pk_register_counter_stats_settings');

function pk_register_counter_stats_settings() {
    register_setting('custom-settings', 'counter_stats_data', [
        'type' => 'array',
        'sanitize_callback' => 'pk_sanitize_counter_stats',
        'default' => [],
    ]);
}

function pk_sanitize_counter_stats($value) {
    if (!is_array($value)) {
        return [];
    }

    $sanitized = [];
    foreach ($value as $group) {
        if (empty($group['items']) || !is_array($group['items'])) {
            continue;
        }

        $clean_items = [];
        foreach ($group['items'] as $item) {
            $number = isset($item['number']) ? $item['number'] : '';
            $label  = isset($item['label'])  ? trim($item['label']) : '';

            if ($number !== '' || $label !== '') {
                $clean_items[] = [
                    'number' => floatval($number),
                    'label'  => sanitize_text_field($label),
                ];
            }
        }

        if (!empty($clean_items)) {
            $sanitized[] = [
                'title'    => sanitize_text_field($group['title'] ?? ''),
                'duration' => max(500, min(5000, intval($group['duration'] ?? 2000))),
                'items'    => $clean_items,
            ];
        }
    }

    return $sanitized;
}

// ===================================================================
// RENDER COUNTER STATS SETTINGS
// ===================================================================
function pk_render_counter_stats_settings() {
    $groups = get_option('counter_stats_data', []);
    if (!is_array($groups)) {
        $groups = [];
    }
    ?>
    <div id="pk-counter-stats-container">
        <div id="pk-stats-groups">
            <?php foreach ($groups as $gi => $group) :
                pk_render_stats_group($gi, $group);
            endforeach; ?>
        </div>

        <button type="button" class="button button-primary" id="pk-add-stats-group">
            + <?php _e('Add Counter Group', 'penkode-headless'); ?>
        </button>
    </div>

    <template id="pk-tpl-stats-group">
        <?php pk_render_stats_group('__GI__', ['title' => '', 'items' => []]); ?>
    </template>

    <template id="pk-tpl-stats-item">
        <?php pk_render_stats_item('__GI__', '__II__', []); ?>
    </template>

    <script>
    jQuery(document).ready(function($) {
        let groupIndex = <?php echo count($groups); ?>;

        $('#pk-add-stats-group').on('click', function() {
            const tpl = document.getElementById('pk-tpl-stats-group');
            const html = tpl.innerHTML.replace(/__GI__/g, groupIndex);
            $('#pk-stats-groups').append(html);
            groupIndex++;
        });

        $(document).on('click', '.pk-add-stats-item', function() {
            const $group = $(this).closest('.pk-stats-group');
            const gi = $group.data('group-index');
            const $list = $group.find('.pk-stats-items');
            const ii = $list.children().length;
            const tpl = document.getElementById('pk-tpl-stats-item');
            const html = tpl.innerHTML.replace(/__GI__/g, gi).replace(/__II__/g, ii);
            $list.append(html);
        });

        $(document).on('click', '.pk-remove-stats-group', function() {
            if (confirm('<?php echo esc_js(__('Remove this entire counter group?', 'penkode-headless')); ?>')) {
                $(this).closest('.pk-stats-group').remove();
            }
        });

        $(document).on('click', '.pk-remove-stats-item', function() {
            $(this).closest('.pk-stats-item').remove();
        });

        $(document).on('input', '.pk-range-slider', function() {
            $(this).next('.pk-range-value').text((this.value / 1000) + 's');
        });

        $(document).on('click', '.pk-copy-stats-shortcode', function() {
            const sc = $(this).data('shortcode');
            const $btn = $(this);
            navigator.clipboard.writeText(sc).then(function() {
                const orig = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() { $btn.text(orig); }, 1500);
            });
        });
    });
    </script>
    <?php
}

// ===================================================================
// RENDER SINGLE GROUP
// ===================================================================
function pk_render_stats_group($gi, $group) {
    $title    = $group['title'] ?? '';
    $duration = intval($group['duration'] ?? 2000);
    $items    = $group['items'] ?? [];
    $sc_id    = is_numeric($gi) ? $gi + 1 : '?';
    ?>
    <div class="pk-stats-group" data-group-index="<?php echo esc_attr($gi); ?>">
        <div class="pk-stats-group-header">
            <h3>
                <input type="text" name="counter_stats_data[<?php echo $gi; ?>][title]" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('Group name (internal)', 'penkode-headless'); ?>" class="widefat">
            </h3>
            <div class="pk-stats-group-actions">
                <div class="pk-shortcode-display">
                    <code>[stats id="<?php echo esc_attr($sc_id); ?>"]</code>
                    <button type="button" class="button button-small pk-copy-stats-shortcode" data-shortcode='[stats id="<?php echo esc_attr($sc_id); ?>"]'>Copy</button>
                </div>
                <button type="button" class="button pk-remove-stats-group">&times;</button>
            </div>
        </div>

        <div class="pk-stats-duration">
            <label><?php _e('Animation speed', 'penkode-headless'); ?>
                <input type="range" name="counter_stats_data[<?php echo $gi; ?>][duration]" value="<?php echo esc_attr($duration); ?>" min="500" max="5000" step="100" class="pk-range-slider">
                <span class="pk-range-value"><?php echo $duration / 1000; ?>s</span>
            </label>
        </div>

        <div class="pk-stats-items">
            <?php foreach ($items as $ii => $item) :
                pk_render_stats_item($gi, $ii, $item);
            endforeach; ?>
        </div>

        <button type="button" class="button pk-add-stats-item">+ <?php _e('Add Counter', 'penkode-headless'); ?></button>
    </div>
    <?php
}

// ===================================================================
// RENDER SINGLE ITEM
// ===================================================================
function pk_render_stats_item($gi, $ii, $item) {
    ?>
    <div class="pk-stats-item pk-slide-fields-inline">
        <label class="pk-stats-field-label">
            <small><?php _e('Number', 'penkode-headless'); ?></small>
            <input type="number" name="counter_stats_data[<?php echo $gi; ?>][items][<?php echo $ii; ?>][number]" value="<?php echo esc_attr($item['number'] ?? ''); ?>" placeholder="150" step="any" style="width:100px;">
        </label>
        <label class="pk-stats-field-label pk-stats-field-label--grow">
            <small><?php _e('Description', 'penkode-headless'); ?></small>
            <input type="text" name="counter_stats_data[<?php echo $gi; ?>][items][<?php echo $ii; ?>][label]" value="<?php echo esc_attr($item['label'] ?? ''); ?>" placeholder="<?php esc_attr_e('e.g. Projects completed', 'penkode-headless'); ?>" class="widefat">
        </label>
        <button type="button" class="button pk-remove-stats-item" title="Remove">&times;</button>
    </div>
    <?php
}
