<div class="mousepad-editor">
  <h3 class="mpe-title">Zone de personnalisation</h3>

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
              <div class="mpe-thumb mpe-thumb-img" style="background-image:url('{$mpe_bg_url}{$bg}');" data-bg="{$mpe_bg_url}{$bg}"></div>
            {/foreach}
          {else}
            <p class="mpe-hint">Aucun fond disponible pour le moment.</p>
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
            <div class="mpe-cbg-img" id="mpe-cbg-img" {if $mpe_customer_bg}style="background-image:url('{$mpe_customer_bg}');" data-bg="{$mpe_customer_bg}"{/if}></div>
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
        <p class="mpe-hint">Uploadez vos images :</p>
        <label class="mpe-upload">
          <input type="file" accept="image/*" multiple class="mpe-file-input" />
          <span>+ Ajouter une image</span>
        </label>
        <div class="mpe-grid mpe-uploaded"></div>
      </div>
    </div>

    <div class="mpe-item">
      <button type="button" class="mpe-head" data-target="mpe-texte">
        <span>Texte</span>
        <span class="mpe-arrow">+</span>
      </button>
      <div class="mpe-body" id="mpe-texte">
        <p class="mpe-hint">Tapez votre texte :</p>
        <input type="text" class="mpe-text-input" placeholder="Votre texte ici..." />
      </div>
    </div>

  </div>
</div>

<script>
(function() {
  var heads = document.querySelectorAll('.mousepad-editor .mpe-head');
  heads.forEach(function(h) {
    h.addEventListener('click', function() {
      var item = h.parentElement;
      var open = item.classList.contains('mpe-open');
      document.querySelectorAll('.mousepad-editor .mpe-item').forEach(function(i) {
        i.classList.remove('mpe-open');
      });
      if (!open) item.classList.add('mpe-open');
    });
  });

  // Upload fond client
  var cdz = document.getElementById('mpe-cdz');
  var cfile = document.getElementById('mpe-cfile');
  var preview = document.getElementById('mpe-cbg-preview');
  var pimg = document.getElementById('mpe-cbg-img');
  var cdel = document.getElementById('mpe-cbg-delete');
  var loading = document.getElementById('mpe-cbg-loading');
  var errBox = document.getElementById('mpe-cbg-error');
  var wrap = document.querySelector('.mpe-customer-upload');
  var uploadUrl = wrap ? wrap.dataset.uploadUrl : null;

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

  var fileInput = document.querySelector('.mousepad-editor .mpe-file-input');
  var uploaded = document.querySelector('.mousepad-editor .mpe-uploaded');
  if (fileInput && uploaded) {
    fileInput.addEventListener('change', function(e) {
      Array.from(e.target.files).forEach(function(file) {
        var reader = new FileReader();
        reader.onload = function(ev) {
          var thumb = document.createElement('div');
          thumb.className = 'mpe-thumb mpe-thumb-img';
          thumb.style.backgroundImage = 'url(' + ev.target.result + ')';
          uploaded.appendChild(thumb);
        };
        reader.readAsDataURL(file);
      });
    });
  }
})();
</script>
