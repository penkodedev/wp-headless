<?php

// TAXONOMÍAS PERSONALIZADAS PARA EL CPT RECURSOS

function register_taxonomies() {
    
    // Nivel educativo
    register_taxonomy('nivel_educativo', 'recursos', [
        'label'        => 'Nivel educativo',
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite'      => ['slug' => 'nivel-educativo'],
    ]);

   
}

add_action('init', 'register_taxonomies');

