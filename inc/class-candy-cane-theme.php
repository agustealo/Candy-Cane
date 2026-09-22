<?php
/**
 * Core theme bootstrap for Candy Cane.
 *
 * @package Candy_Cane
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Candy Cane's WordPress integrations while preserving its classic
 * theme contracts and markup conventions.
 */
final class Candy_Cane_Theme {
	/**
	 * Boot the theme hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		add_action( 'after_setup_theme', array( __CLASS__, 'setup' ) );
		add_action( 'widgets_init', array( __CLASS__, 'register_sidebars' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'preserve_home_category_exclusions' ) );
	}

	/**
	 * Register theme supports, image sizes, navigation locations, and text domain.
	 *
	 * @return void
	 */
	public static function setup() {
		load_theme_textdomain( 'candy-cane', get_template_directory() . '/languages' );

		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support(
			'html5',
			array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'search-form',
				'style',
				'script',
			)
		);

		// Preserve the image-size identifiers and crop positions used by existing sites.
		add_image_size( 'front', 210, 210, array( 'center', 'top' ) );
		add_image_size( 'name_size', 460, 345, array( 'center', 'top' ) );

		register_nav_menus(
			array(
				'header-menu1' => __( 'Main Navigation', 'candy-cane' ),
				'header-menu2' => __( 'Secondary Navigation', 'candy-cane' ),
			)
		);

		$GLOBALS['content_width'] = isset( $GLOBALS['content_width'] ) ? (int) $GLOBALS['content_width'] : 554;
	}

	/**
	 * Register the historical Candy Cane widget areas without changing their IDs.
	 *
	 * @return void
	 */
	public static function register_sidebars() {
		register_sidebar(
			array(
				'name'          => __( 'Right Sidebar', 'candy-cane' ),
				'id'            => 'right_sidebar',
				'before_widget' => '<li id="%1$s" class="widget %2$s">',
				'after_widget'  => '</li>',
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			)
		);

		for ( $footer = 1; $footer <= 4; $footer++ ) {
			$classes = 'three columns footget';
			if ( 4 === $footer ) {
				$classes .= ' fixie';
			}

			register_sidebar(
				array(
					/* translators: %d: footer widget area number. */
					'name'          => sprintf( __( 'Footer %d', 'candy-cane' ), $footer ),
					'id'            => 'footer_' . $footer,
					'before_widget' => '<div class="' . esc_attr( $classes ) . '">',
					'after_widget'  => '</div>',
					'before_title'  => '<h4>',
					'after_title'   => '</h4>',
				)
			);
		}
	}

	/**
	 * Load the original Candy Cane assets through WordPress's dependency system.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		$theme   = wp_get_theme();
		$version = $theme->get( 'Version' );

		wp_enqueue_style(
			'candy-cane-style',
			get_stylesheet_uri(),
			array(),
			$version
		);

		wp_enqueue_script(
			'candy-cane-foundation-compat',
			get_template_directory_uri() . '/javascripts/foundation.js',
			array( 'jquery' ),
			$version,
			true
		);

		wp_enqueue_script(
			'candy-cane-app',
			get_template_directory_uri() . '/javascripts/app.js',
			array( 'jquery', 'candy-cane-foundation-compat' ),
			$version,
			true
		);

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Preserve the historical home-page behavior without replacing the main query.
	 *
	 * Candy Cane has always hidden categories 1 and 5 from the posts index. The
	 * legacy theme did this with query_posts(), which overwrote the main query and
	 * could break pagination. This filter keeps the same result using the canonical
	 * WordPress query lifecycle.
	 *
	 * @param WP_Query $query Main query object.
	 * @return void
	 */
	public static function preserve_home_category_exclusions( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() ) {
			return;
		}

		$query->set( 'category__not_in', array( 1, 5 ) );
	}
}
