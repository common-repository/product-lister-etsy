<?php
$active_channel = ! empty( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : 'home';
?>
<div class="ced-notification-top-wrap">
	<div class="woocommerce-layout__header">
		<div class="woocommerce-layout__header-wrapper">
			<h1 data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text woocommerce-layout__header-heading css-wv5nn e19lxcc00">
				<?php
				echo esc_attr( ucwords( $active_channel ) . ( isset( $_GET['section'] ) ? ' > ' . ucwords( str_replace( '-', ' ', sanitize_text_field( $_GET['section'] ) ) ) : ( isset( $_GET['action'] ) ? ' > ' . ucwords( str_replace( '-', ' ', sanitize_text_field( $_GET['action'] ) ) ) : '' ) ) );
				?>
			</h1>
		</div>
	</div>
</div>
<div class='ced-header-wrapper'>
	<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sales_channel' ) ); ?>" class="nav-tab <?php echo ( 'home' === $active_channel ? 'nav-tab-active' : '' ); ?>">
			<?php esc_html_e( 'Home', 'product-lister-etsy' ); ?>
		</a>
		<?php

		/**
		 * Getting list of all sale channel
		 *
		 * @since 1.0.0
		 */
		$navigation_tabs = apply_filters( 'ced_sales_channels_list', array() );

		foreach ( $navigation_tabs as $navigation ) {
			if ( $navigation['is_active'] ) {
				echo '<a href="' . esc_url( ced_get_navigation_url( $navigation['menu_link'] ) ) . '" class="nav-tab ' . ( $navigation['menu_link'] === $active_channel ? 'nav-tab-active' : '' ) . '">';
				echo esc_html( $navigation['tab'] );
				echo '</a>';
			}
		}
		if ( file_exists( CED_ETSY_DIRPATH . 'admin/template/pricing/class-ced-pricing-page.php' ) ) {
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sales_channel&channel=pricing' ) ); ?>" class="nav-tab <?php echo ( 'pricing' === $active_channel ? 'nav-tab-active' : '' ); ?>">
				<?php esc_html_e( 'Pricing', 'product-lister-etsy' ); ?>
			</a>
			<?php
		}
		?>

	</nav>
</div>
<?php
