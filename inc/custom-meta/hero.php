<?php
/**
 * ===================================================================
 * HERO CPT - CUSTOM META FIELDS
 * ===================================================================
 * MVP Fase 1: Campos básicos para slides del hero
 * 
 * Estructura:
 * - Hero Activo (checkbox)
 * - Hero Position (select: home/page/custom)
 * - Slides (repeater manual con JavaScript)
 *   - Título
 *   - Subtítulo
 *   - Texto del botón
 *   - Link del botón
 *   - Tipo de background (image/video/gradient)
 *   - Background image
 *   - Background video
 */

// Debug: Verificar que el archivo se carga
error_log('✅ Hero custom fields loaded');

// ===================================================================
// ENQUEUE MEDIA UPLOADER SCRIPTS
// ===================================================================
add_action('admin_enqueue_scripts', 'hero_enqueue_media_scripts');

function hero_enqueue_media_scripts($hook) {
    // Solo cargar en las páginas de edición del CPT hero
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }
    
    global $post_type;
    if ('hero' !== $post_type) {
        return;
    }
    
    // Cargar WordPress media uploader
    wp_enqueue_media();
    
    error_log('✅ Hero media scripts enqueued');
}

// ===================================================================
// REGISTER META BOX
// ===================================================================
add_action('add_meta_boxes', 'hero_register_meta_boxes');

function hero_register_meta_boxes() {
    error_log('🎯 Registering hero meta boxes...');
    
    add_meta_box(
        'hero_settings',
        '⚙️ Hero Settings',
        'hero_settings_callback',
        'hero',
        'normal',
        'high'
    );
    
    add_meta_box(
        'hero_slides',
        '🎬 Hero Slides',
        'hero_slides_callback',
        'hero',
        'normal',
        'high'
    );
    
    error_log('✅ Hero meta boxes registered');
}

// ===================================================================
// HERO SETTINGS META BOX
// ===================================================================
function hero_settings_callback($post) {
    wp_nonce_field('hero_settings_nonce', 'hero_settings_nonce');
    
    $hero_active = get_post_meta($post->ID, '_hero_active', true);
    $hero_position = get_post_meta($post->ID, '_hero_position', true);
    $hero_autoplay = get_post_meta($post->ID, '_hero_autoplay', true);
    $hero_interval = get_post_meta($post->ID, '_hero_interval', true) ?: '8000';
    $hero_show_arrows = get_post_meta($post->ID, '_hero_show_arrows', true);
    $hero_show_dots = get_post_meta($post->ID, '_hero_show_dots', true);
    ?>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="hero_active">Activar Hero</label>
            </th>
            <td>
                <label>
                    <input type="checkbox" 
                           name="hero_active" 
                           id="hero_active" 
                           value="1" 
                           <?php checked($hero_active, '1'); ?>>
                    Marcar para activar este hero
                </label>
                <p class="description">Si está desactivado, no se mostrará en el frontend.</p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="hero_position">Posición del Hero</label>
            </th>
            <td>
                <select name="hero_position" id="hero_position" class="regular-text">
                    <option value="home" <?php selected($hero_position, 'home'); ?>>Home Page</option>
                    <option value="page" <?php selected($hero_position, 'page'); ?>>Páginas internas</option>
                    <option value="archive" <?php selected($hero_position, 'archive'); ?>>Archivos (blog, recursos)</option>
                    <option value="custom" <?php selected($hero_position, 'custom'); ?>>Custom (usar shortcode)</option>
                </select>
                <p class="description">Dónde se mostrará este hero.</p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="hero_autoplay">Auto-play</label>
            </th>
            <td>
                <label>
                    <input type="checkbox" 
                           name="hero_autoplay" 
                           id="hero_autoplay" 
                           value="1" 
                           <?php checked($hero_autoplay, '1'); ?>>
                    Reproducción automática de slides
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="hero_interval">Intervalo (ms)</label>
            </th>
            <td>
                <input type="number" 
                       name="hero_interval" 
                       id="hero_interval" 
                       value="<?php echo esc_attr($hero_interval); ?>" 
                       class="small-text"
                       min="1000"
                       step="100">
                <p class="description">Tiempo entre slides en milisegundos (ej: 8000 = 8 segundos).</p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">Controles</th>
            <td>
                <label>
                    <input type="checkbox" 
                           name="hero_show_arrows" 
                           id="hero_show_arrows" 
                           value="1" 
                           <?php checked($hero_show_arrows, '1'); ?>>
                    Mostrar flechas de navegación
                </label>
                <br>
                <label>
                    <input type="checkbox" 
                           name="hero_show_dots" 
                           id="hero_show_dots" 
                           value="1" 
                           <?php checked($hero_show_dots, '1'); ?>>
                    Mostrar dots/indicadores
                </label>
            </td>
        </tr>
    </table>
    
    <?php
}

// ===================================================================
// HERO SLIDES META BOX (REPEATER)
// ===================================================================
function hero_slides_callback($post) {
    wp_nonce_field('hero_slides_nonce', 'hero_slides_nonce');
    
    $slides = get_post_meta($post->ID, '_hero_slides', true);
    if (!is_array($slides)) {
        $slides = [];
    }
    ?>
    
    <div id="hero-slides-container">
        <div id="hero-slides-list">
            <?php
            if (!empty($slides)) {
                foreach ($slides as $index => $slide) {
                    hero_render_slide_row($index, $slide);
                }
            }
            ?>
        </div>
        
        <button type="button" class="button button-primary" id="add-hero-slide">
            + Añadir Slide
        </button>
    </div>
    
    <!-- HIDDEN TEMPLATE FOR NEW SLIDES -->
    <script type="text/html" id="hero-slide-template">
        <div class="hero-slide-item" data-index="{{INDEX}}">
            <div class="hero-slide-actions">
                <button type="button" class="button button-small remove-hero-slide">✕ Eliminar</button>
            </div>
            
            <h4>Slide #<span class="slide-number"></span></h4>
            
            <div class="hero-slide-field">
                <label>Título</label>
                <input type="text" 
                       name="hero_slides[{{INDEX}}][title]" 
                       value=""
                       placeholder="Ej: Next WP Kit">
            </div>
            
            <div class="hero-slide-field">
                <label>Subtítulo</label>
                <textarea name="hero_slides[{{INDEX}}][subtitle]" 
                          placeholder="Ej: Un kit moderno para integrar Next.js con WordPress headless"></textarea>
            </div>
            
            <div class="hero-slide-field">
                <label>Texto del Botón</label>
                <input type="text" 
                       name="hero_slides[{{INDEX}}][button_text]" 
                       value=""
                       placeholder="Ej: Explorar Recursos">
            </div>
            
            <div class="hero-slide-field">
                <label>Link del Botón</label>
                <input type="text" 
                       name="hero_slides[{{INDEX}}][button_link]" 
                       value=""
                       placeholder="Ej: /recursos">
            </div>
            
            <div class="hero-slide-field">
                <label>Tipo de Background</label>
                <select name="hero_slides[{{INDEX}}][background_type]" 
                        class="hero-background-type">
                    <option value="image" selected>Imagen</option>
                    <option value="video">Video</option>
                    <option value="gradient">Gradiente</option>
                </select>
            </div>
            
            <!-- IMAGE BACKGROUND -->
            <div class="hero-slide-field background-type-conditional bg-image active">
                <label>Imagen de Background</label>
                <input type="text" 
                       name="hero_slides[{{INDEX}}][background_image]" 
                       value=""
                       readonly>
                <button type="button" class="button upload-hero-media" data-type="image">Seleccionar Imagen</button>
                <div class="hero-media-preview"></div>
            </div>
            
            <!-- VIDEO BACKGROUND -->
            <div class="hero-slide-field background-type-conditional bg-video">
                <label>Video de Background</label>
                <input type="text" 
                       name="hero_slides[{{INDEX}}][background_video]" 
                       value=""
                       readonly>
                <button type="button" class="button upload-hero-media" data-type="video">Seleccionar Video</button>
                <div class="hero-media-preview"></div>
                
                <label style="margin-top: 10px; display: block;">Velocidad de reproducción</label>
                <input type="number" 
                       name="hero_slides[{{INDEX}}][video_playback_rate]" 
                       value="1"
                       min="0.25"
                       max="2"
                       step="0.25"
                       style="width: 100px;">
                <p class="description">1 = normal, 0.5 = lento, 2 = rápido</p>
            </div>
            
            <!-- GRADIENT BACKGROUND -->
            <div class="hero-slide-field background-type-conditional bg-gradient">
                <label>Color 1 (Inicio del gradiente)</label>
                <input type="color" 
                       name="hero_slides[{{INDEX}}][gradient_color_1]" 
                       value="#6366f1"
                       style="width: 100px; height: 40px;">
                
                <label style="margin-top: 15px; display: block;">Color 2 (Fin del gradiente)</label>
                <input type="color" 
                       name="hero_slides[{{INDEX}}][gradient_color_2]" 
                       value="#8b5cf6"
                       style="width: 100px; height: 40px;">
                
                <label style="margin-top: 15px; display: block;">Dirección del gradiente</label>
                <select name="hero_slides[{{INDEX}}][gradient_direction]" style="width: 200px;">
                    <option value="to bottom" selected>↓ De arriba a abajo</option>
                    <option value="to top">↑ De abajo a arriba</option>
                    <option value="to right">→ De izquierda a derecha</option>
                    <option value="to left">← De derecha a izquierda</option>
                    <option value="to bottom right">↘ Diagonal abajo-derecha</option>
                    <option value="to bottom left">↙ Diagonal abajo-izquierda</option>
                    <option value="135deg">↗ Diagonal arriba-derecha</option>
                    <option value="45deg">↖ Diagonal arriba-izquierda</option>
                </select>
                
                <div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                    <strong>Vista previa del gradiente:</strong>
                    <div class="gradient-preview" style="height: 60px; margin-top: 8px; border-radius: 4px; background: linear-gradient(to bottom, #6366f1, #8b5cf6);"></div>
                </div>
            </div>
        </div>
    </script>
    
    <style>
        .hero-slide-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .hero-slide-item h4 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .hero-slide-actions {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .hero-slide-field {
            margin-bottom: 15px;
        }
        
        .hero-slide-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .hero-slide-field input[type="text"],
        .hero-slide-field textarea,
        .hero-slide-field select {
            width: 100%;
        }
        
        .hero-slide-field textarea {
            height: 80px;
        }
        
        .background-type-conditional {
            display: none;
        }
        
        .background-type-conditional.active {
            display: block;
        }
        
        .hero-media-preview {
            margin-top: 10px;
        }
        
        .hero-media-preview img,
        .hero-media-preview video {
            max-width: 200px;
            height: auto;
            display: block;
            border: 1px solid #ddd;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        let slideIndex = <?php echo count($slides); ?>;
        
        // Add new slide
        $('#add-hero-slide').on('click', function() {
            console.log('🎬 Añadiendo nuevo slide, índice:', slideIndex);
            
            // Get template
            const template = $('#hero-slide-template').html();
            
            // Replace placeholders
            const newSlide = template.replace(/\{\{INDEX\}\}/g, slideIndex);
            
            // Add to list
            const $newSlide = $(newSlide);
            $newSlide.find('.slide-number').text(slideIndex + 1);
            $('#hero-slides-list').append($newSlide);
            
            slideIndex++;
            console.log('✅ Slide añadido correctamente');
        });
        
        // Remove slide
        $(document).on('click', '.remove-hero-slide', function() {
            if (confirm('¿Seguro que quieres eliminar este slide?')) {
                $(this).closest('.hero-slide-item').remove();
                console.log('🗑️ Slide eliminado');
            }
        });
        
        // Background type change
        $(document).on('change', '.hero-background-type', function() {
            const $parent = $(this).closest('.hero-slide-item');
            const value = $(this).val();
            
            console.log('🎨 Cambiando tipo de background a:', value);
            
            $parent.find('.background-type-conditional').removeClass('active');
            $parent.find('.bg-' + value).addClass('active');
        });
        
        // Media upload
        $(document).on('click', '.upload-hero-media', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $input = $button.siblings('input');
            const $preview = $button.siblings('.hero-media-preview');
            const mediaType = $button.data('type');
            
            const frame = wp.media({
                title: 'Seleccionar ' + (mediaType === 'image' ? 'Imagen' : 'Video'),
                button: { text: 'Usar este archivo' },
                library: { type: mediaType },
                multiple: false
            });
            
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.url);
                
                if (mediaType === 'image') {
                    $preview.html('<img src="' + attachment.url + '" alt="">');
                } else {
                    $preview.html('<video src="' + attachment.url + '" controls></video>');
                }
            });
            
            frame.open();
        });
        
        // Initialize conditional fields on page load
        $('.hero-background-type').each(function() {
            const $parent = $(this).closest('.hero-slide-item');
            const value = $(this).val();
            $parent.find('.bg-' + value).addClass('active');
        });
        
        // 🎨 Gradient Preview Update
        function updateGradientPreview($container) {
            const color1 = $container.find('input[name*="[gradient_color_1]"]').val();
            const color2 = $container.find('input[name*="[gradient_color_2]"]').val();
            const direction = $container.find('select[name*="[gradient_direction]"]').val();
            const $preview = $container.find('.gradient-preview');
            
            const gradient = `linear-gradient(${direction}, ${color1}, ${color2})`;
            $preview.css('background', gradient);
            console.log('🎨 Gradiente actualizado:', gradient);
        }
        
        // Update gradient preview on color change
        $(document).on('input change', 'input[name*="[gradient_color_1]"], input[name*="[gradient_color_2]"], select[name*="[gradient_direction]"]', function() {
            const $container = $(this).closest('.bg-gradient');
            updateGradientPreview($container);
        });
        
        // Initialize gradient previews on page load
        $('.bg-gradient').each(function() {
            updateGradientPreview($(this));
        });
        
        // 🎚️ Overlay Opacity Slider Update
        $(document).on('input change', '.overlay-opacity-slider', function() {
            const $slider = $(this);
            const value = $slider.val();
            $slider.siblings('.overlay-opacity-value').text(value);
            console.log('🎚️ Opacidad overlay:', value);
        });
    });
    </script>
    
    <?php
}

// ===================================================================
// RENDER SINGLE SLIDE ROW
// ===================================================================
function hero_render_slide_row($index, $slide = []) {
    $slide = wp_parse_args($slide, [
        'title' => '',
        'title_align' => 'left',
        'subtitle' => '',
        'content_position' => 'center',
        'content_align' => 'center',
        'overlay_opacity' => '0.3',
        'ken_burns' => '0',
        'button_text' => '',
        'button_link' => '',
        'button_style' => 'default',
        'background_type' => 'image',
        'background_image' => '',
        'background_video' => '',
        'video_playback_rate' => '1',
        'gradient_color_1' => '#6366f1',
        'gradient_color_2' => '#8b5cf6',
        'gradient_direction' => 'to bottom'
    ]);
    
    echo hero_get_slide_template($index, $slide);
}

// ===================================================================
// GET SLIDE TEMPLATE (for both existing and new slides)
// ===================================================================
function hero_get_slide_template($index, $slide = []) {
    if (!is_array($slide)) {
        $slide = [];
    }
    
    $slide = wp_parse_args($slide, [
        'title' => '',
        'title_align' => 'left',
        'subtitle' => '',
        'content_position' => 'center',
        'content_align' => 'center',
        'overlay_opacity' => '0.3',
        'ken_burns' => '0',
        'button_text' => '',
        'button_link' => '',
        'button_style' => 'default',
        'background_type' => 'image',
        'background_image' => '',
        'background_video' => '',
        'video_playback_rate' => '1',
        'gradient_color_1' => '#6366f1',
        'gradient_color_2' => '#8b5cf6',
        'gradient_direction' => 'to bottom'
    ]);
    
    ob_start();
    ?>
    
    <div class="hero-slide-item" data-index="<?php echo esc_attr($index); ?>">
        <div class="hero-slide-actions">
            <button type="button" class="button button-small remove-hero-slide">✕ Eliminar</button>
        </div>
        
        <h4>Slide #<?php echo ($index + 1); ?></h4>
        
        <div class="hero-slide-field">
            <label>Título</label>
            <input type="text" 
                   name="hero_slides[<?php echo $index; ?>][title]" 
                   value="<?php echo esc_attr($slide['title']); ?>"
                   placeholder="Ej: Next WP Kit"
                   style="font-size: 16px; padding: 8px;">
        </div>
        
        <div class="hero-slide-field">
            <label>Alineación del Título</label>
            <select name="hero_slides[<?php echo $index; ?>][title_align]" style="width: 200px;">
                <option value="left" <?php selected($slide['title_align'] ?? '', 'left'); ?>>⬅ Izquierda</option>
                <option value="center" <?php selected($slide['title_align'] ?? '', 'center'); ?>>⬌ Centro</option>
                <option value="right" <?php selected($slide['title_align'] ?? '', 'right'); ?>>➡ Derecha</option>
            </select>
        </div>
        
        <div class="hero-slide-field">
            <label>Subtítulo (Editor de texto enriquecido)</label>
            <?php 
            $editor_id = 'hero_subtitle_' . $index;
            $content = $slide['subtitle'] ?? '';
            
            wp_editor($content, $editor_id, [
                'textarea_name' => 'hero_slides[' . $index . '][subtitle]',
                'textarea_rows' => 8,
                'media_buttons' => false,
                'teeny' => false,
                'wpautop' => true, // Añade tags <p> automáticamente
                'default_editor' => 'tinymce', // Fuerza modo visual
                'tinymce' => [
                    'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,forecolor,backcolor,alignleft,aligncenter,alignright,bullist,numlist,link,unlink',
                    'toolbar2' => '',
                    'height' => 200,
                ],
                'quicktags' => ['buttons' => 'strong,em,link,block,del,ins,ul,ol,li'],
            ]);
            ?>
        </div>
        
        <div class="hero-slide-field">
            <label>Posición Vertical del Contenido</label>
            <select name="hero_slides[<?php echo $index; ?>][content_position]" style="width: 200px;">
                <option value="center" <?php selected($slide['content_position'] ?? 'center', 'center'); ?>>↕ Centro</option>
                <option value="top" <?php selected($slide['content_position'] ?? '', 'top'); ?>>⬆ Arriba</option>
                <option value="bottom" <?php selected($slide['content_position'] ?? '', 'bottom'); ?>>⬇ Abajo</option>
            </select>
        </div>
        
        <div class="hero-slide-field">
            <label>Posición Horizontal del Contenido</label>
            <select name="hero_slides[<?php echo $index; ?>][content_align]" style="width: 200px;">
                <option value="center" <?php selected($slide['content_align'] ?? 'center', 'center'); ?>>⬌ Centro</option>
                <option value="left" <?php selected($slide['content_align'] ?? '', 'left'); ?>>⬅ Izquierda</option>
                <option value="right" <?php selected($slide['content_align'] ?? '', 'right'); ?>>➡ Derecha</option>
            </select>
        </div>
        
        <div class="hero-slide-field">
            <label>Texto del Botón</label>
            <input type="text" 
                   name="hero_slides[<?php echo $index; ?>][button_text]" 
                   value="<?php echo esc_attr($slide['button_text']); ?>"
                   placeholder="Ej: Explorar Recursos">
        </div>
        
        <div class="hero-slide-field">
            <label>Link del Botón</label>
            <input type="text" 
                   name="hero_slides[<?php echo $index; ?>][button_link]" 
                   value="<?php echo esc_attr($slide['button_link']); ?>"
                   placeholder="Ej: /recursos">
        </div>
        
        <div class="hero-slide-field">
            <label>Estilo del Botón</label>
            <select name="hero_slides[<?php echo $index; ?>][button_style]" style="width: 200px;">
                <option value="default" <?php selected($slide['button_style'] ?? 'default', 'default'); ?>>🔵 Normal (sólido)</option>
                <option value="outline" <?php selected($slide['button_style'] ?? '', 'outline'); ?>>⬜ Outline (borde)</option>
            </select>
        </div>
        
        <div class="hero-slide-field">
            <label>Tipo de Background</label>
            <select name="hero_slides[<?php echo $index; ?>][background_type]" 
                    class="hero-background-type">
                <option value="image" <?php selected($slide['background_type'], 'image'); ?>>Imagen</option>
                <option value="video" <?php selected($slide['background_type'], 'video'); ?>>Video</option>
                <option value="gradient" <?php selected($slide['background_type'], 'gradient'); ?>>Gradiente</option>
            </select>
        </div>
        
    <!-- IMAGE BACKGROUND -->
        <div class="hero-slide-field background-type-conditional bg-image">
            <label>Imagen de Background</label>
            <input type="text" 
                   name="hero_slides[<?php echo $index; ?>][background_image]" 
                   value="<?php echo esc_attr($slide['background_image']); ?>"
                   readonly>
            <button type="button" class="button upload-hero-media" data-type="image">Seleccionar Imagen</button>
            <div class="hero-media-preview">
                <?php if (!empty($slide['background_image'])): ?>
                    <img src="<?php echo esc_url($slide['background_image']); ?>" alt="">
                <?php endif; ?>
            </div>
            
            <!-- Image-specific controls -->
            <div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                <label style="display: block; margin-bottom: 8px;">Opacidad del Overlay (oscurecer fondo)</label>
                <input type="range" 
                       name="hero_slides[<?php echo $index; ?>][overlay_opacity]" 
                       value="<?php echo esc_attr($slide['overlay_opacity'] ?? '0.3'); ?>"
                       min="0"
                       max="1"
                       step="0.1"
                       class="overlay-opacity-slider"
                       style="width: 100%;">
                <span class="overlay-opacity-value"><?php echo esc_attr($slide['overlay_opacity'] ?? '0.3'); ?></span>
                
                <label style="display: block; margin-top: 15px; margin-bottom: 5px;">Efecto Ken Burns (zoom + paneo)</label>
                <select name="hero_slides[<?php echo $index; ?>][ken_burns]" style="width: 200px;">
                    <option value="0" <?php selected($slide['ken_burns'] ?? '0', '0'); ?>>❌ Desactivado</option>
                    <option value="1" <?php selected($slide['ken_burns'] ?? '', '1'); ?>>✅ Activado</option>
                </select>
            </div>
        </div>
        
    <!-- VIDEO BACKGROUND -->
        <div class="hero-slide-field background-type-conditional bg-video">
            <label>Video de Background</label>
            <input type="text" 
                   name="hero_slides[<?php echo $index; ?>][background_video]" 
                   value="<?php echo esc_attr($slide['background_video']); ?>"
                   readonly>
            <button type="button" class="button upload-hero-media" data-type="video">Seleccionar Video</button>
            <div class="hero-media-preview">
                <?php if (!empty($slide['background_video'])): ?>
                    <video src="<?php echo esc_url($slide['background_video']); ?>" controls></video>
                <?php endif; ?>
            </div>
            
            <label style="margin-top: 10px; display: block;">Velocidad de reproducción</label>
            <input type="number" 
                   name="hero_slides[<?php echo $index; ?>][video_playback_rate]" 
                   value="<?php echo esc_attr($slide['video_playback_rate']); ?>"
                   min="0.25"
                   max="2"
                   step="0.25"
                   style="width: 100px;">
            <p class="description">1 = normal, 0.5 = lento, 2 = rápido</p>
        </div>
        
    <!-- GRADIENT BACKGROUND -->
        <div class="hero-slide-field background-type-conditional bg-gradient">
            <label>Color 1 (Inicio del gradiente)</label>
            <input type="color" 
                   name="hero_slides[<?php echo $index; ?>][gradient_color_1]" 
                   value="<?php echo esc_attr($slide['gradient_color_1'] ?? '#6366f1'); ?>"
                   style="width: 100px; height: 40px;">
            
            <label style="margin-top: 15px; display: block;">Color 2 (Fin del gradiente)</label>
            <input type="color" 
                   name="hero_slides[<?php echo $index; ?>][gradient_color_2]" 
                   value="<?php echo esc_attr($slide['gradient_color_2'] ?? '#8b5cf6'); ?>"
                   style="width: 100px; height: 40px;">
            
            <label style="margin-top: 15px; display: block;">Dirección del gradiente</label>
            <select name="hero_slides[<?php echo $index; ?>][gradient_direction]" style="width: 200px;">
                <option value="to bottom" <?php selected($slide['gradient_direction'] ?? 'to bottom', 'to bottom'); ?>>↓ De arriba a abajo</option>
                <option value="to top" <?php selected($slide['gradient_direction'] ?? '', 'to top'); ?>>↑ De abajo a arriba</option>
                <option value="to right" <?php selected($slide['gradient_direction'] ?? '', 'to right'); ?>>→ De izquierda a derecha</option>
                <option value="to left" <?php selected($slide['gradient_direction'] ?? '', 'to left'); ?>>← De derecha a izquierda</option>
                <option value="to bottom right" <?php selected($slide['gradient_direction'] ?? '', 'to bottom right'); ?>>↘ Diagonal abajo-derecha</option>
                <option value="to bottom left" <?php selected($slide['gradient_direction'] ?? '', 'to bottom left'); ?>>↙ Diagonal abajo-izquierda</option>
                <option value="135deg" <?php selected($slide['gradient_direction'] ?? '', '135deg'); ?>>↗ Diagonal arriba-derecha</option>
                <option value="45deg" <?php selected($slide['gradient_direction'] ?? '', '45deg'); ?>>↖ Diagonal arriba-izquierda</option>
            </select>
            
            <div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                <strong>Vista previa del gradiente:</strong>
                <div class="gradient-preview" 
                     data-color1="<?php echo esc_attr($slide['gradient_color_1'] ?? '#6366f1'); ?>"
                     data-color2="<?php echo esc_attr($slide['gradient_color_2'] ?? '#8b5cf6'); ?>"
                     data-direction="<?php echo esc_attr($slide['gradient_direction'] ?? 'to bottom'); ?>"
                     style="height: 60px; margin-top: 8px; border-radius: 4px; background: linear-gradient(<?php echo esc_attr($slide['gradient_direction'] ?? 'to bottom'); ?>, <?php echo esc_attr($slide['gradient_color_1'] ?? '#6366f1'); ?>, <?php echo esc_attr($slide['gradient_color_2'] ?? '#8b5cf6'); ?>);"></div>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

// ===================================================================
// SAVE META DATA
// ===================================================================
add_action('save_post_hero', 'hero_save_meta_data');

function hero_save_meta_data($post_id) {
    // Check nonce
    if (!isset($_POST['hero_settings_nonce']) || !wp_verify_nonce($_POST['hero_settings_nonce'], 'hero_settings_nonce')) {
        return;
    }
    
    if (!isset($_POST['hero_slides_nonce']) || !wp_verify_nonce($_POST['hero_slides_nonce'], 'hero_slides_nonce')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save settings
    update_post_meta($post_id, '_hero_active', isset($_POST['hero_active']) ? '1' : '0');
    update_post_meta($post_id, '_hero_position', sanitize_text_field($_POST['hero_position'] ?? 'home'));
    update_post_meta($post_id, '_hero_autoplay', isset($_POST['hero_autoplay']) ? '1' : '0');
    update_post_meta($post_id, '_hero_interval', intval($_POST['hero_interval'] ?? 8000));
    update_post_meta($post_id, '_hero_show_arrows', isset($_POST['hero_show_arrows']) ? '1' : '0');
    update_post_meta($post_id, '_hero_show_dots', isset($_POST['hero_show_dots']) ? '1' : '0');
    
    // Save slides
    $slides = [];
    if (isset($_POST['hero_slides']) && is_array($_POST['hero_slides'])) {
        foreach ($_POST['hero_slides'] as $slide) {
            // El subtítulo viene del editor WYSIWYG, necesita balancear tags y permitir HTML
            $subtitle_raw = $slide['subtitle'] ?? '';
            
            // Debug: Ver qué llega del editor
            error_log('🔍 Subtítulo recibido (raw): ' . substr($subtitle_raw, 0, 100));
            
            // Procesar como lo haría WordPress con el contenido del post
            $subtitle_processed = wp_kses_post($subtitle_raw);
            $subtitle_processed = wpautop($subtitle_processed); // Convierte saltos de línea en <p>
            
            error_log('🔍 Subtítulo procesado: ' . substr($subtitle_processed, 0, 100));
            
            $slides[] = [
                'title' => sanitize_text_field($slide['title'] ?? ''),
                'title_align' => sanitize_text_field($slide['title_align'] ?? 'left'),
                'subtitle' => $subtitle_processed,
                'content_position' => sanitize_text_field($slide['content_position'] ?? 'center'),
                'content_align' => sanitize_text_field($slide['content_align'] ?? 'center'),
                'overlay_opacity' => floatval($slide['overlay_opacity'] ?? 0.3),
                'ken_burns' => intval($slide['ken_burns'] ?? 0),
                'button_text' => sanitize_text_field($slide['button_text'] ?? ''),
                'button_link' => esc_url_raw($slide['button_link'] ?? ''),
                'button_style' => sanitize_text_field($slide['button_style'] ?? 'default'),
                'background_type' => sanitize_text_field($slide['background_type'] ?? 'image'),
                'background_image' => esc_url_raw($slide['background_image'] ?? ''),
                'background_video' => esc_url_raw($slide['background_video'] ?? ''),
                'video_playback_rate' => floatval($slide['video_playback_rate'] ?? 1),
                'gradient_color_1' => sanitize_hex_color($slide['gradient_color_1'] ?? '#6366f1'),
                'gradient_color_2' => sanitize_hex_color($slide['gradient_color_2'] ?? '#8b5cf6'),
                'gradient_direction' => sanitize_text_field($slide['gradient_direction'] ?? 'to bottom'),
            ];
        }
    }
    
    error_log('💾 Guardando ' . count($slides) . ' slides');
    update_post_meta($post_id, '_hero_slides', $slides);
}
