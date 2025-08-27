(function() {
  'use strict';

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProductEditor);
  } else {
    initProductEditor();
  }

  function initProductEditor() {
    const $ = (id) => document.getElementById(id);

    const elements = {
      fileInput: $('pe-file'),
      bodyBox: $('pe-body'),
      canvas: $('pe-canvas'),
      loadButton: $('pe-load-border'),
      btnRotateLeft: $('pe-rotate-left'),
      btnRotateRight: $('pe-rotate-right'),
      btnZoomIn: $('pe-zoom-in'),
      btnZoomOut: $('pe-zoom-out'),
      btnReset: $('pe-reset'),
      btnClear: $('pe-clear'),
      hiddenDataUI: $('pe-data-ui'),
      hiddenDataForm: $('pe-data'),
      statusMessage: $('pe-status-message')
    };

    if (!elements.canvas || !elements.fileInput || !elements.loadButton) {
      console.error('Photo Editor: Required DOM elements not found');
      return;
    }

    const productForm = document.querySelector('form.cart');

    function writeCustomizationJSON(json) {
      if (elements.hiddenDataUI) elements.hiddenDataUI.value = json;
      if (elements.hiddenDataForm) elements.hiddenDataForm.value = json;
    }
    function wireHiddenSync() {
      if (!elements.hiddenDataForm) elements.hiddenDataForm = $('pe-data');
      if (!elements.hiddenDataUI) elements.hiddenDataUI = $('pe-data-ui');

      if (elements.hiddenDataForm) {
        elements.hiddenDataForm.addEventListener('input', function() {
          if (elements.hiddenDataUI && elements.hiddenDataUI.value !== elements.hiddenDataForm.value) {
            elements.hiddenDataUI.value = elements.hiddenDataForm.value;
          }
        });
      }
      if (elements.hiddenDataUI) {
        elements.hiddenDataUI.addEventListener('input', function() {
          if (elements.hiddenDataForm && elements.hiddenDataForm.value !== elements.hiddenDataUI.value) {
            elements.hiddenDataForm.value = elements.hiddenDataUI.value;
          }
        });
      }
      if (productForm) {
        productForm.addEventListener('submit', function() {
          if (elements.hiddenDataUI && elements.hiddenDataForm) {
            elements.hiddenDataForm.value = elements.hiddenDataUI.value;
          }
        });
        const addBtn = productForm.querySelector('.single_add_to_cart_button');
        if (addBtn) {
          addBtn.addEventListener('click', function() {
            if (elements.hiddenDataUI && elements.hiddenDataForm) {
              elements.hiddenDataForm.value = elements.hiddenDataUI.value;
            }
          });
        }
      }
    }
    wireHiddenSync();
    document.addEventListener('DOMContentLoaded', wireHiddenSync);

    const ctx = elements.canvas.getContext('2d');

    const CONFIG = {
      ROTATE_STEP: 90,
      ZOOM_STEP: 0.2,
      ZOOM_MIN: 0.1,
      ZOOM_MAX: 5,
      FIT_PADDING: 0.8,
      EXPORT_SIZE: (typeof peVars !== 'undefined' && peVars.exportSize) ? peVars.exportSize : 2400,
      EXPORT_MIME: 'image/png',
      EXPORT_QUALITY: 0.92
    };

    const STRINGS = (typeof peVars !== 'undefined' && peVars.strings) ? peVars.strings : {
      imageLoaded: 'Image loaded successfully!',
      imageCleared: 'Image cleared.',
      invalidFile: 'Please select a valid image file.',
      loadError: 'Error loading image. Please try another file.'
    };

    // Device pixel ratio (cap for performance)
    function getDPR() {
      const d = window.devicePixelRatio || 1;
      // cap at 3x for sanity/perf
      return Math.max(1, Math.min(3, d));
    }

    // Editor state
    const state = {
      img: null,
      imgNaturalWidth: 0,
      imgNaturalHeight: 0,
      rotation: 0,     // degrees
      scale: 1,
      posX: 0,         // in CSS pixels
      posY: 0,         // in CSS pixels
      isDragging: false,
      dragStartX: 0,
      dragStartY: 0,
      imageLoaded: false
    };

    // Preload border (used both in preview + export)
    const borderImg = new Image();
    borderImg.crossOrigin = 'anonymous';
    borderImg.src = (typeof peVars !== 'undefined' && peVars.borderImageUrl) ? peVars.borderImageUrl : '';
    // No need to block; draw() checks if it’s ready.

    // UI helpers
    const ui = {
      showBody() { if (elements.bodyBox) elements.bodyBox.style.display = 'block'; },
      showLoadButton() { if (elements.loadButton) elements.loadButton.style.display = 'block'; },
      hideLoadButton() { if (elements.loadButton) elements.loadButton.style.display = 'none'; },
      updateControls() {
        const controls = [
          elements.btnRotateLeft,
          elements.btnRotateRight,
          elements.btnZoomIn,
          elements.btnZoomOut,
          elements.btnReset,
          elements.btnClear
        ];
        controls.forEach(btn => { if (btn) btn.disabled = !state.imageLoaded; });
        if (state.imageLoaded) elements.canvas.classList.add('pe-canvas-draggable');
        else elements.canvas.classList.remove('pe-canvas-draggable');
      },
      showStatus(message, type = 'success') {
        if (!elements.statusMessage) return;
        elements.statusMessage.textContent = message;
        elements.statusMessage.className = `pe-status-message pe-status-${type}`;
        elements.statusMessage.style.display = 'block';
        setTimeout(() => { elements.statusMessage.style.display = 'none'; }, 3000);
      },
      clearCanvas() {
        const { width: cssW, height: cssH } = elements.canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, cssW, cssH);
      },
      drawEmptyCanvas() {
        elements.canvas.classList.add('pe-empty-canvas');
        this.clearCanvas();
      }
    };

    // === Responsive canvas sizing with DPR ===
    function resizeCanvasToDisplaySize() {
      const dpr = getDPR();
      const rect = elements.canvas.getBoundingClientRect();
      const displayW = Math.max(1, Math.floor(rect.width * dpr));
      const displayH = Math.max(1, Math.floor(rect.height * dpr));

      if (elements.canvas.width !== displayW || elements.canvas.height !== displayH) {
        elements.canvas.width = displayW;
        elements.canvas.height = displayH;
        // map drawing units to CSS pixels
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        return true;
      }
      return false;
    }

    // Degrees → radians
    function degreesToRadians(deg) { return deg * Math.PI / 180; }

    // Fit image to current canvas CSS box
    function fitImageToCanvas() {
      if (!state.img) return;
      const rect = elements.canvas.getBoundingClientRect();
      const cw = rect.width, ch = rect.height;
      const iw = state.imgNaturalWidth, ih = state.imgNaturalHeight;

      const scaleX = (cw * CONFIG.FIT_PADDING) / iw;
      const scaleY = (ch * CONFIG.FIT_PADDING) / ih;

      state.scale = Math.min(scaleX, scaleY, 1);
      state.rotation = 0;
      state.posX = 0;
      state.posY = 0;

      draw();
    }

    // Clamp zoom
    function clampScale(value) { return Math.max(CONFIG.ZOOM_MIN, Math.min(CONFIG.ZOOM_MAX, value)); }

    // === Core redraw ===
    function draw() {
      // Ensure buffer matches display size (and DPR transform is set)
      resizeCanvasToDisplaySize();

      const rect = elements.canvas.getBoundingClientRect();
      const cssW = rect.width, cssH = rect.height;

      ui.clearCanvas();

      if (state.img && state.imageLoaded) {
        elements.canvas.classList.remove('pe-empty-canvas');

        ctx.save();
        ctx.translate(cssW / 2 + state.posX, cssH / 2 + state.posY);
        ctx.rotate(degreesToRadians(state.rotation));
        ctx.scale(state.scale, state.scale);

        ctx.drawImage(
          state.img,
          -state.imgNaturalWidth / 2,
          -state.imgNaturalHeight / 2,
          state.imgNaturalWidth,
          state.imgNaturalHeight
        );
        ctx.restore();
      } else {
        ui.drawEmptyCanvas();
      }

      // Draw border overlay in preview (if loaded)
      if (borderImg && borderImg.complete && borderImg.naturalWidth) {
        ctx.drawImage(borderImg, 0, 0, cssW, cssH);
      }
    }

    // === High-res composite (user image + border) for saving/export ===
    function buildCompositeDataURL(targetLongSide) {
      const rect = elements.canvas.getBoundingClientRect();
      const cssW = rect.width, cssH = rect.height;

      // Keep current aspect ratio; your editor uses a square, but this adapts.
      const aspect = cssW / Math.max(1, cssH);
      let expW = targetLongSide;
      let expH = Math.round(targetLongSide / Math.max(1e-6, aspect));
      if (aspect < 1) { // taller than wide
        expH = targetLongSide;
        expW = Math.round(targetLongSide * aspect);
      }

      const off = document.createElement('canvas');
      off.width = expW;
      off.height = expH;
      const octx = off.getContext('2d');

      // Map CSS pixel transforms → export pixels
      const unit = expW / cssW; // scale CSS px to export px along X (Y uses same because we keep aspect)
      octx.save();
      octx.translate(expW / 2 + state.posX * unit, expH / 2 + state.posY * (expH / cssH));
      octx.rotate(degreesToRadians(state.rotation));
      // Scale: first your user zoom (dimensionless), then CSS→export conversion
      const unitX = unit;
      const unitY = expH / cssH;
      octx.scale(state.scale * unitX, state.scale * unitY);

      // Draw user image centered
      octx.drawImage(
        state.img,
        -state.imgNaturalWidth / 2,
        -state.imgNaturalHeight / 2,
        state.imgNaturalWidth,
        state.imgNaturalHeight
      );
      octx.restore();

      // Border overlay stretched to full export canvas, if available
      if (borderImg && borderImg.complete && borderImg.naturalWidth) {
        octx.drawImage(borderImg, 0, 0, expW, expH);
      }

      try {
        return off.toDataURL(CONFIG.EXPORT_MIME, CONFIG.EXPORT_QUALITY);
      } catch (err) {
        console.warn('Photo Editor: export failed, falling back to preview canvas.', err);
        // Fallback to current preview canvas (lower res)
        const fallback = elements.canvas.toDataURL(CONFIG.EXPORT_MIME, CONFIG.EXPORT_QUALITY);
        return fallback;
      }
    }

    // Save editor state (posts the high-res composite as finalImage)
    function saveData() {
      if (!state.imageLoaded || !state.img) {
        writeCustomizationJSON('');
        return;
      }

      const dataUrl = buildCompositeDataURL(CONFIG.EXPORT_SIZE);

      const rect = elements.canvas.getBoundingClientRect();
      const data = {
        rotation: state.rotation,
        zoom: state.scale,
        positionX: state.posX,
        positionY: state.posY,
        canvasCssWidth: rect.width,
        canvasCssHeight: rect.height,
        imageWidth: state.imgNaturalWidth,
        imageHeight: state.imgNaturalHeight,
        hasImage: state.imageLoaded,
        finalImage: dataUrl, // <-- high-res composite (with border)
        timestamp: Date.now()
      };
      writeCustomizationJSON(JSON.stringify(data));
    }

    // === Load / Clear ===
    function loadImage(file) {
      if (!file) { clearImage(); return; }
      if (!file.type || !file.type.startsWith('image/')) {
        ui.showStatus(STRINGS.invalidFile, 'error'); return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
          state.img = img;
          state.imgNaturalWidth = img.naturalWidth;
          state.imgNaturalHeight = img.naturalHeight;
          state.imageLoaded = true;

          ui.showBody();
          ui.hideLoadButton();
          ui.updateControls();
          fitImageToCanvas();
          ui.showStatus(STRINGS.imageLoaded, 'success');
          saveData();
        };
        img.onerror = () => { ui.showStatus(STRINGS.loadError, 'error'); };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }

    function clearImage() {
      state.img = null;
      state.imageLoaded = false;
      state.rotation = 0;
      state.scale = 1;
      state.posX = 0;
      state.posY = 0;

      draw();
      ui.showLoadButton();
      ui.updateControls();
      if (elements.fileInput) elements.fileInput.value = '';
      writeCustomizationJSON('');
      ui.showStatus(STRINGS.imageCleared, 'success');
    }

    // === Controls ===
    function rotateLeft() { if (!state.imageLoaded) return; state.rotation -= CONFIG.ROTATE_STEP; draw(); saveData(); }
    function rotateRight() { if (!state.imageLoaded) return; state.rotation += CONFIG.ROTATE_STEP; draw(); saveData(); }
    function zoomIn() { if (!state.imageLoaded) return; state.scale = clampScale(state.scale * (1 + CONFIG.ZOOM_STEP)); draw(); saveData(); }
    function zoomOut() { if (!state.imageLoaded) return; state.scale = clampScale(state.scale * (1 - CONFIG.ZOOM_STEP)); draw(); saveData(); }
    function resetView() { if (!state.imageLoaded) return; state.rotation = 0; state.scale = 1; state.posX = 0; state.posY = 0; draw(); saveData(); }

    // === Pointer interactions ===
    function onMouseDown(e) {
      if (!state.imageLoaded) return;
      state.isDragging = true;
      state.dragStartX = e.clientX;
      state.dragStartY = e.clientY;
      elements.canvas.classList.add('pe-canvas-dragging');
      e.preventDefault();
    }
    function onMouseMove(e) {
      if (!state.isDragging || !state.imageLoaded) return;
      const dx = e.clientX - state.dragStartX;
      const dy = e.clientY - state.dragStartY;
      state.dragStartX = e.clientX;
      state.dragStartY = e.clientY;
      state.posX += dx;
      state.posY += dy;
      draw();
    }
    function onMouseUp() {
      if (!state.isDragging) return;
      state.isDragging = false;
      elements.canvas.classList.remove('pe-canvas-dragging');
      saveData();
    }

    function onWheel(e) {
      if (!state.imageLoaded) return;
      e.preventDefault();
      const delta = Math.sign(e.deltaY) > 0 ? -CONFIG.ZOOM_STEP : CONFIG.ZOOM_STEP;
      state.scale = clampScale(state.scale * (1 + delta));
      draw();
      saveData();
    }

    function onTouchStart(e) {
      if (!state.imageLoaded) return;
      e.preventDefault();
      const t = e.touches[0];
      onMouseDown({ clientX: t.clientX, clientY: t.clientY, preventDefault: () => { } });
    }
    function onTouchMove(e) {
      if (!state.imageLoaded) return;
      e.preventDefault();
      const t = e.touches[0];
      onMouseMove({ clientX: t.clientX, clientY: t.clientY });
    }
    function onTouchEnd(e) { e.preventDefault(); onMouseUp(); }

    // === Bind events ===
    elements.loadButton.addEventListener('click', () => elements.fileInput.click());
    elements.fileInput.addEventListener('change', (e) => loadImage(e.target.files ? e.target.files[0] : null));

    if (elements.btnRotateLeft) elements.btnRotateLeft.addEventListener('click', rotateLeft);
    if (elements.btnRotateRight) elements.btnRotateRight.addEventListener('click', rotateRight);
    if (elements.btnZoomIn) elements.btnZoomIn.addEventListener('click', zoomIn);
    if (elements.btnZoomOut) elements.btnZoomOut.addEventListener('click', zoomOut);
    if (elements.btnReset) elements.btnReset.addEventListener('click', resetView);
    if (elements.btnClear) elements.btnClear.addEventListener('click', clearImage);

    elements.canvas.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
    elements.canvas.addEventListener('wheel', onWheel, { passive: false });

    elements.canvas.addEventListener('touchstart', onTouchStart, { passive: false });
    elements.canvas.addEventListener('touchmove', onTouchMove, { passive: false });
    elements.canvas.addEventListener('touchend', onTouchEnd);

    // ResizeObserver – redraw on size changes
    const ro = new ResizeObserver(() => { draw(); /* no saveData here to avoid spam */ });
    ro.observe(elements.bodyBox || elements.canvas);

    // DPR changes (e.g., zoom level) → redraw
    window.addEventListener('resize', () => { draw(); });

    // Init
    ui.drawEmptyCanvas();
    ui.showBody();
    ui.showLoadButton();
    ui.updateControls();

    // Optional public API
    window.PE_writeCustomization = function(payload) {
      const json = (typeof payload === 'string') ? payload : JSON.stringify(payload || {});
      writeCustomizationJSON(json);
    };
    window.PE_buildAndWriteFromCanvas = function() {
      if (!state.imageLoaded) { writeCustomizationJSON(''); return; }
      const dataUrl = buildCompositeDataURL(CONFIG.EXPORT_SIZE);
      const rect = elements.canvas.getBoundingClientRect();
      const payload = {
        rotation: state.rotation,
        zoom: state.scale,
        positionX: state.posX,
        positionY: state.posY,
        canvasCssWidth: rect.width,
        canvasCssHeight: rect.height,
        imageWidth: state.imgNaturalWidth,
        imageHeight: state.imgNaturalHeight,
        hasImage: state.imageLoaded,
        finalImage: dataUrl,
        timestamp: Date.now()
      };
      writeCustomizationJSON(JSON.stringify(payload));
    };
  }
})();

