/**
 * Admin Media Uploader - Genérico para cualquier página de admin
 * Uso: Agregar clase 'media-upload-button' al botón y atributos data:
 *   data-target: ID del input hidden donde guardar el attachment ID
 *   data-preview: Clase CSS del contenedor preview (opcional)
 *   data-allowed-types: Tipos MIME permitidos (opcional, separados por coma)
 *   data-title: Título del media uploader (opcional)
 *   data-button-text: Texto del botón (opcional)
 * 
 * Ejemplo de HTML:
 * <input type="hidden" id="logo_id" value="">
 * <div class="logo_preview"></div>
 * <button type="button" class="button button-secondary media-upload-button" 
 *         data-target="logo_id" 
 *         data-preview="logo_preview"
 *         data-allowed-types="image/svg+xml,image/jpeg,image/png,image/gif"
 *         data-title="Select Logo"
 *         data-button-text="Use this logo">Upload Logo</button>
 */

(function($) {
    'use strict';

    const DEFAULT_ALLOWED_TYPES = ['image/svg+xml', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    const DEFAULT_TITLE = 'Select Media';
    const DEFAULT_BUTTON_TEXT = 'Use this media';

    /**
     * Inicializa el media uploader genérico
     */
    function initMediaUploader() {
        $(document).on('click', '.media-upload-button', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const targetId = $button.data('target');
            const previewClass = $button.data('preview');
            const allowedTypesStr = $button.data('allowed-types');
            const title = $button.data('title') || DEFAULT_TITLE;
            const buttonText = $button.data('button-text') || DEFAULT_BUTTON_TEXT;
            
            // Validar que existe el target
            if (!targetId) {
                console.error('Media Uploader: Falta atributo data-target');
                return;
            }
            
            // Parsear tipos permitidos
            const allowedTypes = allowedTypesStr 
                ? allowedTypesStr.split(',').map(t => t.trim()) 
                : DEFAULT_ALLOWED_TYPES;
            
            // Crear media uploader
            const mediaUploader = wp.media({
                title: title,
                button: { text: buttonText },
                multiple: false
            });

            // Cuando se selecciona un archivo
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Validar tipo de archivo
                if (!allowedTypes.includes(attachment.mime)) {
                    alert('Tipo de archivo no permitido. Tipos válidos: ' + allowedTypes.join(', '));
                    return;
                }
                
                // Actualizar input hidden con el ID
                $('#' + targetId).val(attachment.id);
                
                // Actualizar preview si existe
                if (previewClass) {
                    const isImage = attachment.mime.startsWith('image/');
                    const previewHtml = isImage
                        ? '<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">'
                        : '<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>';
                    
                    $('.' + previewClass).html(previewHtml);
                }
                
                // Disparar evento personalizado para lógica adicional
                $button.trigger('media-selected', [attachment]);
            });

            // Abrir el uploader
            mediaUploader.open();
        });
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        initMediaUploader();
    });

})(jQuery);
