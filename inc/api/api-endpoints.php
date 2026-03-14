<?php

/**
 * =============================================================================
 *   REGISTRATION OF ALL CUSTOM ENDPOINTS FOR THE HEADLESS WORDPRESS REST API
 *   All endpoints are registered WHITIN A SINGLE 'rest_api_init' HOOK.
 * =============================================================================
*/

/**
 * ------------------------------------------------------------------------------
 * CORS CONFIGURATION (Cross-Origin Resource Sharing)
 * ------------------------------------------------------------------------------
 * Fires on 'init' (before REST API processing) so CORS headers are present
 * even on preflight OPTIONS requests. Also removes WP's default handler and
 * adds a backup on rest_pre_serve_request for full coverage.
 */
function pk_get_cors_origin() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (empty($origin)) return '';

    $allowed = [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://penkode-headless.local',
        'https://penkode.com',
        'https://penkode.com/headless',
        'https://reaxy.penkode.com',
        'https://penkode-headless.vercel.app',
    ];

    if (in_array($origin, $allowed, true)) return $origin;
    if (strpos($origin, '.vercel.app') !== false) return $origin;

    return '';
}

function pk_send_cors_headers() {
    $origin = pk_get_cors_origin();
    if (empty($origin)) return;

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

// Early CORS: fire on init for any REST API request (including OPTIONS preflight)
add_action('init', function () {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/wp-json/') === false && !isset($_GET['rest_route'])) {
        return;
    }

    pk_send_cors_headers();

    // Handle preflight immediately — no need for WP to process further
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        status_header(200);
        exit;
    }
}, 1);

// Backup: also fire on rest_pre_serve_request (replaces WP default handler)
remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
add_filter('rest_pre_serve_request', function ($value) {
    pk_send_cors_headers();
    return $value;
});

/**
 * ------------------------------------------------------------------------------
 *   🌐 (WPML) MIDDLEWARE DE IDIOMA PARA LA API  
 * ------------------------------------------------------------------------------
 * Cambia el idioma GLOBAL de WPML para toda la request de la API
 */
add_action('rest_api_init', function () {
    if (isset($_GET['lang'])) {
        $lang = sanitize_text_field($_GET['lang']);
        
        // Method 1: Global sitepress (WPML core)
        global $sitepress;
        if ($sitepress && method_exists($sitepress, 'switch_lang')) {
            $sitepress->switch_lang($lang);
        }
        
        // Method 2: Action hook
        do_action('wpml_switch_language', $lang);
        
        // Method 3: Filter (override current language detection)
        add_filter('wpml_current_language', function() use ($lang) {
            return $lang;
        }, 999);
    }
}, 5);



add_action('rest_api_init', function () {

/*-------------------------------------------------------------------------------------
    🌐 WPML LANGUAGES - Get active languages dynamically
    Route: /custom/v1/languages
-------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/languages', [
        'methods' => 'GET',
        'callback' => 'get_wpml_languages_callback',
        'permission_callback' => '__return_true'
    ]);
    
/*--------------------------------------------------------------------------------------
    🍔 UNIFIED WP NAV MENUS ENDPOINT
    - Route: /custom/v1/menus (gets all menus)
    - Route: /custom/v1/menus?slug={slug} (gets a specific menu by slug)
    - Route: /custom/v1/menus?location={location} (gets a specific menu by location)
---------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/menus', [
        'methods'  => 'GET',
        'callback' => 'menus_endpoint_callback',
        'permission_callback' => '__return_true',
        'args' => [
            'slug' => ['sanitize_callback' => 'sanitize_text_field'],
            'location' => ['sanitize_callback' => 'sanitize_text_field'],
        ],
    ]);


/*-------------------------------------------------------------------------------------
    🔎 CUSTOM SEARCH ENDPOINT
    Route: /custom/v1/search?term={text}
-------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/search', [
        'methods'  => 'GET',
        'callback' => 'custom_search_callback',
        'permission_callback' => '__return_true',
        'args' => ['term' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field']],
    ]);


/*-------------------------------------------------------------------------------------
    ↔️ POSTNAV ENDPOINT (Previous/Next)
    Route: /custom/v1/post-navigation?post_id={id}&post_type={type}
    e.g. /wp-json/custom/v1/post-navigation?post_id=42&post_type=news
-------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/post-navigation', [
        'methods'  => 'GET',
        'callback' => 'get_post_navigation_callback',
        'permission_callback' => '__return_true',
        'args' => [
            'post_id'   => [
                'required' => true, 
                // Usamos una función anónima o 'rest_is_integer' para una validación compatible.
                // 'is_numeric' de PHP no funciona aquí porque recibe más de un argumento.
                'validate_callback' => function($param) { return is_numeric($param); }
            ],
            'post_type' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
        ],
    ]);


/*--------------------------------------------------------------------------------------
    🎠 SLIDER ENDPOINT
    Route: /custom/v1/sliders/{id}
    Returns full slider data (config + slides or CPT posts).
--------------------------------------------------------------------------------------*/
        register_rest_route('custom/v1', '/sliders/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => 'get_slider_data_callback',
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) { return is_numeric($param); },
                ],
                'lang' => [
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);


/*--------------------------------------------------------------------------------------
    💥 ACTIVE POPUPS ENDPOINT
    Route: /custom/v1/active-popups
    Returns all modals configured as popups.
--------------------------------------------------------------------------------------*/
        register_rest_route('custom/v1', '/active-popups', [
            'methods'  => 'GET',
            'callback' => 'get_active_popups_callback',
            'permission_callback' => '__return_true',
        ]);


/*--------------------------------------------------------------------------------------
    ⚙️ SITE INFO ENDPOINT
    Route: /custom/v1/site-info
    Returns data configured in Settings > General.
--------------------------------------------------------------------------------------*/
        register_rest_route('custom/v1', '/site-info', [
            'methods'  => 'GET',
            'callback' => 'get_global_site_info_callback',
            'permission_callback' => '__return_true',
            'args' => [
                'lang' => [
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);


/*-----------------------------------------------------------
    🌐 (WPML) UNIVERSAL TRANSLATIONS ENDPOINT
    Route: /custom/v1/translations/{post_type}/{slug}
    Returns a map of translated URLs for any post, page, or custom post type.
-----------------------------------------------------------*/
    register_rest_route('custom/v1', '/translations/(?P<post_type>[a-zA-Z0-9_-]+)/(?P<slug>[a-zA-Z0-9_-]+)', [
        'methods'  => 'GET',
        'callback' => 'get_universal_translations_callback',
        'permission_callback' => '__return_true',
        'args' => [
            'post_type' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'slug'      => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
        ],
    ]);

/*-----------------------------------------------------------
    🌐 (WPML) TRANSLATION BY POST ID ENDPOINT
    Route: /custom/v1/translation/{post_id}?lang={target_lang}
    Returns the translated URL for a specific post ID
-----------------------------------------------------------*/
    register_rest_route('custom/v1', '/translation/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => 'get_wpml_translation_by_id_callback',
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'required' => true,
                'validate_callback' => function($param) { return is_numeric($param); }
            ],
            'lang' => [
                'required' => false,
                'default' => 'en',
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ],
    ]);

/*--------------------------------------------------------------------------------------
    🛠️ CUSTOM FIELDS SCHEMA ENDPOINT
    Route: /custom/v1/custom-fields-schema
    Returns the schema for custom fields used in the site.
--------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/custom-fields-schema', [
        'methods'  => 'GET',
        'callback' => 'custom_fields_schema_callback',
        'permission_callback' => '__return_true',
    ]);

/*--------------------------------------------------------------------------------------
    ❤️ POST LIKE ENDPOINT
    Route: /custom/v1/like/{post_id}
    Increments like count for a post. Returns new count.
--------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/like/(?P<id>\d+)', [
        'methods'  => 'POST',
        'callback' => 'post_like_callback',
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'required' => true,
                'validate_callback' => function($param) { return is_numeric($param); }
            ]
        ],
    ]);

/*--------------------------------------------------------------------------------------
    ❤️ GET POST LIKES COUNT
    Route: /custom/v1/likes/{post_id}
    Returns current like count for a post.
--------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/likes/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => 'get_post_likes_callback',
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'required' => true,
                'validate_callback' => function($param) { return is_numeric($param); }
            ]
        ],
    ]);

/*--------------------------------------------------------------------------------------
    🎬 HERO DATA ENDPOINT
    Route: /custom/v1/hero?position={position}&lang={lang}
    Returns hero configuration and slides for a specific position.
    
    Parameters:
    - position: home|page|archive|custom (required)
    - lang: es|en|pt-br (optional)
    
    Example: /wp-json/custom/v1/hero?position=home&lang=es
--------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/hero', [
        'methods'  => 'GET',
        'callback' => 'get_hero_data_callback',
        'permission_callback' => '__return_true',
        'args' => [
            'position' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return in_array($param, ['home', 'page', 'archive', 'custom']);
                }
            ],
            'lang' => [
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ],
    ]);


    /*--------------------------------------------------------------------------------------
    📰 TICKER ENDPOINT
    Route: /custom/v1/ticker
    Returns ticker configuration (texts, size, duration, pauseOnHover)
    --------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/ticker', [
        'methods'  => 'GET',
        'callback' => 'ticker_settings',
        'permission_callback' => '__return_true',
        'args' => [
            'page_id' => [
                'required' => false,
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'description' => 'Si se envía, la respuesta incluye showOnThisPage (mostrar ticker solo en las páginas seleccionadas).',
            ],
        ],
    ]);


    /*--------------------------------------------------------------------------------------
    💬 TOOLTIPS ENDPOINT
    Route: /custom/v1/tooltips
    Returns all tooltips configured in Custom Settings.
    
    Example: /wp-json/custom/v1/tooltips
    --------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/tooltips', [
        'methods'  => 'GET',
        'callback' => 'get_tooltips_callback',
        'permission_callback' => '__return_true',
    ]);


    /*--------------------------------------------------------------------------------------
    📊 COUNTER STATS ENDPOINT
    Route: /custom/v1/stats/{id}
    Returns a single counter stats group by 1-based index.

    Example: /wp-json/custom/v1/stats/1
    --------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/stats/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => 'get_counter_stats_callback',
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'required' => true,
                'validate_callback' => function($param) { return is_numeric($param) && $param > 0; },
            ],
        ],
    ]);


    /*--------------------------------------------------------------------------------------
    🎨 APPEARANCE SETTINGS ENDPOINT
    Route: /custom/v1/appearance
    Returns appearance configuration (dark mode toggle, default color scheme).
    
    Example: /wp-json/custom/v1/appearance?lang=en
    --------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/appearance', [
        'methods'  => 'GET',
        'callback' => 'get_appearance_settings_callback',
        'permission_callback' => '__return_true',
    ]);


    // /*--------------------------------------------------------------------------------------
    // 👤 USER STATUS ENDPOINT
    // Route: /custom/v1/user-status
    // Returns whether current user is logged in (editor/admin).
    // This allows Next.js to disable cache for logged-in users.
    
    // Example: /wp-json/custom/v1/user-status
    // --------------------------------------------------------------------------------------*/
    // register_rest_route('custom/v1', '/user-status', [
    //     'methods'  => 'GET',
    //     'callback' => 'get_user_status_callback',
    //     'permission_callback' => '__return_true',
    // ]);


    /*--------------------------------------------------------------------------------------
    🤖 CHATBOT ENDPOINTS
    Route: /custom/v1/chatbot
    
    GET  - Returns chatbot configuration
    POST - Sends message to AI and returns response
    
    Example:
    GET  /wp-json/custom/v1/chatbot
    POST /wp-json/custom/v1/chatbot (body: { "message": "Hola" })
    --------------------------------------------------------------------------------------*/
    register_rest_route('custom/v1', '/chatbot', [
        [
            'methods'  => 'GET',
            'callback' => 'get_chatbot_config_callback',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'  => 'POST',
            'callback' => 'post_chatbot_message_callback',
            'permission_callback' => '__return_true',
            'args' => [
                'message' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ],
            ],
        ],
    ]);
});

// Filtros para añadir audio_url a la REST API de posts
add_filter('rest_prepare_noticias', 'add_audio_url_to_rest', 10, 3);
add_filter('rest_prepare_recursos', 'add_audio_url_to_rest', 10, 3);

function add_audio_url_to_rest($response, $post, $request) {
    $audio_id = get_post_meta($post->ID, '_recurso_audio_id', true);
    if ($audio_id) {
        $audio_url = wp_get_attachment_url($audio_id);
        $file_path = get_attached_file($audio_id);
        if ($audio_url && file_exists($file_path)) {
            $response->data['audio_url'] = $audio_url;
        }
    }
    return $response;
}
