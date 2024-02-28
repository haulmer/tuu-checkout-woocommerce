function validateRut(rut) {
  rut = rut.replace(/[^0-9kK]/g, ''); // Elimina todos los caracteres no numéricos excepto "k" o "K".

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
  $('#woocommerce_wcplugingateway_rut').on('input', function () {
    var rut = $(this).val();

    var esValido = validateRut(rut);

    if (rut.trim() === '') {
      $('#rut-error').hide();
      return;
    }

    if (!esValido) {
      $('#rut-error').show();
    } else {
      $('#rut-error').hide();
    }
  });

  $('#woocommerce_wcplugingateway_clave_secreta').after('<p id="clave-secreta-error" class="error-message" style="color:red;"></p>');

  $('#woocommerce_wcplugingateway_clave_secreta').on('input', function () {
    var claveSecreta = $(this).val();
    var longitud = claveSecreta.length;

    if (longitud !== 80) {
      $('#clave-secreta-error').text('La clave secreta debe tener exactamente 80 caracteres. Actualmente tiene ' + longitud + '.').show();
    } else {
      $('#clave-secreta-error').hide();
    }
  });
});
