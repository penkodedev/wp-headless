<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>

<head>
  <?php load_theme_textdomain('foo'); 
  
  function penkode_custom_header_setup() {
    $args = array(
      'default-image'      => get_template_directory_uri() . 'img/default-image.jpg',
      'default-text-color' => '000',
      'width'              => 1000,
      'height'             => 250,
      'flex-width'         => true,
      'flex-height'        => true,
    );
    add_theme_support( 'custom-header', $args );
  }
  add_action( 'after_setup_theme', 'penkode_custom_header_setup' );
  ?>
  
  <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <meta charset="<?php echo get_bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta http-equiv="Content-Type" content="<?php echo get_bloginfo('html_type'); ?>; charset=<?php echo get_bloginfo('charset'); ?>">
    <meta name="author" content="<?php echo get_bloginfo('name'); ?>">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Open Graph (OG) Tags -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php the_title(); ?>">
    <meta property="og:description" content="<?php echo wp_trim_words(get_the_excerpt(), 25); ?>">
    <meta property="og:url" content="<?php the_permalink(); ?>">
    <meta property="og:image" content="<?php echo get_the_post_thumbnail_url(); ?>">
    <meta property="fb:app_id" content=" "><!-- Your_Facebook_App_ID -->

    <!-- Twitter Card Tags -->
    <meta name="twitter:site" content=" "> <!-- Twitter Username -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:creator" content="@your_twitter_handle">
    <meta name="twitter:title" content="<?php the_title(); ?>">
    <meta name="twitter:description" content="<?php echo wp_trim_words(get_the_excerpt(), 25); ?>">
    <meta name="twitter:image" content="<?php echo get_the_post_thumbnail_url(); ?>">

    <!-- Favicon -->
    <link rel="icon" href="<?php echo get_template_directory_uri(); ?>/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style.css">

  <!-- Vendor CSS begin -->
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/vendor/animate.css" media="screen" title="style (screen)" />
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/vendor/aos.css" media="screen" title="style (screen)" />
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/vendor/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/vendor/flickity.css" media="screen" title="style (screen)" />
  <!-- Vendor CSS CSS end -->


  <!-- LIBRARIES JS BEGIN -->
  <script src="<?php echo get_template_directory_uri(); ?>/js/aos.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    AOS.init();
  });
</script>
<script src="<?php echo get_template_directory_uri(); ?>/js/bootstrap.min.js"></script>

  
  <!-- react -->
  <!--<script src="https://unpkg.com/react@17/umd/react.development.js"></script>-->
  <!--<script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js"></script>-->
  <!-- LIBRARIES JS END -->

  <title>
    <?php wp_title(''); ?>
  </title>
  <?php wp_head(); ?>

  <!-- Google Analytics BEGIN -->
  <!-- Google Analytics END -->
</head>


<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php get_template_part('/template-parts/grid-system'); // CALLS THE GRID LAYOUT SYSTEM FOR GLOBAL WEB ?>

  <header class="grid-header" id="header-container">
  
    <?php if (is_front_page()) {
      get_template_part('/template-parts/logo-container-home');
    } else {
      get_template_part('/template-parts/logo-container');
    }
    ?>
 <div>
  
    <nav role="navigation">
      <?php wp_nav_menu(array(
        'theme_location' => 'mainnav',
        'menu_class' => 'main-nav',
        'fallback_cb'    => false
      )); ?>
    </nav>

    <nav><?php get_template_part('/template-parts/mobile-nav'); ?></nav>

    <!-- Cookies BEGIN -->
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.0/cookieconsent.min.css" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.1.0/cookieconsent.min.js"></script>
    <script>
      window.addEventListener("load", function() {
        window.cookieconsent.initialise({
          "palette": {
            "popup": {
              "background": "#000000",
              "text": "#ffffff"
            },
            "button": {
              "background": "#ffbc51",
              "text": "#000"
            }
          },
          "content": {
            "message": "<?php _e('Utilizamos cookies propias y de terceros para mejorar nuestros servicios. Si continua navegando, consideramos que acepta su uso.', 'panambi'); ?>",
            "dismiss": "Acepto",
            "link": "Ver más",
            "href": "https://www.google.com" // change to your privacy URL //
          }
        })
      });
    </script>
    <!-- Cookies END -->

  </header>


  <!-- Megamenu Overlay BEGIN -->
  <script type="text/javascript">
    jQuery(document).ready(function($) {
      $('li.has-mega-menu').hover(function() {
          $('body').addClass('nav-focus');
          // Add the div inside the nav-overlay
          $('<div class="nav-overlay"></div>').appendTo('body');
        },
        function() {
          $('body').removeClass('nav-focus');
          // Remove the div
          $('.nav-overlay').remove();
        });
    });
    
  </script>
  <!-- Megamenu Overlay END --> 