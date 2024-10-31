<?php
namespace Cedcommerce\Template\View;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


/**
 * The Settings specific class..
 *
 * Ced_View_Settings class is rending fields which are required to show on the settings tab.
 *
 * @package    Ced_Etsy_Integration
 * @subpackage Ced_Etsy_Integration/View/Settings
 */
class Ced_View_Header {
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @var      string    $plugin_name   The shop Name.
	 */
	public $shop_name;
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @var      string    $plugin_name   The shop Name.
	 */
	public $section;
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @var      string    $plugin_name   The shop Name.
	 */
	public $not_show;
	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function __construct( $shop_name = '' ) {
		$this->shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : get_option( 'ced_etsy_shop_name', '' );
		$this->section   = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'overview';
		update_option( 'ced_etsy_shop_name', trim( $this->shop_name ) );
		?>
		<div class="ced-menu-container">
			<?php
			$current_uri = isset( $_SERVER['REQUEST_URI'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=sales_channel&channel=etsy' );
			$parts       = parse_url( $current_uri );
			$query       = array();
			if ( isset( $parts['query'] ) ) {
				parse_str( $parts['query'], $query );
			}
			print_r( $this->header_wrap_view( $this->section, $this->shop_name ) );
			?>
			<div class="ced-right">
				<select style="min-width: 160px;" class="attachment-filters" id="ced_etsy_switch_account">
					<?php
					foreach ( get_etsy_connected_accounts() as $account ) {
						$query['shop_name'] = $account['details']['ced_etsy_shop_name'];
						?>
						<option value="<?php echo esc_url( ced_get_navigation_url( 'etsy', $query ) ); ?>" <?php selected( $account['details']['ced_etsy_shop_name'], get_etsy_shop_name() ); ?>><?php echo esc_html( $account['details']['ced_etsy_shop_name'] ); ?></option>
						<?php
					}
					?>
					<option value="<?php echo esc_url( ced_get_navigation_url( 'etsy', array( 'add-new-account' => 'yes' ) ) ); ?>"><?php esc_html_e( '+ Add another account', 'product-lister-etsy' ); ?></option>
				</select>
			</div>
		</div>
		<div class="success-admin-notices"></div>
		<?php
	}

	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function header_wrap_view( $curnt_section = '', $curnt_shopname = '' ) {
		$view                = '';
		$view                = '<ul class="subsubsub">';
			$header_sections = $this->header_sections();
			$this->not_show  = array( 'shipping-add', 'shipping-edit', 'profile-edit' );
			$count           = 1;
			$total_items     = count( $header_sections );
		foreach ( $header_sections as $section => $name ) {
			$count++;
			if ( in_array( $section, $this->not_show ) ) {
				continue;
			}
			$view .= '
			    <li class="all"><a href="' . esc_url( $this->section_url( $section, $this->shop_name ) ) . '" class="' . esc_attr( $this->check_active( $this->section, $section ) ) . '" aria-current="page">' . esc_html( ucfirst( $name ) ) . '</a> ' . esc_html( ( $count != $total_items ? '|' : '' ) ) . '</li>';
		}
			$view .= '
			</ul>';
		return $view;
	}

	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function check_active( $current_section, $view_sec ) {
		if ( $current_section === $view_sec ) {
			return 'current';
		} else {
			return '';
		}
	}

	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function section_url( $section = '', $shop_name = '' ) {
		if ( empty( $section ) || empty( $shop_name ) ) {
			$section   = $this->section;
			$shop_name = $this->shop_name;
		}
		return admin_url( 'admin.php?page=sales_channel&channel=etsy&section=' . $section . '&shop_name=' . $shop_name );
	}

	/**
	 * The Current shop name which currently active now.
	 *
	 * @since    2.1.3
	 * @param      string $plugin_name   The shop Name.
	 */
	public function header_sections() {
		return array(
			'overview'     => __( 'Overview', 'product-lister-etsy' ),
			'settings'     => __( 'Settings', 'product-lister-etsy' ),
			'templates'    => __( 'Templates', 'product-lister-etsy' ),
			'products'     => __( 'Products', 'product-lister-etsy' ),
			'timeline'     => __( 'Timeline', 'product-lister-etsy' ),
			'profile-edit' => __( 'Profile Edit', 'product-lister-etsy' ),
		);
	}
}

