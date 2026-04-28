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
        .then(function (r) { return r.json(); })
        .then(function (data) {
          block.classList.remove('ceco-loading');
          if (data && data.success) {
            feedback.textContent = active ? 'Réduction appliquée.' : 'Réduction retirée.';
            // Demander à PrestaShop de rafraîchir le résumé panier + checkout
            if (window.prestashop && typeof window.prestashop.emit === 'function') {
              window.prestashop.emit('updateCart', { reason: { linkAction: 'cart-rule' } });
            }
            // Rafraîchir l'étape livraison (recalcul prix transporteur, etc.)
            var deliveryForm = document.getElementById('js-delivery');
            if (deliveryForm && typeof window.$ !== 'undefined') {
              try {
                window.$.ajax({
                  url: deliveryForm.getAttribute('data-url-update'),
                  method: 'POST',
                  data: window.$(deliveryForm).serialize() + '&ajax=1'
                });
              } catch (e) {}
            }
          } else {
            // Rollback visuel
            checkbox.checked = !active;
            feedback.classList.add('ceco-error');
            feedback.textContent = 'Erreur. Veuillez réessayer.';
          }
        })
        .catch(function () {
          block.classList.remove('ceco-loading');
          checkbox.checked = !active;
          feedback.classList.add('ceco-error');
          feedback.textContent = 'Erreur réseau.';
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // PrestaShop charge les étapes du checkout en partial — on réinitialise
  // si la zone disparaît / réapparaît
  if (window.prestashop && typeof window.prestashop.on === 'function') {
    window.prestashop.on('updatedDeliveryForm', function () {
      init();
    });
  }
})();
