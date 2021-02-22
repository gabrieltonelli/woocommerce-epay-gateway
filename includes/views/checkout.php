<div id="payment-form" class="datpayment-form">
  <div class="dpf-title">
      Pagá con todas las tarjetas del mercado
  </div>
  <div class="dpf-card-placeholder"></div>
  <div class="dpf-input-container">
      <div class="dpf-input-row">
          <label class="dpf-input-label">Número de Tarjeta</label>
          <div class="dpf-input-container with-icon">
              <span class="dpf-input-icon"><i class="fa fa-credit-card" aria-hidden="true"></i></span>
              <input type="text" class="dpf-input" size="16" data-type="number" name="epay_cardnumber" id="epay_cardnumber" required>
          </div>
      </div>

      <div class="dpf-input-row">
          <div class="dpf-input-column">
              <input type="hidden" size="2" data-type="exp_month" placeholder="MM">
              <input type="hidden" size="2" data-type="exp_year" placeholder="YY">

              <label class="dpf-input-label">Fecha de Vencimiento</label>
              <div class="dpf-input-container">
                  <input type="text" class="dpf-input" maxlength="5" size="5" data-type="expiry" name="epay_expiry" required>
              </div>
          </div>
          <div class="dpf-input-column">
              <label class="dpf-input-label">Código de Seguridad</label>
              <div class="dpf-input-container">
                  <input type="text" class="dpf-input" minlength="3" size="3" data-type="cvc" name="epay_cvc" required>
              </div>
          </div>
      </div>

      <div class="dpf-input-row">
          <label class="dpf-input-label">Nombre y apellido del titular de la tarjeta</label>
          <div class="dpf-input-container">
              <input type="text" size="4" class="dpf-input" data-type="name" name="epay_name" required>
          </div>
      </div>

      <div class="dpf-input-row">
          <?php include_once('cuotas.php'); ?>
      </div>

  </div>
</div>

<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
<script type="text/javascript">
    var payment_form = new DatPayment({
        form_selector: '#payment-form',
        card_container_selector: '.dpf-card-placeholder',

        number_selector: '.dpf-input[data-type="number"]',
        date_selector: '.dpf-input[data-type="expiry"]',
        cvc_selector: '.dpf-input[data-type="cvc"]',
        name_selector: '.dpf-input[data-type="name"]',

        submit_button_selector: '.dpf-submit',

        placeholders: {
            number: '•••• •••• •••• ••••',
            expiry: '••/••',
            cvc: '•••',
            name: '•••••• ••••••'
        },

        validators: {
            number: function(number){
                return Stripe.card.validateCardNumber(number);
            },
            expiry: function(expiry){
                var expiry = expiry.split(' / ');
                return Stripe.card.validateExpiry(expiry[0]||0,expiry[1]||0);
            },
            cvc: function(cvc){
                return Stripe.card.validateCVC(cvc);
            },
            name: function(value){
                return value.length > 0;
            }
        }
    });

    var demo_log_div = document.getElementById("demo-log");

    payment_form.form.addEventListener('payment_form:submit',function(e){
        var form_data = e.detail;
        payment_form.unlockForm();
        demo_log_div.innerHTML += "<br>"+JSON.stringify(form_data);
    });

    payment_form.form.addEventListener('payment_form:field_validation_success',function(e){
        var input = e.detail;

        demo_log_div.innerHTML += "<br>field_validation_success:"+input.getAttribute("data-type");

    });

    payment_form.form.addEventListener('payment_form:field_validation_failed',function(e){
        var input = e.detail;

        demo_log_div.innerHTML += "<br>field_validation_failed:"+input.getAttribute("data-type");
    });
</script>