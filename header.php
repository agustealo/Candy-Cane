<?php
/**
 * Site header.
 *
 * @package Candy_Cane
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text skip-link" href="#primary-content"><?php esc_html_e( 'Skip to content', 'candy-cane' ); ?></a>

<div class="container">
	<div class="row">
		<div class="two columns" id="site-branding">
			<header id="header">
				<h1><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
			</header>
		</div>

		<div class="ten columns fixie" id="access" role="navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'candy-cane' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'header-menu2',
					'menu_class'     => 'nav-bar2',
					'depth'          => 1,
					'container'      => 'nav',
					'fallback_cb'    => false,
				)
			);
			wp_nav_menu(
				array(
					'theme_location' => 'header-menu1',
					'menu_class'     => 'nav-bar',
					'depth'          => 1,
					'container'      => 'nav',
					'fallback_cb'    => false,
				)
			);
			?>
		</div>
	</div>

	<div class="row wrap" id="primary-content" tabindex="-1">
