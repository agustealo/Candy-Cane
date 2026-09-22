	</div>

	<footer class="row">
		<div class="row">
			<div class="footer clearfix">
				<?php dynamic_sidebar( 'footer_1' ); ?>
				<?php dynamic_sidebar( 'footer_2' ); ?>
				<?php dynamic_sidebar( 'footer_3' ); ?>
				<?php dynamic_sidebar( 'footer_4' ); ?>
			</div>

			<div class="footdown clear">
				<p>
					<?php
					printf(
						/* translators: 1: WordPress URL, 2: theme author URL. */
						wp_kses_post( __( '&copy; 2013 | Powered by <a href="%1$s">WordPress</a> | Design by <a href="%2$s">Agustealo Studio</a>', 'candy-cane' ) ),
						esc_url( 'https://wordpress.org/' ),
						esc_url( 'https://www.agustealo.com/' )
					);
					?>
				</p>
			</div>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
