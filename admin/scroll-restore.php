<?php
// Shared: keeps the admin's scroll position across the full-page reload that
// follows a POST action (approve/reject/delete/update/etc). Include this once
// on any admin page whose action forms redirect back to itself after a POST.
// Saved continuously on scroll (not on 'beforeunload' — iOS Safari doesn't
// reliably fire that event on normal navigations).
?>
<script>
(function() {
  var SCROLL_KEY = 'adminScrollY:' + location.pathname;
  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
  function saveScrollY() {
    sessionStorage.setItem(SCROLL_KEY, String(window.scrollY));
  }
  var scrollSaveTimer = null;
  window.addEventListener('scroll', function() {
    clearTimeout(scrollSaveTimer);
    scrollSaveTimer = setTimeout(saveScrollY, 100);
  }, { passive: true });
  document.addEventListener('click', saveScrollY, true);
  window.addEventListener('DOMContentLoaded', function() {
    var savedY = sessionStorage.getItem(SCROLL_KEY);
    if (savedY !== null) {
      sessionStorage.removeItem(SCROLL_KEY);
      window.scrollTo(0, parseInt(savedY, 10) || 0);
    }
  });
})();
</script>
