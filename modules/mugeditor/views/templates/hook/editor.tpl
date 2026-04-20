{literal}<style>@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap');.product-customization{display:none!important}#mue-preview-container{box-sizing:border-box}#mue-canvas-border{border:1px solid #ddd;display:inline-block;line-height:0}</style>{/literal}
<div id="mue-loader" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:99999;align-items:center;justify-content:center;flex-direction:column;padding:20px;">
  <div style="width:70px;height:70px;border:6px solid rgba(255,255,255,.25);border-top-color:#ee7a03;border-radius:50%;animation:mue-spin 1s linear infinite;"></div>
  <div style="color:#fff;margin-top:18px;font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:1px;">Ajout au panier en cours...</div>
  {if isset($mue_lsv_blocs) && $mue_lsv_blocs|count > 0}
    <div style="margin-top:30px;text-align:center;max-width:400px;">
      <div style="color:#ee7a03;font-weight:700;font-size:16px;margin-bottom:8px;">Le saviez-vous ?</div>
      <div id="mue-lsv-text" style="color:#fff;font-size:14px;line-height:1.5;opacity:1;transition:opacity .5s;">{$mue_lsv_blocs[0].text|escape:'htmlall':'UTF-8'}</div>
    </div>
  {/if}
</div>
{literal}<style>
@keyframes mue-spin { to { transform: rotate(360deg); } }
</style>{/literal}

<div id="mue-confirm-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:99998;align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:8px;max-width:440px;width:100%;padding:28px;box-shadow:0 10px 40px rgba(0,0,0,.3);text-align:center;">
    <h3 style="color:#004774;margin:0 0 12px;font-size:22px;font-family:'Bebas Neue',sans-serif;letter-spacing:1px;">Tout effacer ?</h3>
    <p style="color:#666;font-size:14px;line-height:1.5;margin:0 0 22px;">
      Votre création actuelle (fond, images et textes) sera intégralement supprimée.
      <br/>Cette action est <strong>irréversible</strong>.
    </p>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button type="button" id="mue-confirm-cancel" style="background:#fff;color:#666;border:1px solid #ddd;padding:12px 24px;border-radius:4px;font-weight:600;cursor:pointer;font-size:14px;">Annuler</button>
      <button type="button" id="mue-confirm-ok" style="background:#e74c3c;color:#fff;border:none;padding:12px 24px;border-radius:4px;font-weight:600;cursor:pointer;font-size:14px;">Oui, tout effacer</button>
    </div>
  </div>
</div>

<div id="mue-ext-warning" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:99999;align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:8px;max-width:480px;width:100%;padding:28px;box-shadow:0 10px 40px rgba(0,0,0,.3);text-align:center;">
    <div style="font-size:48px;margin-bottom:10px;">⚠️</div>
    <h3 style="color:#004774;margin:0 0 12px;font-size:20px;">Extension navigateur détectée</h3>
    <p style="color:#666;font-size:14px;line-height:1.5;margin:0 0 20px;">
      Une extension de votre navigateur (gestionnaire de mots de passe type Bitwarden, 1Password, LastPass…) semble interférer avec l'éditeur de personnalisation.
      <br/><br/>
      Pour une expérience optimale, nous vous recommandons de <strong>désactiver temporairement</strong> cette extension sur cette page, ou d'ouvrir le site en <strong>navigation privée</strong>.
    </p>
    <button type="button" id="mue-ext-close" style="background:#ee7a03;color:#fff;border:none;padding:12px 28px;border-radius:4px;font-weight:600;cursor:pointer;font-size:14px;">J'ai compris, continuer</button>
  </div>
</div>

<div class="mug-editor">
  <h3 class="mue-title" style="font-family:'Bebas Neue',sans-serif !important;font-size:32px !important;font-weight:400 !important;letter-spacing:1px;color:#004774 !important;text-align:center;margin:0;line-height:1;">Zone de personnalisation</h3>
  <p class="mue-canvas-caption">Aperçu de votre mug personnalisable</p>

  <div class="mue-layout">
  <div class="mue-canvas-wrap">
    {if isset($mue_render_base) && $mue_render_base}
    <div id="mue-preview-container" style="position:relative;overflow:hidden;margin-bottom:16px;border:1px solid #ddd;background:#fff;">
      <img id="mue-preview-base" src="{$mue_render_base.url}" style="display:block;width:100%;height:auto;" alt="Aperçu mug" />
      <canvas id="mue-preview-perso" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;opacity:1;border:1px solid #ddd;box-sizing:border-box;"></canvas>
      {if isset($mue_render_lighting) && $mue_render_lighting}
      <img id="mue-preview-lighting" src="{$mue_render_lighting.url}" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;" alt="" />
      {/if}
    </div>
    {/if}
    <div id="mue-canvas-border"><canvas id="mue-canvas"></canvas></div>
    <div class="mue-canvas-toolbar">
      <button type="button" class="mue-tool-btn" id="mue-delete-selected" title="Supprimer l'élément sélectionné">🗑 Supprimer la sélection</button>
      <button type="button" class="mue-tool-btn" id="mue-reset" title="Effacer toute la création">↺ Tout effacer</button>
    </div>
  </div>

  <div class="mue-accordion">
    <div id="mue-cart-zone"></div>

    <div class="mue-item">
      <button type="button" class="mue-head" data-target="mue-images">
        <span>Images</span>
        <span class="mue-arrow">+</span>
      </button>
      <div class="mue-body" id="mue-images">
        <p class="mue-hint">Ajoutez des images sur votre mug :</p>
        <label class="mue-upload" id="mue-img-upload-label">
          <input type="file" accept="image/*" id="mue-img-input" />
          <span>+ Ajouter une image</span>
        </label>
        <p class="mue-hint" id="mue-img-counter">0 / 50 images</p>
        <div id="mue-img-list" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;"></div>
      </div>
    </div>

    <div class="mue-item">
      <button type="button" class="mue-head" data-target="mue-texte">
        <span>Texte</span>
        <span class="mue-arrow">+</span>
      </button>
      <div class="mue-body" id="mue-texte">
        <p class="mue-hint">Tapez votre texte :</p>
        <input type="text" class="mue-text-input" id="mue-text-input" placeholder="Votre texte ici..." />
        <div class="mue-text-controls">
          <label>Police
            <select id="mue-text-font">
              {if isset($mue_default_fonts)}
                {foreach from=$mue_default_fonts item=df}
                  <option value="{$df}" style="font-family:'{$df}', sans-serif;">{$df}</option>
                {/foreach}
              {/if}
              {if isset($mue_fonts) && $mue_fonts|count > 0}
                {foreach from=$mue_fonts item=f}
                  <option value="{$f.family}" style="font-family:'{$f.family}', sans-serif;">{$f.family}</option>
                {/foreach}
              {/if}
            </select>
          </label>
          <label>Taille
            <input type="number" id="mue-text-size" value="32" min="10" max="120" />
          </label>
          <label>Couleur
            <input type="color" id="mue-text-color" value="#000000" />
          </label>
          <label>Style
            <div class="mue-style-btns">
              <button type="button" class="mue-style-btn" id="mue-text-bold" title="Gras"><b>B</b></button>
              <button type="button" class="mue-style-btn" id="mue-text-italic" title="Italique"><i>I</i></button>
            </div>
          </label>
        </div>
        <button type="button" class="mue-upload" id="mue-text-add">+ Ajouter le texte</button>
      </div>
    </div>

  </div>
  </div>
</div>

{if isset($mue_google_url) && $mue_google_url}
<link href="{$mue_google_url}" rel="stylesheet">
{/if}
{if isset($mue_fonts) && $mue_fonts|count > 0}
<style>
{foreach from=$mue_fonts item=f}
@font-face { font-family: "{$f.family}"; src: url("{$mue_font_url}{$f.file}") format("{if $f.ext == 'ttf'}truetype{elseif $f.ext == 'otf'}opentype{else}{$f.ext}{/if}"); font-display: swap; }
{/foreach}
</style>
{/if}
<script>
window.mueSerializeState = null;
window.mueComposeHD = null;
window.MUE_COMPOSE_URL = '{$mue_compose_url}';
window.MUE_UPLOADIMAGE_URL = '{$mue_uploadimage_url}';
window.MUE_ATTACH_URL = '{$mue_attach_url}';
window.MUE_PRODUCT_ID = {$mue_product_id};
window.MUE_TEMPLATE_URL = {if isset($mue_template) && $mue_template}'{$mue_template.url}'{else}null{/if};
window.MUE_TEMPLATE_W = {if isset($mue_template) && $mue_template}{$mue_template.width}{else}220{/if};
window.MUE_TEMPLATE_H = {if isset($mue_template) && $mue_template}{$mue_template.height}{else}180{/if};
window.MUE_LSV_BLOCS = {if isset($mue_lsv_blocs) && $mue_lsv_blocs|count > 0}{$mue_lsv_blocs|json_encode nofilter}{else}[]{/if};
{literal}
(function(){
  var shown = false;
  function showWarn(){
    if (shown) return;
    shown = true;
    var m = document.getElementById('mue-ext-warning');
    if (m) m.style.display = 'flex';
  }
  window.addEventListener('error', function(e){
    var src = (e.filename || '') + ' ' + (e.message || '');
    if (/chrome-extension|moz-extension|bitwarden|autofill|lastpass|1password|dashlane/i.test(src)) {
      showWarn();
    }
  }, true);
  setTimeout(function(){
    var markers = ['bitwarden-notification-bar-iframe','__lpform_','__1PasswordExtension','dashlane-com'];
    for (var i=0;i<markers.length;i++){
      if (document.getElementById(markers[i]) || document.querySelector('[id*="'+markers[i]+'"]')) { showWarn(); return; }
    }
  }, 1500);
  document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('mue-ext-close');
    if (btn) btn.addEventListener('click', function(){
      document.getElementById('mue-ext-warning').style.display = 'none';
    });
  });
})();

function mueInit() {
  // Accordion
  var heads = document.querySelectorAll('.mug-editor .mue-head');
  heads.forEach(function(h) {
    h.addEventListener('click', function() {
      var item = h.parentElement;
      var open = item.classList.contains('mue-open');
      document.querySelectorAll('.mug-editor .mue-item').forEach(function(i) { i.classList.remove('mue-open'); });
      if (!open) item.classList.add('mue-open');
    });
  });

  var fabricReady = (typeof fabric !== 'undefined');
  if (!fabricReady) {
    console.warn('[mugeditor] Fabric.js non chargé — mode dégradé');
  }

  var TEMPLATE_URL = window.MUE_TEMPLATE_URL;
  var TEMPLATE_W = window.MUE_TEMPLATE_W;
  var TEMPLATE_H = window.MUE_TEMPLATE_H;
  var RATIO = TEMPLATE_W / TEMPLATE_H;
  var canvasEl = document.getElementById('mue-canvas');
  var wrap = document.querySelector('.mue-canvas-wrap');
  // -2px pour compenser le border 1px de .canvas-container (gauche + droite)
  var W = wrap.clientWidth - 2;
  var H = Math.round(W / RATIO);
  canvasEl.width = W;
  canvasEl.height = H;

  var canvas = null;
  if (fabricReady) {
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
    canvas = new fabric.Canvas('mue-canvas', {
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
  var MAX_IMAGES = 50;
  var templateOverlay = null;
  var STORAGE_KEY = 'mue_state_' + window.MUE_PRODUCT_ID;
  var restoring = true;
  var restoreDone = false;

  function saveState() {
    if (restoring || !restoreDone || !canvas) return;
    try {
      var images = [];
      var texts = [];
      canvas.getObjects().forEach(function(o){
        if (o.mueIsBg || o === templateOverlay || o.mueIsTemplate) return;
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
          var slider = document.getElementById('mue-bg-zoom');
          if (slider) { slider.value = Math.round(bgZoom * 100); }
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
        mueIsTemplate: true
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
      // Positionner le template juste au-dessus du fond (index 0 ou 1),
      // mais en-dessous des objets utilisateur (images, textes)
      var objects = canvas.getObjects();
      var bgIdx = -1;
      for (var i = 0; i < objects.length; i++) {
        if (objects[i].mueIsBg) { bgIdx = i; break; }
      }
      var targetIdx = bgIdx >= 0 ? bgIdx + 1 : 0;
      canvas.moveTo(templateOverlay, targetIdx);
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
    var _bgZoomEl = document.getElementById('mue-bg-zoom');
    if (_bgZoomEl) _bgZoomEl.value = 100;
    var _bgCtrlEl = document.getElementById('mue-bg-controls');
    if (_bgCtrlEl) _bgCtrlEl.style.display = 'block';
    canvas.discardActiveObject();
    canvas.renderAll();
    if (typeof saveState === 'function') saveState();
  }

  function setBackground(value, cb) {
    if (!fabricReady || !canvas) return;
    removeOldBg();
    bgValue = value;

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
        mueIsBg: true
      });
      finishBgSetup(rect);
      if (cb) cb(rect);
      return;
    }

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
        mueIsBg: true
      });
      finishBgSetup(img);
      if (cb) cb(img);
    }, { crossOrigin: 'anonymous' });
  }

  function isBgObject(o) { return o && o.mueIsBg === true; }

  if (fabricReady && canvas) {
    canvas.on('object:moving', function(e){
      var obj = e.target;
      if (!obj || !obj.mueIsBg || obj.type === 'rect') return;
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

    canvas.on('object:added', saveState);
    canvas.on('object:removed', saveState);
    canvas.on('object:modified', saveState);
    canvas.on('mouse:up', saveState);

    // --- Preview cylindrique temps réel ---
    (function(){
      var previewPerso = document.getElementById('mue-preview-perso');
      var previewContainer = document.getElementById('mue-preview-container');
      if (!previewPerso || !previewContainer) return;
      var previewCtx = previewPerso.getContext('2d');
      var updating = false;

      // Dimensions de l'image de base (espace de référence)
      var BASE_W = 1461, BASE_H = 453;

      // Couverture du patron sur le cylindre (en degrés)
      var COVERAGE_DEG = 220;
      var HALF_COV = (COVERAGE_DEG / 2) * Math.PI / 180;
      var TOTAL_COV = COVERAGE_DEG * Math.PI / 180;

      // Zones imprimables pour chaque vue de mug (coordonnées dans l'espace 1461x453)
      // angle de vue de la caméra (en degrés), curve = amplitude incurvation verticale (px)
      var mugViews = [
        { x: 125, y: 100, w: 241, h: 269, angle: -35, curve: 35 },  // Mug gauche (¾, anse gauche)
        { x: 630, y: 90, w: 200, h: 275, angle: 0, curve: 8 },     // Mug centre (face)
        { x: 1092, y: 100, w: 241, h: 269, angle: 35, curve: 35 }   // Mug droit (¾, anse droite)
      ];

      function updatePreview() {
        if (updating) return;
        updating = true;
        requestAnimationFrame(function(){
          var containerW = previewContainer.offsetWidth;
          var baseImg = document.getElementById('mue-preview-base');
          if (!baseImg) { updating = false; return; }
          var imgRatio = baseImg.naturalHeight / baseImg.naturalWidth;
          var containerH = Math.round(containerW * imgRatio);
          previewPerso.width = containerW;
          previewPerso.height = containerH;
          previewCtx.clearRect(0, 0, containerW, containerH);

          // Facteur d'échelle entre l'espace base et l'espace écran
          var scaleX = containerW / BASE_W;
          var scaleY = containerH / BASE_H;

          // Exporter les objets du patron (sans fond ni template) sur fond transparent
          var origBg = canvas.backgroundColor;
          var origBgImg = canvas.backgroundImage;
          canvas.backgroundColor = 'transparent';
          canvas.backgroundImage = null;

          var tempCanvas = document.createElement('canvas');
          tempCanvas.width = canvas.width;
          tempCanvas.height = canvas.height;
          var tempCtx = tempCanvas.getContext('2d');
          var objects = canvas.getObjects();
          objects.forEach(function(obj){
            if (obj === templateOverlay || obj.mueIsTemplate) return;
            if (obj.mueIsBg) return;
            obj.render(tempCtx);
          });

          canvas.backgroundColor = origBg;
          canvas.backgroundImage = origBgImg;

          // Rendre les pixels blancs/quasi-blancs transparents (pour la prévisualisation uniquement)
          var imgData = tempCtx.getImageData(0, 0, tempCanvas.width, tempCanvas.height);
          var px = imgData.data;
          var whiteThreshold = 240; // tolérance : tout pixel RGB > 240 → transparent
          for (var p = 0; p < px.length; p += 4) {
            if (px[p] >= whiteThreshold && px[p+1] >= whiteThreshold && px[p+2] >= whiteThreshold) {
              px[p+3] = 0; // alpha = 0
            }
          }
          tempCtx.putImageData(imgData, 0, 0);

          // Projeter le patron sur chaque vue de mug avec distorsion cylindrique
          var patronW = tempCanvas.width;
          var patronH = tempCanvas.height;

          for (var v = 0; v < mugViews.length; v++) {
            var view = mugViews[v];
            var viewAngle = view.angle * Math.PI / 180;

            // Coordonnées écran de la zone imprimable
            var dx = Math.round(view.x * scaleX);
            var dy = Math.round(view.y * scaleY);
            var dw = Math.round(view.w * scaleX);
            var dh = Math.round(view.h * scaleY);

            // Amplitude de l'incurvation (en pixels écran)
            var curveAmp = (view.curve || 0) * scaleY;

            // Projection cylindrique colonne par colonne
            for (var col = 0; col < dw; col++) {
              // Position normalisée sur l'écran [-1, 1]
              var screenX = (col / (dw - 1)) * 2 - 1;

              // Limiter pour éviter NaN (arcsin limite à [-1,1])
              if (screenX < -0.98) screenX = -0.98;
              if (screenX > 0.98) screenX = 0.98;

              // Angle sur le cylindre
              var theta = viewAngle + Math.asin(screenX);

              // Position sur le patron (normalisée 0-1)
              var patronU = (theta + HALF_COV) / TOTAL_COV;

              // Hors du patron → pas de contenu
              if (patronU < 0 || patronU > 1) continue;

              // Colonne source dans le patron
              var srcCol = Math.round(patronU * (patronW - 1));
              if (srcCol < 0 || srcCol >= patronW) continue;

              // Incurvation "sur les rails" : le haut et le bas suivent
              // chacun la courbe du rebord du mug, comme un print collé sur le cylindre.
              var bendFactor = 1 - Math.cos(theta - viewAngle);

              // Rail HAUT : très subtil — le rebord supérieur du mug est quasi plat
              // vu de cet angle (la caméra est légèrement au-dessus)
              var topCurve  = -curveAmp * 0.12 * bendFactor;

              // Rail BAS : INCHANGÉ — les valeurs sont bonnes
              var botCurve  = -curveAmp * 0.4 * bendFactor;

              var colTop = dy + topCurve;
              var colH   = dh + (botCurve - topCurve);  // hauteur ajustée entre les 2 rails

              // Dessiner la colonne étirée entre les 2 courbes
              previewCtx.drawImage(
                tempCanvas,
                srcCol, 0, 1, patronH,
                dx + col, colTop, 1, colH
              );
            }
          }

          updating = false;
        });
      }

      canvas.on('after:render', updatePreview);
      setTimeout(updatePreview, 500);
    })();
  }

  document.querySelectorAll('.mue-bg-thumb').forEach(function(t){ bindBgClick(t); });

  function bindBgClick(el) {
    el.addEventListener('click', function(e){
      e.stopPropagation();
      var url = el.dataset.bg;
      if (url) {
        setBackground(url);
        document.querySelectorAll('.mue-bg-thumb').forEach(function(x){ x.classList.remove('mue-active'); });
        el.classList.add('mue-active');
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

  var _bgZoomSlider = document.getElementById('mue-bg-zoom');
  if (_bgZoomSlider) {
    _bgZoomSlider.addEventListener('change', function(){ saveState(); });
    _bgZoomSlider.addEventListener('input', function(e){
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
  }

  // Upload fond client (AJAX)
  var cdz = document.getElementById('mue-cdz');
  var cfile = document.getElementById('mue-cfile');
  var preview = document.getElementById('mue-cbg-preview');
  var pimg = document.getElementById('mue-cbg-img');
  var cdel = document.getElementById('mue-cbg-delete');
  var loading = document.getElementById('mue-cbg-loading');
  var errBox = document.getElementById('mue-cbg-error');
  var cwrap = document.querySelector('.mue-customer-upload');
  var uploadUrl = cwrap ? cwrap.dataset.uploadUrl : null;

  if (cdz && cfile && uploadUrl) {
    cdz.addEventListener('click', function(){ cfile.click(); });
    ['dragenter','dragover'].forEach(function(e){
      cdz.addEventListener(e, function(ev){ ev.preventDefault(); cdz.classList.add('mue-cdz-over'); });
    });
    ['dragleave','drop'].forEach(function(e){
      cdz.addEventListener(e, function(ev){ ev.preventDefault(); cdz.classList.remove('mue-cdz-over'); });
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
      var wasActive = pimg && pimg.classList.contains('mue-active');
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
            pimg.classList.remove('mue-active');
            if (wasActive && canvas && bgImage) {
              canvas.remove(bgImage);
              bgImage = null;
              bgValue = null;
              saveState();
              canvas.backgroundColor = '#f0f0f0';
              document.getElementById('mue-bg-controls').style.display = 'none';
              canvas.requestRenderAll();
            }
          }
        });
    });
  }

  function scrollToCanvas(){
    var w = document.querySelector('.mue-canvas-wrap');
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
          document.querySelectorAll('.mue-bg-thumb').forEach(function(x){ x.classList.remove('mue-active'); });
          pimg.classList.add('mue-active');
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

  if (pimg && pimg.dataset.bg) {
    bindBgClick(pimg);
  }

  // Images uploadables sur le canvas
  var imgInput = document.getElementById('mue-img-input');
  var imgCounter = document.getElementById('mue-img-counter');
  var imgUploadLabel = document.getElementById('mue-img-upload-label');

  var imgList = document.getElementById('mue-img-list');

  imgInput.addEventListener('change', function(e){
    if (!fabricReady || !canvas) { alert('Éditeur non chargé.'); return; }
    if (imageCount >= MAX_IMAGES) { alert('Maximum ' + MAX_IMAGES + ' images.'); return; }
    var file = e.target.files[0];
    if (!file) return;
    try {
      var ufd = new FormData();
      ufd.append('file', file);
      fetch(window.MUE_UPLOADIMAGE_URL, { method: 'POST', body: ufd, credentials: 'same-origin' });
    } catch(e) {}
    var dataUrl = null;
    var reader = new FileReader();
    reader.onload = function(ev) {
      dataUrl = ev.target.result;
      fabric.Image.fromURL(dataUrl, function(img){
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
        addImgThumb(img, dataUrl, file.name);
        // Garder la section Images ouverte
        var imgSection = document.querySelector('[data-target="mue-images"]');
        if (imgSection) imgSection.parentElement.classList.add('mue-open');
      });
    };
    reader.readAsDataURL(file);
    imgInput.value = '';
  });

  function addImgThumb(fabricObj, thumbUrl, fileName) {
    if (!imgList) return;
    var wrap = document.createElement('div');
    wrap.style.cssText = 'position:relative;width:70px;height:70px;border:1px solid #ddd;overflow:hidden;cursor:pointer;';
    var thumb = document.createElement('img');
    thumb.src = thumbUrl;
    thumb.alt = fileName || 'image';
    thumb.style.cssText = 'width:100%;height:100%;object-fit:cover;';
    var del = document.createElement('button');
    del.type = 'button';
    del.textContent = '✕';
    del.style.cssText = 'position:absolute;top:2px;right:2px;background:#e74c3c;color:#fff;border:none;width:20px;height:20px;font-size:11px;cursor:pointer;line-height:20px;padding:0;text-align:center;';
    del.title = 'Supprimer';
    // Clic sur la vignette = sélectionner l'objet sur le canvas
    wrap.addEventListener('click', function(e){
      if (e.target === del) return;
      canvas.setActiveObject(fabricObj);
      canvas.renderAll();
    });
    // Clic sur le bouton supprimer
    del.addEventListener('click', function(){
      canvas.remove(fabricObj);
      imgList.removeChild(wrap);
      imageCount = Math.max(0, imageCount - 1);
      updateImgCounter();
      saveState();
      canvas.renderAll();
    });
    wrap.appendChild(thumb);
    wrap.appendChild(del);
    imgList.appendChild(wrap);
  }

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
  ['mue-text-bold','mue-text-italic'].forEach(function(id){
    document.getElementById(id).addEventListener('click', function(){
      this.classList.toggle('mue-active-style');
    });
  });

  document.getElementById('mue-text-add').addEventListener('click', function(){
    if (!fabricReady || !canvas) { alert('Éditeur non chargé. Vérifiez votre connexion.'); return; }
    var input = document.getElementById('mue-text-input');
    var txt = input.value.trim();
    if (!txt) return;
    var font = document.getElementById('mue-text-font').value;
    var size = parseInt(document.getElementById('mue-text-size').value, 10) || 32;
    var color = document.getElementById('mue-text-color').value;
    var bold = document.getElementById('mue-text-bold').classList.contains('mue-active-style');
    var italic = document.getElementById('mue-text-italic').classList.contains('mue-active-style');
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
  document.getElementById('mue-delete-selected').addEventListener('click', function(){
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
  document.getElementById('mue-reset').addEventListener('click', function(){
    if (!canvas) return;
    document.getElementById('mue-confirm-modal').style.display = 'flex';
  });

  document.getElementById('mue-confirm-cancel').addEventListener('click', function(){
    document.getElementById('mue-confirm-modal').style.display = 'none';
  });

  document.getElementById('mue-confirm-ok').addEventListener('click', function(){
    document.getElementById('mue-confirm-modal').style.display = 'none';
    if (!canvas) return;
    canvas.clear();
    canvas.backgroundColor = '#f0f0f0';
    bgImage = null;
    bgValue = null;
    templateOverlay = null;
    imageCount = 0;
    try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    updateImgCounter();
    document.getElementById('mue-bg-controls').style.display = 'none';
    document.querySelectorAll('.mue-bg-thumb').forEach(function(x){ x.classList.remove('mue-active'); });
    loadTemplateOverlay();
    canvas.requestRenderAll();
  });

  // Sérialisation d'état pour recomposition serveur HD
  window.mueSerializeState = function() {
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
      if (o.mueIsTemplate) return;
      if (o.mueIsBg) {
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

  window.mueComposeHD = function(cb) {
    var state = window.mueSerializeState();
    if (!state) { cb && cb({success:false, error:'État indisponible'}); return; }
    showLoader();
    fetch(window.MUE_COMPOSE_URL, {
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
    var el = document.getElementById('mue-loader');
    if (el) el.style.display = 'flex';
  }
  function hideLoader() {
    var el = document.getElementById('mue-loader');
    if (el) el.style.display = 'none';
  }

  // Interception ajout panier
  (function interceptAddToCart(){
    var form = document.getElementById('add-to-cart-or-refresh');
    if (!form) return;
    var btn = form.querySelector('[data-button-action="add-to-cart"]') || form.querySelector('button[type="submit"]');
    if (!btn) return;
    var bypass = false;

    btn.addEventListener('click', function(e){
      if (bypass) return;
      var state = window.mueSerializeState && window.mueSerializeState();
      if (!state) return;
      var hasContent = state.bg || (state.images && state.images.length) || (state.texts && state.texts.length);
      if (!hasContent) return;

      e.preventDefault();
      e.stopImmediatePropagation();

      showLoader();
      var lowres = '';
      try { lowres = canvas.toDataURL('image/jpeg', 0.85); } catch(ex) {}

      var fd = new FormData();
      fd.append('id_product', window.MUE_PRODUCT_ID);
      fd.append('state_json', JSON.stringify(state));
      fd.append('lowres', lowres);
      fetch(window.MUE_ATTACH_URL, { method:'POST', body: fd, credentials:'same-origin' })
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
    var newW = wrap.clientWidth - 2;
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

  if (fabricReady && canvas) {
    setTimeout(restoreState, 300);
  }
}
// Rotation "Le saviez-vous ?"
(function(){
  var blocs = window.MUE_LSV_BLOCS || [];
  var el = document.getElementById('mue-lsv-text');
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
  if (typeof fabric !== 'undefined') { mueInit(); return; }
  if (tries > 50) { console.error('[mue] Fabric.js failed to load'); mueInit(); return; }
  setTimeout(function(){ waitFabric(tries+1); }, 100);
})(0);

// Déplacer quantité + ajout panier vers la zone éditeur, remplacer par bouton Personnaliser
(function(){
  var addToCart = document.querySelector('.product-add-to-cart');
  var cartZone = document.getElementById('mue-cart-zone');
  if (!addToCart || !cartZone) return;

  var customBtn = document.createElement('a');
  customBtn.href = '#mue-cart-zone';
  customBtn.textContent = 'JE PERSONNALISE MON PRODUIT';
  customBtn.style.cssText = 'display:inline-block;background-color:#ee7a03;color:#fff;padding:10px 20px;font-weight:700;font-size:14px;text-decoration:none;text-align:center;cursor:pointer;margin-top:10px;';
  customBtn.addEventListener('click', function(e){
    e.preventDefault();
    var target = document.getElementById('mue-cart-zone');
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
  addToCart.parentNode.insertBefore(customBtn, addToCart);

  cartZone.appendChild(addToCart);
  addToCart.style.display = '';
  addToCart.style.marginBottom = '15px';

  // Masquer le label "Quantité", garder l'input qty sans les boutons +/-
  var qtyLabel = addToCart.querySelector('.control-label');
  if (qtyLabel) qtyLabel.style.display = 'none';
  var qtyInput = addToCart.querySelector('#quantity_wanted');
  if (qtyInput) {
    qtyInput.setAttribute('type', 'number');
    qtyInput.style.cssText = 'width:60px;text-align:center;border:1px solid #ddd;padding:8px;font-size:14px;-moz-appearance:textfield;';
  }
  // Masquer les boutons +/- natifs Bootstrap/PS (TouchSpin)
  var touchSpinBtns = addToCart.querySelectorAll('.bootstrap-touchspin-up, .bootstrap-touchspin-down, .input-group-btn-vertical, .btn-touchspin');
  touchSpinBtns.forEach(function(b){ b.style.display = 'none'; });
  // Ré-essai après init TouchSpin (PS l'initialise en différé)
  setTimeout(function(){
    addToCart.querySelectorAll('.input-group-btn-vertical, .bootstrap-touchspin-up, .bootstrap-touchspin-down, .btn-touchspin')
      .forEach(function(b){ b.style.display = 'none'; });
  }, 1000);
})();
{/literal}
</script>
