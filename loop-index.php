<div class="twelve columns">


    <!-- Exclude Categories -->
    <?php if ( is_home() ) {
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
query_posts($query_string . '&cat=-1,-5&paged='.$paged);
} ?>

    <!-- End Exclude Categories -->      


	<!-- Start the Loop -->	
<?php  $postindex = 1;  ?>
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>


		<!-- Begin the first article -->

		<article class="three columns<?php if(($postindex % 4) == 0){ echo ' fixie';}?>">

 <div id="post-<?php the_ID(); ?>" <?php post_class( 'img-cont' ); ?>>
<a href="<?php the_permalink(); ?>">

<?php if ( has_post_thumbnail() ) {
the_post_thumbnail('front');
} else { ?>
<img src="<?php bloginfo('template_directory'); ?>/images/none.gif" alt="<?php the_title(); ?>" />
<?php } ?>

  </a>

			<!-- Display the Title as a link to the Post's permalink. -->
			<div class="mask">
            <h2>
				<a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
			</h2>
			
                 <div class="inmeta">
			<?php the_time('F jS, Y') ?>
		    </div>

</div>
</div>
		</article>
<?php if(($postindex % 4) == 0){ echo '<div class="clear"></div>';}?>
             <?php ++$postindex; ?>

		<!-- Closes the first article -->

	<!-- Stop The Loop (but note the "else:" - see next line). -->
	<?php endwhile; else: ?>
	
		<!-- The very first "if" tested to see if there were any Posts to -->
		<!-- display.  This "else" part tells what do if there weren't any. -->
		<p>Sorry, no posts matched your criteria.</p>
	
	<!--End the loop -->
	<?php endif; ?>

	<?php if (function_exists("emm_paginate")) {
	    emm_paginate();

	} ?>	     


</div>
