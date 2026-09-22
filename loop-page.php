<?php
/**
 * Page loop template.
 *
 * @package Candy_Cane
 */

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<div class="two columns">
			<h2 class="potitle"><?php the_title(); ?></h2>
		</div>

		<div class="seven columns">
			<div class="entry">
				<?php the_content(); ?>
			</div>
			<?php wp_link_pages(); ?>
		</div>
		<?php
	endwhile;
else :
	?>
	<div class="alert-box error"><?php esc_html_e( 'Sorry, the page you requested was not found', 'candy-cane' ); ?></div>
	<?php
endif;
