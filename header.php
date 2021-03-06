<!DOCTYPE html>

<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 8]><!--> <html <?php language_attributes(); ?>> <!--<![endif]-->
<head>
	<meta charset="utf-8" />
	
	<!-- Set the viewport width to device width for mobile -->
    <meta name="viewport" content="initial-scale=1.6; maximum-scale=1.0; width=device-width; "/>
	
	<title><?php bloginfo('name'); ?> | <?php is_home() ? bloginfo('description') : wp_title(''); ?></title>
  
	<!-- Included CSS Files -->
	<link rel="stylesheet" href="<?php bloginfo( 'stylesheet_url' ); ?>">
	
	<!-- IE Fix for HTML5 Tags -->
	<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<?php if (function_exists('wp_enqueue_script') && function_exists('is_singular')) : ?>
<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
<?php endif; ?>
<?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>

	<!-- Begin Container -->
	<div class="container">
		
		<!-- Header Row -->
		<div class="row">
			
				<!-- Header Column -->
				<div class="two columns" id="access" role="navigation">
					
				
					<!-- Site Description & Title -->
					<hgroup id="header">
						<h1><a href="<?php echo site_url(); ?>"><?php bloginfo('title'); ?></a></h1>
					</hgroup>
		</div>
					<!-- Navigation --> 	
				<div class="ten columns fixie" id="access" role="navigation">
        <?php wp_nav_menu( array( 'theme_location' => 'header-menu2','menu_class' => 'nav-bar2','depth' => 1,'container' => 'nav') ); ?>				
 <?php wp_nav_menu( array( 'theme_location' => 'header-menu1','menu_class' => 'nav-bar','depth' => 1,'container' => 'nav') ); ?>
	 		
				</div>
				<!-- Header Column -->
				
        </div>

		<!-- Header Row -->
		
		<!-- Main Row -->
	<div class="row wrap">

