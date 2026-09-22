<?php
/**
 * Template Name: Archives
 *
 * @package Candy_Cane
 */

get_header();
?>

<div class="nine columns">
	<?php
	the_post();
	?>
	<h2 class="potitle"><?php the_title(); ?></h2>

	<?php
	$candy_cane_archive_query = new WP_Query(
		array(
			'posts_per_page'      => -1,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	$candy_cane_post_index = 1;

	while ( $candy_cane_archive_query->have_posts() ) :
		$candy_cane_archive_query->the_post();
		$candy_cane_archive_classes = array( 'six', 'columns', 'arkib' );

		if ( 0 === $candy_cane_post_index % 2 ) {
			$candy_cane_archive_classes[] = 'fixie';
		}
		?>
		<article class="<?php echo esc_attr( implode( ' ', $candy_cane_archive_classes ) ); ?>">
			<a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" rel="bookmark"><?php the_title(); ?></a>
		</article>
		<?php
		++$candy_cane_post_index;
	endwhile;

	wp_reset_postdata();
	?>
</div>

<?php
get_sidebar();
get_footer();
