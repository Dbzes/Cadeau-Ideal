<div class="mousepad-editor">
  <h3 class="mpe-title">Personnalisez votre tapis de souris</h3>

  <div class="mpe-accordion">

    <div class="mpe-item">
      <button type="button" class="mpe-head" data-target="mpe-fonds">
        <span>Fonds</span>
        <span class="mpe-arrow">+</span>
      </button>
      <div class="mpe-body" id="mpe-fonds">
        <p class="mpe-hint">Choisissez un fond pour votre tapis :</p>
        <div class="mpe-grid">
          <div class="mpe-thumb"></div>
          <div class="mpe-thumb"></div>
          <div class="mpe-thumb"></div>
          <div class="mpe-thumb"></div>
          <div class="mpe-thumb"></div>
          <div class="mpe-thumb"></div>
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
