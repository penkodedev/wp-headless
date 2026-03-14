<?php
ob_start();
/*
* Template Name: 
*/
get_header(); // Calls the WordPress Header

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<section class="page-title">
    <h1><?php _e('Blog', 'penkode'); ?></h1>
</section>

<main class="grid-main animate fadeIn" id="main-container">
    <section class="post-grid">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <h5>
                    <?php the_title(); ?>
                </h5>
                <p class="grid-item-excerpt">
                    <?php echo excerpt('24'); ?>
                </p>
            <?php endwhile;
        else: ?>
            <p><?php _e('Sorry, no posts matched your criteria.', 'penkode'); ?></p>
        <?php endif; ?>
    </section>

</main>
<?php get_footer(); ?>