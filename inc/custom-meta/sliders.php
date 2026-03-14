<?php
/**
 * ===================================================================
 * SLIDERS CPT — META BOX RENDER
 * ===================================================================
 *
 * Slider types:
 *   - cpt          : Pulls latest posts from any CPT (uses PostCard in frontend)
 *   - testimonials : Repeatable slides with name, role, quote, photo
 *   - logos        : Repeatable slides with image + optional link
 *   - images       : Repeatable slides with image + optional caption
 *   - custom       : Repeatable slides with title, text, image, link
 *
 * Meta fields:
 *   _slider_type    (string)  – one of the types above
 *   _slider_config  (array)   – Swiper options (autoplay, speed, perView, loop, nav, pagination)
 *   _slider_source  (array)   – CPT source settings (postType, perPage, order)  [only for type=cpt]
 *   _slider_slides  (array)   – Array of slide objects                          [for all other types]
 *
 * Related files:
 *   sliders-save.php      – Save logic
 *   sliders-rest.php      – REST API field + image resolver
 *   sliders-shortcode.php – [slider id="X"] shortcode
 *   js/admin-slider-metabox.js – Client-side logic (cloneNode, reindex, media uploader)
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/sliders-save.php';


// ===================================================================
// META BOX REGISTRATION
// ===================================================================

function pk_slider_register_meta_boxes() {
    add_meta_box(
        'pk_slider_config',
        __('Slider Configuration', 'penkode-headless'),
        'pk_slider_render_config_metabox',
        'sliders',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'pk_slider_register_meta_boxes');


// ===================================================================
// RENDER META BOX
// ===================================================================

function pk_slider_render_config_metabox($post) {
    wp_nonce_field('pk_slider_save', 'pk_slider_nonce');

    $type    = get_post_meta($post->ID, '_slider_type', true) ?: 'cpt';
    $config  = get_post_meta($post->ID, '_slider_config', true) ?: [];
    $source  = get_post_meta($post->ID, '_slider_source', true) ?: [];
    $slides  = get_post_meta($post->ID, '_slider_slides', true) ?: [];

    $config = wp_parse_args($config, [
        'autoplay'     => 1,
        'speed'        => 3000,
        'perView'      => 3,
        'loop'         => 1,
        'navigation'   => 0,
        'pagination'   => 0,
        'fullWidth'    => 0,
        'displayMode'  => 'contain',
        'gap'          => 20,
        'grayscale'    => 0,
        'opacity'      => 100,
    ]);

    $source = wp_parse_args($source, [
        'postType' => 'post',
        'perPage'  => 6,
        'order'    => 'desc',
    ]);

    $all_types = get_post_types(['public' => true], 'objects');
    ?>

    <!-- Type Selector -->
    <div class="pk-slider-field">
        <label for="pk_slider_type"><strong><?php _e('Content Type', 'penkode-headless'); ?></strong></label>
        <select id="pk_slider_type" name="pk_slider_type" class="widefat">
            <option value="cpt" <?php selected($type, 'cpt'); ?>><?php _e('CPT (Existing Posts)', 'penkode-headless'); ?></option>
            <option value="testimonials" <?php selected($type, 'testimonials'); ?>><?php _e('Testimonials', 'penkode-headless'); ?></option>
            <option value="media" <?php selected($type, 'media'); ?>><?php _e('Media (Images / Logos)', 'penkode-headless'); ?></option>
            <option value="custom" <?php selected($type, 'custom'); ?>><?php _e('Custom (Free Content)', 'penkode-headless'); ?></option>
        </select>
    </div>

    <hr>

    <!-- Swiper Configuration -->
    <h3><?php _e('Swiper Settings', 'penkode-headless'); ?></h3>
    <div class="pk-slider-grid">
        <label>
            <input type="checkbox" name="pk_slider_config[autoplay]" value="1" <?php checked($config['autoplay'], 1); ?>>
            <?php _e('Autoplay', 'penkode-headless'); ?>
        </label>
        <label>
            <input type="checkbox" name="pk_slider_config[loop]" value="1" <?php checked($config['loop'], 1); ?>>
            <?php _e('Loop', 'penkode-headless'); ?>
        </label>
        <label>
            <input type="checkbox" name="pk_slider_config[navigation]" value="1" <?php checked($config['navigation'], 1); ?>>
            <?php _e('Navigation Arrows', 'penkode-headless'); ?>
        </label>
        <label>
            <input type="checkbox" name="pk_slider_config[pagination]" value="1" <?php checked($config['pagination'], 1); ?>>
            <?php _e('Pagination Dots', 'penkode-headless'); ?>
        </label>
        <label>
            <input type="checkbox" name="pk_slider_config[fullWidth]" value="1" <?php checked($config['fullWidth'], 1); ?>>
            <?php _e('Full Width (100vw)', 'penkode-headless'); ?>
        </label>
        <div>
            <label for="pk_slider_speed"><?php _e('Speed (ms)', 'penkode-headless'); ?></label>
            <input type="number" id="pk_slider_speed" name="pk_slider_config[speed]" value="<?php echo esc_attr($config['speed']); ?>" min="500" step="500" style="width:100px;">
        </div>
        <div>
            <label for="pk_slider_perView"><?php _e('Slides per View', 'penkode-headless'); ?></label>
            <input type="number" id="pk_slider_perView" name="pk_slider_config[perView]" value="<?php echo esc_attr($config['perView']); ?>" min="1" max="10" style="width:60px;">
        </div>
        <div>
            <label for="pk_slider_gap"><?php _e('Gap (px)', 'penkode-headless'); ?></label>
            <input type="number" id="pk_slider_gap" name="pk_slider_config[gap]" value="<?php echo esc_attr($config['gap']); ?>" min="0" max="100" step="5" style="width:60px;">
        </div>
        <div>
            <label for="pk_slider_displayMode"><?php _e('Image Mode', 'penkode-headless'); ?></label>
            <select id="pk_slider_displayMode" name="pk_slider_config[displayMode]">
                <option value="contain" <?php selected($config['displayMode'], 'contain'); ?>><?php _e('Contain (logos)', 'penkode-headless'); ?></option>
                <option value="cover" <?php selected($config['displayMode'], 'cover'); ?>><?php _e('Cover (photos)', 'penkode-headless'); ?></option>
            </select>
        </div>
        <label>
            <input type="checkbox" name="pk_slider_config[grayscale]" value="1" <?php checked($config['grayscale'], 1); ?>>
            <?php _e('Grayscale', 'penkode-headless'); ?>
        </label>
        <div>
            <label for="pk_slider_opacity"><?php _e('Opacity (%)', 'penkode-headless'); ?></label>
            <input type="number" id="pk_slider_opacity" name="pk_slider_config[opacity]" value="<?php echo esc_attr($config['opacity']); ?>" min="10" max="100" step="5" style="width:60px;">
        </div>
    </div>

    <hr>

    <!-- ============================================================ -->
    <!-- CPT Source (only for type=cpt)                               -->
    <!-- ============================================================ -->
    <div id="pk-slider-source" class="pk-slider-type-panel" data-type="cpt" <?php echo $type !== 'cpt' ? 'style="display:none;"' : ''; ?>>
        <h3><?php _e('Data Source', 'penkode-headless'); ?></h3>
        <div class="pk-slider-grid">
            <div>
                <label for="pk_slider_source_pt"><strong><?php _e('Post Type', 'penkode-headless'); ?></strong></label>
                <select id="pk_slider_source_pt" name="pk_slider_source[postType]" class="widefat">
                    <?php foreach ($all_types as $pt) :
                        if ($pt->name === 'attachment') continue;
                    ?>
                        <option value="<?php echo esc_attr($pt->name); ?>" <?php selected($source['postType'], $pt->name); ?>>
                            <?php echo esc_html($pt->labels->name); ?> (<?php echo esc_html($pt->name); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="pk_slider_source_pp"><?php _e('Per Page', 'penkode-headless'); ?></label>
                <input type="number" id="pk_slider_source_pp" name="pk_slider_source[perPage]" value="<?php echo esc_attr($source['perPage']); ?>" min="1" max="50" style="width:60px;">
            </div>
            <div>
                <label for="pk_slider_source_order"><?php _e('Order', 'penkode-headless'); ?></label>
                <select id="pk_slider_source_order" name="pk_slider_source[order]">
                    <option value="desc" <?php selected($source['order'], 'desc'); ?>><?php _e('Newest first', 'penkode-headless'); ?></option>
                    <option value="asc" <?php selected($source['order'], 'asc'); ?>><?php _e('Oldest first', 'penkode-headless'); ?></option>
                    <option value="rand" <?php selected($source['order'], 'rand'); ?>><?php _e('Random', 'penkode-headless'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Testimonials Panel                                           -->
    <!-- ============================================================ -->
    <div id="pk-slider-testimonials" class="pk-slider-type-panel" data-type="testimonials" <?php echo $type !== 'testimonials' ? 'style="display:none;"' : ''; ?>>
        <h3><?php _e('Testimonial Slides', 'penkode-headless'); ?></h3>
        <div class="pk-slider-repeater" data-type="testimonials">
            <?php
            $testimonials = ($type === 'testimonials' && !empty($slides)) ? $slides : [];
            if (empty($testimonials)) $testimonials = [['name' => '', 'role' => '', 'text' => '', 'image_id' => '']];
            foreach ($testimonials as $i => $slide) :
                pk_slider_render_slide('testimonials', $i, $slide);
            endforeach;
            ?>
        </div>
        <button type="button" class="button pk-add-slide" data-type="testimonials"><?php _e('+ Add Testimonial', 'penkode-headless'); ?></button>
    </div>

    <!-- ============================================================ -->
    <!-- Media Panel (images, logos, any visual media)                 -->
    <!-- ============================================================ -->
    <div id="pk-slider-media" class="pk-slider-type-panel" data-type="media" <?php echo $type !== 'media' ? 'style="display:none;"' : ''; ?>>
        <h3><?php _e('Media Slides', 'penkode-headless'); ?></h3>
        <div class="pk-slider-repeater" data-type="media">
            <?php
            $media = ($type === 'media' && !empty($slides)) ? $slides : [];
            if (empty($media)) $media = [['image_id' => '', 'link' => '', 'alt' => '', 'caption' => '']];
            foreach ($media as $i => $slide) :
                pk_slider_render_slide('media', $i, $slide);
            endforeach;
            ?>
        </div>
        <button type="button" class="button pk-add-slide" data-type="media"><?php _e('+ Add Media', 'penkode-headless'); ?></button>
    </div>

    <!-- ============================================================ -->
    <!-- Custom Panel                                                  -->
    <!-- ============================================================ -->
    <div id="pk-slider-custom" class="pk-slider-type-panel" data-type="custom" <?php echo $type !== 'custom' ? 'style="display:none;"' : ''; ?>>
        <h3><?php _e('Custom Slides', 'penkode-headless'); ?></h3>
        <div class="pk-slider-repeater" data-type="custom">
            <?php
            $custom = ($type === 'custom' && !empty($slides)) ? $slides : [];
            if (empty($custom)) $custom = [['title' => '', 'text' => '', 'image_id' => '', 'link' => '']];
            foreach ($custom as $i => $slide) :
                pk_slider_render_slide('custom', $i, $slide);
            endforeach;
            ?>
        </div>
        <button type="button" class="button pk-add-slide" data-type="custom"><?php _e('+ Add Slide', 'penkode-headless'); ?></button>
    </div>

    <!-- ============================================================ -->
    <!-- TEMPLATES (used by JS cloneNode — single source of truth)    -->
    <!-- ============================================================ -->
    <template id="pk-tpl-testimonials">
        <?php pk_slider_render_slide('testimonials', '__IDX__', []); ?>
    </template>
    <template id="pk-tpl-media">
        <?php pk_slider_render_slide('media', '__IDX__', []); ?>
    </template>
    <template id="pk-tpl-custom">
        <?php pk_slider_render_slide('custom', '__IDX__', []); ?>
    </template>

    <?php
}


// ===================================================================
// RENDER SINGLE SLIDE (used by both loops AND <template> elements)
// ===================================================================

function pk_slider_render_slide($type, $index, $slide) {
    $idx = $index;
    $preview = '';
    if (!empty($slide['image_id'])) {
        $img = wp_get_attachment_image_url(absint($slide['image_id']), 'thumbnail');
        if ($img) {
            $preview = '<img src="' . esc_url($img) . '" style="max-height:50px;">';
        }
    }
    ?>
    <div class="pk-slider-slide" data-index="<?php echo esc_attr($idx); ?>">
        <div class="pk-slide-header">
            <strong><?php printf(__('Slide %s', 'penkode-headless'), is_numeric($idx) ? $idx + 1 : '#'); ?></strong>
            <button type="button" class="button pk-remove-slide">&times;</button>
        </div>

        <?php if ($type === 'testimonials') : ?>
        <div class="pk-slide-fields">
            <input type="text" name="pk_slider_slides[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($slide['name'] ?? ''); ?>" placeholder="<?php esc_attr_e('Name', 'penkode-headless'); ?>" class="widefat">
            <input type="text" name="pk_slider_slides[<?php echo $idx; ?>][role]" value="<?php echo esc_attr($slide['role'] ?? ''); ?>" placeholder="<?php esc_attr_e('Role / Company', 'penkode-headless'); ?>" class="widefat">
            <textarea name="pk_slider_slides[<?php echo $idx; ?>][text]" placeholder="<?php esc_attr_e('Testimonial text...', 'penkode-headless'); ?>" class="widefat" rows="3"><?php echo esc_textarea($slide['text'] ?? ''); ?></textarea>
            <div class="pk-slide-image">
                <input type="hidden" name="pk_slider_slides[<?php echo $idx; ?>][image_id]" value="<?php echo esc_attr($slide['image_id'] ?? ''); ?>" class="pk-slide-image-id">
                <button type="button" class="button pk-slide-media-btn" data-title="<?php esc_attr_e('Select Photo', 'penkode-headless'); ?>"><?php _e('Select Photo', 'penkode-headless'); ?></button>
                <span class="pk-slide-image-preview"><?php echo $preview; ?></span>
            </div>
        </div>

        <?php elseif ($type === 'media') : ?>
        <div class="pk-slide-fields pk-slide-fields-inline">
            <div class="pk-slide-image">
                <input type="hidden" name="pk_slider_slides[<?php echo $idx; ?>][image_id]" value="<?php echo esc_attr($slide['image_id'] ?? ''); ?>" class="pk-slide-image-id">
                <button type="button" class="button pk-slide-media-btn" data-title="<?php esc_attr_e('Select Image', 'penkode-headless'); ?>"><?php _e('Select Image', 'penkode-headless'); ?></button>
                <span class="pk-slide-image-preview"><?php echo $preview; ?></span>
            </div>
            <input type="text" name="pk_slider_slides[<?php echo $idx; ?>][alt]" value="<?php echo esc_attr($slide['alt'] ?? ''); ?>" placeholder="<?php esc_attr_e('Alt text', 'penkode-headless'); ?>" class="widefat">
            <input type="text" name="pk_slider_slides[<?php echo $idx; ?>][caption]" value="<?php echo esc_attr($slide['caption'] ?? ''); ?>" placeholder="<?php esc_attr_e('Caption (optional)', 'penkode-headless'); ?>" class="widefat">
            <input type="url" name="pk_slider_slides[<?php echo $idx; ?>][link]" value="<?php echo esc_url($slide['link'] ?? ''); ?>" placeholder="<?php esc_attr_e('Link URL (optional)', 'penkode-headless'); ?>" class="widefat">
        </div>

        <?php elseif ($type === 'custom') : ?>
        <div class="pk-slide-fields">
            <input type="text" name="pk_slider_slides[<?php echo $idx; ?>][title]" value="<?php echo esc_attr($slide['title'] ?? ''); ?>" placeholder="<?php esc_attr_e('Title', 'penkode-headless'); ?>" class="widefat">
            <textarea name="pk_slider_slides[<?php echo $idx; ?>][text]" placeholder="<?php esc_attr_e('Content...', 'penkode-headless'); ?>" class="widefat" rows="3"><?php echo esc_textarea($slide['text'] ?? ''); ?></textarea>
            <div class="pk-slide-image">
                <input type="hidden" name="pk_slider_slides[<?php echo $idx; ?>][image_id]" value="<?php echo esc_attr($slide['image_id'] ?? ''); ?>" class="pk-slide-image-id">
                <button type="button" class="button pk-slide-media-btn" data-title="<?php esc_attr_e('Select Image', 'penkode-headless'); ?>"><?php _e('Select Image', 'penkode-headless'); ?></button>
                <span class="pk-slide-image-preview"><?php echo $preview; ?></span>
            </div>
            <input type="url" name="pk_slider_slides[<?php echo $idx; ?>][link]" value="<?php echo esc_url($slide['link'] ?? ''); ?>" placeholder="<?php esc_attr_e('Link URL (optional)', 'penkode-headless'); ?>" class="widefat">
        </div>
        <?php endif; ?>
    </div>
    <?php
}
