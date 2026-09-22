<?php
/**
 * Single-post loop template.
 *
 * @package Candy_Cane
 */

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<div class="two columns metamon">
			<h2 class="potitle"><?php the_title(); ?></h2>
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</div>

		<div class="seven columns">
			<article>
				<div class="entry">
					<?php the_content(); ?>
				</div>

				<p class="postmetadata">
					<?php esc_html_e( 'Posted in', 'candy-cane' ); ?> <?php the_category( ', ' ); ?>
					<?php if ( has_tag() ) : ?>
						| <?php esc_html_e( 'Tags:', 'candy-cane' ); ?> <?php the_tags( '', ', ' ); ?>
					<?php endif; ?>
				</p>

				<div class="post-link">
					<div class="pagination-newer"><?php previous_post_link(); ?></div>
					<div class="pagination-older"><?php next_post_link(); ?></div>
				</div>
			</article>

			<?php wp_link_pages(); ?>
			<?php comments_template(); ?>
		</div>
		<?php
	endwhile;
else :
	?>
	<div class="alert-box error"><?php esc_html_e( 'Sorry, the page you requested was not found', 'candy-cane' ); ?></div>
	<?php
endif;
