<?php


//******************** Add Class to submenu MEGAMENU **************************
function change_ul_item_classes_in_nav($classes, $args, $depth)
{

  if (0 == $depth) {
    $classes[] = 'level-sub1';
    $classes[] = 'megamenu-container';
    $classes[] = 'animate';
    $classes[] = 'fadeIn'; // AOS anmimation
  }
  if (1 == $depth) { // change sub-menu depth
    $classes[] = 'level-sub2';
  }
  if (2 == $depth) { // change sub-menu depth
    $classes[] = 'mega-menu-column';
  }

  return $classes;
}
add_filter('nav_menu_submenu_css_class', 'change_ul_item_classes_in_nav', 10, 3);


//************************* Apply CSS clases on FIRST & LAST MENU ITEMS **************************************
function add_first_and_last($items)
{
  if (!empty($items) && is_array($items)) {
    // Add first-item class to the first menu item
    if (isset($items[0]) && is_object($items[0])) {
      $items[0]->classes[] = 'first-item';
    }

    // Add last-item class to the last menu item
    $last_index = count($items) - 1;
    if (isset($items[$last_index]) && is_object($items[$last_index])) {
      $items[$last_index]->classes[] = 'last-item';
    }
  }

  return $items;
}
add_filter('wp_nav_menu_objects', 'add_first_and_last');

