<?php
//******************************* register MENUS ***********************************************

add_theme_support('menus');

if (function_exists('register_nav_menu')) {
  register_nav_menu('mainnav', 'Main Menu');
}

if (function_exists('register_nav_menu')) {
  register_nav_menu('mainnavhome', 'Main Menu Home');
}

if (function_exists('register_nav_menu')) {
  register_nav_menu('floatnav', 'Float Menu');
}

if (function_exists('register_nav_menu')) {
  register_nav_menu('mobilenav', 'Mobile Nav');
}

if (function_exists('register_nav_menu')) {
  register_nav_menu('footernav', 'Footer Menu');
}