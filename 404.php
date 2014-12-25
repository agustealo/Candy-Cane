<?php get_header(); ?>
	
	<div class="nine columns">
	<div class="alert-box error"><?php _e('Just an error.404.', 'four') ?></div>
	<h2><?php _e('Not Found', 'four') ?></h2>
	<p><?php _e('Try to search the site', 'four') ?></p>
	
	<?php get_search_form(); ?>
	
	<a href="<?php echo home_url( '/' ); ?>"><?php _e('Go Home', 'four') ?></a>

        </div>

<?php get_sidebar(); ?>
		
<?php get_footer(); ?>