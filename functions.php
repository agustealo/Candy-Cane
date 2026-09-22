<?php
/**
 * Candy Cane theme functions and backward-compatibility layer.
 *
 * @package Candy_Cane
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/inc/class-candy-cane-theme.php';

Candy_Cane_Theme::boot();

/**
 * Remove the WordPress generator meta output.
 *
 * Kept under its historical public function name for child-theme compatibility.
 *
 * @return string
 */
function remove_generators() {
	return '';
}
add_filter( 'the_generator', 'remove_generators' );

/**
 * Backward-compatible wrapper for Candy Cane's former custom image-size helper.
 *
 * WordPress Core has supported crop-position arrays natively for years, so the
 * old custom GD pipeline is no longer needed.
 *
 * @param string     $name   Image size identifier.
 * @param int        $width  Width in pixels.
 * @param int        $height Height in pixels.
 * @param bool|array $crop   Crop setting or crop-position array.
 * @return void
 */
function bt_add_image_size( $name, $width = 210, $height = 210, $crop = true ) {
	add_image_size( $name, absint( $width ), absint( $height ), $crop );
}

/**
 * Compatibility no-op for code that still calls the old image-size filter.
 *
 * This function is deliberately no longer attached to
 * intermediate_image_sizes_advanced. Returning the incoming sizes allows Core
 * to generate its normal responsive image set.
 *
 * @param array $sizes Intermediate image sizes.
 * @return array
 */
function bt_intermediate_image_sizes_advanced( $sizes ) {
	return $sizes;
}

/**
 * Compatibility no-op for the retired custom attachment-metadata generator.
 *
 * @param array $metadata      Attachment metadata.
 * @param int   $attachment_id Attachment ID.
 * @return array
 */
function bt_generate_attachment_metadata( $metadata, $attachment_id ) {
	unset( $attachment_id );
	return $metadata;
}

/**
 * Compatibility wrapper around WordPress's canonical resize-dimension helper.
 *
 * @param int        $orig_w Original width.
 * @param int        $orig_h Original height.
 * @param int        $dest_w Destination width.
 * @param int        $dest_h Destination height.
 * @param bool|array $crop   Crop behavior.
 * @return false|array
 */
function bt_image_resize_dimensions( $orig_w, $orig_h, $dest_w, $dest_h, $crop = false ) {
	return image_resize_dimensions( $orig_w, $orig_h, $dest_w, $dest_h, $crop );
}

/**
 * Resize an image using WordPress's current image editor abstraction.
 *
 * Kept for backward compatibility with child themes that called the original
 * Candy Cane helper directly.
 *
 * @param string     $file         Source file path.
 * @param int        $max_w        Maximum width.
 * @param int        $max_h        Maximum height.
 * @param bool|array $crop         Crop behavior.
 * @param string     $suffix       Optional destination suffix.
 * @param string     $dest_path    Optional destination directory.
 * @param int        $jpeg_quality JPEG quality.
 * @return string|WP_Error
 */
function bt_image_resize( $file, $max_w, $max_h, $crop = false, $suffix = null, $dest_path = null, $jpeg_quality = 90 ) {
	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		return $editor;
	}

	$quality_result = $editor->set_quality( max( 1, min( 100, (int) $jpeg_quality ) ) );
	if ( is_wp_error( $quality_result ) ) {
		return $quality_result;
	}

	$resize_width  = $max_w ? absint( $max_w ) : null;
	$resize_height = $max_h ? absint( $max_h ) : null;
	$resize_result = $editor->resize( $resize_width, $resize_height, $crop );
	if ( is_wp_error( $resize_result ) ) {
		return $resize_result;
	}

	if ( null === $suffix || '' === $suffix ) {
		$size   = $editor->get_size();
		$suffix = absint( $size['width'] ) . 'x' . absint( $size['height'] );
	}

	$destination = $editor->generate_filename( sanitize_file_name( $suffix ), $dest_path );
	$saved       = $editor->save( $destination );

	if ( is_wp_error( $saved ) ) {
		return $saved;
	}

	return $saved['path'];
}

/**
 * Create an intermediate image size using the modern editor compatibility path.
 *
 * @param string     $file   Source file path.
 * @param int        $width  Width.
 * @param int        $height Height.
 * @param bool|array $crop   Crop behavior.
 * @return false|array
 */
function bt_image_make_intermediate_size( $file, $width, $height, $crop = false ) {
	if ( ! $width && ! $height ) {
		return false;
	}

	$resized_file = bt_image_resize( $file, $width, $height, $crop );
	if ( is_wp_error( $resized_file ) ) {
		return false;
	}

	$image = wp_getimagesize( $resized_file );
	if ( ! $image ) {
		return false;
	}

	return array(
		'file'      => wp_basename( $resized_file ),
		'width'     => (int) $image[0],
		'height'    => (int) $image[1],
		'mime-type' => isset( $image['mime'] ) ? $image['mime'] : '',
		'filesize'  => file_exists( $resized_file ) ? filesize( $resized_file ) : 0,
	);
}

/**
 * Historical callback used to render pingbacks and trackbacks.
 *
 * @param WP_Comment $comment Comment object.
 * @param array      $args    Comment arguments.
 * @param int        $depth   Comment depth.
 * @return void
 */
function custom_pings( $comment, $args, $depth ) {
	unset( $args, $depth );
	?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
		<div class="comment-author">
			<?php
			printf(
				/* translators: 1: comment author, 2: comment date, 3: comment time. */
				wp_kses_post( __( 'By %1$s on %2$s at %3$s', 'candy-cane' ) ),
				wp_kses_post( get_comment_author_link( $comment ) ),
				esc_html( get_comment_date( '', $comment ) ),
				esc_html( get_comment_time( '', false, true, $comment ) )
			);
			edit_comment_link( esc_html__( 'Edit', 'candy-cane' ), ' <span class="meta-sep">|</span> <span class="edit-link">', '</span>' );
			?>
		</div>
		<?php if ( '0' === $comment->comment_approved ) : ?>
			<span class="unapproved"><?php esc_html_e( 'Your trackback is awaiting moderation.', 'candy-cane' ); ?></span>
		<?php endif; ?>
		<div class="comment-content"><?php comment_text( $comment ); ?></div>
	<?php
}

/**
 * Render Candy Cane's historical pagination markup using safe modern output.
 *
 * @param array|string|null $args Pagination arguments.
 * @return string
 */
function emm_paginate( $args = null ) {
	$defaults = array(
		'page'         => null,
		'pages'        => null,
		'range'        => 3,
		'gap'          => 3,
		'anchor'       => 1,
		'before'       => '<ul class="pagination">',
		'after'        => '</ul>',
		'title'        => '',
		'nextpage'     => '&raquo;',
		'previouspage' => '&laquo;',
		'echo'         => 1,
	);

	$args  = wp_parse_args( $args, $defaults );
	$page  = $args['page'];
	$pages = $args['pages'];

	if ( ! $page && ! $pages ) {
		global $wp_query;

		$page           = max( 1, absint( get_query_var( 'paged' ) ) );
		$posts_per_page = max( 1, absint( get_query_var( 'posts_per_page' ) ) );
		$pages          = (int) ceil( $wp_query->found_posts / $posts_per_page );
	}

	$page   = max( 1, absint( $page ) );
	$pages  = max( 0, absint( $pages ) );
	$range  = max( 0, absint( $args['range'] ) );
	$gap    = max( 0, absint( $args['gap'] ) );
	$anchor = max( 1, absint( $args['anchor'] ) );
	$output = '';

	if ( $pages > 1 ) {
		$output .= wp_kses_post( $args['before'] );
		$output .= '<li class="unavailable">' . wp_kses_post( $args['title'] ) . '</li>';
		$ellipsis = '<li class="unavailable">&hellip;</li>';

		if ( $page > 1 && ! empty( $args['previouspage'] ) ) {
			$output .= '<li><a href="' . esc_url( get_pagenum_link( $page - 1 ) ) . '">' . wp_kses_post( $args['previouspage'] ) . '</a></li>';
		}

		$min_links  = ( $range * 2 ) + 1;
		$block_min  = max( 1, min( $page - $range, $pages - $min_links ) );
		$block_high = min( $pages, max( $page + $range, $min_links ) );
		$left_gap   = ( $block_min - $anchor - $gap ) > 0;
		$right_gap  = ( $block_high + $anchor + $gap ) < $pages;

		if ( $left_gap && ! $right_gap ) {
			$output .= emm_paginate_loop( 1, $anchor, $page ) . $ellipsis . emm_paginate_loop( $block_min, $pages, $page );
		} elseif ( $left_gap && $right_gap ) {
			$output .= emm_paginate_loop( 1, $anchor, $page ) . $ellipsis . emm_paginate_loop( $block_min, $block_high, $page ) . $ellipsis . emm_paginate_loop( $pages - $anchor + 1, $pages, $page );
		} elseif ( $right_gap ) {
			$output .= emm_paginate_loop( 1, $block_high, $page ) . $ellipsis . emm_paginate_loop( $pages - $anchor + 1, $pages, $page );
		} else {
			$output .= emm_paginate_loop( 1, $pages, $page );
		}

		if ( $page < $pages && ! empty( $args['nextpage'] ) ) {
			$output .= '<li><a href="' . esc_url( get_pagenum_link( $page + 1 ) ) . '">' . wp_kses_post( $args['nextpage'] ) . '</a></li>';
		}

		$output .= wp_kses_post( $args['after'] );
	}

	if ( ! empty( $args['echo'] ) ) {
		echo wp_kses_post( $output );
	}

	return $output;
}

/**
 * Build a range of page links for emm_paginate().
 *
 * @param int $start First page number.
 * @param int $max   Last page number.
 * @param int $page  Current page number.
 * @return string
 */
function emm_paginate_loop( $start, $max, $page = 0 ) {
	$output = '';
	$start  = max( 1, absint( $start ) );
	$max    = max( 0, absint( $max ) );
	$page   = absint( $page );

	for ( $index = $start; $index <= $max; $index++ ) {
		if ( $page === $index ) {
			$output .= '<li class="current"><a href="' . esc_url( get_pagenum_link( $index ) ) . '" aria-current="page">' . esc_html( (string) $index ) . '</a></li>';
		} else {
			$output .= '<li><a href="' . esc_url( get_pagenum_link( $index ) ) . '">' . esc_html( (string) $index ) . '</a></li>';
		}
	}

	return $output;
}
