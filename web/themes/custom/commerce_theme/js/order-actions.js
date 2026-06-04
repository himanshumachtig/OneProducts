(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.orderActions = {
    attach(context) {
      const s = drupalSettings.orderActions || {};

      // ── Cancel Order ──────────────────────────────────────────────────────
      once('order-cancel', '#cancel-order-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
          modal.show();
        });
      });

      once('order-cancel-confirm', '#confirm-cancel-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          btn.disabled = true;
          btn.textContent = 'Cancelling…';

          $.ajax({
            url: s.cancelUrl,
            method: 'POST',
            data: { csrf_token: s.cancelToken },
            success: function (data) {
              bootstrap.Modal.getInstance(document.getElementById('cancelModal')).hide();
              showAlert('success', data.message || 'Order cancelled.');
              setTimeout(() => location.reload(), 1500);
            },
            error: function (xhr) {
              const msg = xhr.responseJSON ? xhr.responseJSON.error : 'Failed to cancel order.';
              showAlert('danger', msg);
              btn.disabled = false;
              btn.textContent = 'Yes, Cancel Order';
            },
          });
        });
      });

      // ── Request Refund ────────────────────────────────────────────────────
      once('order-refund-open', '#refund-request-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          const modal = new bootstrap.Modal(document.getElementById('refundModal'));
          modal.show();
        });
      });

      once('order-refund-submit', '#submit-refund-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          const reason = document.getElementById('refund-reason').value.trim();
          const errEl  = document.getElementById('refund-reason-error');

          if (!reason) {
            errEl.textContent = 'Please describe your reason for the refund.';
            errEl.classList.remove('d-none');
            return;
          }
          errEl.classList.add('d-none');

          btn.disabled = true;
          btn.textContent = 'Submitting…';

          $.ajax({
            url: s.refundUrl,
            method: 'POST',
            data: { csrf_token: s.refundToken, reason: reason },
            success: function (data) {
              bootstrap.Modal.getInstance(document.getElementById('refundModal')).hide();
              showAlert('success', data.message || 'Refund request submitted.');
              setTimeout(() => location.reload(), 1800);
            },
            error: function (xhr) {
              const msg = xhr.responseJSON ? xhr.responseJSON.error : 'Failed to submit request.';
              errEl.textContent = msg;
              errEl.classList.remove('d-none');
              btn.disabled = false;
              btn.textContent = 'Submit Request';
            },
          });
        });
      });

      // Helper: show inline alert above the content
      function showAlert(type, message) {
        const el = document.createElement('div');
        el.className = `alert alert-${type} alert-dismissible fade show`;
        el.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        const content = document.querySelector('.card-body');
        if (content) content.prepend(el);
      }

      // ── Rate Product ──────────────────────────────────────────────────────
      once('order-rating', '#order-rating-section', context).forEach(function (section) {

        // Star hover + click (select only, no AJAX on click)
        section.querySelectorAll('.order-star-picker').forEach(function (picker) {
          const stars     = picker.querySelectorAll('.order-star');
          const submitBtn = picker.closest('.order-rating-item').querySelector('.order-submit-review');

          // Track selected rating (initialise from data-current)
          let selected = parseInt(picker.dataset.current, 10) || 0;

          function applyRated(val) {
            stars.forEach(function (s) {
              s.classList.toggle('rated', parseInt(s.dataset.value, 10) <= val);
            });
          }
          applyRated(selected);

          stars.forEach(function (star) {
            star.addEventListener('mouseenter', function () {
              const val = parseInt(star.dataset.value, 10);
              stars.forEach(function (s) {
                s.classList.toggle('hover', parseInt(s.dataset.value, 10) <= val);
              });
            });
            star.addEventListener('mouseleave', function () {
              stars.forEach(function (s) { s.classList.remove('hover'); });
              applyRated(selected);
            });
            star.addEventListener('click', function () {
              selected = parseInt(star.dataset.value, 10);
              applyRated(selected);
              picker.dataset.current = selected;
              // Update submit button label
              if (submitBtn) { submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit Review'; }
            });
          });
        });

        // Submit review (rating + text)
        section.querySelectorAll('.order-submit-review').forEach(function (btn) {
          btn.addEventListener('click', function () {
            const item       = btn.closest('.order-rating-item');
            const picker     = item.querySelector('.order-star-picker');
            const textArea   = item.querySelector('.order-review-text');
            const statusEl   = item.querySelector('.order-rating-status');
            const msgEl      = item.querySelector('.order-review-msg');
            const rating     = parseInt(picker.dataset.current, 10) || 0;
            const reviewText = textArea ? textArea.value.trim() : '';
            const productId  = picker.dataset.productId;

            if (!rating) {
              if (msgEl) { msgEl.innerHTML = '<span class="text-danger">Please select a star rating first.</span>'; }
              return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving…';
            if (msgEl) { msgEl.textContent = ''; }

            $.ajax({
              url: s.ratingUrl || '/orders/0/rate-product',
              method: 'POST',
              data: {
                csrf_token:  s.ratingToken,
                product_id:  productId,
                rating:      rating,
                review_text: reviewText,
              },
              success: function (res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i>Update Review';
                if (statusEl) {
                  statusEl.innerHTML = '<span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>Reviewed</span>';
                }
                if (msgEl) {
                  msgEl.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Review saved!</span>';
                  setTimeout(function () { msgEl.textContent = ''; }, 3000);
                }
              },
              error: function (xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.error : 'Failed to save review.';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit Review';
                if (msgEl) { msgEl.innerHTML = '<span class="text-danger">' + msg + '</span>'; }
              },
            });
          });
        });

      });
    },
  };

})(jQuery, Drupal, drupalSettings);
