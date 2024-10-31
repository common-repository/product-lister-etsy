<?php
/**
 * Plugin Name: Product Lister For Etsy
 * Plugin URI:  https://cedcommerce.com
 * Description: Product Lister for Etsy allows merchants to list their products on Etsy marketplace.
 * Version: 3.1.2
 * Author: CedCommerce
 * Author URI:  https://woocommerce.com/vendor/cedcommerce/
 * Text Domain: product-lister-etsy
 * Domain Path: /languages
 *
 * Woo: 5712585:9d5ab77db564bf30538b38e556b7b183
 * WC requires at least: 3.0
 * WC tested up to: 8.6.1
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PRODUCT_LISTER_INTEGRATION_VERSION', '2.3.6' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-etsy-integration-activator.php
 */
function activate_ced_etsy_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-etsy-integration-activator.php';
	ced_Etsy_Integration_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-etsy-integration-deactivator.php
 */
function deactivate_ced_etsy_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-etsy-integration-deactivator.php';
	ced_Etsy_Integration_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ced_etsy_integration' );
register_deactivation_hook( __FILE__, 'deactivate_ced_etsy_integration' );

/* DEFINE CONSTANTS */
! defined( 'CED_ETSY_LOG_DIRECTORY' ) ? define( 'CED_ETSY_LOG_DIRECTORY', wp_upload_dir()['basedir'] . '/etsy_logs' ) : '';
! defined( 'CED_ETSY_VERSION' ) ? define( 'CED_ETSY_VERSION', '1.0.0' ) : '';
! defined( 'CED_ETSY_PREFIX' ) ? define( 'CED_ETSY_PREFIX', 'ced_etsy' ) : '';
! defined( 'CED_ETSY_DIRPATH' ) ? define( 'CED_ETSY_DIRPATH', plugin_dir_path( __FILE__ ) ) : '';
! defined( 'CED_ETSY_URL' ) ? define( 'CED_ETSY_URL', plugin_dir_url( __FILE__ ) ) : '';
! defined( 'CED_ETSY_ABSPATH' ) ? define( 'CED_ETSY_ABSPATH', untrailingslashit( plugin_dir_path( __DIR__ ) ) ) : '';
! defined( 'CED_ETSY_LISTER_PLUGIN_BASENAME' ) ? define( 'CED_ETSY_LISTER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ) : '';


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-etsy-integration.php';
/**
* This file includes core functions to be used globally in plugin.
*/
require_once plugin_dir_path( __FILE__ ) . 'includes/ced-etsy-core-functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ced_etsy_integration() {

	$plugin = new ced_Etsy_Integration();
	$plugin->run();
}




/**
 * Runs only when the plugin is activated.
 *
 * @since 1.0.0
 */
function ced_admin_notice_example_activation_hook_ced_etsy() {

	/* Create transient data */
	set_transient( 'ced-etsy-admin-notice', true, 5 );
}



/**
 * Admin Notice on Activation.
 *
 * @since 0.1.0
 */


function ced_etsy_admin_notice_activation() {

	/* Check transient, if available display notice */
	if ( get_transient( 'ced-etsy-admin-notice' ) ) {?>
		<div class="updated notice is-dismissible">
			<p>Welcome to Product Lister. Start listing, syncing, managing, & automating your WooCommerce and Etsy stores to boost sales.</p>
			<p> To get started , proceed with <a href="admin.php?page=ced_etsy" class ="ced_configuration_plugin_main">connecting</a> your Etsy marketplace account. </p>
		</div>
		<?php
		/* Delete transient, only display this notice once. */
		delete_transient( 'ced-etsy-admin-notice' );
	}
}

if ( ced_etsy_check_woocommerce_active() ) {
	add_action(
		'before_woocommerce_init',
		function() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}
	);

	run_ced_etsy_integration();
	/* Register activation hook. */
	register_activation_hook( __FILE__, 'ced_admin_notice_example_activation_hook_ced_etsy' );
	/*Admin admin notice */

	add_action( 'admin_notices', 'ced_etsy_admin_notice_activation' );
} else {
	add_action( 'admin_init', 'deactivate_ced_etsy_woo_missing' );
}

