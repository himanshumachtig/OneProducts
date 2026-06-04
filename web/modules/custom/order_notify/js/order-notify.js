(function (Drupal, once) {
  'use strict';

  var POLL_MS = 60 * 1000;

  Drupal.behaviors.orderNotify = {
    attach: function (context, settings) {
      once('order-notify', '.order-notify-wrap', context).forEach(function (wrap) {
        var trigger    = wrap.querySelector('.order-notify-trigger');
        var badge      = wrap.querySelector('.order-notify-badge');
        var dropdown   = wrap.querySelector('.order-notify-dropdown');
        var list       = wrap.querySelector('.order-notify-list');
        var markAllBtn = wrap.querySelector('.order-notify-mark-read');
        var cfg        = settings.orderNotify || {};
        var cached     = [];

        // ── Helpers ──────────────────────────────────────────────
        function setBadge(count) {
          if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.removeAttribute('hidden');
          } else {
            badge.setAttribute('hidden', '');
          }
        }

        function renderList(orders) {
          if (!orders.length) {
            list.innerHTML = '<li class="order-notify-empty">' + Drupal.t('No new orders') + '</li>';
            return;
          }
          list.innerHTML = orders.map(function (o) {
            return '<li class="order-notify-item" data-order-id="' + o.id + '">'
              + '<a href="' + o.url + '">'
              +   '<span class="on-id">Order #' + o.number + '</span>'
              +   '<span class="on-customer">' + o.customer + '</span>'
              +   '<span class="on-meta">'
              +     '<span class="on-date">' + o.date + '</span>'
              +     '<span class="on-total">' + o.currency + ' ' + o.total + '</span>'
              +   '</span>'
              + '</a>'
              + '<button class="on-dismiss" type="button" data-order-id="' + o.id + '" title="' + Drupal.t('Dismiss') + '">&#10005;</button>'
              + '</li>';
          }).join('');
        }

        // ── Get fresh CSRF token from Drupal ─────────────────────
        function getFreshToken() {
          return fetch('/session/token', { credentials: 'same-origin' })
            .then(function (r) { return r.text(); })
            .then(function (t) { return t.trim(); });
        }

        // ── Poll server for new orders ────────────────────────────
        function poll() {
          fetch(cfg.countUrl, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              cached = data.orders || [];
              setBadge(data.count || 0);
            })
            .catch(function () {});
        }

        // ── Dismiss single order ──────────────────────────────────
        function dismissOrder(orderId, btn) {
          btn.disabled = true;

          getFreshToken()
            .then(function (token) {
              return fetch(cfg.dismissUrl, {
                method:      'POST',
                credentials: 'same-origin',
                headers: {
                  'X-CSRF-Token': token,
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: parseInt(orderId, 10) }),
              });
            })
            .then(function (r) {
              if (!r.ok) { throw new Error('Server error ' + r.status); }
              // Remove this order from cached list and re-render.
              cached = cached.filter(function (o) {
                return String(o.id) !== String(orderId);
              });
              setBadge(cached.length);
              renderList(cached);
            })
            .catch(function (err) {
              console.error('Dismiss failed:', err);
              btn.disabled = false;
            });
        }

        // ── Toggle dropdown ───────────────────────────────────────
        trigger.addEventListener('click', function (e) {
          e.stopPropagation();
          var isHidden = dropdown.hasAttribute('hidden');
          if (isHidden) {
            renderList(cached);
            dropdown.removeAttribute('hidden');
            trigger.setAttribute('aria-expanded', 'true');
          } else {
            dropdown.setAttribute('hidden', '');
            trigger.setAttribute('aria-expanded', 'false');
          }
        });

        // ── Dismiss button click (event delegation on list) ───────
        list.addEventListener('click', function (e) {
          var btn = e.target.closest('.on-dismiss');
          if (!btn) { return; }
          e.preventDefault();
          e.stopPropagation();
          dismissOrder(btn.dataset.orderId, btn);
        });

        // ── Mark ALL read ─────────────────────────────────────────
        markAllBtn.addEventListener('click', function () {
          markAllBtn.disabled = true;
          markAllBtn.textContent = Drupal.t('Saving…');

          getFreshToken()
            .then(function (token) {
              return fetch(cfg.markReadUrl, {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'X-CSRF-Token': token },
              });
            })
            .then(function (r) {
              if (!r.ok) { throw new Error('Server error ' + r.status); }
              cached = [];
              setBadge(0);
              renderList([]);
              dropdown.setAttribute('hidden', '');
              trigger.setAttribute('aria-expanded', 'false');
              poll();
            })
            .catch(function (err) {
              console.error('Mark-read failed:', err);
            })
            .finally(function () {
              markAllBtn.disabled = false;
              markAllBtn.textContent = Drupal.t('Mark all read');
            });
        });

        // ── Close on outside click ────────────────────────────────
        document.addEventListener('click', function (e) {
          if (!wrap.contains(e.target)) {
            dropdown.setAttribute('hidden', '');
            trigger.setAttribute('aria-expanded', 'false');
          }
        });

        // ── Start ─────────────────────────────────────────────────
        poll();
        setInterval(poll, POLL_MS);
      });
    }
  };

}(Drupal, once));
