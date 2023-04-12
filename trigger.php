<?php
/*
    Plugin Name: Trigger add-on for woocommerce Plugin
    Description: This plugin will automatic send complete donation data to your API.
    Setting up configurable fields for our plugin.
    Author: Naaz Premier
    Version: 1.0.0
*/

class Complete_Woocommerce_Trigger_Plugin
{
	public function __construct()
	{
		// Hook into the admin menu
		add_action('admin_menu', array($this, 'create_plugin_settings_page'));
	}
	public function create_plugin_settings_page()
	{
		// Add the menu item and page
		$page_title = 'WooCommerce Trigger Settings Page';
		$menu_title = 'WooCommerce Trigger';
		$capability = 'manage_options';
		$slug = 'woocommerce_trigger';
		$callback = array($this, 'plugin_settings_page_content');
		$icon = 'dashicons-admin-plugins';
		$position = 100;

		add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);
	}


	public function plugin_settings_page_content()
	{
		if ($_POST['update'] === 'true') {
			$this->handle_form($_POST);
		} ?>

		<div class="wrap">
			<h2>WooCommerce Trigger Settings Page</h2>
			<form method="POST">
				<?php wp_nonce_field('awesome_update', 'awesome_form'); ?>
				<table class="form-table">
					<tbody>

						<tr>
							<th><label for="checkbox">Activate</label><br></th>
							<td><input data-activation="<?php echo get_option('checkbox'); ?>" type="checkbox" id="checkbox" name="checkbox" value="activate" disabled></td>
						</tr>

						<tr>
							<th><label for="url">Endpoint Address</label></th>
							<td><input name="url" id="url" type="text" value="<?php echo get_option('url'); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th><label for="authorization">Token</label></th>
							<td><input name="authorization" id="authorization" type="text" value="<?php echo get_option('authorization'); ?>" class="regular-text" /></td>
						</tr>
					</tbody>
					<input type="hidden" name="update" value='true' />
				</table>

				<br>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="Save">
				</p>

			</form>

			<script type="text/javascript" src="https://code.jquery.com/jquery-1.8.2.js"></script>
			<script>

				function updateCheckbox() {
					$('#checkbox').attr({checked: "checked",})
				}

				function validate_checkbox() {
					if ((jQuery('#url').val().length !== 0) && (jQuery('#authorization').val().length !== 0)) {
						console.log(jQuery('#url').val().length)
						jQuery('#checkbox').prop('disabled', false);
					}

					var activation = jQuery('#checkbox').data('activation');
					if (activation === 'activate') {
						updateCheckbox()
					}
				}

				$(document).ready(function() {
					validate_checkbox();
					$('#url').change(function() {
						validate_checkbox();
					});

					$('#authorization').change(function() {
						validate_checkbox();
					});
				});
			</script>

		</div> <?php
			}

			////--to handle endpoint and token--//////

			public function handle_form($DATA)
			{

				error_log($DATA);

				if (
					!isset($DATA['awesome_form']) || !wp_verify_nonce($DATA['awesome_form'], 'awesome_update')
				) {
				?>
			<div class="error">
				<p>Sorry, your nonce was not correct. Please try again.</p>
			</div> <?php
					exit;
				} else {

					$url = sanitize_text_field($DATA['url']);
					$authorization = sanitize_text_field($DATA['authorization']);
					$checkbox = sanitize_text_field($DATA['checkbox']);

					if (!empty($url) && !empty($authorization)) {
						update_option('url', $url);
						update_option('authorization', $authorization);
						update_option('checkbox', $checkbox);
					?>
				<div class="updated">
					<p>Your fields were saved!</p>
				</div> <?php
					} else { ?>
				<div class="error">
					<p>Your endpoint or token were invalid.</p>
				</div> <?php
					}
				}
			}
		}

		// Adds transaction ID to a WooCommerce order
		// when placed, if not automatically added by the WooCommerce payment gateway.
		//
		// Built for use with MyWorks Sync for QuickBooks - but applicable
		// in any scenario where a transaction ID is needed in an order.


		add_action( 'woocommerce_new_order', 'myworks_action_woocommerce_new_order', 10, 1 ); 
		function myworks_action_woocommerce_new_order( $order_id ) { 
			$applicable_gateways = array(
			'Direct bank transfer',
			'Check payments',
			'Cash on delivery',
			'senangPay'
		//The gateways set here will determine which gateways this logic applies to. Add/remove as desired.
		);

		$order = wc_get_order( $order_id );

			do_action('myworks_woocommerce_order_transaction_details',$order, $applicable_gateways);	   
		}; 
         
		add_action( 'myworks_woocommerce_order_transaction_details', 'myworks_add_transaction_fee_woocommerce_order', 10, 4 );

		function myworks_add_transaction_fee_woocommerce_order( $order, $applicable_gateways = array() ){
			$payment_title = $order->get_payment_method_title();
	
			if(in_array($payment_title, $applicable_gateways)){

			add_post_meta($order->id, '_transaction_id', date('Ymdhis'));
			}
		}


		////--to hook order complete status --////

		new Complete_WooCommerce_Trigger_Plugin();

		function process_data($order_id)
		{
            $order = wc_get_order( $order_id );

			global $wp;
			$currentURL = home_url($wp->request);

			$objOrder = array(
				
				"orderNumber"      => $order->get_order_number(),
				"orderDate"        => date("Y-m-d H:i:s", strtotime(get_post($order->get_id())->post_date)),
				"status"           => $order->get_status(),
				"customerID"       => $order->get_user_id(),
				"transactionID"    => $order->get_transaction_id(),
				"totalAmount"      => $order->get_total(),
				"firstname"        => $order->get_billing_first_name(),
				"lastname"         => $order->get_billing_last_name(),
				"email"            => $order->get_billing_email(),
				"phone"            => $order->get_billing_phone(),
				"penama"           => get_post_meta( $order->id, '_billing_penama_wakaf', true ),
				"payment_gateway"  => $order->get_payment_method(),
				"current_url"	   => $currentURL,
				"Platform"		   => "WooCommerce"

			);
			//
			
			error_log($order);

			error_log($objOrder);

			error_log($order_id);
			

			/////////////////////////////////////////////

			
			// API URL
				
			$url = get_option('url');

			$authorization = "Authorization: Token ".get_option('authorization');
			
			$ch = curl_init($url);
			
			curl_setopt_array($ch, [
			
				CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			]);
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', $authorization));
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			$objOrder['status'] = array('pending', 'cancelled', 'failed');
			$load = json_encode($objOrder);
			
			curl_setopt($ch, CURLOPT_POSTFIELDS, $load);
			
			$result = curl_exec($ch);
			
			curl_close($ch);
			
			error_log('First example response: ' . $result . PHP_EOL);
         
        }
          add_action('woocommerce_order_status_completed', 'process_data', 200, 3);