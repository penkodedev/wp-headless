<?php
//************************* INCLUDE JS FUNCTIONS **************************************
  // En setup headless, solo mantenemos scripts esenciales para admin


function enqueue_scripts()
{
  wp_enqueue_script(
    'secure-login',
    get_template_directory_uri() . '/js/secure-login.js',
    array(), // Sin dependencias
    '', // Version
    true  // Load in footer
  );

}
add_action('wp_enqueue_scripts', 'enqueue_scripts');
