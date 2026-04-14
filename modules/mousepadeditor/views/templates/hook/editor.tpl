{literal}<style>@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap');.product-customization{display:none!important}</style>{/literal}
<div id="mpe-loader" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:99999;align-items:center;justify-content:center;flex-direction:column;padding:20px;">
  <div style="width:70px;height:70px;border:6px solid rgba(255,255,255,.25);border-top-color:#ee7a03;border-radius:50%;animation:mpe-spin 1s linear infinite;"></div>
  <div style="color:#fff;margin-top:18px;font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:1px;">Ajout au panier en cours...</div>
  {if isset($mpe_lsv_blocs) && $mpe_lsv_blocs|count > 0}
    <div style="margin-top:30px;text-align:center;max-width:400px;">
      <div style="color:#ee7a03;font-weight:700;font-size:16px;margin-bottom:8px;">Le saviez-vous ?</div>
      <div id="mpe-lsv-text" style="color:#fff;font-size:14px;line-height:1.5;opacity:1;transition:opacity .5s;">{$mpe_lsv_blocs[0].text|escape:'htmlall':'UTF-8'}</div>
    </div>
  {/if}
</div>
{literal}<style>
@keyframes mpe-spin { to { transform: rotate(360deg); } }
</style>{/literal}

<div id="mpe-confirm-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:99998;align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:8px;max-width:440px;width:100%;padding:28px;box-shadow:0 10px 40px rgba(0,0,0,.3);text-align:center;">
    <h3 style="color:#004774;margin:0 0 12px;font-size:22px;font-family:'Bebas Neue',sans-serif;letter-spacing:1px;">Tout effacer ?</h3>
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
  <h3 class="mpe-title" style="font-family:'Bebas Neue',sans-serif !important;font-size:32px !important;font-weight:400 !important;letter-spacing:1px;color:#004774 !important;text-align:center;margin:0;line-height:1;">Zone de personnalisation</h3>
  <p class="mpe-canvas-caption">Aperçu de votre tapis de souris personnalisable</p>

  <div class="mpe-layout">
  <div class="mpe-canvas-wrap">
    <canvas id="mpe-canvas"></canvas>
    <div class="mpe-canvas-toolbar">
      <button type="button" class="mpe-tool-btn" id="mpe-delete-selected" title="Supprimer l'élément sélectionné">🗑 Supprimer la sélection</button>
      <button type="button" class="mpe-tool-btn" id="mpe-reset" title="Effacer toute la création">↺ Tout effacer</button>
    </div>
  </div>

  <div class="mpe-accordion">
    <div id="mpe-cart-zone"></div>

    <div class="mpe-item">
      <button type="button" class="mpe-head" data-target="mpe-fonds">
        <span style="font-family:'Bebas Neue',sans-serif !important;font-size:20px;font-weight:400;letter-spacing:1px;">Fonds</span>
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
        <span style="font-family:'Bebas Neue',sans-serif !important;font-size:20px;font-weight:400;letter-spacing:1px;">Images</span>
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
        <span style="font-family:'Bebas Neue',sans-serif !important;font-size:20px;font-weight:400;letter-spacing:1px;">Textes</span>
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
window.mpeSerializeState = null;
window.mpeComposeHD = null;
window.MPE_COMPOSE_URL = '{$mpe_compose_url}';
window.MPE_UPLOADIMAGE_URL = '{$mpe_uploadimage_url}';
window.MPE_ATTACH_URL = '{$mpe_attach_url}';
window.MPE_PRODUCT_ID = {$mpe_product_id};
window.MPE_TEMPLATE_URL = {if isset($mpe_template) && $mpe_template}'{$mpe_template.url}'{else}null{/if};
window.MPE_TEMPLATE_W = {if isset($mpe_template) && $mpe_template}{$mpe_template.width}{else}220{/if};
window.MPE_TEMPLATE_H = {if isset($mpe_template) && $mpe_template}{$mpe_template.height}{else}180{/if};
window.MPE_LSV_BLOCS = {if isset($mpe_lsv_blocs) && $mpe_lsv_blocs|count > 0}{$mpe_lsv_blocs|json_encode nofilter}{else}[]{/if};
{literal}
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

  var TEMPLATE_URL = window.MPE_TEMPLATE_URL;
  var TEMPLATE_W = window.MPE_TEMPLATE_W;
  var TEMPLATE_H = window.MPE_TEMPLATE_H;
  var RATIO = TEMPLATE_W / TEMPLATE_H;
  var canvasEl = document.getElementById('mpe-canvas');
  var wrap = document.querySelector('.mpe-canvas-wrap');
  var W = wrap.clientWidth;
  var H = Math.round(W / RATIO);
  canvasEl.width = W;
  canvasEl.height = H;

  var canvas = null;
  if (fabricReady) {
    // Fix warning Chrome : Fabric utilise 'alphabetical' (invalide) au lieu de 'alphabetic'
    try {
      var _proto = CanvasRenderingContext2D.prototype;
      var _desc = Object.getOwnPropertyDescriptor(_proto, 'textBaseline');
      if (_desc && _desc.set) {
        Object.defineProperty(_proto, 'textBaseline', {
          set: function(v){ _desc.set.call(this, v === 'alphabetical' ? 'alphabetic' : v); },
          get: _desc.get, configurable: true
        });
      }
    } catch(e) {}
    if (fabric.Text && fabric.Text.prototype) fabric.Text.prototype.textBaseline = 'alphabetic';
    if (fabric.IText && fabric.IText.prototype) fabric.IText.prototype.textBaseline = 'alphabetic';
    if (fabric.Textbox && fabric.Textbox.prototype) fabric.Textbox.prototype.textBaseline = 'alphabetic';
    canvas = new fabric.Canvas('mpe-canvas', {
      backgroundColor: '#f0f0f0',
      preserveObjectStacking: true
    });
    canvas.setDimensions({ width: W, height: H });
    loadTemplateOverlay();
  }

  var bgImage = null;
  var bgZoom = 1;
  var bgValue = null;
  var imageCount = 0;
  var MAX_IMAGES = 3;
  var templateOverlay = null;
  var STORAGE_KEY = 'mpe_state_' + window.MPE_PRODUCT_ID;
  var restoring = true; // bloque les saves jusqu'à la fin de restoreState
  var restoreDone = false;

  function saveState() {
    if (restoring || !restoreDone || !canvas) return;
    try {
      var images = [];
      var texts = [];
      canvas.getObjects().forEach(function(o){
        if (o.mpeIsBg || o === templateOverlay || o.mpeIsTemplate) return;
        if (o.type === 'image') {
          images.push({
            src: o.getSrc ? o.getSrc() : o._element.src,
            leftR: o.left / W, topR: o.top / H,
            scaleXR: o.scaleX / W, scaleYR: o.scaleY / W,
            angle: o.angle || 0
          });
        } else if (o.type === 'i-text' || o.type === 'text' || o.type === 'textbox') {
          texts.push({
            text: o.text,
            fontFamily: o.fontFamily, fontSizeR: o.fontSize / W, fill: o.fill,
            fontWeight: o.fontWeight, fontStyle: o.fontStyle,
            leftR: o.left / W, topR: o.top / H, angle: o.angle || 0,
            scaleXR: o.scaleX / W, scaleYR: o.scaleY / W
          });
        }
      });
      var bg = null;
      if (bgValue) {
        bg = { value: bgValue, zoom: bgZoom };
        if (bgImage && bgImage.type !== 'rect') {
          bg.leftR = bgImage.left / W;
          bg.topR = bgImage.top / H;
        }
      }
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ v: 2, bg: bg, images: images, texts: texts }));
    } catch(e) {}
  }

  function restoreState() {
    var raw;
    try { raw = localStorage.getItem(STORAGE_KEY); } catch(e) { restoring = false; restoreDone = true; return; }
    if (!raw) { restoring = false; restoreDone = true; return; }
    var state;
    try { state = JSON.parse(raw); } catch(e) { restoring = false; restoreDone = true; return; }
    if (!state) { restoring = false; restoreDone = true; return; }

    var isV2 = state.v === 2;
    var pendingImages = (state.images || []).slice();
    var pendingTexts = (state.texts || []).slice();

    function loadImagesAndTexts() {
      function nextImg() {
        if (!pendingImages.length) { loadTexts(); return; }
        var d = pendingImages.shift();
        var iLeft = isV2 ? d.leftR * W : d.left;
        var iTop = isV2 ? d.topR * H : d.top;
        var iScaleX = isV2 ? d.scaleXR * W : d.scaleX;
        var iScaleY = isV2 ? d.scaleYR * W : d.scaleY;
        fabric.Image.fromURL(d.src, function(img){
          img.set({
            left: iLeft, top: iTop,
            originX: 'center', originY: 'center',
            scaleX: iScaleX, scaleY: iScaleY, angle: d.angle,
            cornerColor: '#ee7a03', borderColor: '#ee7a03', cornerSize: 10, transparentCorners: false
          });
          canvas.add(img);
          imageCount++;
          nextImg();
        });
      }
      function loadTexts() {
        pendingTexts.forEach(function(d){
          var tLeft = isV2 ? d.leftR * W : d.left;
          var tTop = isV2 ? d.topR * H : d.top;
          var tFontSize = isV2 ? d.fontSizeR * W : d.fontSize;
          var tScaleX = isV2 ? (d.scaleXR ? d.scaleXR * W : 1) : (d.scaleX || 1);
          var tScaleY = isV2 ? (d.scaleYR ? d.scaleYR * W : 1) : (d.scaleY || 1);
          var t = new fabric.IText(d.text, {
            left: tLeft, top: tTop,
            originX: 'center', originY: 'center',
            fontFamily: d.fontFamily, fontSize: tFontSize, fill: d.fill,
            fontWeight: d.fontWeight, fontStyle: d.fontStyle,
            scaleX: tScaleX, scaleY: tScaleY, angle: d.angle,
            cornerColor: '#ee7a03', borderColor: '#ee7a03', cornerSize: 10, transparentCorners: false
          });
          canvas.add(t);
        });
        if (typeof updateImgCounter === 'function') updateImgCounter();
        bringTemplateToFront();
        canvas.renderAll();
        restoring = false;
        restoreDone = true;
      }
      nextImg();
    }

    if (state.bg && state.bg.value) {
      setBackground(state.bg.value, function(obj){
        if (obj && obj.type !== 'rect' && state.bg.zoom) {
          bgZoom = state.bg.zoom;
          var baseScale = Math.max(W / obj.width, H / obj.height);
          obj.scaleX = baseScale * bgZoom;
          obj.scaleY = baseScale * bgZoom;
          if (isV2) {
            if (state.bg.leftR != null) obj.left = state.bg.leftR * W;
            if (state.bg.topR != null) obj.top = state.bg.topR * H;
          } else {
            if (state.bg.left != null) obj.left = state.bg.left;
            if (state.bg.top != null) obj.top = state.bg.top;
          }
          if (bgZoom > 1) {
            obj.lockMovementX = false;
            obj.lockMovementY = false;
            obj.hoverCursor = 'move';
          }
          obj.setCoords();
          var slider = document.getElementById('mpe-bg-zoom');
          if (slider) slider.value = Math.round(bgZoom * 100);
        }
        loadImagesAndTexts();
      });
    } else {
      loadImagesAndTexts();
    }
  }

  function loadTemplateOverlay() {
    if (!TEMPLATE_URL || !fabricReady || !canvas) return;
    fabric.Image.fromURL(TEMPLATE_URL, function(img){
      var scale = W / img.width;
      img.set({
        originX: 'center', originY: 'center',
        left: W / 2, top: H / 2,
        scaleX: scale, scaleY: scale,
        selectable: false, evented: false,
        hoverCursor: 'default',
        mpeIsTemplate: true
      });
      if (templateOverlay) { canvas.remove(templateOverlay); }
      templateOverlay = img;
      canvas.add(img);
      canvas.bringToFront(img);
      canvas.requestRenderAll();
    }, { crossOrigin: 'anonymous' });
  }

  function bringTemplateToFront() {
    if (templateOverlay && canvas) {
      canvas.bringToFront(templateOverlay);
    }
  }

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
    bringTemplateToFront();
    bgImage = obj;
    bgZoom = 1;
    document.getElementById('mpe-bg-zoom').value = 100;
    document.getElementById('mpe-bg-controls').style.display = 'block';
    canvas.discardActiveObject();
    canvas.renderAll();
    if (typeof saveState === 'function') saveState();
  }

  function setBackground(value, cb) {
    if (!fabricReady || !canvas) return;
    removeOldBg();
    bgValue = value;

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
      if (cb) cb(rect);
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
      if (cb) cb(img);
    }, { crossOrigin: 'anonymous' });
  }

  // Empêche la sélection active du fond (pas de suppression via toolbar)
  function isBgObject(o) { return o && o.mpeIsBg === true; }

  // Plaquage aimanté du fond contre les bords lors du drag
  if (fabricReady && canvas) {
    canvas.on('object:moving', function(e){
      var obj = e.target;
      if (!obj || !obj.mpeIsBg || obj.type === 'rect') return;
      var sw = obj.width * obj.scaleX;
      var sh = obj.height * obj.scaleY;
      var minLeft = W - sw / 2;
      var maxLeft = sw / 2;
      var minTop = H - sh / 2;
      var maxTop = sh / 2;
      if (obj.left > maxLeft) obj.left = maxLeft;
      if (obj.left < minLeft) obj.left = minLeft;
      if (obj.top > maxTop) obj.top = maxTop;
      if (obj.top < minTop) obj.top = minTop;
    });

    // Persistance localStorage
    canvas.on('object:added', saveState);
    canvas.on('object:removed', saveState);
    canvas.on('object:modified', saveState);
    canvas.on('mouse:up', saveState);
  }

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

  function clampBg() {
    if (!bgImage || bgImage.type === 'rect') return;
    var sw = bgImage.width * bgImage.scaleX;
    var sh = bgImage.height * bgImage.scaleY;
    var minLeft = W - sw / 2;
    var maxLeft = sw / 2;
    var minTop = H - sh / 2;
    var maxTop = sh / 2;
    if (bgImage.left > maxLeft) bgImage.left = maxLeft;
    if (bgImage.left < minLeft) bgImage.left = minLeft;
    if (bgImage.top > maxTop) bgImage.top = maxTop;
    if (bgImage.top < minTop) bgImage.top = minTop;
  }

  // Zoom fond (désactivé pour les couleurs unies)
  document.getElementById('mpe-bg-zoom').addEventListener('change', function(){ saveState(); });
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
    clampBg();
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
              bgValue = null;
              saveState();
              canvas.backgroundColor = '#f0f0f0';
              document.getElementById('mpe-bg-controls').style.display = 'none';
              canvas.requestRenderAll();
            }
          }
        });
    });
  }

  function scrollToCanvas(){
    var w = document.querySelector('.mpe-canvas-wrap');
    if (w) w.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
          scrollToCanvas();
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
    // Upload silencieux côté serveur (fire & forget) pour visualisation BO
    try {
      var ufd = new FormData();
      ufd.append('file', file);
      fetch(window.MPE_UPLOADIMAGE_URL, { method: 'POST', body: ufd, credentials: 'same-origin' });
    } catch(e) {}
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
        bringTemplateToFront();
        canvas.renderAll();
        imageCount++;
        updateImgCounter();
        scrollToCanvas();
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
    bringTemplateToFront();
    canvas.renderAll();
    input.value = '';
    scrollToCanvas();
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
    bgValue = null;
    templateOverlay = null;
    imageCount = 0;
    try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    updateImgCounter();
    document.getElementById('mpe-bg-controls').style.display = 'none';
    document.querySelectorAll('.mpe-bg-thumb').forEach(function(x){ x.classList.remove('mpe-active'); });
    loadTemplateOverlay();
    canvas.requestRenderAll();
  });

  // Sérialisation d'état pour recomposition serveur HD
  window.mpeSerializeState = function() {
    if (!canvas) return null;
    var state = {
      canvasW: W,
      canvasH: H,
      targetW: Math.round(TEMPLATE_W * 150 * 0.0393701),
      targetH: Math.round(TEMPLATE_H * 150 * 0.0393701),
      bg: null,
      images: [],
      texts: []
    };
    canvas.getObjects().forEach(function(o){
      if (o.mpeIsTemplate) return;
      if (o.mpeIsBg) {
        if (o.type === 'rect') {
          state.bg = { color: o.fill };
        } else if (o.type === 'image') {
          state.bg = {
            url: o._originalElement ? o._originalElement.src : (o.getSrc ? o.getSrc() : null),
            left: o.left, top: o.top,
            zoom: bgZoom
          };
        }
        return;
      }
      if (o.type === 'image') {
        state.images.push({
          url: o._originalElement ? o._originalElement.src : (o.getSrc ? o.getSrc() : null),
          left: o.left, top: o.top,
          scaleX: o.scaleX, scaleY: o.scaleY,
          angle: o.angle || 0
        });
      } else if (o.type === 'i-text' || o.type === 'text' || o.type === 'textbox') {
        state.texts.push({
          text: o.text,
          fontFamily: o.fontFamily,
          fontSize: o.fontSize,
          fill: o.fill,
          bold: o.fontWeight === 'bold' || o.fontWeight === 700,
          italic: o.fontStyle === 'italic',
          left: o.left, top: o.top,
          angle: o.angle || 0
        });
      }
    });
    return state;
  };

  // Appel serveur de recomposition HD avec loader
  window.mpeComposeHD = function(cb) {
    var state = window.mpeSerializeState();
    if (!state) { cb && cb({success:false, error:'État indisponible'}); return; }
    showLoader();
    fetch(window.MPE_COMPOSE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(state),
      credentials: 'same-origin'
    })
    .then(function(r){ return r.json(); })
    .then(function(d){ hideLoader(); cb && cb(d); })
    .catch(function(e){ hideLoader(); cb && cb({success:false, error:String(e)}); });
  };

  function showLoader() {
    var el = document.getElementById('mpe-loader');
    if (el) el.style.display = 'flex';
  }
  function hideLoader() {
    var el = document.getElementById('mpe-loader');
    if (el) el.style.display = 'none';
  }

  // Interception ajout panier : envoie state + low-res, HD en arrière-plan serveur
  (function interceptAddToCart(){
    var form = document.getElementById('add-to-cart-or-refresh');
    if (!form) return;
    var btn = form.querySelector('[data-button-action="add-to-cart"]') || form.querySelector('button[type="submit"]');
    if (!btn) return;
    var bypass = false;

    btn.addEventListener('click', function(e){
      if (bypass) return;
      var state = window.mpeSerializeState && window.mpeSerializeState();
      if (!state) return;
      var hasContent = state.bg || (state.images && state.images.length) || (state.texts && state.texts.length);
      if (!hasContent) return;

      e.preventDefault();
      e.stopImmediatePropagation();

      // Générer une vignette low-res depuis le canvas (avec overlay inclus visuellement)
      showLoader();
      var lowres = '';
      try { lowres = canvas.toDataURL('image/jpeg', 0.85); } catch(ex) {}

      var fd = new FormData();
      fd.append('id_product', window.MPE_PRODUCT_ID);
      fd.append('state_json', JSON.stringify(state));
      fd.append('lowres', lowres);
      fetch(window.MPE_ATTACH_URL, { method:'POST', body: fd, credentials:'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(d){
          hideLoader();
          if (!d.success) { alert('Erreur : ' + d.error); return; }
          var customField = document.getElementById('product_customization_id');
          if (customField) customField.value = d.id_customization;
          bypass = true;
          btn.click();
        })
        .catch(function(err){ hideLoader(); alert('Erreur réseau : ' + err); });
    }, true);
  })();

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
    if (templateOverlay) {
      var ts = newW / templateOverlay.width;
      templateOverlay.set({ left: newW / 2, top: newH / 2, scaleX: ts, scaleY: ts });
    }
    W = newW; H = newH;
    bringTemplateToFront();
    canvas.renderAll();
  });

  // Restauration état persisté
  if (fabricReady && canvas) {
    setTimeout(restoreState, 300);
  }
}
// Rotation "Le saviez-vous ?" dans le loader
(function(){
  var blocs = window.MPE_LSV_BLOCS || [];
  var el = document.getElementById('mpe-lsv-text');
  if (!el || blocs.length < 2) return;
  var idx = 0;
  setInterval(function(){
    el.style.opacity = '0';
    setTimeout(function(){
      idx = (idx + 1) % blocs.length;
      el.textContent = blocs[idx].text;
      el.style.opacity = '1';
    }, 500);
  }, 10000);
})();

(function waitFabric(tries){
  if (typeof fabric !== 'undefined') { mpeInit(); return; }
  if (tries > 50) { console.error('[mpe] Fabric.js failed to load'); mpeInit(); return; }
  setTimeout(function(){ waitFabric(tries+1); }, 100);
})(0);

// Déplacer quantité + ajout panier vers la zone éditeur, remplacer par bouton Personnaliser
(function(){
  var addToCart = document.querySelector('.product-add-to-cart');
  var cartZone = document.getElementById('mpe-cart-zone');
  if (!addToCart || !cartZone) return;

  // Créer un wrapper centré pour le bouton Personnaliser
  var btnWrap = document.createElement('div');
  btnWrap.style.cssText = 'text-align:center;margin-top:10px;';
  var customBtn = document.createElement('a');
  customBtn.href = '#mpe-cart-zone';
  customBtn.textContent = 'JE PERSONNALISE MON PRODUIT';
  customBtn.style.cssText = 'display:inline-block;background-color:#ee7a03;color:#fff;padding:10px 20px;font-weight:700;font-size:14px;text-decoration:none;text-align:center;cursor:pointer;';
  customBtn.addEventListener('click', function(e){
    e.preventDefault();
    var target = document.getElementById('mpe-cart-zone');
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
  btnWrap.appendChild(customBtn);
  addToCart.parentNode.insertBefore(btnWrap, addToCart);

  // Injecter du CSS ciblé pour forcer le layout dans la cart zone
  var cartStyle = document.createElement('style');
  cartStyle.textContent = '#mpe-cart-zone .product-add-to-cart { text-align:center !important; margin-bottom:15px; }'
    + '#mpe-cart-zone .product-add-to-cart > .control-label { display:none !important; }'
    + '#mpe-cart-zone .product-quantity { display:inline-flex !important; justify-content:center !important; align-items:center !important; gap:10px !important; float:none !important; width:auto !important; }'
    + '#mpe-cart-zone .product-quantity .qty { float:none !important; }'
    + '#mpe-cart-zone .product-quantity .qty input { text-align:center !important; -moz-appearance:textfield !important; }'
    + '#mpe-cart-zone .product-quantity .qty input::-webkit-outer-spin-button, #mpe-cart-zone .product-quantity .qty input::-webkit-inner-spin-button { -webkit-appearance:none !important; margin:0 !important; }'
    + '#mpe-cart-zone .product-quantity .add { float:none !important; }'
    + '#mpe-cart-zone .product-availability { display:block !important; text-align:center !important; }';
  document.head.appendChild(cartStyle);

  // Déplacer le vrai bloc dans la zone éditeur
  cartZone.appendChild(addToCart);

  // Texte explicatif
  var helpText = document.createElement('p');
  helpText.textContent = 'Cliquez sur "Ajouter au panier" une fois votre personnalisation terminée.';
  helpText.style.cssText = 'text-align:center;font-size:13px;color:#666;margin-top:8px;';
  cartZone.appendChild(helpText);
})();
{/literal}
</script>
