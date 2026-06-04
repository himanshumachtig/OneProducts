(function ($) {
  'use strict';

  // Show overlay on card hover
  $(document).on('mouseenter', '.product-card', function () {
    $(this).find('.product-card__overlay').css('opacity', '1');
    $(this).find('img').css('transform', 'scale(1.05)');
  }).on('mouseleave', '.product-card', function () {
    $(this).find('.product-card__overlay').css('opacity', '0');
    $(this).find('img').css('transform', 'scale(1)');
  });

  // Quick View button click — populate and open modal
  $(document).on('click', '.quick-view-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $card = $(this).closest('.product-card');

    var title    = $card.data('title')    || '';
    var price    = $card.data('price')    || '';
    var image    = $card.data('image')    || '';
    var url      = $card.data('url')      || '#';
    var tagline  = $card.data('tagline')  || '';
    var category = $card.data('category') || '';

    // Populate modal fields
    $('#quickViewModalLabel').text(title);
    $('#qv-image').attr('src', image).attr('alt', title);
    $('#qv-price').text(price);
    $('#qv-tagline').text(tagline);
    $('#qv-link').attr('href', url).html(
      '<i class="fas fa-shopping-bag me-2"></i>View Full Details'
    );

    if (category) {
      $('#qv-category').text(category).removeClass('d-none');
    } else {
      $('#qv-category').addClass('d-none');
    }

    // Hide unused fields
    $('#qv-sku').hide();
    $('#qv-desc').hide();

    // Open modal
    var modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
    modal.show();
  });

})(jQuery);
