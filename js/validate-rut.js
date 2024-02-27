function validateRut(rut) {
  rut = rut.replace(/[^0-9kK]/g, ''); // Remove any non-digit characters except 'k' or 'K'

  if (rut.length < 9 || rut.length > 10) {
    return false;
  }

  var rutNumber = rut.substring(0, rut.length - 1);
  var dv = rut.substring(rut.length - 1).toUpperCase();

  var sum = 0;
  var multiplier = 2;
  for (var i = rutNumber.length - 1; i >= 0; i--) {
    sum += parseInt(rutNumber.charAt(i)) * multiplier;
    multiplier = multiplier === 7 ? 2 : multiplier + 1;
  }

  var modulo = sum % 11;
  var verificationDigitExpected = 11 - modulo;
  if (verificationDigitExpected === 10) {
    verificationDigitExpected = 'K';
  } else if (verificationDigitExpected === 11) {
    verificationDigitExpected = '0';
  }

  return dv === verificationDigitExpected.toString();
}

jQuery(document).ready(function ($) {
  $('#woocommerce_wcplugingateway_rut').after('<p id="rut-error" class="error-message" style="display:none;color:red;">El rut no tiene un formato valido</p>');
  $('#woocommerce_wcplugingateway_rut').on('change', function () {

    console.log("rut: " + rut);

    var esValido = validateRut(rut);

    if (!esValido) {
      // insert after the input
      $('#rut-error').show();
    } else {
      $('#rut-error').remove();
    }
  });
});
