(function () {
  'use strict';

  function init(root) {
    var buttons = root.querySelectorAll('.brandsaz__letter[data-letter]');
    var groups  = root.querySelectorAll('.brandsaz__group');
    var input   = root.querySelector('.brandsaz__input');
    var none    = root.querySelector('.brandsaz__none');
    var current = 'all';

    function hasAllButton() {
      return !!root.querySelector('.brandsaz__letter[data-letter="all"]');
    }

    function setActive() {
      buttons.forEach(function (b) {
        var on = b.getAttribute('data-letter') === current;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
    }

    function message(q) {
      if (q) {
        return (root.getAttribute('data-msg-search') || '').replace('{query}', q);
      }
      if (current !== 'all') {
        return (root.getAttribute('data-msg-letter') || '').replace('{letter}', current);
      }
      return root.getAttribute('data-msg-all') || '';
    }

    function render() {
      var raw = input ? input.value.trim() : '';
      var q = raw.toLowerCase();
      var shown = 0;

      groups.forEach(function (g) {
        var letterOk = current === 'all' || g.getAttribute('data-letter') === current;
        var matches = 0;
        g.querySelectorAll('li').forEach(function (li) {
          var ok = !q || (li.getAttribute('data-name') || '').indexOf(q) !== -1;
          li.style.display = ok ? '' : 'none';
          if (ok) { matches++; }
        });
        var visible = letterOk && matches > 0;
        g.style.display = visible ? '' : 'none';
        if (visible) { shown++; }
      });

      if (shown > 0) {
        none.hidden = true;
        none.textContent = '';
      } else {
        none.textContent = message(raw);
        none.hidden = false;
      }
    }

    buttons.forEach(function (b) {
      b.addEventListener('click', function () {
        var letter = b.getAttribute('data-letter');
        // Clicking the active letter again goes back to "all" (if View All exists).
        if (letter === current && letter !== 'all' && hasAllButton()) {
          letter = 'all';
        }
        current = letter;
        if (input) { input.value = ''; }
        setActive();
        render();
      });
    });

    if (input) {
      input.addEventListener('input', function () {
        current = 'all';
        setActive();
        render();
      });
    }

    setActive();
    render();
  }

  function boot() {
    document.querySelectorAll('.brandsaz').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
