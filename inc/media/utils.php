<?php


//************************* YOUTUBE/VIMEO 100% WIDTH ********************
add_theme_support('responsive-embeds');

//************** REMOVE WORD "Category:" from category pages titles ******************
function prefix_category_title($title)
{
  if (is_category()) {
    $title = single_cat_title('', false);
  }
  return $title;
}
add_filter('get_the_archive_title', 'prefix_category_title');



//******************** Allow to upload and display WEBP images  **************************
add_filter('mime_types', function($existing_mimes) {
  $existing_mimes['webp'] = 'image/webp';
  return $existing_mimes;
});

/* Display WebP thumbnail*/
add_filter('file_is_displayable_image', function($result, $path) {
  return ($result) ? $result : (empty(@getimagesize($path)) || !in_array(@getimagesize($path)[2], [IMAGETYPE_WEBP]));
}, 10, 2);


///////////////////////////////////////////////////////////
//
//         Permite subir archivos xhtml 1/2
//
//////////////////////////////////////////////////////////
function permitir_mime_xhtml($mimes)
{
    $mimes['xhtml'] = 'application/xhtml+xml';
    return $mimes;
}
add_filter('upload_mimes', 'permitir_mime_xhtml');


///////////////////////////////////////////////////////////
//
//         Permite subir archivos xhtml 2/2
//
//////////////////////////////////////////////////////////
function permitir_archivo_xhtml_check($data, $file, $filename, $mimes)
{
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if ($ext === 'xhtml') {
        $data['ext']  = 'xhtml';
        $data['type'] = 'application/xhtml+xml';
    }
    return $data;
}
add_filter('wp_check_filetype_and_ext', 'permitir_archivo_xhtml_check', 10, 4);

