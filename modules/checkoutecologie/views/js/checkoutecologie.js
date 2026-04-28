(function () {
  'use strict';

  function init() {
    var checkbox = document.getElementById('ceco-checkbox');
    if (!checkbox) {
      return;
    }
    var block = checkbox.closest('.ceco-block');
    var feedback = document.getElementById('ceco-feedback');
    var url = checkbox.getAttribute('data-toggle-url');
    if (!url) {
      return;
    }

    checkbox.addEventListener('change', function () {
      var active = checkbox.checked ? 1 : 0;
      block.classList.add('ceco-loading');
      feedback.classList.remove('ceco-error');
      feedback.textContent = '';

      var fd = new FormData();
      fd.append('active', active);
      fd.append('ajax', '1');

      fetch(url, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) {
          return r.text().then(function (txt) {
            try {
              return { ok: r.ok, data: JSON.parse(txt) };
            } catch (e) {
              return { ok: r.ok, data: null, raw: txt };
            }
          });
        })
        .then(function (resp) {
          block.classList.remove('ceco-loading');
          if (resp.data && resp.data.success) {
            feedback.classList.remove('ceco-error');
            feedback.textContent = active ? 'Réduction appliquée. Le total sera mis à jour à l\'étape suivante.' : 'Réduction retirée.';
          } else {
            // Si décocher et que le serveur dit "invalid_state" (cart rule pas appliquée),
            // c'est OK : on accepte la décoche silencieusement.
            if (!active && resp.data && resp.data.error === 'invalid_state') {
              feedback.classList.remove('ceco-error');
              feedback.textContent = 'Réduction retirée.';
              return;
            }
            checkbox.checked = !active;
            feedback.classList.add('ceco-error');
            var msg = 'Action impossible.';
            if (resp.data && resp.data.error) {
              msg = 'Erreur : ' + resp.data.error;
            } else if (resp.raw) {
              msg = 'Réponse inattendue (HTTP ' + (resp.ok ? '200' : 'erreur') + ').';
              console.error('[checkoutecologie] non-JSON response:', resp.raw.substring(0, 200));
            }
            feedback.textContent = msg;
          }
        })
        .catch(function (err) {
          block.classList.remove('ceco-loading');
          checkbox.checked = !active;
          feedback.classList.add('ceco-error');
          feedback.textContent = 'Erreur réseau (' + (err && err.message ? err.message : 'inconnue') + ').';
          console.error('[checkoutecologie] fetch error:', err);
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
