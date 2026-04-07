<div id="mpe-confirm-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:99998;align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:8px;max-width:440px;width:100%;padding:28px;box-shadow:0 10px 40px rgba(0,0,0,.3);text-align:center;">
    <div style="font-size:44px;margin-bottom:10px;">⚠️</div>
    <h3 style="color:#004774;margin:0 0 12px;font-size:20px;font-family:'Bebas Neue',sans-serif;letter-spacing:1px;">Tout effacer ?</h3>
    <p style="color:#666;font-size:14px;line-height:1.5;margin:0 0 22px;">
      Votre création actuelle (fond, images et textes) sera intégralement supprimée.
      <br/>Cette action est <strong>irréversible</strong>.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button type="button" id="mpe-confirm-cancel" style="background:#fff;color:#666;border:1px solid #ddd;padding:12px 24px;border-radius:4px;font-weight:600;cursor:pointer;font-size:14px;">Annuler</button>
      <button type="button" id="mpe-confirm-ok" style="background:#e74c3c;color:#fff;border:none;padding:12px 24px;border-radius:4px;font-weight:600;cursor:pointer;font-size:14px;">Oui, tout effacer</button>
    </div>
  </div>
</div>

<div id="mpe-ext-warning" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:99999;align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:8px;max-width:480px;width:100%;padding:28px;box-shadow:0 10px 40px rgba(0,0,0,.3);text-align:center;">
    <div style="font-size:48px;margin-bottom:10px;">⚠️</div>
    <h3 style="color:#004774;margin:0 0 12px;font-size:20px;">Extension navigateur détectée</h3>
    <p style="color:#666;font-size:14px;line-height:1.5;margin:0 0 20px;">
      Une extension de votre navigateur (gestionnaire de mots de passe type Bitwarden, 1Password, LastPass…) semble interférer avec l'éditeur de personnalisation.
      <br/><br/>
      Pour une expérience optimale, nous vous recommandons de <strong>désactiver temporairement</strong> cette extension sur cette page, ou d'ouvrir le site en <strong>navigation privée</strong>.
    </p>
    <button type="button" id="mpe-ext-close" style="background:#ee7a03;color:#fff;border:none;padding:12px 28px;border-radius:4px;font-weight:600;cursor:pointer;font-size:14px;">J'ai compris, continuer</button>
  </div>
</div>

<div class="mousepad-editor">
  <h3 class="mpe-title">Zone de personnalisation</h3>

  <div class="mpe-layout">
  <div class="mpe-canvas-wrap">
    <canvas id="mpe-canvas"></canvas>
    <div class="mpe-canvas-toolbar">
      <button type="button" class="mpe-tool-btn" id="mpe-delete-selected" title="Supprimer l'élément sélectionné">🗑 Supprimer la sélection</button>
      <button type="button" class="mpe-tool-btn" id="mpe-reset" title="Effacer toute la création">↺ Tout effacer</button>
    </div>
  </div>

  <div class="mpe-accordion">

    <div class="mpe-item">
      <button type="button" class="mpe-head" data-target="mpe-fonds">
        <span>Fonds</span>
        <span class="mpe-arrow">+</span>
      </button>
      <div class="mpe-body" id="mpe-fonds">
        <div class="mpe-bg-controls" id="mpe-bg-controls" style="display:none;margin-bottom:14px;">
          <label class="mpe-slider-label">Zoom du fond
            <input type="range" id="mpe-bg-zoom" min="100" max="300" value="100" />
          </label>
        </div>

        <p class="mpe-hint">Choisissez un fond pour votre tapis :</p>
        <div class="mpe-grid">
          <div class="mpe-thumb mpe-bg-thumb mpe-thumb-solid" style="background:#ffffff;border:2px solid #ccc;" data-bg="#ffffff" title="Fond blanc"></div>
          <div class="mpe-thumb mpe-bg-thumb mpe-thumb-solid" style="background:#000000;border:2px solid #000;" data-bg="#000000" title="Fond noir"></div>
          {if isset($mpe_backgrounds) && $mpe_backgrounds|count > 0}
            {foreach from=$mpe_backgrounds item=bg}
              <div class="mpe-thumb mpe-thumb-img mpe-bg-thumb" style="background-image:url('{$mpe_bg_url}{$bg}');" data-bg="{$mpe_bg_url}{$bg}"></div>
            {/foreach}
          {/if}
        </div>

        <div class="mpe-divider"><span>OU</span></div>

        <p class="mpe-hint mpe-subtitle">Importez votre propre fond :</p>
        <div class="mpe-customer-upload" data-upload-url="{$mpe_upload_url}">
          <div class="mpe-cdz" id="mpe-cdz" {if $mpe_customer_bg}style="display:none;"{/if}>
            <div class="mpe-cdz-icon">⬆</div>
            <div class="mpe-cdz-title">Glissez votre image ici</div>
            <div class="mpe-cdz-sub">ou cliquez pour parcourir<br/>jpg, png, webp, heic — max 10 Mo</div>
            <input type="file" id="mpe-cfile" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" />
          </div>
          <div class="mpe-cbg-preview" id="mpe-cbg-preview" {if !$mpe_customer_bg}style="display:none;"{/if}>
            <div class="mpe-cbg-img mpe-bg-thumb" id="mpe-cbg-img" {if $mpe_customer_bg}style="background-image:url('{$mpe_customer_bg}');" data-bg="{$mpe_customer_bg}"{/if}></div>
            <button type="button" class="mpe-cbg-delete" id="mpe-cbg-delete">✕ Supprimer mon fond</button>
          </div>
          <div class="mpe-cbg-loading" id="mpe-cbg-loading" style="display:none;">Envoi en cours...</div>
          <div class="mpe-cbg-error" id="mpe-cbg-error" style="display:none;"></div>
        </div>
      </div>
    </div>

    <div class="mpe-item">
      <button type="button" class="mpe-head" data-target="mpe-images">
        <span>Images</span>
        <span class="mpe-arrow">+</span>
      </button>
      <div class="mpe-body" id="mpe-images">
        <p class="mpe-hint">Ajoutez jusqu'à 3 images sur votre tapis :</p>
        <label class="mpe-upload" id="mpe-img-upload-label">
          <input type="file" accept="image/*" id="mpe-img-input" />
          <span>+ Ajouter une image</span>
        </label>
        <p class="mpe-hint" id="mpe-img-counter">0 / 3 images</p>
      </div>
    </div>

    <div class="mpe-item">
      <button type="button" class="mpe-head" data-target="mpe-texte">
        <span>Texte</span>
        <span class="mpe-arrow">+</span>
      </button>
      <div class="mpe-body" id="mpe-texte">
        <p class="mpe-hint">Tapez votre texte :</p>
        <input type="text" class="mpe-text-input" id="mpe-text-input" placeholder="Votre texte ici..." />
        <div class="mpe-text-controls">
          <label>Police
            <select id="mpe-text-font">
              {if isset($mpe_default_fonts)}
                {foreach from=$mpe_default_fonts item=df}
                  <option value="{$df}" style="font-family:'{$df}', sans-serif;">{$df}</option>
                {/foreach}
              {/if}
              {if isset($mpe_fonts) && $mpe_fonts|count > 0}
                {foreach from=$mpe_fonts item=f}
                  <option value="{$f.family}" style="font-family:'{$f.family}', sans-serif;">{$f.family}</option>
                {/foreach}
              {/if}
            </select>
          </label>
          <label>Taille
            <input type="number" id="mpe-text-size" value="32" min="10" max="120" />
          </label>
          <label>Couleur
            <input type="color" id="mpe-text-color" value="#000000" />
          </label>
          <label>Style
            <div class="mpe-style-btns">
              <button type="button" class="mpe-style-btn" id="mpe-text-bold" title="Gras"><b>B</b></button>
              <button type="button" class="mpe-style-btn" id="mpe-text-italic" title="Italique"><i>I</i></button>
            </div>
          </label>
        </div>
        <button type="button" class="mpe-upload" id="mpe-text-add">+ Ajouter le texte</button>
      </div>
    </div>

  </div>
  </div>
</div>

{if isset($mpe_google_url) && $mpe_google_url}
<link href="{$mpe_google_url}" rel="stylesheet">
{/if}
{if isset($mpe_fonts) && $mpe_fonts|count > 0}
<style>
{foreach from=$mpe_fonts item=f}
@font-face { font-family: "{$f.family}"; src: url("{$mpe_font_url}{$f.file}") format("{if $f.ext == 'ttf'}truetype{elseif $f.ext == 'otf'}opentype{else}{$f.ext}{/if}"); font-display: swap; }
{/foreach}
</style>
{/if}
<script>
// Détection d'extensions navigateur interférant
(function(){
  var shown = false;
  function showWarn(){
    if (shown) return;
    shown = true;
    var m = document.getElementById('mpe-ext-warning');
    if (m) m.style.display = 'flex';
  }
  window.addEventListener('error', function(e){
    var src = (e.filename || '') + ' ' + (e.message || '');
    if (/chrome-extension|moz-extension|bitwarden|autofill|lastpass|1password|dashlane/i.test(src)) {
      showWarn();
    }
  }, true);
  // Détection DOM Bitwarden/LastPass/1Password
  setTimeout(function(){
    var markers = ['bitwarden-notification-bar-iframe','__lpform_','__1PasswordExtension','dashlane-com'];
    for (var i=0;i<markers.length;i++){
      if (document.getElementById(markers[i]) || document.querySelector('[id*="'+markers[i]+'"]')) { showWarn(); return; }
    }
  }, 1500);
  document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('mpe-ext-close');
    if (btn) btn.addEventListener('click', function(){
      document.getElementById('mpe-ext-warning').style.display = 'none';
    });
  });
})();

function mpeInit() {
  // Accordion
  var heads = document.querySelectorAll('.mousepad-editor .mpe-head');
  heads.forEach(function(h) {
    h.addEventListener('click', function() {
      var item = h.parentElement;
      var open = item.classList.contains('mpe-open');
      document.querySelectorAll('.mousepad-editor .mpe-item').forEach(function(i) { i.classList.remove('mpe-open'); });
      if (!open) item.classList.add('mpe-open');
    });
  });

  // Canvas init
  var fabricReady = (typeof fabric !== 'undefined');
  if (!fabricReady) {
    console.warn('[mousepadeditor] Fabric.js non chargé — mode dégradé');
  }

  var RATIO = 220 / 180; // largeur / hauteur
  var canvasEl = document.getElementById('mpe-canvas');
  var wrap = document.querySelector('.mpe-canvas-wrap');
  var W = wrap.clientWidth;
  var H = Math.round(W / RATIO);
  canvasEl.width = W;
  canvasEl.height = H;

  var canvas = null;
  if (fabricReady) {
    canvas = new fabric.Canvas('mpe-canvas', {
      backgroundColor: '#f0f0f0',
      preserveObjectStacking: true
    });
    canvas.setDimensions({ width: W, height: H });
  }

  var bgImage = null;
  var bgZoom = 1;
  var imageCount = 0;
  var MAX_IMAGES = 3;

  function removeOldBg() {
    if (bgImage) {
      canvas.remove(bgImage);
      bgImage = null;
    }
    canvas.backgroundColor = '#f0f0f0';
  }

  function finishBgSetup(obj) {
    canvas.add(obj);
    canvas.sendToBack(obj);
    bgImage = obj;
    bgZoom = 1;
    document.getElementById('mpe-bg-zoom').value = 100;
    document.getElementById('mpe-bg-controls').style.display = 'block';
    canvas.discardActiveObject();
    canvas.renderAll();
  }

  function setBackground(value) {
    if (!fabricReady || !canvas) return;
    removeOldBg();

    // Couleur unie
    if (value && value.charAt(0) === '#') {
      var rect = new fabric.Rect({
        left: W / 2, top: H / 2,
        originX: 'center', originY: 'center',
        width: W, height: H,
        fill: value,
        selectable: true, evented: true,
        hasControls: false, hasBorders: false,
        lockRotation: true, lockScalingX: true, lockScalingY: true,
        lockMovementX: true, lockMovementY: true,
        hoverCursor: 'default',
        mpeIsBg: true
      });
      finishBgSetup(rect);
      return;
    }

    // Image URL
    fabric.Image.fromURL(value, function(img) {
      var scale = Math.max(W / img.width, H / img.height);
      img.set({
        originX: 'center', originY: 'center',
        left: W / 2, top: H / 2,
        scaleX: scale, scaleY: scale,
        selectable: true, evented: true,
        hasControls: false, hasBorders: false,
        lockRotation: true, lockScalingX: true, lockScalingY: true,
        lockMovementX: true, lockMovementY: true,
        hoverCursor: 'default',
        mpeIsBg: true
      });
      finishBgSetup(img);
    }, { crossOrigin: 'anonymous' });
  }

  // Empêche la sélection active du fond (pas de suppression via toolbar)
  // Le drag reste possible via evented=true mais getActiveObject ignore le bg
  function isBgObject(o) { return o && o.mpeIsBg === true; }

  // Click sur thumb fond
  document.querySelectorAll('.mpe-bg-thumb').forEach(function(t){ bindBgClick(t); });

  function bindBgClick(el) {
    el.addEventListener('click', function(e){
      e.stopPropagation();
      var url = el.dataset.bg;
      if (url) {
        setBackground(url);
        document.querySelectorAll('.mpe-bg-thumb').forEach(function(x){ x.classList.remove('mpe-active'); });
        el.classList.add('mpe-active');
      }
    });
  }

  // Zoom fond (désactivé pour les couleurs unies)
  document.getElementById('mpe-bg-zoom').addEventListener('input', function(e){
    if (!bgImage || bgImage.type === 'rect') return;
    var z = parseInt(e.target.value, 10) / 100;
    bgZoom = z;
    var baseScale = Math.max(W / bgImage.width, H / bgImage.height);
    bgImage.scaleX = baseScale * z;
    bgImage.scaleY = baseScale * z;
    if (z > 1) {
      bgImage.lockMovementX = false;
      bgImage.lockMovementY = false;
      bgImage.hoverCursor = 'move';
    } else {
      bgImage.lockMovementX = true;
      bgImage.lockMovementY = true;
      bgImage.hoverCursor = 'default';
      bgImage.left = W / 2;
      bgImage.top = H / 2;
    }
    bgImage.setCoords();
    canvas.requestRenderAll();
  });

  // Upload fond client (AJAX)
  var cdz = document.getElementById('mpe-cdz');
  var cfile = document.getElementById('mpe-cfile');
  var preview = document.getElementById('mpe-cbg-preview');
  var pimg = document.getElementById('mpe-cbg-img');
  var cdel = document.getElementById('mpe-cbg-delete');
  var loading = document.getElementById('mpe-cbg-loading');
  var errBox = document.getElementById('mpe-cbg-error');
  var cwrap = document.querySelector('.mpe-customer-upload');
  var uploadUrl = cwrap ? cwrap.dataset.uploadUrl : null;

  if (cdz && cfile && uploadUrl) {
    cdz.addEventListener('click', function(){ cfile.click(); });
    ['dragenter','dragover'].forEach(function(e){
      cdz.addEventListener(e, function(ev){ ev.preventDefault(); cdz.classList.add('mpe-cdz-over'); });
    });
    ['dragleave','drop'].forEach(function(e){
      cdz.addEventListener(e, function(ev){ ev.preventDefault(); cdz.classList.remove('mpe-cdz-over'); });
    });
    cdz.addEventListener('drop', function(ev){
      if (ev.dataTransfer.files.length) doUpload(ev.dataTransfer.files[0]);
    });
    cfile.addEventListener('change', function(){
      if (cfile.files.length) doUpload(cfile.files[0]);
    });
  }

  if (cdel && uploadUrl) {
    cdel.addEventListener('click', function(){
      var wasActive = pimg && pimg.classList.contains('mpe-active');
      var fd = new FormData();
      fd.append('action', 'delete');
      fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(d){
          if (d.success) {
            preview.style.display = 'none';
            cdz.style.display = '';
            cfile.value = '';
            pimg.style.backgroundImage = '';
            pimg.dataset.bg = '';
            pimg.classList.remove('mpe-active');
            // Retirer aussi le fond du canvas s'il correspondait
            if (wasActive && canvas && bgImage) {
              canvas.remove(bgImage);
              bgImage = null;
              canvas.backgroundColor = '#f0f0f0';
              document.getElementById('mpe-bg-controls').style.display = 'none';
              canvas.requestRenderAll();
            }
          }
        });
    });
  }

  function showError(msg){
    errBox.textContent = msg;
    errBox.style.display = 'block';
    setTimeout(function(){ errBox.style.display = 'none'; }, 5000);
  }

  function doUpload(file) {
    errBox.style.display = 'none';
    if (file.size > 10485760) { showError('Fichier trop volumineux (max 10 Mo)'); return; }
    loading.style.display = 'block';
    cdz.style.display = 'none';
    var fd = new FormData();
    fd.append('action', 'upload');
    fd.append('file', file);
    fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){
        loading.style.display = 'none';
        if (d.success) {
          pimg.style.backgroundImage = 'url(' + d.url + ')';
          pimg.dataset.bg = d.url;
          preview.style.display = 'block';
          bindBgClick(pimg);
          setBackground(d.url);
          document.querySelectorAll('.mpe-bg-thumb').forEach(function(x){ x.classList.remove('mpe-active'); });
          pimg.classList.add('mpe-active');
        } else {
          showError(d.error || 'Erreur inconnue');
          cdz.style.display = '';
        }
      })
      .catch(function(){
        loading.style.display = 'none';
        cdz.style.display = '';
        showError('Erreur réseau');
      });
  }

  // Bind si fond client déjà présent
  if (pimg && pimg.dataset.bg) {
    bindBgClick(pimg);
  }

  // Images uploadables sur le canvas
  var imgInput = document.getElementById('mpe-img-input');
  var imgCounter = document.getElementById('mpe-img-counter');
  var imgUploadLabel = document.getElementById('mpe-img-upload-label');

  imgInput.addEventListener('change', function(e){
    if (!fabricReady || !canvas) { alert('Éditeur non chargé.'); return; }
    if (imageCount >= MAX_IMAGES) { alert('Maximum 3 images.'); return; }
    var file = e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(ev) {
      fabric.Image.fromURL(ev.target.result, function(img){
        var maxDim = W / 3;
        var scale = Math.min(maxDim / img.width, maxDim / img.height);
        img.set({
          left: W / 2, top: H / 2,
          originX: 'center', originY: 'center',
          scaleX: scale, scaleY: scale,
          cornerColor: '#ee7a03', borderColor: '#ee7a03', cornerSize: 10, transparentCorners: false
        });
        canvas.add(img);
        canvas.setActiveObject(img);
        canvas.renderAll();
        imageCount++;
        updateImgCounter();
      });
    };
    reader.readAsDataURL(file);
    imgInput.value = '';
  });

  function updateImgCounter() {
    imgCounter.textContent = imageCount + ' / ' + MAX_IMAGES + ' images';
    if (imageCount >= MAX_IMAGES) {
      imgUploadLabel.style.opacity = '0.4';
      imgUploadLabel.style.pointerEvents = 'none';
    } else {
      imgUploadLabel.style.opacity = '';
      imgUploadLabel.style.pointerEvents = '';
    }
  }

  // Texte
  ['mpe-text-bold','mpe-text-italic'].forEach(function(id){
    document.getElementById(id).addEventListener('click', function(){
      this.classList.toggle('mpe-active-style');
    });
  });

  document.getElementById('mpe-text-add').addEventListener('click', function(){
    if (!fabricReady || !canvas) { alert('Éditeur non chargé. Vérifiez votre connexion.'); return; }
    var input = document.getElementById('mpe-text-input');
    var txt = input.value.trim();
    if (!txt) return;
    var font = document.getElementById('mpe-text-font').value;
    var size = parseInt(document.getElementById('mpe-text-size').value, 10) || 32;
    var color = document.getElementById('mpe-text-color').value;
    var bold = document.getElementById('mpe-text-bold').classList.contains('mpe-active-style');
    var italic = document.getElementById('mpe-text-italic').classList.contains('mpe-active-style');
    var t = new fabric.IText(txt, {
      left: W / 2, top: H / 2,
      originX: 'center', originY: 'center',
      fontFamily: font, fontSize: size, fill: color,
      fontWeight: bold ? 'bold' : 'normal',
      fontStyle: italic ? 'italic' : 'normal',
      cornerColor: '#ee7a03', borderColor: '#ee7a03', cornerSize: 10, transparentCorners: false
    });
    canvas.add(t);
    canvas.setActiveObject(t);
    canvas.renderAll();
    input.value = '';
  });

  // Suppression sélection
  document.getElementById('mpe-delete-selected').addEventListener('click', function(){
    if (!canvas) return;
    var obj = canvas.getActiveObject();
    if (!obj || isBgObject(obj)) return;
    var wasImage = obj.type === 'image';
    canvas.remove(obj);
    canvas.discardActiveObject();
    canvas.renderAll();
    if (wasImage) { imageCount = Math.max(0, imageCount - 1); updateImgCounter(); }
  });

  // Reset
  document.getElementById('mpe-reset').addEventListener('click', function(){
    if (!canvas) return;
    document.getElementById('mpe-confirm-modal').style.display = 'flex';
  });

  document.getElementById('mpe-confirm-cancel').addEventListener('click', function(){
    document.getElementById('mpe-confirm-modal').style.display = 'none';
  });

  document.getElementById('mpe-confirm-ok').addEventListener('click', function(){
    document.getElementById('mpe-confirm-modal').style.display = 'none';
    if (!canvas) return;
    canvas.clear();
    canvas.backgroundColor = '#f0f0f0';
    bgImage = null;
    imageCount = 0;
    updateImgCounter();
    document.getElementById('mpe-bg-controls').style.display = 'none';
    document.querySelectorAll('.mpe-bg-thumb').forEach(function(x){ x.classList.remove('mpe-active'); });
    canvas.requestRenderAll();
  });

  // Resize responsive
  window.addEventListener('resize', function(){
    if (!canvas) return;
    var newW = wrap.clientWidth;
    var newH = Math.round(newW / RATIO);
    var ratio = newW / W;
    canvas.setDimensions({ width: newW, height: newH });
    canvas.getObjects().forEach(function(o){
      o.scaleX *= ratio; o.scaleY *= ratio;
      o.left *= ratio; o.top *= ratio;
      o.setCoords();
    });
    if (canvas.backgroundImage) {
      var bg = canvas.backgroundImage;
      var s = Math.max(newW / bg.width, newH / bg.height) * bgZoom;
      bg.set({ left: newW / 2, top: newH / 2, scaleX: s, scaleY: s });
    }
    W = newW; H = newH;
    canvas.renderAll();
  });
}
(function waitFabric(tries){
  if (typeof fabric !== 'undefined') { mpeInit(); return; }
  if (tries > 50) { console.error('[mpe] Fabric.js failed to load'); mpeInit(); return; }
  setTimeout(function(){ waitFabric(tries+1); }, 100);
})(0);
</script>
