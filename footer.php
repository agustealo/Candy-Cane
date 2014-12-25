	</div>
	<!-- Main Row -->
	
	<!-- Footer -->
	<footer class="row">
	
	
			<div class="row">
			  <div class="footer clearfix">

			<?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('Footer 1')) : ?>
			<?php endif; ?>
				
			<?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('Footer 2')) : ?>
			<?php endif; ?>

			<?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('Footer 3')) : ?>
			<?php endif; ?>

			<?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('Footer 4')) : ?>
			<?php endif; ?>

			</div>
                        
<div class="footdown clear">

<p><?php _e('&copy; 2013 | Powered by <a href="http://www.wordpress.org">Wordpress</a> | Design by <a href="http://www.agustealo.com">Agustealo Studio</a>', 'four') ?></p>


</div>
                          
 </div>
	
	</footer>
	<!-- Footer -->

	</div>
	<!-- container -->

	<!-- Included JS Files -->	
	<script src="<?php echo get_template_directory_uri(); ?>/javascripts/foundation.js"></script>
	<script src="<?php echo get_template_directory_uri(); ?>/javascripts/app.js"></script>

	<?php wp_footer(); ?>
	
</body>
</html>