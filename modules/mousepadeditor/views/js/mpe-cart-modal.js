(function(){
  'use strict';

  function ready(fn){
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function openModal(url){
    var overlay = document.getElementById('mpe-cart-modal');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'mpe-cart-modal';
      overlay.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;align-items:center;justify-content:center;cursor:zoom-out;padding:20px;';
      var img = document.createElement('img');
      img.id = 'mpe-cart-modal-img';
      img.style.cssText = 'max-width:92vw;max-height:92vh;box-shadow:0 0 40px rgba(0,0,0,.5);border-radius:6px;';
      overlay.appendChild(img);
      overlay.addEventListener('click', function(){ overlay.style.display = 'none'; });
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape') overlay.style.display = 'none'; });
      document.body.appendChild(overlay);
    }
    document.getElementById('mpe-cart-modal-img').src = url;
    overlay.style.display = 'flex';
  }

  function rewirePersoLinks(){
    // Cibler tous les liens/textes "Personnalisation" dans le panier
    var labels = document.querySelectorAll('.product-customization, .cart-item-customizations, .customization');
    labels.forEach(function(el){
      // Renommer le libellé
      el.querySelectorAll('*').forEach(function(node){
        if (node.childNodes) {
          node.childNodes.forEach(function(c){
            if (c.nodeType === 3 && c.nodeValue && c.nodeValue.trim() === 'Personnalisation') {
              c.nodeValue = 'Aperçu de la création';
            }
          });
        }
      });
      // Intercepter les liens vers /upload/ pour ouvrir en modal
      el.querySelectorAll('a[href*="/upload/"]').forEach(function(a){
        if (a.dataset.mpeBound) return;
        a.dataset.mpeBound = '1';
        a.textContent = 'Aperçu de la création';
        a.addEventListener('click', function(e){
          e.preventDefault();
          // Tenter de charger la version _full (avec overlay) si elle existe
          var href = a.getAttribute('href');
          var fullUrl = href.indexOf('_full') === -1 ? href + '_full' : href;
          var test = new Image();
          test.onload = function(){ openModal(fullUrl); };
          test.onerror = function(){ openModal(href); };
          test.src = fullUrl;
        });
      });
    });
  }

  ready(function(){
    rewirePersoLinks();
    // Observer les changements (ajax cart update)
    var obs = new MutationObserver(function(){ rewirePersoLinks(); });
    obs.observe(document.body, { childList: true, subtree: true });
  });
})();
