<?php
/*
Plugin Name: Forte Payment Systems Integration with WooCommerce
Description: Extends WooCommerce to Process Payments with Forte payment gateway service
Version: 1.1
Plugin URI: http://www.forte.net/
Author: WC Marketplace, The Grey Parrots
Author URI: http://thegreyparrots.com/contacts/
License:  GPLv2 or later

*/

add_action('plugins_loaded', 'woocommerce_forte_init', 0);

function woocommerce_forte_init() {

	if ( !class_exists( 'WC_Payment_Gateway' ) ) 
      return;

   	/**
   	* Localisation
   	*/
   	load_plugin_textdomain('wc-forte', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
   
	/**
	* Forte Payment Systems Gateway class
	*/
	class WC_forte extends WC_Payment_Gateway {
		protected $msg = array();
      
		public function __construct() {

			$this->id = 'forte';
			$this->method_title = __('Forte Payment Systems', 'wc-forte');
			$this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/master.jpg';
			$this->has_fields = true;
			$this->init_form_fields();
			$this->init_settings();
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->access_id = $this->settings['access_id'];
			$this->secure_key = $this->settings['secure_key'];
			$this->account_id = $this->settings['account_id'];
			$this->location_id = $this->settings['location_id'];
			$this->mode = $this->settings['working_mode'];
			$this->card_type = $this->settings['card_type'];
			$this->success_message  = $this->settings['success_message'];
			$this->failed_message = $this->settings['failed_message'];
			$this->liveurl = 'https://api.forte.net/v2';
			$this->testurl = 'https://sandbox.forte.net/api/v2';
			$this->msg['message'] = "";
			$this->msg['class'] = "";
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}

			add_action('woocommerce_receipt_forte', array(&$this, 'receipt_page'));
			add_action('woocommerce_thankyou_forte',array(&$this, 'thankyou_page'));
		}

		function init_form_fields() {

			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'wc-forte'),
					'type' => 'checkbox',
					'label' => __('Enable forte Payment Module.', 'wc-forte'),
					'default' => 'no'),
				'title' => array(
					'title' => __('Title:', 'wc-forte'),
					'type' => 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'wc-forte'),
					'default' => __('Checkout via Credit Card', 'wc-forte')),
				'description'  => array(
					'title' => __('Description:', 'wc-forte'),
					'type' => 'textarea',
					'description' => __('This controls the description which the user sees during checkout.', 'wc-forte'),
					'default' => __('Pay securely by Credit or Debit Card through forte Secure Servers.', 'wc-forte')),
				'access_id' => array(
                    'title' => __('API Access ID', 'forte'),
                    'type' => 'text',
                    'description' => __('This is API access id.')),
				'secure_key' => array(
                    'title' => __('API Secure Key', 'forte'),
                    'type' => 'text',
                    'description' => __('This is API secure key.','forte')),
				'account_id' => array(
                    'title' => __('Account ID', 'forte'),
                    'type' => 'text',
                    'description' => __('This is Account ID to be used for transaction.','forte'),
                    'default' => __('300425', 'wc-forte')),
				'location_id' => array(
                    'title' => __('Location ID', 'forte'),
                    'type' => 'text',
                    'description' => __('This is Location ID to be used for transaction.','forte'),
                    'default' => __('187666', 'wc-forte')),
				'success_message' => array(
					'title'        => __('Transaction Success Message', 'wc-forte'),
					'type'         => 'textarea',
					'description'=>  __('Message to be displayed on successful transaction.', 'wc-forte'),
					'default'      => __('Your payment has been procssed successfully.', 'wc-forte')),
				'failed_message'  => array(
					'title'        => __('Transaction Failed Message', 'wc-forte'),
					'type'         => 'textarea',
					'description'  =>  __('Message to be displayed on failed transaction.', 'wc-forte'),
					'default'      => __('Your transaction has been declined.', 'wc-forte')),
				'working_mode'    => array(
					'title'        => __('API Mode'),
					'type'         => 'select',
					'options'      => array('false'=>'Live Mode', 'true'=>'Test/Sandbox Mode'),
					'description'  => "Live/Test Mode"),
				'card_type' => array(
	                    'title'       => __('Accepted Cards', 'wc-forte'),
	                    'type'        => 'multiselect',
	                    'description' => __('Select which card types to accept', 'wc-forte'),
	                    'default'     => '',
	                    'options'     => array(
	                                        'mast' => 'MasterCard',
	                                        'visa' => 'Visa',
	                                        'disc' => 'Discover',
	                                        'amex' => 'American Express',
	                                    )) 
				);
		}	
		/**
		* Admin Panel Options
		* 
		**/
		public function admin_options() {
			echo '<h3>'.__('Forte Payment Systems', 'wc-forte').'</h3>';
			echo '<p>'.__('A comprehensive suite of payment solutions crafted for developers and merchants that expands and moves right with you').'</p>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}
	  
		/**
		*  Fields for Payment Bills Service
		**/
		function payment_fields() {
			if ( $this->description ) 
				$card_title = array(
								'mast' => 'MasterCard',
								'visa' => 'Visa',
								'disc' => 'Discover',
								'amex' => 'American Express'
								);
				echo wpautop(wptexturize($this->description));
				echo '<label style="margin-right:46px; line-height:40px;">Credit Card :</label> <input placeholder="4111111111111111" type="text" name="forte_credircard" /><br/>';
				echo '<label style="margin-right:26px; line-height:40px;">Name on Card :</label> <input placeholder="John Doe" type="text" name="forte_credircard_name" /><br/>';
				echo '<label style="margin-right:57px; line-height:40px;">Card Type :</label><select name="forte_credircard_type" id="forte_credircard_type">';
				foreach( $this->card_type as $type ) {
					echo '<option value="' . $type . '">' . $card_title[$type] . '</option>';
				}
				echo '</select><br/>';
				echo '<label style="margin-right:25px; line-height:40px;">Expiry (Month) :</label> <input placeholder="12" type="text"  style="width:70px;" name="forte_ccexpmonth" maxlength="2" /><br/>';
				echo '<label style="margin-right:38px; line-height:40px;">Expiry (Year) :</label> <input placeholder="2017" type="text"  style="width:70px;" name="forte_ccexpyear" maxlength="4" /><br/>';
				echo '<label style="margin-right:92px; line-height:40px;">CVV :</label> <input placeholder="123" type="text" style="width:70px;" name="forte_ccvnumber"  maxlength="3" /><br/>';
		}
	
		/*
		* Basic Card validation
		*/
		public function validate_fields() {
			global $woocommerce;
		
			if (!$this->isCreditCardNumber($_POST['forte_credircard'])) 
				wc_add_notice(__('(Credit Card Number) is not valid.', 'wc-forte'), 'error' );
		
			if (!$this->isCorrectExpireDate($_POST['forte_ccexpyear']))    
				wc_add_notice(__('(Card Expiry Year) is not valid.', 'wc-forte'), 'error' );
		
			if (!$this->isCCVNumber($_POST['forte_ccvnumber'])) 
				wc_add_notice(__('(Card Verification Number) is not valid.', 'wc-forte'), 'error' );
		}
		
		/*
		* Check card 
		*/
		private function isCreditCardNumber($toCheck) {
			if (!is_numeric($toCheck))
				return false;
		
			$number = preg_replace('/[^0-9]+/', '', $toCheck);
			$strlen = strlen($number);
			$sum    = 0;
	
			if ($strlen < 13)
				return false; 
				
			for ($i=0; $i < $strlen; $i++) {
				$digit = substr($number, $strlen - $i - 1, 1);
				if($i % 2 == 1) {
					$sub_total = $digit * 2;
					if($sub_total > 9) {
						$sub_total = 1 + ($sub_total - 10);
					}
				} else {
					$sub_total = $digit;
				}
				$sum += $sub_total;
			}
		
			if ($sum > 0 AND $sum % 10 == 0)
				return true; 
	
			return false;
		}
		
		private function isCCVNumber($toCheck) {
			$length = strlen($toCheck);
			return is_numeric($toCheck) AND $length > 2 AND $length < 4;
		}
	
		/*
		* Check expiry date
		*/
		private function isCorrectExpireDate($date) {
			if (is_numeric($date) && (strlen($date) == 4)) {
				return true;
			}
			return false;
		}
	  
		public function thankyou_page($order_id) 
		{ 
		}
	  
		/**
		* Receipt Page
		**/
		function receipt_page($order) {
			echo '<p>'.__('Thank you for your order.', 'wc-forte').'</p>';
			$redirect_url = get_site_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key;
			wp_safe_redirect($redirect_url);
			exit;
		}
	  
		/**
		* Process the payment and return the result
		**/
		function process_payment($order_id) {
			  global $woocommerce;
			  $order = new WC_Order($order_id);
		
			  if($this->mode == 'true') {
				  $process_url = $this->testurl;
			  } else {
				  $process_url = $this->liveurl;
			  }
			  
			  if(isset($this->account_id) && trim($this->account_id) != '' && isset($this->location_id) && trim($this->location_id) != '') {
	
				  $process_url .= '/accounts/act_' . $this->account_id . '/locations/loc_' . $this->location_id . '/transactions';
				  
				  $auth_token = base64_encode($this->access_id . ":" . $this->secure_key);	
				  
				  $params = $this->generate_forte_params($order);
				  
				  $result_data = socketPost($process_url, $auth_token, $this->account_id, $params);
				  $server_response = json_decode($result_data, true);
				  
				  if(isset($server_response) && is_array($server_response) && isset($server_response['response']) && is_array($server_response['response'])) {

					  if($server_response['response']['response_type'] == 'A' && $server_response['response']['response_code'] == 'A01') {
					  	  if(isset($server_response['transaction_id'])) $transaction_id = $server_response['transaction_id'];
						  update_post_meta($order->id, 'transaction_id', $transaction_id);

						  $order->update_status( 'completed' );
						  wc_add_notice(wpautop(wptexturize($this->success_message)));
						  wc_add_notice( __(wpautop(wptexturize($this->success_message)) . ' Thank You For Your Order.</h3></br>Transaction ID:'. $transaction_id, 'woocommerce'));
						  $woocommerce->cart->empty_cart();
						  //$order->payment_complete();
						  $order->add_order_note( __($this->success_message, 'woocommerce') );
						  return array(
						  	  'refresh' => true,
						  	  'reload' => false,
						  	  'result' => 'success',
						  	  'redirect' =>  $this->get_return_url($order),
						  	  'mn' => 'return_order'
						  );
					  } else {   
						  wc_add_notice( __('Payment error:', 'woocommerce') . wpautop(wptexturize($this->failed_message)) . '</br>' . $orderInfo, 'error' );
					  }
				  } else {
				  	  wc_add_notice( __('There is some error in processing your payment. Kindly contact Site Administrator.', 'woocommerce'), 'error' ); 
				  }
			  } else  {   
				 wc_add_notice( __('There is some misconfiguration in your Payment Gateway. Kindly contact Site Administrator.', 'woocommerce'), 'error' );
			  }
		}
	  
		/**
		* Generate Forte request parameter
		**/
		public function generate_forte_params($order) {
			$forte_args = array (
				'account_id' => 'act_' . $this->account_id,
				'location_id' => 'loc_' . $this->location_id,
				'action' => 'sale',
				'authorization_amount' => $order->order_total,
				'billing_address' => 
					array (
					'first_name' => $order->billing_first_name,
					'last_name' => $order->billing_last_name,
					'email' => $order->billing_email,
					'phone' => $order->billing_phone,
					'address_type' => 'default_billing',
					'physical_address' => array (
							'street_line1' => $order->billing_address_1,
							'street_line2' => $order->billing_address_2,
							'locality' => $order->billing_city,
							'region' => $order->billing_state,
							'postal_code' => $order->billing_postcode
							)
					),
				'card' => 
					array (
						'card_type' => $_POST['forte_credircard_type'],
						'name_on_card' => $_POST['forte_credircard_name'],
						'account_number' => $_POST['forte_credircard'],
						'expire_month' => $_POST['forte_ccexpmonth'],
						'expire_year' => $_POST['forte_ccexpyear'],
						'card_verification_value' => $_POST['forte_ccvnumber'],
					),
			);
			return $forte_args;
		}
	}

	/**
	* Add this ip to WooCommerce
	**/
	function get_client_ip($type = 0) {
		$type = $type ? 1 : 0;
		static $ip = NULL;
		if ($ip !== NULL)
			return $ip [$type];
		if (isset ( $_SERVER ['HTTP_X_FORWARDED_FOR'] )) {
			$arr = explode ( ',', $_SERVER ['HTTP_X_FORWARDED_FOR'] );
			$pos = array_search ( 'unknown', $arr );
			if (false !== $pos)
				unset ( $arr [$pos] );
			$ip = trim ( $arr [0] );
		} elseif (isset ( $_SERVER ['HTTP_CLIENT_IP'] )) {
			$ip = $_SERVER ['HTTP_CLIENT_IP'];
		} elseif (isset ( $_SERVER ['REMOTE_ADDR'] )) {
			$ip = $_SERVER ['REMOTE_ADDR'];
		}
		// IP address
		$long = ip2long ( $ip );
		$ip = $long ? array (
				$ip,
				$long 
		) : array (
				'0.0.0.0',
				0 
		);
		return $ip [$type];
	}
	//get response
	function socketPost($url, $auth_token, $account_id, $data) {
		$post_variables = json_encode($data);
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$auth_token,
												   'X-Forte-Auth-Account-Id: act_' . $account_id,
												   'Content-Type: application/json'));
		
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_HEADER, 0); 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, true); 
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_variables);
		$xmlrs = curl_exec($curl);
		curl_close($curl);
		return $xmlrs;
	}
	
	/**
	* Add this Gateway to WooCommerce
	**/
	function woocommerce_add_forte_gateway($methods) 
	{
	  $methods[] = 'WC_forte';
	  return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_forte_gateway' );
}
