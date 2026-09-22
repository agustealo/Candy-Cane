<?php
/**
 * Search form template.
 *
 * @package Candy_Cane
 */

$candy_cane_search_field_id = wp_unique_id( 'candy-cane-search-field-' );
?>
<form role="search" method="get" id="searchform" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div>
		<label class="screen-reader-text" for="<?php echo esc_attr( $candy_cane_search_field_id ); ?>"><?php esc_html_e( 'Search for:', 'candy-cane' ); ?></label>
		<input type="search" class="search-field" placeholder="<?php echo esc_attr_x( 'Search', 'search form placeholder', 'candy-cane' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" id="<?php echo esc_attr( $candy_cane_search_field_id ); ?>" />
	</div>
</form>
