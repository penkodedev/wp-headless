<?php

/**
 * Sistema de Preview optimizado para WordPress headless
 * OPTIMIZACIÓN: Solo se ejecuta en admin, usa fallback inmediato, no bloquea carga
 */

// Botón de vista previa en frontend para cualquier CPT
// Para editor clásico: en el box de Publicar
// Para Gutenberg: en el sidebar

add_action('admin_init', function() {
	$post_types = get_post_types(['public' => true], 'names');
	foreach ($post_types as $pt) {
		// Para editor clásico
		add_action('post_submitbox_misc_actions', function() use ($pt) {
			global $post, $typenow;
			if (!$post || $typenow !== $pt) return;
			// Solo si no es Gutenberg
			if (!use_block_editor_for_post($post)) {
				$url = get_frontend_preview_url($post);
				if ($url) {
					echo '<div class="misc-pub-section"><a href="' . esc_url($url) . '" target="_blank" class="button button-primary" style="width:100%;margin-top:4px;">Preview</a></div>';
				}
			}
		});
		// Para Gutenberg: meta box en side
		if (use_block_editor_for_post_type($pt)) {
			add_meta_box(
				'frontend_preview_metabox',
				'Preview',
				function($post) {
					$url = get_frontend_preview_url($post);
					if ($url) {
						echo '<a href="' . esc_url($url) . '" target="_blank" class="button button-primary button-large" style="width:100%;text-align:center;">Preview</a>';
					} else {
						echo '<p style="color:#999;">No se pudo generar la URL de preview.</p>';
					}
				},
				$pt,
				'side',
				'high'
			);
		}
	}
});

function get_frontend_preview_url($post) {
	// OPTIMIZACIÓN: Usar fallback inmediato, intentar API en background
	$fallback_url = 'http://localhost:3000'; // Fallback por defecto
	
	// Intentar obtener del transient (cache de 12 horas)
	$site_info = get_transient('frontend_site_info');
	
	// Si no existe en cache, usar fallback y intentar cargar en background
	if ($site_info === false) {
		// Usar fallback inmediatamente (no bloquear)
		$site_info = ['front_url' => $fallback_url];
		
		// Intentar obtener de la API de forma no bloqueante
		// IMPORTANTE: timeout de solo 1 segundo
		$response = wp_remote_get(home_url('/wp-json/custom/v1/site-info'), [
			'timeout' => 1,
			'blocking' => false // No bloqueante - continúa sin esperar respuesta
		]);
		
		// Cachear el fallback por 5 minutos para evitar llamadas repetidas
		set_transient('frontend_site_info', $site_info, 5 * MINUTE_IN_SECONDS);
	}
	
	$front_url = rtrim($site_info['front_url'], '/');
	$slug = $post->post_name;
	$type = $post->post_type;
	
	// WPML: obtener idioma si está activo
	$lang = '';
	$default_lang = 'es'; // Idioma por defecto de WPML
	if (function_exists('apply_filters')) {
		$current_lang = apply_filters('wpml_post_language_details', null, $post->ID);
		if (!empty($current_lang['language_code']) && $current_lang['language_code'] !== $default_lang) {
			$lang = $current_lang['language_code'];
		}
	}
	
	// Detectar si es la página de inicio (front page)
	$is_front_page = (get_option('page_on_front') == $post->ID);
	
	// Construir URL
	$url = $front_url;
	
	// Si es la home page, solo añadir idioma (si no es el por defecto) y ?preview=true
	if ($is_front_page) {
		if ($lang) {
			$url .= '/' . $lang;
		}
		$url .= '/?preview=true';
		return $url;
	}
	
	// Solo añadir idioma si NO es el por defecto
	if ($lang) {
		$url .= '/' . $lang;
	}
	
	// Para páginas (type = 'page'), NO incluir /page/ en la URL
	// Para otros CPTs, sí incluir el tipo
	if ($type !== 'page') {
		$url .= '/' . $type;
	}
	
	$url .= '/' . $slug . '?preview=true';
	
	return $url;
}

// NUEVO: Función helper para actualizar el cache manualmente desde admin
// Útil si cambias la URL del frontend
add_action('admin_notices', function() {
	// Solo mostrar en el dashboard principal
	$screen = get_current_screen();
	if ($screen && $screen->id === 'dashboard') {
		$site_info = get_transient('frontend_site_info');
		if ($site_info && isset($site_info['front_url'])) {
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p><strong>Frontend URL:</strong> ' . esc_html($site_info['front_url']) . '</p>';
			echo '</div>';
		}
	}
});


