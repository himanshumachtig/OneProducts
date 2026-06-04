(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.imageSearch = {
    attach: function (context) {
      once('image-search', '#image-search-drop-zone', context).forEach(function (zone) {
        var input    = document.getElementById('image-search-input');
        var thumb    = document.getElementById('image-search-thumb');
        var preview  = document.getElementById('image-search-preview');
        var clearBtn = document.getElementById('image-search-clear');
        var status   = document.getElementById('image-search-status');
        var results  = document.getElementById('image-search-results');
        var grid     = document.getElementById('image-search-grid');
        var apiUrl   = '/image-search/api';
        var currentFile = null;

        // Drag and drop
        zone.addEventListener('dragover', function (e) {
          e.preventDefault();
          zone.classList.add('drag-over');
        });
        zone.addEventListener('dragleave', function () {
          zone.classList.remove('drag-over');
        });
        zone.addEventListener('drop', function (e) {
          e.preventDefault();
          zone.classList.remove('drag-over');
          var file = e.dataTransfer.files[0];
          if (file && file.type.startsWith('image/')) {
            handleFile(file);
          }
        });

        // Click to pick file
        zone.addEventListener('click', function (e) {
          if (e.target === clearBtn || clearBtn.contains(e.target)) return;
          if (!preview.hidden) return;
          input.click();
        });

        input.addEventListener('change', function () {
          if (input.files[0]) handleFile(input.files[0]);
        });

        clearBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          resetState();
        });

        function handleFile(file) {
          currentFile = file;
          var reader = new FileReader();
          reader.onload = function (e) {
            thumb.src = e.target.result;
            preview.hidden = false;
            zone.querySelector('.upload-inner').style.display = 'none';
          };
          reader.readAsDataURL(file);
          runSearch(file);
        }

        function resetState() {
          currentFile = null;
          input.value = '';
          thumb.src = '';
          preview.hidden = true;
          zone.querySelector('.upload-inner').style.display = '';
          status.className = 'image-search-status';
          status.textContent = '';
          results.hidden = true;
          grid.innerHTML = '';
        }

        function runSearch(file) {
          status.textContent = Drupal.t('Searching for similar products…');
          status.className = 'image-search-status searching';
          results.hidden = true;
          grid.innerHTML = '';

          var formData = new FormData();
          formData.append('image', file);

          fetch(apiUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
          })
          .then(function (r) {
            if (!r.ok) throw new Error('Server error ' + r.status);
            return r.json();
          })
          .then(function (data) {
            status.className = 'image-search-status';
            if (!data.results || data.results.length === 0) {
              status.innerHTML = '<span class="status-none">' + Drupal.t('No Results Found') + '</span>';
              return;
            }
            status.textContent = '';
            renderResults(data.results);
          })
          .catch(function (err) {
            status.className = 'image-search-status';
            status.innerHTML = '<span class="status-error">' + Drupal.t('Something went wrong. Please try again.') + '</span>';
            console.error('[image-search]', err);
          });
        }

        function renderResults(items) {
          grid.innerHTML = '';
          items.forEach(function (item) {
            var card = document.createElement('a');
            card.className = 'result-card';
            card.href = item.url || '#';

            var imgHtml;
            if (item.img_url) {
              imgHtml = '<img class="result-card-img" src="' + escHtml(item.img_url) + '" alt="' + escHtml(item.title) + '" loading="lazy">';
            } else {
              imgHtml = '<div class="result-card-img placeholder">&#128247;</div>';
            }

            var priceHtml = item.price
              ? '<div class="result-card-price">' + escHtml(item.price) + '</div>'
              : '';

            card.innerHTML = imgHtml +
              '<div class="result-card-body">' +
                '<div class="result-card-title">' + escHtml(item.title) + '</div>' +
                priceHtml +
                '<div class="result-card-score">' + Drupal.t('Match: @score%', {'@score': item.score}) + '</div>' +
              '</div>';

            grid.appendChild(card);
          });
          results.hidden = false;
        }

        function escHtml(str) {
          return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
        }
      });
    }
  };

}(Drupal, drupalSettings, once));
