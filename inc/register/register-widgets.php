<?php

function widget_registration($name, $id, $description, $beforeWidget, $afterWidget, $beforeTitle, $afterTitle)
{
  register_sidebar(array(
    'name' => $name,
    'id' => $id,
    'description' => $description,
    'before_widget' => $beforeWidget,
    'after_widget' => $afterWidget,
    'before_title' => $beforeTitle,
    'after_title' => $afterTitle,
  ));
}

function multiple_widget_init()
{
  widget_registration('Sidebar 01',           'sidebar-1',     '', '<div class="sidebox">', '</div>', '', '');
  widget_registration('Contenido Pie',        'footer-1',      '', '<div class="footerbox">', '</div>', '', '');
  widget_registration('Scrolling Text',       'scrolling',      '', '<div class="scrolling-text" id="scrolling-text-container">', '</div>', '', '');
  widget_registration('Modal Content 01',     'modal-content-1','', '<div class="modal-01">', '</div>', '', '');
}

add_action('widgets_init', 'multiple_widget_init');