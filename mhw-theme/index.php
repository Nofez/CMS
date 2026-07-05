<?php
get_header();
// Сюда будет выводиться контент страниц
if (have_posts()) :
    while (have_posts()) : the_post();
        the_content();
    endwhile;
endif;
get_footer();
