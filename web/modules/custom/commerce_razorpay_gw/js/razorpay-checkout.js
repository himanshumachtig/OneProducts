(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.razorpayCheckout = {
    attach: function (context, settings) {
      var rp = drupalSettings.razorpay;
      if (!rp || context !== document) {
        return;
      }

      var options = {
        key: rp.key,
        amount: rp.amount,
        currency: rp.currency,
        name: rp.name,
        description: rp.description,
        order_id: rp.orderId,
        handler: function (response) {
          var form = document.querySelector('.payment-redirect-form');
          if (!form) {
            return;
          }
          form.querySelector('input[name="razorpay_payment_id"]').value = response.razorpay_payment_id;
          form.querySelector('input[name="razorpay_order_id"]').value   = response.razorpay_order_id;
          form.querySelector('input[name="razorpay_signature"]').value  = response.razorpay_signature;
          form.submit();
        },
        modal: {
          ondismiss: function () {
            window.location.href = rp.cancelUrl;
          }
        }
      };

      var rzp = new Razorpay(options);

      var btn = document.getElementById('razorpay-pay-btn');
      if (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          rzp.open();
        });
        // Auto-open on page load.
        rzp.open();
      }
    }
  };

}(Drupal, drupalSettings));
