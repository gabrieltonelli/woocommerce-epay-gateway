<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');
add_action( "woocommerce_single_product_summary", 'epay_render_simulador_product', 35 );
add_action( "woocommerce_after_cart", 'epay_render_simulador_cart', 35 );

function epay_render_simulador_product(){
  global $product;
  $epay = new WC_EPay_Gateway();
  if($epay->get_option('simulador_product') == 'yes'){
      if($epay->get_option('debug_mode') == 'yes'){
          $store_name = $epay->get_option('store_name_testing');
          $shared_secret = $epay->get_option('store_shared_secret_testing');
      }
      else{
          $store_name = $epay->get_option('store_name');
          $shared_secret = $epay->get_option('store_shared_secret');
      }
      $epay_url = "https://manager.epaypago.com/api/v1/simulador?token=PHSCDXamcaqzhnPStmPIWVrF7FF4Xtbp";
      $epay_url .= "&store_id=" . $store_name;
      $epay_url .= "&shared_secret=" . urlencode($shared_secret);

      $request = wp_remote_get($epay_url);
      if( !is_wp_error( $request ) ) {
          $body = $request['body'];
          $cuotas = json_decode($body, true);
          if(!empty($cuotas)) render_simulador($cuotas, $product->get_price());
      }
  }
}

function epay_render_simulador_cart(){
  global $woocommerce;
  $epay = new WC_EPay_Gateway();
  if($epay->get_option('simulador_cart') == 'yes' && !$woocommerce->cart->is_empty()){
      if($epay->get_option('debug_mode') == 'yes'){
          $store_name = $epay->get_option('store_name_testing');
          $shared_secret = $epay->get_option('store_shared_secret_testing');
      }
      else{
          $store_name = $epay->get_option('store_name');
          $shared_secret = $epay->get_option('store_shared_secret');
      }
      $epay_url = "https://manager.epaypago.com/api/v1/simulador?token=PHSCDXamcaqzhnPStmPIWVrF7FF4Xtbp";
      $epay_url .= "&store_id=" . $store_name;
      $epay_url .= "&shared_secret=" . urlencode($shared_secret);

      $request = wp_remote_get($epay_url);
      if( !is_wp_error( $request ) ) {
          $body = $request['body'];
          $cuotas = json_decode($body, true);
          if(!empty($cuotas)) render_simulador($cuotas, $woocommerce->cart->total);
      }
  }
}

function render_simulador($cuotas, $total){
    global $product;
    $cuotas_html = '<div class="epay_simulador"><h4>Cuotas disponibles</h4><span>Podrás elegir una opción al realizar la compra.</span>';
    $cuotas_html .= '<ul>';
    for($i=2; $i<=24; $i++){
      if(isset($cuotas["coeficiente".$i]) and is_numeric($cuotas["coeficiente".$i]) and $cuotas["coeficiente".$i] >= 0){
          $interes = $cuotas["coeficiente".$i] == 1 ? "SIN INTERES" : "";
          $monto_cuota = number_format (($total * $cuotas["coeficiente".$i]) / $i, 2 , "," , ".");
          $tot = number_format($total * $cuotas["coeficiente".$i], 2, ",", ".");
          $cuotas_html .= '<li>'.$i.' cuotas '.$interes.' de $'.$monto_cuota.' (Total: $'.$tot.')'.'</li>';
      }
    }
    $cuotas_html .= '</ul></div>';
    echo $cuotas_html;
}