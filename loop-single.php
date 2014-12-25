<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

<div class="two columns metamon">	
			<h2 class="potitle"><?php the_title(); ?></h2>
			<?php the_time('F jS, Y') ?>

</div>

<div class="seven columns">

	
		<!-- Begin the first article -->
		<article>
			
			<!-- Display the Post's Content in a div box. -->
			<div class="entry">
				<?php the_content(); ?>
			</div>
			
			<!-- Display a comma separated list of the Post's Categories. -->

	<p class="postmetadata"><?php _e('Posted in', 'four') ?> <?php the_category(', '); ?> | Tags: <?php the_tags(', '); ?></p>

    <div class="post-link">
     <div class="pagination-newer"><?php previous_posts_link(); ?></div> 
      <div class="pagination-older"><?php next_post_link(); ?></div>
	</div>
		
		</article>
		<!-- Closes the first article -->
			 <?php wp_link_pages( $args ); ?>
		<!-- Begin Comments -->
	    <?php comments_template( '', true ); ?>
	    <!-- End Comments -->
	
	<!-- Stop The Loop (but note the "else:" - see next line). -->
	<?php endwhile; else: ?>
	
		<!-- The very first "if" tested to see if there were any Posts to -->
		<!-- display.  This "else" part tells what do if there weren't any. -->
		<div class="alert-box error"><?php _e('Sorry, the page you requested was not found', 'four') ?> </div>

	<!--End the loop -->
	<?php endif; ?>
	
</div>
