<footer class="grid-footer" id="footer">
    <section class="footer-content">

        <section class="footer-widgets">
            <?php get_template_part ('/template-parts/footer-widgets'); ?> <!-- an include of footer WordPress widgets -->
        </section>
        
        <section class="footer-social">
        <?php 
        if(is_front_page()){ 
            //get_template_part ('/template-parts/social'); //different content in footer if is home page 
            }else{
            //get_template_part ('/template-parts/social');
        }; ?>
        </section>
        <nav>
            <?php wp_nav_menu( array(	
                'theme_location' => 'footernav',
                'menu_class' => 'nav-footer',
                'fallback_cb'    => false
            )); ?>
        </nav>

    </section>
</footer>

<?php wp_footer(); ?>

<div role="nav" class="back-to-top"></div>
</body> <!-- CLOSE BODY -->
</html> <!-- CLOSE HTML -->