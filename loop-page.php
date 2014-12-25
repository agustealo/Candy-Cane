<!-- Start the Loop -->

	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

<div class="two columns">	

			<h2 class="potitle"><?php the_title(); ?></h2>

</div>

<div class="seven columns">
	
		<!-- Begin the first div -->
		<div>
				
			
			<!-- Display the Page's Content in a div box. -->
			<div class="entry">
				<?php the_content(); ?>
			</div>
		
		</div>
		<!-- Closes the first div -->
	
	<!-- Stop The Loop (but note the "else:" - see next line). -->
	<?php endwhile; else: ?>
	
		<!-- The very first "if" tested to see if there were any Posts to -->
		<!-- display.  This "else" part tells what do if there weren't any. -->

		<div class="alert-box error"><?php _e('Sorry, the page you requested was not found', 'four') ?> </div>
	
	<!--End the loop -->
	<?php endif; ?>
	
</div>