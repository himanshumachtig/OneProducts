(function ($, Drupal, drupalSettings, once) {
  'use strict';

  var _selectedColor   = '';
  var _selectedSize    = '';
  var _selectedStorage = '';

  Drupal.behaviors.productGallery = {
    attach: function (context, settings) {
      var pg = settings.productGallery;
      if (!pg) return;

      var catType = pg.categoryType || 'clothing';

      // Seed defaults from the first visible swatch / button.
      if (!_selectedColor) {
        var $firstSwatch = $('.product-color-swatch').first();
        if ($firstSwatch.length) _selectedColor = $firstSwatch.data('color');
      }
      if (!_selectedSize) {
        var $firstSize = $('.product-size-btn:not(.product-storage-btn)').first();
        if ($firstSize.length) _selectedSize = $firstSize.data('size');
      }
      if (!_selectedStorage) {
        var $firstStorage = $('.product-storage-btn').first();
        if ($firstStorage.length) _selectedStorage = $firstStorage.data('storage');
      }

      once('pg-init', 'body', context).forEach(function () {
        _updateDetails(_selectedColor, _selectedSize, _selectedStorage, pg);
      });

      // ── Thumbnail gallery clicks ──────────────────────────────────────────
      once('pg-thumbs', '.product-thumb-gallery', context).forEach(function (gallery) {
        $(gallery).on('click', '.product-thumb', function () {
          var $thumb = $(this);
          _setActiveThumb($thumb);
          _updateMainImage($thumb.data('full'));
        });
      });

      // ── Color swatch clicks ───────────────────────────────────────────────
      once('pg-colors', '.product-color-swatches', context).forEach(function (wrap) {
        $(wrap).on('click', '.product-color-swatch', function () {
          var $swatch = $(this);
          var color   = $swatch.data('color');
          _selectedColor = color;

          $('.product-color-swatch').removeClass('active');
          $swatch.addClass('active');

          var $label = $('.product-selected-color-name');
          if ($label.length) $label.text(color);

          _rebuildGalleryForColor(color, pg);
          _updateAvailability(color, _selectedStorage, pg);
          _updateDetails(_selectedColor, _selectedSize, _selectedStorage, pg);
          _syncAttributeSelect('attribute_color', parseInt($swatch.data('attribute-id'), 10));
        });
      });

      // ── Size button clicks ────────────────────────────────────────────────
      once('pg-sizes', '.product-size-selector', context).forEach(function (wrap) {
        $(wrap).on('click', '.product-size-btn:not(.product-storage-btn):not(.unavailable)', function () {
          var $btn = $(this);
          _selectedSize = $btn.data('size');
          $('.product-size-btn:not(.product-storage-btn)').removeClass('active');
          $btn.addClass('active');

          _updateDetails(_selectedColor, _selectedSize, _selectedStorage, pg);
          _syncAttributeSelect('attribute_size', parseInt($btn.data('attribute-id'), 10));
        });

        $(wrap).on('click', '.product-storage-btn:not(.unavailable)', function () {
          var $btn = $(this);
          _selectedStorage = $btn.data('storage');
          $('.product-storage-btn').removeClass('active');
          $btn.addClass('active');

          _updateDetails(_selectedColor, _selectedSize, _selectedStorage, pg);
          _syncAttributeSelect('attribute_storage', parseInt($btn.data('attribute-id'), 10));
        });
      });

      // ── Hide native Commerce attribute selects ───────────────────────────
      once('pg-hide-selects', 'form', context).forEach(function (form) {
        $('[name*="attribute_color"]',   form).closest('.js-form-item').addClass('pg-hidden-select');
        $('[name*="attribute_size"]',    form).closest('.js-form-item').addClass('pg-hidden-select');
        $('[name*="attribute_shoe_size"]',form).closest('.js-form-item').addClass('pg-hidden-select');
        $('[name*="attribute_age_group"]',form).closest('.js-form-item').addClass('pg-hidden-select');
        $('[name*="attribute_storage"]', form).closest('.js-form-item').addClass('pg-hidden-select');
      });
    }
  };

  // ── Helpers ───────────────────────────────────────────────────────────────

  function _updateMainImage(url) {
    var img = document.getElementById('product-img-main');
    if (!img || !url) return;
    img.src = url;
  }

  function _setActiveThumb($thumb) {
    $('.product-thumb').removeClass('active');
    $thumb.addClass('active');
  }

  function _rebuildGalleryForColor(color, pg) {
    var variationImages = [];
    $.each(pg.variations, function (vid, v) {
      if (v.color === color) {
        $.each(v.images, function (i, url) {
          if (variationImages.indexOf(url) === -1) variationImages.push(url);
        });
      }
    });

    var images;
    if (variationImages.length > 0) {
      images = variationImages.slice();
      $.each(pg.galleryImages, function (i, url) {
        if (images.indexOf(url) === -1) images.push(url);
      });
    } else {
      images = pg.galleryImages.slice();
    }
    _renderThumbs(images);
  }

  function _renderThumbs(images) {
    var $gallery = $('.product-thumb-gallery');
    if (!$gallery.length || images.length === 0) return;

    $gallery.empty();
    $.each(images, function (idx, url) {
      var $thumb = $('<div class="product-thumb' + (idx === 0 ? ' active' : '') + '" data-full="' + url + '"><img src="' + url + '" alt="" loading="lazy"></div>');
      $gallery.append($thumb);
    });
    _updateMainImage(images[0]);
  }

  function _updateAvailability(color, storage, pg) {
    // Sizes available for this color (clothing).
    var availSizes = [];
    // Storage options available for this color (electronics).
    var availStorage = [];

    $.each(pg.variations, function (vid, v) {
      if (v.color === color) {
        if (v.size)    availSizes.push(v.size);
        if (v.storage) availStorage.push(v.storage);
      }
    });

    // Update size buttons.
    if (availSizes.length > 0) {
      $('.product-size-btn:not(.product-storage-btn)').each(function () {
        var $btn  = $(this);
        var label = $btn.data('size');
        if (availSizes.indexOf(label) === -1) {
          $btn.addClass('unavailable').removeClass('active');
        } else {
          $btn.removeClass('unavailable');
        }
      });
    } else {
      $('.product-size-btn:not(.product-storage-btn)').removeClass('unavailable');
    }

    // Update storage buttons.
    if (availStorage.length > 0) {
      $('.product-storage-btn').each(function () {
        var $btn  = $(this);
        var label = $btn.data('storage');
        if (availStorage.indexOf(label) === -1) {
          $btn.addClass('unavailable').removeClass('active');
        } else {
          $btn.removeClass('unavailable');
        }
      });
    } else {
      $('.product-storage-btn').removeClass('unavailable');
    }
  }

  function _updateDetails(color, size, storage, pg) {
    var matched = false;
    $.each(pg.variations, function (vid, v) {
      var colorMatch   = !color   || v.color   === color;
      var sizeMatch    = !size    || v.size    === size    || v.size    === '';
      var storageMatch = !storage || v.storage === storage || v.storage === '';

      // For electronics (storage product), match color + storage.
      // For clothing / shoes / kids, match color + size.
      if (!colorMatch) return;
      if (storage && v.storage && v.storage !== storage) return;
      if (size    && v.size    && v.size    !== size)    return;

      matched = true;

      var $sku = $('#product-sku-value');
      if ($sku.length && v.sku) $sku.text(v.sku);

      if (v.price) {
        var formatted = _formatPrice(v.price.number, v.price.currency_code);
        $('#product-price').text(formatted);
      }

      var $lp = $('#product-list-price');
      if (v.listPrice) {
        $lp.text(_formatPrice(v.listPrice.number, v.listPrice.currency_code)).show();
      } else {
        $lp.hide();
      }

      var $disc = $('#product-discount');
      if (v.discount > 0) {
        $disc.text(v.discount + '% OFF').show();
      } else {
        $disc.hide();
      }

      var $stock = $('#product-stock-status');
      if ($stock.length) {
        if (v.stock === null || v.stock === undefined) {
          $stock.html('');
        } else if (v.stock > 10) {
          $stock.html('<span class="badge bg-success"><i class="fa fa-check-circle me-1"></i>In Stock (' + v.stock + ' available)</span>');
        } else if (v.stock > 0) {
          $stock.html('<span class="badge bg-warning text-dark"><i class="fa fa-exclamation-circle me-1"></i>Only ' + v.stock + ' left!</span>');
        } else {
          $stock.html('<span class="badge bg-danger"><i class="fa fa-times-circle me-1"></i>Out of Stock</span>');
        }
      }

      return false; // break $.each
    });
  }

  function _formatPrice(number, currencyCode) {
    var num = parseFloat(number);
    try {
      return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currencyCode,
        minimumFractionDigits: 2
      }).format(num);
    } catch (e) {
      return currencyCode + ' ' + num.toFixed(2);
    }
  }

  function _syncAttributeSelect(attributeName, attributeId) {
    var $select = $('select[name*="[' + attributeName + ']"], select[name*="' + attributeName + '"]');
    if (!$select.length) return;
    $select.val(String(attributeId));
    $select[0].dispatchEvent(new Event('change', { bubbles: true }));
  }

})(jQuery, Drupal, drupalSettings, once);
