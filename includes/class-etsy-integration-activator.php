<?php

/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 *
 * @package    Ced_Etsy_Integration
 * @subpackage Ced_Etsy_Integration/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ced_Etsy_Integration
 * @subpackage Ced_Etsy_Integration/includes
 */
class Ced_Etsy_Integration_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Profile
		$tableName            = $wpdb->prefix . 'ced_etsy_profiles';
		$create_profile_table =
		"CREATE TABLE $tableName (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		profile_name VARCHAR(255) NOT NULL,
		profile_status VARCHAR(255) NOT NULL,
		shop_name VARCHAR(255) DEFAULT NULL,
		profile_data TEXT DEFAULT NULL,
		woo_categories TEXT DEFAULT NULL,
		PRIMARY KEY (id)
		);";
		dbDelta( $create_profile_table );

		// Auth info
		if ( ! get_option( 'ced_etsy_auth_info', '' ) ) {
			update_option(
				'ced_etsy_auth_info',
				array(
					'scrt' => 'LA1tU+0AQ7PNGjcMmeSvVjCabqB9Lcqt',
					'ky'   => base64_encode( 'Q2VkRXRzeUBXb29AIyQlXiYqS2V5' ),
				)
			);
		}
	}
}
