<?php 
/*
Template Name: Archives
*/

get_header();

?>

<!-- archives -->

<div class="nine columns">

	<?php the_post(); ?>
			<h2 class="potitle"><?php the_title(); ?></h2>

<?php 
   $my_query = new WP_Query('posts_per_page=-1');
   $postindex = 1;
   while ($my_query->have_posts()) : $my_query->the_post();$do_not_duplicate = $post->ID;
?>

<article class="six columns arkib<?php if(($postindex % 2) == 0){ echo ' fixie';}?>">

   <a href="<?php the_permalink() ?>" title="<?php the_title() ?>" rel="bookmark"><?php the_title() ?></a>

</article>

             <?php ++$postindex; ?>
                        <?php endwhile; ?>

</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>

<!-- archives -->