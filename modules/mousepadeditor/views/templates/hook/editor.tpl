<div class="mousepad-editor">
  <h3 class="mpe-title">Zone de personnalisation</h3>

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
        <p class="mpe-hint">Choisissez un fond pour votre tapis :</p>
        <div class="mpe-grid">
          {if isset($mpe_backgrounds) && $mpe_backgrounds|count > 0}
            {foreach from=$mpe_backgrounds item=bg}
              <div class="mpe-thumb mpe-thumb-img mpe-bg-thumb" style="background-image:url('{$mpe_bg_url}{$bg}');" data-bg="{$mpe_bg_url}{$bg}"></div>
            {/foreach}
          {else}
            <p class="mpe-hint">Aucun fond catalogue disponible.</p>
          {/if}
        </div>

        <div class="mpe-bg-controls" id="mpe-bg-controls" style="display:none;">
          <label class="mpe-slider-label">Zoom du fond
            <input type="range" id="mpe-bg-zoom" min="100" max="300" value="100" />
          </label>
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
                  <option value="{$df}">{$df}</option>
                {/foreach}
              {/if}
              {if isset($mpe_fonts) && $mpe_fonts|count > 0}
                {foreach from=$mpe_fonts item=f}
                  <option value="{$f.family}">{$f.family}</option>
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

<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&family=Bebas+Neue&display=swap" rel="stylesheet">
{if isset($mpe_fonts) && $mpe_fonts|count > 0}
<style>
{foreach from=$mpe_fonts item=f}
@font-face { font-family: "{$f.family}"; src: url("{$mpe_font_url}{$f.file}") format("{if $f.ext == 'ttf'}truetype{elseif $f.ext == 'otf'}opentype{else}{$f.ext}{/if}"); font-display: swap; }
{/foreach}
</style>
{/if}
<script>
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

  function setBackground(url) {
    if (!fabricReady || !canvas) return;
    fabric.Image.fromURL(url, function(img) {
      var scale = Math.max(W / img.width, H / img.height);
      img.set({
        originX: 'center', originY: 'center',
        left: W / 2, top: H / 2,
        scaleX: scale, scaleY: scale,
        selectable: false, evented: false
      });
      canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));
      bgImage = img;
      bgZoom = 1;
      document.getElementById('mpe-bg-zoom').value = 100;
      document.getElementById('mpe-bg-controls').style.display = 'block';
    }, { crossOrigin: 'anonymous' });
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

  // Zoom fond
  document.getElementById('mpe-bg-zoom').addEventListener('input', function(e){
    if (!bgImage) return;
    var z = parseInt(e.target.value, 10) / 100;
    var baseScale = Math.max(W / bgImage.width, H / bgImage.height);
    bgImage.set({ scaleX: baseScale * z, scaleY: baseScale * z });
    canvas.renderAll();
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
    if (!obj) return;
    var wasImage = obj.type === 'image';
    canvas.remove(obj);
    canvas.discardActiveObject();
    canvas.renderAll();
    if (wasImage) { imageCount = Math.max(0, imageCount - 1); updateImgCounter(); }
  });

  // Reset
  document.getElementById('mpe-reset').addEventListener('click', function(){
    if (!canvas) return;
    if (!confirm('Tout réinitialiser ?')) return;
    canvas.clear();
    canvas.setBackgroundImage(null, canvas.renderAll.bind(canvas));
    canvas.backgroundColor = '#f0f0f0';
    bgImage = null;
    imageCount = 0;
    updateImgCounter();
    document.getElementById('mpe-bg-controls').style.display = 'none';
    document.querySelectorAll('.mpe-bg-thumb').forEach(function(x){ x.classList.remove('mpe-active'); });
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
