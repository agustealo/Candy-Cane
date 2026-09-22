<?php
/**
 * 404 template.
 *
 * @package Candy_Cane
 */

get_header();
?>

<div class="nine columns">
	<div class="alert-box error"><?php esc_html_e( 'Just an error.404.', 'candy-cane' ); ?></div>
	<h2><?php esc_html_e( 'Not Found', 'candy-cane' ); ?></h2>
	<p><?php esc_html_e( 'Try to search the site', 'candy-cane' ); ?></p>

	<?php get_search_form(); ?>

	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go Home', 'candy-cane' ); ?></a>
</div>

<?php
get_sidebar();
get_footer();
