(function ($) {
  'use strict';

  var debounceTimer = null;
  var emailValid = false;

  var $emailInput  = $('#reset-email-input');
  var $status      = $('#reset-email-status');
  var $sendBtn     = $('#reset-send-btn');

  function setStatus(type, msg) {
    var icon = type === 'success' ? 'fa-check-circle text-success'
             : type === 'error'   ? 'fa-times-circle text-danger'
             : type === 'loading' ? 'fa-spinner fa-spin text-muted'
             :                      'fa-info-circle text-muted';
    $status.html('<i class="fas ' + icon + ' me-1"></i>' + msg);
  }

  function checkEmail(email) {
    setStatus('loading', 'Checking…');
    $sendBtn.prop('disabled', true);
    emailValid = false;

    $.getJSON('/api/check-email', { email: email }, function (data) {
      if (!data.valid) {
        setStatus('error', 'Please enter a valid email address.');
      } else if (!data.exists) {
        setStatus('error', 'No account found with this email address.');
      } else {
        setStatus('success', 'Email found! Click the button below to send a reset link.');
        emailValid = true;
        $sendBtn.prop('disabled', false);
      }
    }).fail(function () {
      setStatus('info', 'Could not verify email. You may still try sending.');
      $sendBtn.prop('disabled', false);
    });
  }

  $emailInput.on('input', function () {
    var email = $.trim($(this).val());
    $sendBtn.prop('disabled', true);
    emailValid = false;
    $status.html('');

    clearTimeout(debounceTimer);
    if (email.length < 5) { return; }

    debounceTimer = setTimeout(function () { checkEmail(email); }, 500);
  });

  $sendBtn.on('click', function () {
    if (!emailValid) return;

    var email = $.trim($emailInput.val());

    // Fill the hidden Drupal form field and submit it
    var $drupalName = $('#drupal-pass-form-wrapper').find('[name="name"]');
    $drupalName.val(email);
    $('#drupal-pass-form-wrapper').find('input[type="submit"]').first().trigger('click');

    $sendBtn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>Sending…');
  });

}(jQuery));
