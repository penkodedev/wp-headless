<?php

//************************* WPML current link language class *****************************
function custom_language_selector()
{
  $languages = icl_get_languages('skip_missing=0&orderby=custom&order=desc');
  if (1 < count($languages)) {
    foreach ($languages as $l) {
      //adds the class "current_language" if the language that is being viewed.
      $current = $l['active'] ? ' class="current_language"' : '';
      $langs[] = '<a' . $current . ' href="' . $l['url'] . '">' . $l['native_name'] . '</a>';
    }
    echo join(' / ', $langs);
  }
}
