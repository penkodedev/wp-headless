# Penkode Headless Framework

**WordPress Headless para proyectos con frontend en React.**

---

## Estructura General

- **Backend:** WordPress con tema personalizado `penkode-headless`, con funcionalidades avanzadas para headless.  
- **Frontend:** Proyecto separado (`next-wp-kit`) que consume la API de WordPress.  
- **Multilingüe:** Soporte completo WPML con endpoints para traducciones.

---

## Características Principales

### API REST Personalizada

Más de 15 endpoints en `/wp-json/custom/v1`, incluyendo:

- Menús unificados  
- Búsqueda personalizada  
- Navegación prev/next en posts  
- Popups activos  
- Información del sitio  
- Sistema de likes  
- Datos de hero sections  
- Soporte WPML  

### Tipos de Contenido Personalizados

- `noticias` (noticias/artículos)  
- `hero` (secciones hero)  
- `recursos` (recursos)  
- `modales` (popups/modales)  

### Funcionalidades Adicionales

- Campos personalizados avanzados  
- Taxonomías custom  
- Sistema de preview  
- Integración con GraphQL  
- Configuración CORS para desarrollo local (`localhost:3000`)

---

## Configuración

- Usa **Composer** para dependencias PHP  
- Configuración incluida para **Local by Flywheel**  
- Archivos de configuración para **nginx, PHP y MySQL**  

---

## Instalación

1. Clona el repositorio:

   ```bash
   git clone https://github.com/tu-usuario/penkode-headless.git
