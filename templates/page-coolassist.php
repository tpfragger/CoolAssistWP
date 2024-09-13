<?php
get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php
        while (have_posts()) :
            the_post();
            
            echo do_shortcode('[coolassist_page]');

        endwhile;
        ?>
    </main>
</div>

<?php
get_footer();
?>
