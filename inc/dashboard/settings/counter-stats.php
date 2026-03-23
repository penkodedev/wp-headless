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
        $type  = isset($group['type']) && $group['type'] === 'countdown' ? 'countdown' : 'counter';
        $align = isset($group['align']) && in_array($group['align'], ['left', 'center', 'right'], true)
            ? $group['align']
            : 'center';

        if ($type === 'countdown') {
            $end_date = isset($group['end_date']) ? $group['end_date'] : '';
            if (empty($end_date)) {
                continue;
            }
            $sanitized[] = [
                'title'      => sanitize_text_field($group['title'] ?? ''),
                'type'       => 'countdown',
                'align'      => $align,
                'start_date' => !empty($group['start_date']) ? sanitize_text_field($group['start_date']) : '',
                'end_date'   => sanitize_text_field($end_date),
            ];
        } else {
            if (empty($group['items']) || !is_array($group['items'])) {
                continue;
            }

            $clean_items = [];
            foreach ($group['items'] as $item) {
                $number = isset($item['number']) ? $item['number'] : '';
                $label  = isset($item['label']) ? trim($item['label']) : '';
                $suffix = isset($item['suffix']) ? sanitize_text_field($item['suffix']) : '';

                if ($number !== '' || $label !== '' || $suffix !== '') {
                    $clean_items[] = [
                        'number' => floatval($number),
                        'label'  => sanitize_text_field($label),
                        'suffix' => $suffix,
                    ];
                }
            }

            if (!empty($clean_items)) {
                $sanitized[] = [
                    'title'    => sanitize_text_field($group['title'] ?? ''),
                    'type'     => 'counter',
                    'align'    => $align,
                    'duration' => max(500, min(5000, intval($group['duration'] ?? 2000))),
                    'items'    => $clean_items,
                ];
            }
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
        <div class="pk-rows-list">
            <?php foreach ($groups as $gi => $group) :
                pk_render_stats_group($gi, $group);
            endforeach; ?>
        </div>

        <button type="button" class="add-item" id="pk-add-stats-group">
            + <?php _e('Add Counter Group', 'penkode-headless'); ?>
        </button>
    </div>

    <template id="pk-tpl-stats-group">
        <?php pk_render_stats_group('__GI__', ['title' => '', 'type' => 'counter', 'align' => 'center', 'items' => []]); ?>
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
            $('.pk-rows-list').append(html);
            groupIndex++;
        });

        $(document).on('click', '.pk-row .add-item', function() {
            const $group = $(this).closest('.pk-row');
            const gi = $group.data('group-index');
            const $list = $group.find('.pk-row-items');
            const ii = $list.children().length;
            const tpl = document.getElementById('pk-tpl-stats-item');
            const html = tpl.innerHTML.replace(/__GI__/g, gi).replace(/__II__/g, ii);
            $list.append(html);
        });

        $(document).on('click', '#pk-counter-stats-container .pk-remove-row', function() {
            if (confirm('<?php echo esc_js(__('Remove this entire counter group?', 'penkode-headless')); ?>')) {
                $(this).closest('.pk-row').remove();
            }
        });

        $(document).on('click', '.pk-remove-row-item', function() {
            $(this).closest('.pk-row-item').remove();
        });

        $(document).on('input', '.pk-range-slider', function() {
            $(this).next('.pk-range-value').text((this.value / 1000) + 's');
        });

        $(document).on('click', '#pk-counter-stats-container .pk-copy-shortcode', function() {
            const sc = $(this).data('shortcode');
            const $btn = $(this);
            navigator.clipboard.writeText(sc).then(function() {
                const orig = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() { $btn.text(orig); }, 1500);
            });
        });

        function toggleStatsTypeFields($group) {
            const type = $group.find('.pk-type-select').val();
            $group.find('.pk-row-fields--counter').toggle(type === 'counter');
            $group.find('.pk-row-fields--countdown').toggle(type === 'countdown');
        }

        $(document).on('change', '.pk-type-select', function() {
            toggleStatsTypeFields($(this).closest('.pk-row'));
        });

        $('#pk-counter-stats-container .pk-row').each(function() { toggleStatsTypeFields($(this)); });
    });
    </script>
    <?php
}

// ===================================================================
// RENDER SINGLE GROUP
// ===================================================================
function pk_render_stats_group($gi, $group) {
    $title      = $group['title'] ?? '';
    $type       = isset($group['type']) && $group['type'] === 'countdown' ? 'countdown' : 'counter';
    $align      = isset($group['align']) && in_array($group['align'], ['left', 'center', 'right'], true) ? $group['align'] : 'center';
    $duration   = intval($group['duration'] ?? 2000);
    $items      = $group['items'] ?? [];
    $start_date = $group['start_date'] ?? '';
    $end_date   = $group['end_date'] ?? '';
    $sc_id      = is_numeric($gi) ? $gi + 1 : '?';
    ?>
    <div class="pk-row" data-group-index="<?php echo esc_attr($gi); ?>">
        <div class="pk-row-header">
            <h3>
                <input type="text" name="counter_stats_data[<?php echo $gi; ?>][title]" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('Group name (internal)', 'penkode-headless'); ?>" class="widefat">
            </h3>
            <div class="pk-shortcode-display">
                <code>[stats id="<?php echo esc_attr($sc_id); ?>"]</code>
                <button type="button" class="button button-small pk-copy-shortcode" data-shortcode='[stats id="<?php echo esc_attr($sc_id); ?>"]'>Copy</button>
            </div>
        </div>

        <div class="pk-field pk-field--type">
            <label><?php _e('Type', 'penkode-headless'); ?></label>
            <select name="counter_stats_data[<?php echo $gi; ?>][type]" class="pk-type-select">
                <option value="counter" <?php selected($type, 'counter'); ?>><?php _e('Counter (numbers)', 'penkode-headless'); ?></option>
                <option value="countdown" <?php selected($type, 'countdown'); ?>><?php _e('Countdown (timer to date)', 'penkode-headless'); ?></option>
            </select>
        </div>

        <div class="pk-field pk-field--align">
            <label><?php _e('Horizontal position', 'penkode-headless'); ?></label>
            <select name="counter_stats_data[<?php echo $gi; ?>][align]">
                <option value="left" <?php selected($align, 'left'); ?>><?php _e('Left', 'penkode-headless'); ?></option>
                <option value="center" <?php selected($align, 'center'); ?>><?php _e('Center', 'penkode-headless'); ?></option>
                <option value="right" <?php selected($align, 'right'); ?>><?php _e('Right', 'penkode-headless'); ?></option>
            </select>
        </div>

        <div class="pk-row-fields--counter" style="<?php echo $type === 'countdown' ? 'display:none' : ''; ?>">
            <div class="pk-field pk-field--duration">
                <label><?php _e('Animation speed', 'penkode-headless'); ?>
                    <input type="range" name="counter_stats_data[<?php echo $gi; ?>][duration]" value="<?php echo esc_attr($duration); ?>" min="500" max="5000" step="100" class="pk-range-slider">
                    <span class="pk-range-value"><?php echo $duration / 1000; ?>s</span>
                </label>
            </div>

            <div class="pk-row-items">
                <?php foreach ($items as $ii => $item) :
                    pk_render_stats_item($gi, $ii, $item);
                endforeach; ?>
            </div>

            <button type="button" class="button add-item">+ <?php _e('Add Counter', 'penkode-headless'); ?></button>
        </div>

        <div class="pk-row-fields--countdown" style="<?php echo $type === 'counter' ? 'display:none' : ''; ?>">
            <div class="pk-field pk-field--datetime">
                <label class="pk-field-label">
                    <small><?php _e('Start date (optional)', 'penkode-headless'); ?></small>
                    <input type="datetime-local" name="counter_stats_data[<?php echo $gi; ?>][start_date]" value="<?php echo esc_attr($start_date); ?>" class="widefat">
                </label>
                <p class="description"><?php _e('Leave empty to count from now.', 'penkode-headless'); ?></p>
            </div>
            <div class="pk-field pk-field--datetime">
                <label class="pk-field-label">
                    <small><?php _e('End date (required)', 'penkode-headless'); ?></small>
                    <input type="datetime-local" name="counter_stats_data[<?php echo $gi; ?>][end_date]" value="<?php echo esc_attr($end_date); ?>" class="widefat">
                </label>
            </div>
        </div>

        <div class="pk-row-actions">
            <button type="button" class="button button-small pk-remove-row">✕ <?php _e('Remove', 'penkode-headless'); ?></button>
        </div>
    </div>
    <?php
}

// ===================================================================
// RENDER SINGLE ITEM
// ===================================================================
function pk_render_stats_item($gi, $ii, $item) {
    ?>
    <div class="pk-row-item pk-slide-fields-inline">
        <label class="pk-field-label">
            <small><?php _e('Number', 'penkode-headless'); ?></small>
            <input type="number" name="counter_stats_data[<?php echo $gi; ?>][items][<?php echo $ii; ?>][number]" value="<?php echo esc_attr($item['number'] ?? ''); ?>" placeholder="150" step="any" style="width:100px;">
        </label>
        <label class="pk-field-label" style="width:60px;">
            <small><?php _e('Suffix', 'penkode-headless'); ?></small>
            <input type="text" name="counter_stats_data[<?php echo $gi; ?>][items][<?php echo $ii; ?>][suffix]" value="<?php echo esc_attr($item['suffix'] ?? ''); ?>" placeholder="%, €" maxlength="10" style="width:60px;">
        </label>
        <label class="pk-field-label pk-field-label--grow">
            <small><?php _e('Description', 'penkode-headless'); ?></small>
            <input type="text" name="counter_stats_data[<?php echo $gi; ?>][items][<?php echo $ii; ?>][label]" value="<?php echo esc_attr($item['label'] ?? ''); ?>" placeholder="<?php esc_attr_e('e.g. Projects completed', 'penkode-headless'); ?>" class="widefat">
        </label>
        <button type="button" class="button button-small pk-remove-row-item">✕ <?php _e('Remove', 'penkode-headless'); ?></button>
    </div>
    <?php
}
