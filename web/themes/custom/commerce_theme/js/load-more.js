(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.loadMoreProducts = {
    attach(context) {
      once('load-more-products', '#load-more-sentinel', context).forEach(function (sentinel) {
        let loading = false;
        let finished = false;

        const $pager    = $('#products-pager');
        const $grid     = $('#products-grid-container');
        const $loader   = $('#load-more-loader');

        // Check immediately if there's a next page.
        function hasNextPage() {
          return $pager.find('a[rel="next"], .pager__item--next a').length > 0;
        }

        // Hide sentinel if no more pages on first load.
        if (!hasNextPage()) {
          $(sentinel).hide();
          return;
        }

        const observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting || loading || finished) return;

            const $nextLink = $pager.find('a[rel="next"], .pager__item--next a').first();
            if (!$nextLink.length) {
              finished = true;
              $(sentinel).hide();
              $loader.hide();
              return;
            }

            let nextUrl = $nextLink.attr('href');
            if (!nextUrl) {
              finished = true;
              $(sentinel).hide();
              return;
            }

            // Preserve active filters (title=, field_category_target_id=, etc.)
            // in the next-page URL — the pager link only carries ?page=N.
            try {
              var currentParams = new URLSearchParams(window.location.search);
              var nextObj = new URL(nextUrl, window.location.origin);
              currentParams.forEach(function (val, key) {
                if (key !== 'page' && !nextObj.searchParams.has(key)) {
                  nextObj.searchParams.set(key, val);
                }
              });
              nextUrl = nextObj.toString();
            } catch (e) {}

            loading = true;
            $loader.show();

            $.get(nextUrl, function (html) {
              const $response   = $(html);
              const $newItems   = $response.find('#products-grid-container').children();
              const $newPager   = $response.find('#products-pager');

              if ($newItems.length) {
                $grid.append($newItems);
                $pager.html($newPager.html());
              }

              // Check if more pages remain.
              if (!$newItems.length || !hasNextPage()) {
                finished = true;
                $(sentinel).hide();
              }

              $loader.hide();
              loading = false;
            }).fail(function () {
              $loader.hide();
              loading = false;
            });
          });
        }, {
          root: null,
          rootMargin: '200px', // Trigger 200px before reaching the sentinel.
          threshold: 0,
        });

        observer.observe(sentinel);
      });
    }
  };

})(jQuery, Drupal, once);
