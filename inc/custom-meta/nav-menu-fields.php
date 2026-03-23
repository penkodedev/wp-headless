<?php
/**
 * ===================================================================
 * NAV MENU CUSTOM FIELDS
 * ===================================================================
 * Adds image field to menu items for Mega Menu support.
 * Uses wp_nav_menu_item_custom_fields (WP 5.4+) and wp_update_nav_menu_item.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ===================================================================
// ENQUEUE MEDIA UPLOADER (only on Appearance > Menus)
// ===================================================================
add_action('admin_enqueue_scripts', 'pk_nav_menu_enqueue_media');

function pk_nav_menu_enqueue_media($hook) {
    if ('nav-menus.php' !== $hook) {
        return;
    }
    wp_enqueue_media();
    wp_add_inline_script('jquery', "
        jQuery(document).ready(function($) {
            $(document).on('click', '.pk-menu-item-image-select', function(e) {
                e.preventDefault();
                var \$btn = $(this);
                var \$input = \$btn.siblings('input[type=\"hidden\"]');
                var frame = wp.media({
                    library: { type: 'image' },
                    multiple: false
                });
                frame.on('select', function() {
                    var att = frame.state().get('selection').first().toJSON();
                    \$input.val(att.id);
                    \$btn.siblings('.pk-menu-item-image-preview').html('<img src=\"' + att.sizes.thumbnail.url + '\" alt=\"\" style=\"max-width:60px;height:auto;\">').show();
                });
                frame.open();
            });
            $(document).on('click', '.pk-menu-item-image-remove', function(e) {
                e.preventDefault();
                var \$wrap = $(this).closest('.pk-menu-item-image-wrap');
                \$wrap.find('input[type=\"hidden\"]').val('');
                \$wrap.find('.pk-menu-item-image-preview').html('').hide();
            });
        });
    ");
}

// ===================================================================
// ADD IMAGE FIELD TO MENU ITEM
// ===================================================================
add_action('wp_nav_menu_item_custom_fields', 'pk_nav_menu_item_image_field', 10, 5);

function pk_nav_menu_item_image_field($item_id, $item, $depth, $args, $current_object_id) {
    $image_id = get_post_meta($item_id, '_menu_item_image_id', true);
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
    ?>
    <p class="field-image description description-wide pk-menu-item-image-wrap">
        <label class="pk-appearance-desc">
            <?php _e('Image (Mega Menu)', 'penkode-headless'); ?>
        </label>
        <span class="pk-menu-item-image-preview" style="<?php echo $image_url ? '' : 'display:none;'; ?>">
            <?php if ($image_url) : ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width:60px;height:auto;">
            <?php endif; ?>
        </span>
        <input type="hidden" name="menu-item-image[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($image_id); ?>">
        <button type="button" class="button button-small pk-menu-item-image-select"><?php _e('Select image', 'penkode-headless'); ?></button>
        <button type="button" class="button button-small pk-menu-item-image-remove"><?php _e('Remove', 'penkode-headless'); ?></button>
    </p>
    <?php
}

// ===================================================================
// SAVE IMAGE FIELD
// ===================================================================
add_action('wp_update_nav_menu_item', 'pk_save_nav_menu_item_image', 10, 3);

function pk_save_nav_menu_item_image($menu_id, $menu_item_db_id, $args) {
    if (!current_user_can('edit_theme_options')) {
        return;
    }
    if (!isset($_POST['menu-item-image']) || !is_array($_POST['menu-item-image'])) {
        return;
    }
    $image_id = isset($_POST['menu-item-image'][$menu_item_db_id])
        ? absint($_POST['menu-item-image'][$menu_item_db_id])
        : 0;
    update_post_meta($menu_item_db_id, '_menu_item_image_id', $image_id);
}
