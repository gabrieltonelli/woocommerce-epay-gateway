<?php

class WC_EPay_Gateway extends WC_Payment_Gateway {

	protected $debug_mode;
	protected $store_name; 
	protected $store_shared_secret;
	protected $store_name_testing; 
	protected $store_shared_secret_testing; 

	public function __construct( $child = false ) {
		$this->id           = 'epay';
		$this->method_title = 'e-pay';
		$this->title        = 'e-pay | Paga con tarjetas de débito y crédito';
		$this->description     = 'Pagá con todas las tarjetas del mercado';
		$this->method_description = $this->get_method_description();
		$this->has_fields   = true;

		$this->init_form_fields();
		$this->init_settings();

		$this->enabled      	   		   = $this->get_option( 'enabled' );
		$this->store_name          		   = $this->get_option( 'store_name' );
		$this->store_shared_secret 		   = $this->get_option( 'store_shared_secret' );
		$this->store_name_testing          = $this->get_option( 'store_name_testing' );
		$this->store_shared_secret_testing = $this->get_option( 'store_shared_secret_testing' );
		$this->gateway_icon 	   		   = plugins_url('includes/assets/images/tarjetas.jpg', __FILE__);
		$this->debug_mode   	   		   = $this->get_option( 'debug_mode' );

		if ( $child === false ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
		}
	}
	
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => 'Habilitado',
				'type'    => 'checkbox',
				'label'   => 'Habilitar e-pay',
				'default' => 'no'
			),
			'store_name' => array(
				'title'       => 'Store ID',
				'type'        => 'text',
				'description' => 'ID de la tienda',
				'default'     => '',
				'placeholder' => 'XXXXXXXXX'
			),
			'store_shared_secret'     => array(
				'title'       => 'Shared Secret',
				'type'        => 'text',
				'description' => 'Shared Secret de la tienda',
				'default'     => '',
				'placeholder' => ''
			),
			'debug_mode' => array(
				'title'       => 'Modo Testing',
				'type'        => 'checkbox',
				'label'       => 'Habilitar ',
				'default'     => 'no',
				'description' => 'Puede utilizar el modo de testing para realizar todas las pruebas correspondientes. No olvide desactivar esta opción para recibir pagos reales.'
			),
			'store_name_testing' => array(
				'title'       => 'Store ID - Testing',
				'type'        => 'text',
				'description' => 'ID de la tienda para el modo de prueba',
				'default'     => '',
				'placeholder' => 'XXXXXXXXX'
			),
			'store_shared_secret_testing' => array(
				'title'       => 'Shared Secret - Testing',
				'type'        => 'text',
				'description' => 'Shared Secret de la tienda para el modo de prueba',
				'default'     => '',
				'placeholder' => ''
			),
			'simulador_title' => array(
				'title'       => 'Simulador de Cuotas',
				'type'        => 'title',
			),
			'simulador_product' => array(
				'title'       => 'Producto',
				'type'        => 'checkbox',
				'label'       => 'Habilitar ',
				'default'     => 'no',
				'description' => 'Muestra las cuotas disponibles en la página de un producto.'
			),
			'simulador_cart' => array(
				'title'       => 'Carrito',
				'type'        => 'checkbox',
				'label'       => 'Habilitar ',
				'default'     => 'no',
				'description' => 'Muestra las cuotas disponibles en la página del carrito.'
			)
		);
	}

	public function admin_options() {
		include_once( dirname( __FILE__ ) . '/includes/views/admin_options_html.php' );
	}

	public function validate_fields() {
		$success = true;
		$cardNumber = isset($_POST['epay_cardnumber']) ? $_POST['epay_cardnumber'] : '';
		$cardExpiry = isset($_POST['epay_expiry']) ? $_POST['epay_expiry'] : '';
		$cardCVC = isset($_POST['epay_cvc']) ? $_POST['epay_cvc'] : '';
		$cardName = isset($_POST['epay_name']) ? $_POST['epay_name'] : '';
		$cuotas = isset($_POST['epay_cuotas']) ? $_POST['epay_cuotas'] : '';

		//Vencimiento Desktop o Mobile (Por el momento)
		if (strpos($cardExpiry, '/') !== false) {
	        $expiry_array = explode('/', $cardExpiry);
	        $exp_month = $expiry_array[0];
	        $exp_year = $expiry_array[1];
	    }
	    else {
	        $exp_month = substr($cardExpiry, 0, 2);
	        $exp_year = substr($cardExpiry, 2, 2);
	    }

		if (strlen($cardNumber) < 16) {
			wc_add_notice('Nro. de tarjeta inválido', 'error');
			$success = false;
		}
		if (strlen($exp_month.$exp_year) < 4 || ($exp_year < date('y')) || ($exp_year == date('y') && $exp_month < date('m'))) {
			wc_add_notice('Vencimiento de Tarjeta inválido', 'error');
			$success = false;
		}
		if (strlen($cardCVC) < 3) {
			wc_add_notice('Código de Seguridad inválido', 'error');
			$success = false;
		}
		if (strlen($cardName) < 1) {
			wc_add_notice('Titular de Tarjeta inválido', 'error');
			$success = false;
		}
		if (trim($cuotas) == '') {
			wc_add_notice('Cantidad de Cuotas inválida', 'error');
			$success = false;
		}

		return $success;
	}

	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );

		$cardNumber = isset($_POST['epay_cardnumber']) ? str_replace(' ', '', $_POST['epay_cardnumber']) : '';
		$cardExpiry = isset($_POST['epay_expiry']) ? $_POST['epay_expiry'] : '';
		$cardCVC = isset($_POST['epay_cvc']) ? $_POST['epay_cvc'] : '';
		$cardName = isset($_POST['epay_name']) ? $_POST['epay_name'] : '';
		$cuotas = isset($_POST['epay_cuotas']) ? $_POST['epay_cuotas'] : 1;
		$coef = isset($_POST['epay_coef']) ? $_POST['epay_coef'] : 1;
		$total_final = $order->get_total();

		//Vencimiento Desktop o Mobile (Por el momento)
		if (strpos($cardExpiry, '/') !== false) {
	        $expiry_array = explode('/', $cardExpiry);
	        $exp_month = $expiry_array[0];
	        $exp_year = $expiry_array[1];
	    }
	    else {
	        $exp_month = substr($cardExpiry, 0, 2);
	        $exp_year = substr($cardExpiry, 2, 2);
	    }

		if($cuotas > 1 and $cuotas <= 24){
		    $total_orig = $total_final;
		    $total_final = $total_final * $coef;
		    $fee = $total_final - $total_orig;
		}

		if($this->debug_mode == 'yes'){
			$modo = 'testing';
			$store_name = $this->get_option( 'store_name_testing' );
			$shared_secret = $this->get_option( 'store_shared_secret_testing' );
		}
		else{
			$modo = 'production';
			$store_name = $this->get_option( 'store_name' );
			$shared_secret = $this->get_option( 'store_shared_secret' );
		}

		$epay_url = "https://manager.epaypago.com/api/v1/payment?token=PHSCDXamcaqzhnPStmPIWVrF7FF4Xtbp";
		$epay_url .= "&modo=" . $modo;
		$epay_url .= "&store_id=" . $store_name;
		$epay_url .= "&shared_secret=" . urlencode($shared_secret);
		$epay_url .= "&cardnumber=" . $cardNumber;
		$epay_url .= "&expmonth=" . $exp_month;
		$epay_url .= "&expyear=" . $exp_year;
		$epay_url .= "&cvm=" . $cardCVC;
		$epay_url .= "&oid=" . $order_id;
		$epay_url .= "&cuotas=" . $cuotas;
		$epay_url .= "&chargetotal=" . $total_final;
		$epay_url .= "&cli_nombre=" . $order->get_billing_last_name() . ' ' . $order->get_billing_first_name();
		$epay_url .= "&cli_tel=" . urlencode($order->get_billing_phone());
		$epay_url .= "&cli_email=" . urlencode($order->get_billing_email());

		$country = $order->get_billing_country();
		$state = $order->get_billing_state();
		$full_country = isset($woocommerce->countries->countries[$country]) ? $woocommerce->countries->countries[$country] : '';
		$full_state = isset($woocommerce->countries->get_states($country)[$state]) ? $woocommerce->countries->get_states($country)[$state] : '';

		$epay_url .= "&cli_pais=" . $full_country;
		$epay_url .= "&cli_pcia=" . $full_state;
		$epay_url .= "&cli_ciudad=" . $order->get_billing_city();
		$epay_url .= "&cli_cp=" . $order->get_billing_postcode();
		$epay_url .= "&cli_direc=" . $order->get_billing_address_1();
		$epay_url .= "&cli_ip=" . urlencode($_SERVER['REMOTE_ADDR']);
		$response = wp_remote_post($epay_url);

		if( !is_wp_error( $response ) ) {
			//$file = fopen("/home/bitnami/apps/wordpress/htdocs/wp-content/plugins/woocommerce-epay-gateway/log.txt", "a");
	        //file_put_contents("/home/bitnami/apps/wordpress/htdocs/wp-content/plugins/woocommerce-epay-gateway/log.txt", print_r($response, TRUE));
		    $body = $response['body'];
		    $payment = json_decode($body, true);
		    if($payment['success']){
		    	if($fee){
					$myitem_fee = new WC_Order_Item_Fee();
					$myitem_fee->set_name( "Comisión por financiamiento en ".$cuotas.' cuotas de $'.number_format (($total_final/$cuotas), 2 , "." , "" ).' cada una' );
					$myitem_fee->set_amount( 1 );
					$myitem_fee->set_total( $fee );
					$order->add_item( $myitem_fee );
					$order->calculate_totals();
				}
				elseif($cuotas){
					$order->add_order_note( $cuotas." CUOTAS SIN INTERES DE ".number_format (($total_final/$cuotas), 2 , "." , "" ).' CADA UNA.' );
				}

		    	$order->add_order_note('Pago registrado con éxito. Order ID -> '.$payment['oid']);
		    	$order->update_status('processing');
				
				if ( function_exists( 'wc_reduce_stock_levels' ) ) {
					wc_reduce_stock_levels( $order_id );
				} else {
					$order->reduce_order_stock();
				}
				
				$order->save();

				$woocommerce->cart->empty_cart();
		    	return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);
		    }
		    else{
		    	$order->add_order_note('Pago rechazado.');
		    	$order->update_status('failed');
		    	wc_add_notice('Pago rechazado. Revise los datos de su tarjeta.', 'error');
		    	return array(
            		'result' => 'fail',
            		'redirect' => '',
        		);
		    }
		}
		else{
			wc_add_notice('Error al conectar con el servidor de e-pay. Intente nuevamente', 'error');
			return array(
            	'result' => 'fail',
            	'redirect' => '',
        	);
		}
	}

	public function payment_fields() { 
		include_once( dirname( __FILE__ ) . '/includes/views/checkout.php' );
	}

	public function get_icon() {

		if ( trim( $this->gateway_icon ) === 'http://' ) {
			return '';
		}

		if ( trim( $this->gateway_icon ) != '' ) {
			return '<img class="epay_payment_icon" src="' . esc_attr( $this->gateway_icon ) . '" />';
		}

		return '';
	}

	public function get_method_description(){
        return '<div class="epay-header-logo">
            <div class="epay-left-header">
                <img src="' . plugins_url('includes/assets/images/epay.png', __FILE__) . '">
            </div>
            <div>Pagá con todas las tarjetas del mercado</div>
        </div>';
    }
}
