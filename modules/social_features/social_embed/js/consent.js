(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.social_embed = {
    attach: function (context, settings) {

      // Attach a click listener to the clear button.
      var showContentBtn = document.getElementById('social-embed-show-button');
      showContentBtn.addEventListener('click', function () {
        $.ajax({
          url: Drupal.url('api/opensocial/social-embed/generate'),
          type: 'POST',
          data: JSON.stringify ({'url': 'https://www.youtube.com/watch?v=VXgLBa5jgr8'}),
          contentType: "application/json",
          dataType: 'json'
        });
      }, false);

    }
  }
} (jQuery, Drupal, drupalSettings));
