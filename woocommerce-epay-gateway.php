<?php
/* @wordpress-plugin
 * Plugin Name:       Epay Payment Gateway
 * Plugin URI:        https://gabrieltonelli.com.ar/wp-plugins/woocommerce-epay-gateway
 * Description:       Cobrá con todas las tarjetas del mercado con las comisiones más bajas.
 * Version:           1.0.0
 * WC requires at least: 3.0
 * WC tested up to: 4.9
 * Author:            Gabriel Tonelli
 * Author URI:        https://gabrieltonelli.com.ar/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

class EPay{

	protected static $_instance = null;

	public static function get_instance(){
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
	    if(!$this->is_woocommerce_active()){
	        return;
        }

		add_action( 'plugins_loaded', array($this, 'load_plugin_textdomain') );
		add_action( 'plugins_loaded', array($this, 'init_epay_gateway') );
		add_action( 'plugins_loaded', array($this, 'init_epay_simulador') );
		add_filter( 'woocommerce_payment_gateways', array($this, 'add_epay_gateway') );
		add_action( 'admin_init', array($this, 'admin_css') );
		add_action( 'admin_enqueue_scripts', array($this, 'admin_js') );
		add_action( 'wp_enqueue_scripts', array($this, 'frontend_scripts') );
	}

	public function admin_js($hook){
		if('woocommerce_page_wc-settings' === $hook){
			wp_enqueue_script( 'custompayment', plugins_url( "includes/assets/js/custompayment.js", __FILE__ ) , array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-accordion' ), '1.3.5', true );
		}
	}

	public function frontend_scripts() {
		if(is_checkout()){
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script('epay_payment_front_js',plugins_url('includes/assets/js/custom-payment-front.js', __FILE__), array('jquery-ui-datepicker') );
			wp_enqueue_script('signature_pad',plugins_url('includes/assets/js/signature_pad.min.js', __FILE__) );
			wp_enqueue_style( 'jquery-ui-datepicker-style' , '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/flick/jquery-ui.css');
			wp_enqueue_style( 'epay_payment_front_css', plugins_url('includes/assets/css/front.css', __FILE__) );
			wp_enqueue_style( 'hint-css', plugins_url('includes/assets/css/hint.min.css', __FILE__) );
			wp_enqueue_style( 'dat_payment_css', plugins_url('includes/assets/css/DatPayment.css', __FILE__) );
			wp_enqueue_script( 'dat_payment_js', plugins_url('includes/assets/js/DatPayment.js', __FILE__) );
		}
		if(is_product() || is_cart()){
			wp_enqueue_style( 'epay_payment_simulador_css', plugins_url('includes/assets/css/simulador.css', __FILE__) );
		}
	}

    public function init_epay_gateway(){
		require_once 'class-woocommerce-epay-gateway.php';
		include_once( dirname( __FILE__ ) . '/includes/views/simulador.php' );
	}

	public function init_epay_simulador(){
		include_once( dirname( __FILE__ ) . '/includes/views/simulador.php' );
	}

    public function admin_css() {
		wp_enqueue_style( 'epay_payment_admin_css', plugins_url('includes/assets/css/admin.css', __FILE__) );
	}
    public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-epay-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	public function add_epay_gateway( $gateways ){
		$gateways[] = 'WC_EPay_Gateway';
		return $gateways;
	}

	private function is_woocommerce_active(){
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() )
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
    }
}

EPay::get_instance();