<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');
global $woocommerce;
$total_aux = number_format ( $woocommerce->cart->total , 2 , "," , "." ); 
$total_aux = str_replace(",00","",$total_aux);
$total_aux = get_woocommerce_currency_symbol().$total_aux;
$cuotas_html = '<option for="1" value="1">1 pago de '.$total_aux.'</option>';

//if($_POST['cardnumber']){
    $epay = new WC_EPay_Gateway();
    if($epay->get_option('debug_mode') == 'yes'){
        $store_name = $epay->get_option('store_name_testing');
        $shared_secret = $epay->get_option('store_shared_secret_testing');
    }
    else{
        $store_name = $epay->get_option('store_name');
        $shared_secret = $epay->get_option('store_shared_secret');
    }
    $epay_url = "https://manager.epaypago.com/api/v1/cuotas?token=PHSCDXamcaqzhnPStmPIWVrF7FF4Xtbp";
    $epay_url .= "&store_id=" . $store_name;
    $epay_url .= "&shared_secret=" . urlencode($shared_secret);
    $epay_url .= "&card_number=" . str_replace(' ', '', $_POST['cardnumber']);

    $request = wp_remote_get($epay_url);
    if( !is_wp_error( $request ) ) {
        $body = $request['body'];
        $cuotas = json_decode($body, true);
        for($i=2; $i<=24; $i++){
          if(isset($cuotas["coeficiente".$i]) and is_numeric($cuotas["coeficiente".$i]) and $cuotas["coeficiente".$i] >= 0){
              if($cuotas["coeficiente".$i] == 1){
                $interes = "SIN INTERES";
              }
              $monto_cuota = number_format (($woocommerce->cart->total * $cuotas["coeficiente".$i]) / $i, 2 , "," , ".");
              //$dif = ($woocommerce->cart->total * $cuotas["coeficiente".$i]) - $woocommerce->cart->total;
              $tot = number_format($woocommerce->cart->total * $cuotas["coeficiente".$i], 2, ",", ".");
              //$cuotas_html .= '<option for="'.$cuotas['coeficiente'.$i].'" value="'.$i.'">'.$i.' cuotas de $'.$monto_cuota.' (diferencia $'.$dif.')'.'</option>';
              $cuotas_html .= '<option for="'.$cuotas['coeficiente'.$i].'" value="'.$i.'">'.$i.' cuotas '.$interes.' de $'.$monto_cuota.' (Total: $'.$tot.')'.'</option>';
          }
        }
    }
//}

echo $cuotas_html;