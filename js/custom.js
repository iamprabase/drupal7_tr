jQuery.fn.redirect = function (url) {
    window.location.href = Drupal.settings.basePath + url;
};