<?php
/**
 * Posts index loop.
 *
 * @package Candy_Cane
 */

?>
<div class="twelve columns">
	<?php $postindex = 1; ?>

	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article class="three columns<?php echo 0 === ( $postindex % 4 ) ? ' fixie' : ''; ?>">
				<div id="post-<?php the_ID(); ?>" <?php post_class( 'img-cont' ); ?>>
					<a href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'front' ); ?>
						<?php else : ?>
							<img src="<?php echo esc_url( get_template_directory_uri() . '/images/none.gif' ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
						<?php endif; ?>
					</a>

					<div class="mask">
						<h2>
							<?php
							/* translators: %s: post title. */
							$permalink_title = sprintf( __( 'Permanent Link to %s', 'candy-cane' ), get_the_title() );
							?>
							<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php echo esc_attr( $permalink_title ); ?>"><?php the_title(); ?></a>
						</h2>
						<div class="inmeta"><?php echo esc_html( get_the_date( 'F jS, Y' ) ); ?></div>
					</div>
				</div>
			</article>

			<?php if ( 0 === ( $postindex % 4 ) ) : ?>
				<div class="clear"></div>
			<?php endif; ?>

			<?php ++$postindex; ?>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Sorry, no posts matched your criteria.', 'candy-cane' ); ?></p>
	<?php endif; ?>

	<?php emm_paginate(); ?>
</div>
