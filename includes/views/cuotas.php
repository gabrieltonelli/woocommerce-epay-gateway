<?php
global $woocommerce;
$total_aux = number_format ( $woocommerce->cart->total , 2 , "," , "." ); 
$total_aux = str_replace(",00","",$total_aux);
$total_aux = get_woocommerce_currency_symbol().$total_aux;

?>
<div class="dpf-input-row">
    <label class="dpf-input-label">Cantidad de Cuotas</label>
    <div class="dpf-input-container">
        <select class="dpf-input" data-type="cuotas" id="epay_cuotas" name="epay_cuotas" required>
            <option for="1" value="1">1 pago de <?php echo $total_aux; ?></option>
        </select>
        <input type="hidden" id="epay_coef" name="epay_coef" value="1" />
    </div>
    <script>
        getCuotas();

        function formatMoneda(importe){
            importef = parseFloat(importe);
            var output = importef.toLocaleString(['ban', 'id']);
            output = output.replace(',00', '');
            return '$ ' + output;
        }

        jQuery('#epay_cardnumber').blur(function(){
            getCuotas();
        });

        jQuery('#epay_cuotas').change(function() {
            var cuotas_cant = parseFloat(jQuery('#epay_cuotas').val());
            var total     = <?php echo $woocommerce->cart->total; ?>;
            var total_ini   = total;
            if(cuotas_cant > 1){
                var element = jQuery('option:selected', this);
                var cuotas_cof = parseFloat(element.attr('for'));
                if(cuotas_cof > 0){
                    total = total * cuotas_cof;
                }
                var diferencia  = total - total_ini;
                var cuota       = total / cuotas_cant;
                var cuota_txt   = formatMoneda(cuota);
                var total_res   = 'Total en ' + cuotas_cant + ' cuotas de '+ cuota_txt + ' cada una';
                var total_res2  = 'Total '+formatMoneda(total)+' en ' + cuotas_cant + ' cuotas de '+ cuota_txt + ' cada una';
            }
            else{
                var total_res   = 'Total'
                var total_res2  = '';
            }

            total_txt = formatMoneda(total);
            jQuery('#epay_coef').val(cuotas_cof);
            jQuery('.order-total').html('<th>'+total_res+'</th><td><strong><span class="woocommerce-Price-amount amount">'+total_txt+'</span></strong></td>');
        });

        function getCuotas(){
            var card = jQuery('#epay_cardnumber').val();
            jQuery.ajax({
              type: 'POST',
              url: '/wp-content/plugins/woocommerce-epay-gateway/includes/controller/cuotas.php',
              data: {'cardnumber': card}
            })
            .done(function(cuotas){
              jQuery('#epay_cuotas').html(cuotas);
            })
            .fail(function(){
              alert('Hubo un error al obtener las cuotas')
            })
        }
      </script>
</div>