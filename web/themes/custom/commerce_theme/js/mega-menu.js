(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.megaMenu = {
    attach: function (context) {
      once('mega-menu', '.navbar-nav', context).forEach(function (nav) {

        var isMobile = function () { return window.innerWidth < 992; };

        // ── Desktop: hover open/close ──
        nav.querySelectorAll('.cat-dropdown').forEach(function (item) {
          item.addEventListener('mouseenter', function () {
            if (isMobile()) return;
            nav.querySelectorAll('.cat-dropdown.is-open').forEach(function (o) {
              if (o !== item) o.classList.remove('is-open');
            });
            item.classList.add('is-open');
          });
          item.addEventListener('mouseleave', function () {
            if (isMobile()) return;
            item.classList.remove('is-open');
          });
        });

        // ── Mobile: toggle submenu on button click ──
        nav.querySelectorAll('.cat-mobile-toggle').forEach(function (btn) {
          btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var item = btn.closest('.cat-dropdown');
            var isOpen = item.classList.contains('mobile-open');
            // Close others
            nav.querySelectorAll('.cat-dropdown.mobile-open').forEach(function (o) {
              if (o !== item) o.classList.remove('mobile-open');
            });
            item.classList.toggle('mobile-open', !isOpen);
          });
        });

        // ── Close on outside click ──
        document.addEventListener('click', function (e) {
          if (!nav.contains(e.target)) {
            nav.querySelectorAll('.cat-dropdown.is-open').forEach(function (o) {
              o.classList.remove('is-open');
            });
          }
        });

        // ── Reset mobile state on resize to desktop ──
        window.addEventListener('resize', function () {
          if (!isMobile()) {
            nav.querySelectorAll('.cat-dropdown.mobile-open').forEach(function (o) {
              o.classList.remove('mobile-open');
            });
          }
        });

      });
    }
  };

})(Drupal, once);
