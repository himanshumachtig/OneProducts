(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.imageSearchBtn = {
    attach: function (context) {
      once('image-search-btn', '.img-search-cam-btn', context).forEach(function (btn) {
        var fileInput  = btn.parentNode.querySelector('input[type="file"]');
        var form       = btn.closest('form');
        var apiUrl     = '/image-search/api';

        // Build results dropdown, anchored to the search form wrapper.
        var wrapper = form ? form.parentNode : btn.parentNode;
        wrapper.style.position = 'relative';

        var dropdown = document.createElement('div');
        dropdown.className = 'img-search-dropdown';
        dropdown.innerHTML =
          '<div class="img-search-header">' +
            '<span>' + Drupal.t('Visual search results') + '</span>' +
            '<button type="button" class="img-search-close-dd" aria-label="' + Drupal.t('Close') + '">&times;</button>' +
          '</div>' +
          '<div class="img-search-body"></div>';
        wrapper.appendChild(dropdown);

        var body     = dropdown.querySelector('.img-search-body');
        var closeBtn = dropdown.querySelector('.img-search-close-dd');

        // Camera button opens file picker.
        btn.addEventListener('click', function () {
          fileInput.value = '';
          fileInput.click();
        });

        // File chosen → upload.
        fileInput.addEventListener('change', function () {
          var file = fileInput.files[0];
          if (!file) return;
          doSearch(file);
        });

        closeBtn.addEventListener('click', function () {
          dropdown.classList.remove('active');
        });

        // Close when clicking outside.
        document.addEventListener('click', function (e) {
          if (!wrapper.contains(e.target) && e.target !== fileInput) {
            dropdown.classList.remove('active');
          }
        });

        function doSearch(file) {
          body.innerHTML = '<div class="img-search-loading">' + Drupal.t('Searching…') + '</div>';
          dropdown.classList.add('active');

          var formData = new FormData();
          formData.append('image', file);

          fetch(apiUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
          })
          .then(function (r) {
            if (!r.ok) throw new Error('Server error');
            return r.json();
          })
          .then(function (data) {
            if (!data.results || data.results.length === 0) {
              body.innerHTML = '<div class="img-search-none">' + Drupal.t('No matching products found.') + '</div>';
              return;
            }
            renderItems(data.results);
          })
          .catch(function () {
            body.innerHTML = '<div class="img-search-none">' + Drupal.t('Something went wrong. Please try again.') + '</div>';
          });
        }

        function renderItems(items) {
          var exact   = items.filter(function(i) { return i.type === 'exact'; });
          var similar = items.filter(function(i) { return i.type !== 'exact'; });

          body.innerHTML = '';

          if (exact.length) {
            body.appendChild(sectionHeading(Drupal.t('Exact Matches')));
            body.appendChild(buildGrid(exact));
          }
          if (similar.length) {
            body.appendChild(sectionHeading(Drupal.t('Similar Products')));
            body.appendChild(buildGrid(similar));
          }
        }

        function sectionHeading(label) {
          var h = document.createElement('div');
          h.className = 'img-search-section-heading';
          h.textContent = label;
          return h;
        }

        function buildGrid(items) {
          var grid = document.createElement('div');
          grid.className = 'img-search-grid';

          items.forEach(function (item) {
            var a = document.createElement('a');
            a.className = 'img-search-item';
            a.href = item.url || '#';

            var badge = item.type === 'exact'
              ? '<span class="img-search-badge exact">' + Drupal.t('Exact') + '</span>'
              : '<span class="img-search-badge similar">' + Drupal.t('Similar') + '</span>';

            var imgTag = item.img_url
              ? '<div class="img-search-img-wrap">' + badge + '<img src="' + esc(item.img_url) + '" alt="' + esc(item.title) + '" loading="lazy"></div>'
              : '<div class="img-search-img-wrap">' + badge + '<div style="aspect-ratio:1;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:1.5rem;">&#128247;</div></div>';

            var price = item.price
              ? '<div class="img-search-item-price">' + esc(item.price) + '</div>'
              : '';

            a.innerHTML = imgTag +
              '<div class="img-search-item-info">' +
                '<div class="img-search-item-title">' + esc(item.title) + '</div>' +
                price +
                '<div class="img-search-item-score">' + Drupal.t('Match: @s%', {'@s': item.score}) + '</div>' +
              '</div>';

            grid.appendChild(a);
          });

          return grid;
        }

        function esc(s) {
          return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
        }
      });
    }
  };

}(Drupal, once));
