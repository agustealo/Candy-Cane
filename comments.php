<?php
/**
 * Comments template.
 *
 * @package Candy_Cane
 */

if ( post_password_required() ) {
	return;
}
?>

<h2 id="comments">
	<?php comments_number( esc_html__( 'No Comments', 'candy-cane' ), esc_html__( '1 Comment', 'candy-cane' ), esc_html__( '% Comments', 'candy-cane' ) ); ?>
</h2>

<?php if ( have_comments() ) : ?>
	<ol id="commentlist">
		<?php
		wp_list_comments(
			array(
				'style' => 'ol',
			)
		);
		?>
	</ol>

	<?php
	the_comments_pagination(
		array(
			'prev_text' => esc_html__( 'Previous comments', 'candy-cane' ),
			'next_text' => esc_html__( 'Next comments', 'candy-cane' ),
		)
	);
	?>
<?php else : ?>
	<p><?php esc_html_e( 'No comments yet.', 'candy-cane' ); ?></p>
<?php endif; ?>

<?php if ( comments_open() ) : ?>
	<?php comment_form(); ?>
<?php elseif ( get_comments_number() ) : ?>
	<p><?php esc_html_e( 'Sorry, the comment form is closed at this time.', 'candy-cane' ); ?></p>
<?php endif; ?>
