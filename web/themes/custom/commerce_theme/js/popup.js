(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.sitePopup = {
    attach: function (context) {
      once('site-popup', '#site-popup', context).forEach(function (overlay) {

        function closePopup() {
          overlay.classList.remove('popup-active');
        }

        // Show popup after a short delay on every page load.
        setTimeout(function () {
          overlay.classList.add('popup-active');
        }, 1500);

        // Close button.
        var btn = overlay.querySelector('.popup-close-btn');
        if (btn) { btn.addEventListener('click', closePopup); }

        // ESC key.
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && overlay.classList.contains('popup-active')) {
            closePopup();
          }
        });
      });
    }
  };

}(Drupal, once));
