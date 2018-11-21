<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Only If Not Already Defined
if ( ! class_exists( 'WC_Settings_BMEW' ) ) :

// Woo Benchmark Email Settings Class
class WC_Settings_BMEW extends WC_Settings_Page {

	// Constructor
	public function __construct() {
		$this->id = 'bmew';
		$this->label = __( 'Benchmark Email', 'woo-benchmark-email' );
		add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_page' ], 20 );
		add_action( 'woocommerce_settings_' . $this->id, [ $this, 'output' ] );
		add_action( 'woocommerce_settings_save_' . $this->id, [ $this, 'save' ] );
	}

	// Get Settings Array
	public function get_settings() {

		// Load Messages
		bmew_admin::wp_dashboard_setup();

		// Return Settings Array
		return apply_filters( 'woocommerce_' . $this->id . '_settings', [

			// Add Section Title
			[ 'desc' => '', 'id' => 'bmew_title', 'name' => 'Benchmark Email', 'type' => 'title' ],

			// Add Skip Cart Field
			[
				'desc' => '<br>' . __( 'Skips the cart step and redirects customers to the checkout form that conveniently displays a mini cart.', 'woo-benchmark-email' )
					. '<br>' . __( "If they need to edit their cart, they will have to click on your theme's cart link in order to do so.", 'woo-benchmark-email' ),
				'desc_tip' => __( 'This may improve the chances of the email address being provided by customers and thus available to abandoned cart offers.', 'woo-benchmark-email' ),
				'id' => 'bmew_skip_cart',
				'name' => __( 'Skip the cart step', 'woo-benchmark-email' ),
				'type' => 'checkbox',
			],

			// Add Move Email Field
			[
				'desc' => '<br>' . __( 'Moves the email address and phone number fields up and underneath the name fields.', 'woo-benchmark-email' ),
				'desc_tip' => __( 'This may improve the chances of the email address being provided by customers and thus available to abandoned cart offers.', 'woo-benchmark-email' ),
				'id' => 'bmew_checkout_reorder',
				'name' => __( 'Move email field up', 'woo-benchmark-email' ),
				'type' => 'checkbox',
			],

			// Add Optin Toggle Field
			[
				'default' => __( 'Opt-in to receive exclusive customer communications', 'woo-benchmark-email' ),
				'desc' => '<br>' . __( 'Checkout form opt-in field label', 'woo-benchmark-email' ),
				'desc_tip' => __( 'Label for checkout form opt-in checkbox field.', 'woo-benchmark-email' ) . ' '
					. __( 'Leave this setting blank to eliminate the opt-in field from your checkout form.', 'woo-benchmark-email' ),
				'id' => 'bmew_checkout_optin_label',
				'name' => __( 'Checkout Opt-In Field', 'woo-benchmark-email' ),
				'type' => 'text',
			],

			// Add API Key Field
			[
				'desc' => sprintf(
					'<a id="get_api_key" class="button" href="#">%s</a><br />%s',
					__( 'Get API Key', 'woo-benchmark-email' ),
					__( 'API Key from your Benchmark Email account', 'woo-benchmark-email' )
				),
				'desc_tip' => __( 'Log into https://ui.benchmarkemail.com and copy your API key here.', 'woo-benchmark-email' ),
				'id' => 'bmew_key',
				'name' => __( 'API Key', 'woo-benchmark-email' ),
				'type' => 'text',
			],

			// Add Debug Toggle Field
			[
				'desc' => '<br>' . __( 'For temporary use, saves all API communications into WooCommerce > Status > Logs.', 'woo-benchmark-email' ),
				'desc_tip' => __( "For a nicer logs UI, set `define( 'WC_LOG_HANDLER', 'WC_Log_Handler_DB' );` inside your  `wp-config.php`.", 'woo-benchmark-email' ),
				'id' => 'bmew_debug',
				'name' => __( 'Log debug messages?', 'woo-benchmark-email' ),
				'type' => 'checkbox',
			],

			// Add Sync Customers Field
			[
				'desc' => '
					<p>
						<a id="sync_customers" class="button" href="#">Sync Customers to Benchmark Email</a>
					</p>
					<p>
						<span id="sync_in_progress" style="display:none;">
							' . sprintf(
								"<strong>%s</strong> %s",
								__( 'Please wait.', 'woo-benchmark-email' ),
								__( 'Syncing at 10 orders per page, completed pages...', 'woo-benchmark-email' )
							) . '
						</span>
						<span id="sync_progress_bar"></span>
						<span id="sync_complete" style="display:none;">
							' . __( 'Finished Customer Sync.', 'woo-benchmark-email' ) . '
						</span>
					</p>
				',
				'desc_tip' => __( 'This will sync all historic customers to Benchmark Email.', 'woo-benchmark-email' ),
				'id' => 'bmew_sync',
				'name' => __( 'Sync historic customers', 'woo-benchmark-email' ),
				'type' => 'checkbox',
			],

			// End Settings Section
			[ 'id' => 'bmew_sectionend', 'type' => 'sectionend' ],
		] );
	}

}

endif;

return new WC_Settings_BMEW();
