(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.productReviews = {
    attach: function (context) {

      // Target the comment form directly — works regardless of wrapper
      once('product-review-form', '.comment-product-review-form', context).forEach(function (form) {

        var nativeSelect = form.querySelector('select[name="field_rating"]');
        if (!nativeSelect) return;

        // ── 1. Build the star picker ─────────────────────────────────────
        var pickerLabel = document.createElement('div');
        pickerLabel.className = 'star-picker-label';
        pickerLabel.textContent = Drupal.t('Your Rating') + ' *';

        var picker = document.createElement('div');
        picker.className = 'star-picker';

        var errorMsg = document.createElement('div');
        errorMsg.className = 'star-picker-error';
        errorMsg.textContent = Drupal.t('Please select a star rating.');

        for (var i = 5; i >= 1; i--) {
          var radio = document.createElement('input');
          radio.type  = 'radio';
          radio.name  = 'star_visual';
          radio.id    = 'star-pick-' + i;
          radio.value = i;

          var lbl = document.createElement('label');
          lbl.htmlFor = 'star-pick-' + i;
          lbl.innerHTML = '&#9733;';
          lbl.title = i + (i === 1 ? ' Star' : ' Stars');

          (function (val) {
            radio.addEventListener('change', function () {
              nativeSelect.value = val;
              errorMsg.style.display = 'none';
            });
          })(i);

          picker.appendChild(radio);
          picker.appendChild(lbl);
        }

        // Insert star picker before the first visible form-item
        var firstItem = form.querySelector('.form-item');
        form.insertBefore(pickerLabel, firstItem);
        form.insertBefore(picker,      firstItem);
        form.insertBefore(errorMsg,    firstItem);

        // ── 2. Validate on submit ────────────────────────────────────────
        form.addEventListener('submit', function (e) {
          if (!nativeSelect.value || nativeSelect.value === '_none') {
            e.preventDefault();
            errorMsg.style.display = 'block';
            picker.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        });

        // ── 3. Rename Save → Submit Review ───────────────────────────────
        var saveBtn = form.querySelector('input[value="Save"]');
        if (saveBtn) saveBtn.value = Drupal.t('Submit Review');

        // ── 4. Placeholders ──────────────────────────────────────────────
        var subjectInput = form.querySelector('[name="subject[0][value]"]');
        if (subjectInput) subjectInput.placeholder = Drupal.t('Give your review a title…');

        var bodyTextarea = form.querySelector('[name="comment_body[0][value]"]');
        if (bodyTextarea) {
          bodyTextarea.placeholder = Drupal.t('Share your experience with this product…');
          bodyTextarea.rows = 4;
        }
      });
    }
  };

})(Drupal, once);
