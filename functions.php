<?php

// Disable WordPress version reporting as a basic protection against attacks
function remove_generators() {
	return '';
}		

add_filter('the_generator','remove_generators');

// Default thumbnail support

 add_theme_support( 'post-thumbnails' );


/*=========================/
/ Add Dynamic Crop Support
/==========================*/


// Set crop size: 
bt_add_image_size( 'front', 210, 210, array( 'center', 'top' ) );
bt_add_image_size( 'name_size', 460, 345, array( 'center', 'top' ) );

 
add_filter( 'intermediate_image_sizes_advanced', 'bt_intermediate_image_sizes_advanced' );
add_filter( 'wp_generate_attachment_metadata', 'bt_generate_attachment_metadata', 10, 2 );
 
/**
 * Registers a new image size with cropping positions
 *
 * The $crop parameter works as in the 'add_image_size' function taking true or
 * false values. If set to true, the default cropping position is 'center', 'center'.
 * 
 * The $crop parameter also takes an array of the format
 * array( x_crop_position, y_crop_position )
 * x_crop_position can be 'left', 'center', 'right'
 * y_crop_position can be 'top', 'center', 'bottom'
 * 
 * @param string $name Image size identifier.
 * @param int $width Image width.
 * @param int $height Image height.
 * @param bool|array $crop Optional, default is false. Whether to crop image to specified height and width or resize. An array can specify positioning of the crop area.
 * @return bool|array False, if no image was created. Metadata array on success.
 */
function bt_add_image_size( $name, $width = 210, $height = 210, $crop = true ) {
	global $_wp_additional_image_sizes;
	$_wp_additional_image_sizes[$name] = array( 'width' => absint( $width ), 'height' => absint( $height ), 'crop' => $crop );
}
 
 
/**
 * Returning no sizes (an empty array) will force
 * wp_generate_attachment_metadata to skip creating intermediate image sizes on
 * upload, then we can run our own resizing functions by hooking into the
 * 'wp_generate_attachment_metadata' filter
 */
function bt_intermediate_image_sizes_advanced( $sizes ) {
	return array();
}
 
 
function bt_generate_attachment_metadata( $metadata, $attachment_id ) {
    $attachment = get_post( $attachment_id );
    
    $uploadPath = wp_upload_dir();
    $file = path_join($uploadPath['basedir'], $metadata['file']);
 
	if ( !preg_match('!^image/!', get_post_mime_type( $attachment )) || !file_is_displayable_image( $file ) ) return $metadata;
 
    global $_wp_additional_image_sizes;
 
    foreach ( get_intermediate_image_sizes() as $s ) {
        $sizes[$s] = array( 'width' => '', 'height' => '', 'crop' => true );
        if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
            $sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
        else
            $sizes[$s]['width'] = get_option( "{$s}_size_w" ); // For default sizes set in options
        if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
            $sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
        else
            $sizes[$s]['height'] = get_option( "{$s}_size_h" ); // For default sizes set in options
        if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )
            $sizes[$s]['crop'] = $_wp_additional_image_sizes[$s]['crop'];
        else
            $sizes[$s]['crop'] = get_option( "{$s}_crop" );
    }
 
    foreach ( $sizes as $size => $size_data ) {
        $resized = bt_image_make_intermediate_size( $file, $size_data['width'], $size_data['height'], $size_data['crop'] );
        if ( $resized )
            $metadata['sizes'][$size] = $resized;
    }
    
    return $metadata;
}
 
 
/**
 * Resize an image to make a thumbnail or intermediate size.
 *
 * The returned array has the file size, the image width, and image height. The
 * filter 'image_make_intermediate_size' can be used to hook in and change the
 * values of the returned array. The only parameter is the resized file path.
 *
 * @param string $file File path.
 * @param int $width Image width.
 * @param int $height Image height.
 * @param bool|array $crop Optional, default is false. Whether to crop image to specified height and width or resize. An array can specify positioning of the crop area.
 * @return bool|array False, if no image was created. Metadata array on success.
 */
function bt_image_make_intermediate_size( $file, $width, $height, $crop = false ) {
	if ( $width || $height ) {
		$resized_file = bt_image_resize( $file, $width, $height, $crop, null, null, 90 );
		if ( !is_wp_error( $resized_file ) && $resized_file && $info = getimagesize( $resized_file ) ) {
			$resized_file = apply_filters('image_make_intermediate_size', $resized_file);
			return array(
				'file' => wp_basename( $resized_file ),
				'width' => $info[0],
				'height' => $info[1],
			);
		}
	}
	return false;
}
 
 
 
/**
 * Retrieve calculated resized dimensions for use in imagecopyresampled().
 *
 * Calculate dimensions and coordinates for a resized image that fits within a
 * specified width and height. If $crop is true, the largest matching central
 * portion of the image will be cropped out and resized to the required size.
 *
 * @param int $orig_w Original width.
 * @param int $orig_h Original height.
 * @param int $dest_w New width.
 * @param int $dest_h New height.
 * @param bool $crop Optional, default is false. Whether to crop image or resize.
 * @return bool|array False, on failure. Returned array matches parameters for imagecopyresampled() PHP function.
 */
function bt_image_resize_dimensions($orig_w, $orig_h, $dest_w, $dest_h, $crop = false) {
 
	if ($orig_w <= 0 || $orig_h <= 0)
		return false;
	// at least one of dest_w or dest_h must be specific
	if ($dest_w <= 0 && $dest_h <= 0)
		return false;
 
	if ( $crop ) {
		// crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
		$aspect_ratio = $orig_w / $orig_h;
		$new_w = min($dest_w, $orig_w);
		$new_h = min($dest_h, $orig_h);
 
		if ( !$new_w ) {
			$new_w = intval($new_h * $aspect_ratio);
		}
 
		if ( !$new_h ) {
			$new_h = intval($new_w / $aspect_ratio);
		}
 
		$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);
 
		$crop_w = round($new_w / $size_ratio);
		$crop_h = round($new_h / $size_ratio);
 
        if ( !is_array( $crop ) || count( $crop ) != 2 ) {
			$crop = apply_filters( 'image_resize_crop_default', array( 'center', 'center' ) );
		}
		
		switch ( $crop[0] ) {
			case 'left': $s_x = 0; break;
			case 'right': $s_x = $orig_w - $crop_w; break;
			default: $s_x = floor( ( $orig_w - $crop_w ) / 2 );
		}
 
		switch ( $crop[1] ) {
			case 'top': $s_y = 0; break;
			case 'bottom': $s_y = $orig_h - $crop_h; break;
			default: $s_y = floor( ( $orig_h - $crop_h ) / 2 );
		}
	} else {
		// don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
		$crop_w = $orig_w;
		$crop_h = $orig_h;
 
		$s_x = 0;
		$s_y = 0;
 
		list( $new_w, $new_h ) = wp_constrain_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );
	}
 
	// if the resulting image would be the same size or larger we don't want to resize it
	if ( $new_w >= $orig_w && $new_h >= $orig_h )
		return false;
 
	// the return array matches the parameters to imagecopyresampled()
	// int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
	return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );
 
}
 
 
/**
 * Scale down an image to fit a particular size and save a new copy of the image.
 *
 * The PNG transparency will be preserved using the function, as well as the
 * image type. If the file going in is PNG, then the resized image is going to
 * be PNG. The only supported image types are PNG, GIF, and JPEG.
 *
 * Some functionality requires API to exist, so some PHP version may lose out
 * support. This is not the fault of WordPress (where functionality is
 * downgraded, not actual defects), but of your PHP version.
 *
 * @since 2.5.0
 *
 * @param string $file Image file path.
 * @param int $max_w Maximum width to resize to.
 * @param int $max_h Maximum height to resize to.
 * @param bool $crop Optional. Whether to crop image or resize.
 * @param string $suffix Optional. File Suffix.
 * @param string $dest_path Optional. New image file path.
 * @param int $jpeg_quality Optional, default is 90. Image quality percentage.
 * @return mixed WP_Error on failure. String with new destination path.
 */
function bt_image_resize( $file, $max_w, $max_h, $crop = false, $suffix = null, $dest_path = null, $jpeg_quality = 90 ) {
 
	$image = wp_load_image( $file );
	if ( !is_resource( $image ) )
		return new WP_Error( 'error_loading_image', $image, $file );
 
	$size = @getimagesize( $file );
	if ( !$size )
		return new WP_Error('invalid_image', __('Could not read image size'), $file);
	list($orig_w, $orig_h, $orig_type) = $size;
 
	// Rotate if EXIF 'Orientation' is set
	// This code is from the reverted patch at
	// http://core.trac.wordpress.org/changeset/11746/trunk/wp-includes/media.php
	$rotate = false;
	if ( is_callable( 'exif_read_data' ) && in_array( $orig_type, apply_filters( 'wp_read_image_metadata_types', array( IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM ) ) ) ) {
		$exif = @exif_read_data( $file, null, true );
		if ( $exif && isset( $exif['IFD0'] ) && is_array( $exif['IFD0'] ) && isset( $exif['IFD0']['Orientation'] ) ) {
			if ( 6 == $exif['IFD0']['Orientation'] )
				$rotate = 90;
			elseif ( 8 == $exif['IFD0']['Orientation'] )
				$rotate = 270;
		}
	}
	
	if ( $rotate )
		list($max_h,$max_w) = array($max_w,$max_h);
 
	$dims = bt_image_resize_dimensions($orig_w, $orig_h, $max_w, $max_h, $crop);
	if ( !$dims )
		return new WP_Error( 'error_getting_dimensions', __('Could not calculate resized image dimensions') );
	list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $dims;
 
	$newimage = wp_imagecreatetruecolor( $dst_w, $dst_h );
 
	if ( $rotate )
		list($src_y,$src_x) = array($src_x,$src_y);
 
	imagecopyresampled( $newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
 
	// convert from full colors to index colors, like original PNG.
	if ( IMAGETYPE_PNG == $orig_type && function_exists('imageistruecolor') && !imageistruecolor( $image ) )
		imagetruecolortopalette( $newimage, false, imagecolorstotal( $image ) );
 
	// we don't need the original in memory anymore
	imagedestroy( $image );
 
	// $suffix will be appended to the destination filename, just before the extension
	if ( !$suffix ) {
		if ( $rotate )
			$suffix = "{$dst_h}x{$dst_w}";
		else
			$suffix = "{$dst_w}x{$dst_h}";
	}
 
	$info = pathinfo($file);
	$dir = $info['dirname'];
	$ext = $info['extension'];
	$name = wp_basename($file, ".$ext");
 
	if ( !is_null($dest_path) and $_dest_path = realpath($dest_path) )
		$dir = $_dest_path;
	$destfilename = "{$dir}/{$name}-{$suffix}.{$ext}";
 
	if ( IMAGETYPE_GIF == $orig_type ) {
		if ( !imagegif( $newimage, $destfilename ) )
			return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
	} elseif ( IMAGETYPE_PNG == $orig_type ) {
		if ( !imagepng( $newimage, $destfilename ) )
			return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
	} else {
		if ( $rotate ) {
			$newimage = _rotate_image_resource( $newimage, 360 - $rotate );
		}
		
		// all other formats are converted to jpg
		$destfilename = "{$dir}/{$name}-{$suffix}.jpg";
		$return = imagejpeg( $newimage, $destfilename, apply_filters( 'jpeg_quality', $jpeg_quality, 'image_resize' ) );
		if ( !$return )
			return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
	}
 
	imagedestroy( $newimage );
 
	// Set correct file permissions
	$stat = stat( dirname( $destfilename ));
	$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
	@ chmod( $destfilename, $perms );
 
	return $destfilename;
}


/*=========================/
/ End Dynamic Crop Support
/==========================*/





// Disable the admin bar, set to true if you want it to be visible.

show_admin_bar(FALSE);

// content width

if ( ! isset( $content_width ) ) $content_width = 554;

// Add theme support for Automatic Feed Links

add_theme_support( 'automatic-feed-links' );

// Custom Navigation

add_theme_support('nav-menus');

if ( function_exists( 'register_nav_menus' ) ) {
	register_nav_menus(
		array(
		  // - Header Navigation
		  'header-menu1' => 'Main Navigation',
		  'header-menu2' => 'Secondary Navigation',
		)
	);
}


// Sidebars

if (function_exists('register_sidebar')) {

	// Right Sidebar

	register_sidebar(array(
		'name'=> 'Right Sidebar',
		'id' => 'right_sidebar',
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<h4>',
		'after_title' => '</h4>',
	));
	
	// Footer Widget
	
	register_sidebar(array(
		'name'=> 'Footer 1',
		'id' => 'footer_1',
		'before_widget' => '<div class="three columns footget">',
		'after_widget' => '</div>',
		'before_title' => '<h4>',
		'after_title' => '</h4>',
	));
	register_sidebar(array(
		'name'=> 'Footer 2',
		'id' => 'footer_2',
		'before_widget' => '<div class="three columns footget">',
		'after_widget' => '</div>',
		'before_title' => '<h4>',
		'after_title' => '</h4>',
	));
	register_sidebar(array(
		'name'=> 'Footer 3',
		'id' => 'footer_3',
		'before_widget' => '<div class="three columns footget">',
		'after_widget' => '</div>',
		'before_title' => '<h4>',
		'after_title' => '</h4>',
	));
	register_sidebar(array(
		'name'=> 'Footer 4',
		'id' => 'footer_4',
		'before_widget' => '<div class="three columns footget fixie">',
		'after_widget' => '</div>',
		'before_title' => '<h4>',
		'after_title' => '</h4>',
	));
}


// Custom callback to list pings
function custom_pings($comment, $args, $depth) {
       $GLOBALS['comment'] = $comment;
        ?>
            <li id="comment-<?php comment_ID() ?>" <?php comment_class() ?>>
                <div class="comment-author"><?php printf(__('By %1$s on %2$s at %3$s', 'Foundation'),
                        get_comment_author_link(),
                        get_comment_date(),
                        get_comment_time() );
                        edit_comment_link(__('Edit', 'Foundation'), ' <span class="meta-sep">|</span> <span class="edit-link">', '</span>'); ?></div>
    <?php if ($comment->comment_approved == '0') _e('\t\t\t\t\t<span class="unapproved">Your trackback is awaiting moderation.</span>\n', 'Foundation') ?>
            <div class="comment-content">
                <?php comment_text() ?>
            </div>
<?php } // end custom_pings


// Custom Pagination
/**
 * Retrieve or display pagination code.
 *
 * The defaults for overwriting are:
 * 'page' - Default is null (int). The current page. This function will
 *      automatically determine the value.
 * 'pages' - Default is null (int). The total number of pages. This function will
 *      automatically determine the value.
 * 'range' - Default is 3 (int). The number of page links to show before and after
 *      the current page.
 * 'gap' - Default is 3 (int). The minimum number of pages before a gap is 
 *      replaced with ellipses (...).
 * 'anchor' - Default is 1 (int). The number of links to always show at begining
 *      and end of pagination
 * 'before' - Default is '<div class="emm-paginate">' (string). The html or text 
 *      to add before the pagination links.
 * 'after' - Default is '</div>' (string). The html or text to add after the
 *      pagination links.
 * 'next_page' - Default is '__('&raquo;')' (string). The text to use for the 
 *      next page link.
 * 'previous_page' - Default is '__('&laquo')' (string). The text to use for the 
 *      previous page link.
 * 'echo' - Default is 1 (int). To return the code instead of echo'ing, set this
 *      to 0 (zero).
 *
 * @author Eric Martin <eric@ericmmartin.com>
 * @copyright Copyright (c) 2009, Eric Martin
 * @version 1.0
 *
 * @param array|string $args Optional. Override default arguments.
 * @return string HTML content, if not displaying.
 */
function emm_paginate($args = null) {
	$defaults = array(
		'page' => null, 'pages' => null, 
		'range' => 3, 'gap' => 3, 'anchor' => 1,
		'before' => '<ul class="pagination">', 'after' => '</ul>',
		'title' => __('<li class="unavailable"></li>'),
		'nextpage' => __('&raquo;'), 'previouspage' => __('&laquo'),
		'echo' => 1
	);

	$r = wp_parse_args($args, $defaults);
	extract($r, EXTR_SKIP);

	if (!$page && !$pages) {
		global $wp_query;

		$page = get_query_var('paged');
		$page = !empty($page) ? intval($page) : 1;

		$posts_per_page = intval(get_query_var('posts_per_page'));
		$pages = intval(ceil($wp_query->found_posts / $posts_per_page));
	}
	
	$output = "";
	if ($pages > 1) {	
		$output .= "$before<li>$title</li>";
		$ellipsis = "<li class='unavailable'>...</li>";

		if ($page > 1 && !empty($previouspage)) {
			$output .= "<li><a href='" . get_pagenum_link($page - 1) . "'>$previouspage</a></li>";
		}
		
		$min_links = $range * 2 + 1;
		$block_min = min($page - $range, $pages - $min_links);
		$block_high = max($page + $range, $min_links);
		$left_gap = (($block_min - $anchor - $gap) > 0) ? true : false;
		$right_gap = (($block_high + $anchor + $gap) < $pages) ? true : false;

		if ($left_gap && !$right_gap) {
			$output .= sprintf('%s%s%s', 
				emm_paginate_loop(1, $anchor), 
				$ellipsis, 
				emm_paginate_loop($block_min, $pages, $page)
			);
		}
		else if ($left_gap && $right_gap) {
			$output .= sprintf('%s%s%s%s%s', 
				emm_paginate_loop(1, $anchor), 
				$ellipsis, 
				emm_paginate_loop($block_min, $block_high, $page), 
				$ellipsis, 
				emm_paginate_loop(($pages - $anchor + 1), $pages)
			);
		}
		else if ($right_gap && !$left_gap) {
			$output .= sprintf('%s%s%s', 
				emm_paginate_loop(1, $block_high, $page),
				$ellipsis,
				emm_paginate_loop(($pages - $anchor + 1), $pages)
			);
		}
		else {
			$output .= emm_paginate_loop(1, $pages, $page);
		}

		if ($page < $pages && !empty($nextpage)) {
			$output .= "<li><a href='" . get_pagenum_link($page + 1) . "'>$nextpage</a></li>";
		}

		$output .= $after;
	}

	if ($echo) {
		echo $output;
	}

	return $output;
}

/**
 * Helper function for pagination which builds the page links.
 *
 * @access private
 *
 * @author Eric Martin <eric@ericmmartin.com>
 * @copyright Copyright (c) 2009, Eric Martin
 * @version 1.0
 *
 * @param int $start The first link page.
 * @param int $max The last link page.
 * @return int $page Optional, default is 0. The current page.
 */
function emm_paginate_loop($start, $max, $page = 0) {
	$output = "";
	for ($i = $start; $i <= $max; $i++) {
		$output .= ($page === intval($i)) 
			? "<li class='current'><a href='#'>$i</a></li>" 
			: "<li><a href='" . get_pagenum_link($i) . "'>$i</a></li>";
	}
	return $output;
} 


?>