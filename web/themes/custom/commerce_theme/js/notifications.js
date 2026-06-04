(function ($, Drupal, drupalSettings, once) {
  'use strict';

  function showToast(message, type) {
    var styles = {
      status:  { bg: 'bg-success', icon: 'fa-check-circle' },
      error:   { bg: 'bg-danger',  icon: 'fa-times-circle' },
      warning: { bg: 'bg-warning text-dark', icon: 'fa-exclamation-triangle' },
    };
    var s = styles[type] || styles.status;
    var id = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 9999);

    var toastEl = document.createElement('div');
    toastEl.id = id;
    toastEl.className = 'toast align-items-center text-white ' + s.bg + ' border-0 mb-2 shadow-lg';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body d-flex align-items-center gap-2" style="font-size:0.95rem;">' +
          '<i class="fas ' + s.icon + ' fa-lg flex-shrink-0"></i>' +
          '<span>' + message + '</span>' +
        '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';

    var container = document.getElementById('toast-notification-container');
    if (!container) return;
    container.appendChild(toastEl);

    var toast = new bootstrap.Toast(toastEl, { delay: 4000, autohide: true });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
  }

  Drupal.behaviors.commerceNotifications = {
    attach: function (context, settings) {
      // Primary: read from drupalSettings (set by PHP preprocess)
      if (settings && settings.commerceToastMessages && settings.commerceToastMessages.length) {
        var msgs = settings.commerceToastMessages.slice();
        delete settings.commerceToastMessages;
        if (drupalSettings) delete drupalSettings.commerceToastMessages;

        // Mark all current DOM messages as done so the DOM scanner below won't double-fire
        document.querySelectorAll('[data-drupal-messages] [role="contentinfo"]').forEach(function (el) {
          el.dataset.toastDone = '1';
          var block = el.closest('[data-drupal-messages]');
          if (block) block.style.display = 'none';
        });

        msgs.forEach(function (msg) {
          showToast(msg.text, msg.type);
        });
      }

      // Fallback: scan DOM for Drupal message elements (covers AJAX-injected messages)
      var root = (context === document) ? document.body : context;
      if (!root || !root.querySelectorAll) return;

      root.querySelectorAll('[data-drupal-messages] [role="contentinfo"]').forEach(function (el) {
        if (el.dataset.toastDone) return;
        el.dataset.toastDone = '1';

        var type = 'status';
        if (el.classList.contains('messages--error'))   type = 'error';
        if (el.classList.contains('messages--warning')) type = 'warning';

        // Strip visually-hidden headings then get text
        var clone = el.cloneNode(true);
        clone.querySelectorAll('.visually-hidden').forEach(function (n) { n.remove(); });

        var items = clone.querySelectorAll('li');
        if (items.length) {
          items.forEach(function (li) {
            var t = li.textContent.trim();
            if (t) showToast(t, type);
          });
        } else {
          var t = clone.textContent.trim();
          if (t) showToast(t, type);
        }

        // Hide the original block so it doesn't show twice
        var block = el.closest('[data-drupal-messages]');
        if (block) block.style.display = 'none';
      });
    }
  };

  // Quick View Modal — populate modal fields from data-* attributes on button
  Drupal.behaviors.quickViewModal = {
    attach: function (context, settings) {
      var modal = document.getElementById('quickViewModal');
      if (!modal) return;

      once('quick-view', '.quick-view-btn', context).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var title    = btn.dataset.productTitle   || '';
          var img      = btn.dataset.productImg     || '';
          var price    = btn.dataset.productPrice   || '';
          var sku      = btn.dataset.productSku     || '';
          var tagline  = btn.dataset.productTagline || '';
          var desc     = btn.dataset.productDesc    || '';
          var url      = btn.dataset.productUrl     || '#';

          modal.querySelector('#quickViewModalLabel').textContent = title;
          var imgEl = modal.querySelector('#qv-image');
          imgEl.src = img;
          imgEl.alt = title;
          modal.querySelector('#qv-price').textContent  = price;
          modal.querySelector('#qv-tagline').textContent = tagline;
          modal.querySelector('#qv-desc').textContent   = desc ? desc + '…' : '';
          modal.querySelector('#qv-link').href          = url;

          var skuEl = modal.querySelector('#qv-sku');
          skuEl.textContent = sku ? 'SKU: ' + sku : '';
          skuEl.style.display = sku ? '' : 'none';
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings, once);
