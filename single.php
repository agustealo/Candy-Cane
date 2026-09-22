<?php
/**
 * Single post template.
 *
 * @package Candy_Cane
 */

get_header();
get_template_part( 'loop', 'single' );
get_sidebar();
get_footer();
