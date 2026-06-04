(function ($, Drupal, once) {
  'use strict';

  var STORAGE_KEY = 'commerce_wishlist';

  // ── Storage helpers ───────────────────────────────────────────────────────

  function _getAll() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; }
    catch (e) { return []; }
  }

  function _save(items) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
  }

  function _has(id) {
    return _getAll().some(function (i) { return i.id === String(id); });
  }

  function _add(item) {
    var items = _getAll();
    if (!_has(item.id)) { items.push(item); _save(items); }
  }

  function _remove(id) {
    _save(_getAll().filter(function (i) { return i.id !== String(id); }));
  }

  function _toggle(id, data) {
    if (_has(id)) { _remove(id); return false; }
    _add(data); return true;
  }

  // ── UI helpers ────────────────────────────────────────────────────────────

  function _updateCountBadge() {
    var count = _getAll().length;
    $('.wishlist-count-badge').each(function () {
      if (count > 0) { $(this).text(count).show(); }
      else           { $(this).hide(); }
    });
  }

  function _applyHeartState($btn, isWished) {
    var $icon = $btn.find('i');
    if (isWished) {
      $icon.removeClass('far fa-heart').addClass('fas fa-heart');
      $btn.addClass('wishlisted').attr('title', Drupal.t('Remove from Wishlist'));
    } else {
      $icon.removeClass('fas fa-heart').addClass('far fa-heart');
      $btn.removeClass('wishlisted').attr('title', Drupal.t('Add to Wishlist'));
    }
  }

  function _showToast(msg) {
    var $t = $('<div class="wishlist-toast">' + msg + '</div>');
    $('body').append($t);
    setTimeout(function () { $t.addClass('show'); }, 10);
    setTimeout(function () { $t.removeClass('show'); setTimeout(function () { $t.remove(); }, 300); }, 2200);
  }

  // ── Wishlist page renderer ────────────────────────────────────────────────

  function _renderWishlistPage() {
    var $wrap = $('#wishlist-page-items');
    if (!$wrap.length) return;
    var items = _getAll();
    $wrap.empty();

    if (items.length === 0) {
      $wrap.html(
        '<div class="col-12 text-center py-5">' +
        '<i class="far fa-heart" style="font-size:4rem;color:#dee2e6;"></i>' +
        '<h4 class="mt-3 text-muted">' + Drupal.t('Your wishlist is empty') + '</h4>' +
        '<a href="/products" class="btn btn-primary rounded-pill px-4 mt-2">' + Drupal.t('Start Shopping') + '</a>' +
        '</div>'
      );
      return;
    }

    $.each(items, function (i, item) {
      var $card = $(
        '<div class="col-sm-6 col-md-4 col-lg-3 mb-4">' +
          '<div class="card h-100 border-0 shadow-sm rounded-3 position-relative">' +
            '<button class="wishlist-remove-btn position-absolute btn btn-sm btn-danger rounded-circle" ' +
                    'data-id="' + item.id + '" style="top:10px;right:10px;width:32px;height:32px;padding:0;z-index:2;" ' +
                    'title="' + Drupal.t('Remove') + '">' +
              '<i class="fas fa-times"></i>' +
            '</button>' +
            '<a href="' + item.url + '">' +
              '<img src="' + item.image + '" alt="' + item.title + '" ' +
                   'class="card-img-top p-3" style="height:200px;object-fit:contain;">' +
            '</a>' +
            '<div class="card-body d-flex flex-column">' +
              '<h6 class="card-title fw-semibold mb-1">' +
                '<a href="' + item.url + '" class="text-dark text-decoration-none">' + item.title + '</a>' +
              '</h6>' +
              (item.price ? '<p class="text-primary fw-bold mb-2">' + item.price + '</p>' : '') +
              '<a href="' + item.url + '" class="btn btn-primary btn-sm rounded-pill mt-auto">' +
                Drupal.t('View Product') +
              '</a>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
      $wrap.append($card);
    });

    // Remove button on wishlist page
    $wrap.on('click', '.wishlist-remove-btn', function () {
      var id = $(this).data('id');
      _remove(id);
      _updateCountBadge();
      // Re-sync heart on any visible product card
      $('[data-wishlist-id="' + id + '"]').each(function () {
        _applyHeartState($(this), false);
      });
      _renderWishlistPage();
    });
  }

  // ── Drupal behavior ───────────────────────────────────────────────────────

  Drupal.behaviors.wishlist = {
    attach: function (context) {

      // Sync count badge on every attach (page load + AJAX)
      _updateCountBadge();

      // Render wishlist page if the container is present
      once('wl-page', '#wishlist-page-items', context).forEach(function () {
        _renderWishlistPage();
      });

      // ── Product card heart buttons (listing pages) ──────────────────────
      once('wl-card', '.wl-card-btn', context).forEach(function (btn) {
        var $btn = $(btn);
        var id   = String($btn.data('wishlist-id'));
        _applyHeartState($btn, _has(id));

        $btn.on('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var data = {
            id:    id,
            title: $btn.data('title'),
            url:   $btn.data('url'),
            price: $btn.data('price'),
            image: $btn.data('image'),
          };
          var added = _toggle(id, data);
          _applyHeartState($btn, added);
          _updateCountBadge();
          _showToast(added ? Drupal.t('Added to Wishlist') : Drupal.t('Removed from Wishlist'));
        });
      });

      // ── Product detail page wishlist button ──────────────────────────────
      once('wl-detail', '#product-wishlist-btn', context).forEach(function (btn) {
        var $btn = $(btn);
        var id   = String($btn.data('product-id'));
        _applyHeartState($btn, _has(id));

        $btn.on('click', function (e) {
          e.preventDefault();
          var data = {
            id:    id,
            title: $btn.data('title'),
            url:   $btn.data('url'),
            price: $btn.data('price'),
            image: $btn.data('image'),
          };
          var added = _toggle(id, data);
          _applyHeartState($btn, added);
          _updateCountBadge();
          _showToast(added ? Drupal.t('Added to Wishlist') : Drupal.t('Removed from Wishlist'));
        });
      });

    }
  };

})(jQuery, Drupal, once);
