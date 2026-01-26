<?php

//**************** LIST CHILD PAGES ********************
function list_child_pages() {
  global $post;

  if (is_page() && $post->post_parent)
      $childpages = wp_list_pages('sort_column=menu_order&title_li=&child_of=' . $post->post_parent . '&echo=0');
  else
      $childpages = wp_list_pages('sort_column=menu_order&title_li=&child_of=' . $post->ID . '&echo=0');

  if ($childpages) {
      $string = '<div class="child-pages-section">' . $childpages . '</div>'; // CONTAINER DIV
      return $string;
  }
}
add_shortcode('wpb_childpages', 'list_child_pages'); //USAGE SHORTCODE [list_child_pages]

//************************* CUSTOM EXCERPT FUNCTION **************************
function excerpt($limit) {
  $excerpt = explode(' ', get_the_excerpt(), $limit);
  if (count($excerpt) >= $limit) {
    array_pop($excerpt);
    $excerpt = implode(" ", $excerpt) . '...';
  } else {
    $excerpt = implode(" ", $excerpt);
  }
  $excerpt = preg_replace('`\[[^\]]*\]`', '', $excerpt);
  return $excerpt;
}


//************************* LIMIT OR DISABLE WP REVISIONS *****************************
//define('MY_CUSTOM_POST_REVISIONS', 3); // Limit to 3 revisions
//define('WP_POST_REVISIONS', false); // Disable revisions
