(function ($) {
  'use strict';

  $(document).ready(function () {
    var form = $('form#user-form');
    if (!form.length) return;

    // Add a hint box before the current_pass field
    var $currentPass = form.find('#edit-current-pass').closest('.form-item');
    if ($currentPass.length) {
      $currentPass.before(
        '<div class="account-current-pass-hint">' +
          '<i class="fas fa-info-circle"></i>' +
          'Enter your <strong>current password</strong> to save any changes.' +
        '</div>'
      );
    }

    // Add a visual divider between email and password section
    var $passField = form.find('#edit-pass').closest('.form-item, .js-form-type-password-confirm');
    if ($passField.length) {
      $passField.before('<hr class="account-section-divider">');
      $passField.before(
        '<p class="text-muted mb-2" style="font-size:.85rem;">' +
          '<i class="fas fa-key me-1 text-warning"></i>' +
          'Leave password fields empty if you don\'t want to change it.' +
        '</p>'
      );
    }

    // Force the confirm password field to always be visible
    var showConfirm = function () {
      form.find('.js-password-confirm, .confirm-parent, .password-confirm').css('display', 'block');
    };
    showConfirm();
    // Re-run after Drupal's password.js has a chance to run
    setTimeout(showConfirm, 200);
    setTimeout(showConfirm, 600);

    // Remove all Drupal description text from form items
    form.find('.form-item--current-pass .description,\
               .form-item--mail .description,\
               .form-type--password-confirm .description').hide();
  });

}(jQuery));
