(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.liveSearch = {
    attach: function (context) {
      once('live-search', 'input[name="keys"]', context).forEach(function (input) {

        // Anchor the dropdown to the closest .position-relative wrapper
        // (the div wrapping the search form), not the narrow flex form-item.
        var container = input.closest('.position-relative') || input.closest('form') || input.parentNode;
        container.style.position = 'relative';

        var dropdown = document.createElement('div');
        dropdown.className = 'live-search-dropdown';
        container.appendChild(dropdown);

        var timer = null;

        input.addEventListener('input', function () {
          clearTimeout(timer);
          var q = input.value.trim();

          if (q.length < 2) {
            dropdown.innerHTML = '';
            dropdown.classList.remove('active');
            return;
          }

          timer = setTimeout(function () {
            fetch('/api/live-search?q=' + encodeURIComponent(q))
              .then(function (r) { return r.json(); })
              .then(function (results) {
                dropdown.innerHTML = '';

                if (!results.length) {
                  dropdown.innerHTML = '<div class="ls-empty">No products found</div>';
                  dropdown.classList.add('active');
                  return;
                }

                results.forEach(function (item) {
                  var row = document.createElement('a');
                  row.href = item.url;
                  row.className = 'ls-item';
                  row.innerHTML =
                    '<img src="' + (item.image || '') + '" alt="' + item.title + '">' +
                    '<div class="ls-info">' +
                      '<span class="ls-title">' + item.title + '</span>' +
                      (item.category ? '<span class="ls-cat">' + item.category + '</span>' : '') +
                    '</div>' +
                    '<span class="ls-price">' + (item.price || '') + '</span>';
                  dropdown.appendChild(row);
                });

                var seeAll = document.createElement('a');
                seeAll.href = '/products?title=' + encodeURIComponent(q);
                seeAll.className = 'ls-see-all';
                seeAll.textContent = 'See all results for "' + q + '"';
                dropdown.appendChild(seeAll);

                dropdown.classList.add('active');
              })
              .catch(function () {
                dropdown.classList.remove('active');
              });
          }, 300);
        });

        // Close on outside click or Escape.
        document.addEventListener('click', function (e) {
          if (!container.contains(e.target)) {
            dropdown.classList.remove('active');
          }
        });

        input.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            dropdown.classList.remove('active');
            input.blur();
          }
        });
      });
    }
  };

})(Drupal, once);
