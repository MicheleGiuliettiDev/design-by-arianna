/**
 * Product Editor JavaScript
 * Handles image upload, manipulation, and preview for WooCommerce products
 * UPDATED: adds hi-res (1600x1600) + thumb (320x320) exports via offscreen canvases
 */

(function() {
  'use strict';

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProductEditor);
  } else {
    initProductEditor();
  }

  function initProductEditor() {
    const elements = {
      fileInput: document.getElementById('pe-file'),
      bodyBox: document.getElementById('pe-body'),
      canvas: document.getElementById('pe-canvas'),
      loadButton: document.getElementById('pe-load-border'),
      btnRotateLeft: document.getElementById('pe-rotate-left'),
      btnRotateRight: document.getElementById('pe-rotate-right'),
      btnZoomIn: document.getElementById('pe-zoom-in'),
      btnZoomOut: document.getElementById('pe-zoom-out'),
      btnReset: document.getElementById('pe-reset'),
      btnClear: document.getElementById('pe-clear'),

      // === Campi nascosti ===
      // Fuori dal form (UI buffer, SENZA name)
      hiddenDataUI: document.getElementById('pe-data-ui'),
      // Dentro al form (verrà postato; name="image_customization")
      hiddenDataForm: document.getElementById('pe-data'),

      statusMessage: document.getElementById('pe-status-message')
    };

    // Non bloccare l'init se manca il campo dentro il form:
    // possiamo comunque far funzionare l'editor e sincronizzare più tardi.
    if (!elements.canvas || !elements.fileInput || !elements.loadButton) {
      console.error('Photo Editor: Required DOM elements not found');
      return;
    }

    // Riferimento al form prodotto (per la sync prima del submit)
    const productForm = document.querySelector('form.cart');

    // Bridge di scrittura: aggiorna sempre entrambi i campi (UI + form)
    function writeCustomizationJSON(json) {
      if (elements.hiddenDataUI) elements.hiddenDataUI.value = json;
      if (elements.hiddenDataForm) elements.hiddenDataForm.value = json;
    }

    // Aggancia la sync bidirezionale se i campi esistono
    function wireHiddenSync() {
      // Se il tema ha stampato il form dopo, riprova a cercare i campi
      if (!elements.hiddenDataForm) {
        elements.hiddenDataForm = document.getElementById('pe-data');
      }
      if (!elements.hiddenDataUI) {
        elements.hiddenDataUI = document.getElementById('pe-data-ui');
      }

      // Se qualcuno scrive direttamente su #pe-data (vecchio codice), riflettiamo su UI
      if (elements.hiddenDataForm) {
        elements.hiddenDataForm.addEventListener('input', function() {
          if (elements.hiddenDataUI && elements.hiddenDataUI.value !== elements.hiddenDataForm.value) {
            elements.hiddenDataUI.value = elements.hiddenDataForm.value;
          }
        });
      }
      // Se qualcuno scrive su #pe-data-ui, riflettiamo su #pe-data
      if (elements.hiddenDataUI) {
        elements.hiddenDataUI.addEventListener('input', function() {
          if (elements.hiddenDataForm && elements.hiddenDataForm.value !== elements.hiddenDataUI.value) {
            elements.hiddenDataForm.value = elements.hiddenDataUI.value;
          }
        });
      }

      // Safety: prima del submit/click assicuriamo che il campo nel form abbia l'ultimo JSON
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
    // wire subito e anche al DOMContentLoaded (nel caso alcuni temi ritardino la stampa del form)
    wireHiddenSync();
    document.addEventListener('DOMContentLoaded', wireHiddenSync);

    const ctx = elements.canvas.getContext('2d');

    // State for user image
    const state = {
      img: null,
      imgNaturalWidth: 0,
      imgNaturalHeight: 0,
      rotation: 0,
      scale: 1,
      posX: 0,
      posY: 0,
      isDragging: false,
      dragStartX: 0,
      dragStartY: 0,
      imageLoaded: false
    };

    // Config constants
    const CONFIG = {
      ROTATE_STEP: 90,
      ZOOM_STEP: 0.2,
      ZOOM_MIN: 0.1,
      ZOOM_MAX: 5,
      FIT_PADDING: 0.8,

      // NEW: export sizes
      EXPORT_FULL: 1600,   // hi-res edge (set to taste)
      EXPORT_THUMB: 320    // small thumb edge for fast pages
    };

    // Localized strings (fallback)
    const STRINGS = (typeof peVars !== 'undefined' && peVars.strings) ? peVars.strings : {
      imageLoaded: 'Image loaded successfully!',
      imageCleared: 'Image cleared.',
      invalidFile: 'Please select a valid image file.',
      loadError: 'Error loading image. Please try another file.'
    };

    // ✅ Preload static border image
    const borderImg = new Image();
    borderImg.src = (typeof peVars !== 'undefined' && peVars.borderImageUrl) ? peVars.borderImageUrl : '';
    borderImg.onload = () => {
      // console.log("Border image loaded:", borderImg.src);
      draw();
    };

    // UI helpers
    const ui = {
      showBody() {
        if (elements.bodyBox) elements.bodyBox.style.display = 'block';
      },
      hideBody() {
        if (elements.bodyBox) elements.bodyBox.style.display = 'none';
      },
      showLoadButton() {
        if (elements.loadButton) elements.loadButton.style.display = 'block';
      },
      hideLoadButton() {
        if (elements.loadButton) elements.loadButton.style.display = 'none';
      },
      updateControls() {
        const controls = [
          elements.btnRotateLeft,
          elements.btnRotateRight,
          elements.btnZoomIn,
          elements.btnZoomOut,
          elements.btnReset,
          elements.btnClear
        ];
        controls.forEach(btn => {
          if (btn) btn.disabled = !state.imageLoaded;
        });

        if (state.imageLoaded) {
          elements.canvas.classList.add('pe-canvas-draggable');
        } else {
          elements.canvas.classList.remove('pe-canvas-draggable');
        }
      },
      showStatus(message, type = 'success') {
        if (!elements.statusMessage) return;
        elements.statusMessage.textContent = message;
        elements.statusMessage.className = `pe-status-message pe-status-${type}`;
        elements.statusMessage.style.display = 'block';
        setTimeout(() => {
          elements.statusMessage.style.display = 'none';
        }, 3000);
      },
      clearCanvas() {
        ctx.clearRect(0, 0, elements.canvas.width, elements.canvas.height);
      },
      drawEmptyCanvas() {
        elements.canvas.classList.add('pe-empty-canvas');
        this.clearCanvas();
      }
    };

    // Degrees → radians
    function degreesToRadians(deg) {
      return deg * Math.PI / 180;
    }

    // === Canvas draw function ===
    function draw() {
      ui.clearCanvas();

      if (state.img && state.imageLoaded) {
        elements.canvas.classList.remove('pe-empty-canvas');

        ctx.save();
        const cx = elements.canvas.width / 2;
        const cy = elements.canvas.height / 2;
        ctx.translate(cx, cy);
        ctx.translate(state.posX, state.posY);
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

      // ✅ Always draw border image last (overlay)
      if (borderImg && borderImg.complete && borderImg.naturalWidth) {
        ctx.drawImage(borderImg, 0, 0, elements.canvas.width, elements.canvas.height);
      }
    }

    // Fit image inside canvas
    function fitImageToCanvas() {
      if (!state.img) return;

      const cw = elements.canvas.width;
      const ch = elements.canvas.height;
      const iw = state.imgNaturalWidth;
      const ih = state.imgNaturalHeight;

      const scaleX = (cw * CONFIG.FIT_PADDING) / iw;
      const scaleY = (ch * CONFIG.FIT_PADDING) / ih;

      state.scale = Math.min(scaleX, scaleY, 1);
      state.rotation = 0;
      state.posX = 0;
      state.posY = 0;

      draw();
    }

    // Clamp zoom scale
    function clampScale(value) {
      return Math.max(CONFIG.ZOOM_MIN, Math.min(CONFIG.ZOOM_MAX, value));
    }

    // === NEW: build composite at arbitrary size (hi-res or thumb) ===
    function exportComposite(targetW, targetH) {
      const off = document.createElement('canvas');
      off.width = targetW;
      off.height = targetH;
      const octx = off.getContext('2d');

      if (state.img && state.imageLoaded) {
        // map current transforms into new resolution
        const scaleFactorX = targetW / elements.canvas.width;
        const scaleFactorY = targetH / elements.canvas.height;

        octx.save();
        octx.translate(targetW / 2, targetH / 2);
        octx.translate(state.posX * scaleFactorX, state.posY * scaleFactorY);
        octx.rotate(degreesToRadians(state.rotation));
        octx.scale(state.scale, state.scale);

        octx.drawImage(
          state.img,
          -state.imgNaturalWidth / 2,
          -state.imgNaturalHeight / 2,
          state.imgNaturalWidth,
          state.imgNaturalHeight
        );

        octx.restore();
      }

      // Border overlay scaled to target
      if (borderImg && borderImg.complete && borderImg.naturalWidth) {
        octx.drawImage(borderImg, 0, 0, targetW, targetH);
      }

      try {
        return off.toDataURL('image/png');
      } catch (e) {
        console.warn('Photo Editor: Could not generate export data URL:', e);
        return '';
      }
    }

    // Load image from file
    function loadImage(file) {
      if (!file) {
        clearImage();
        return;
      }
      if (!file.type || !file.type.startsWith('image/')) {
        ui.showStatus(STRINGS.invalidFile, 'error');
        return;
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
        img.onerror = () => {
          ui.showStatus(STRINGS.loadError, 'error');
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }

    // Clear image
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
      // svuota entrambi i campi
      writeCustomizationJSON('');

      ui.showStatus(STRINGS.imageCleared, 'success');
    }

    // === UPDATED: Save editor state with hi-res + thumb ===
    function saveData() {
      if (!state.imageLoaded || !state.img) {
        // nessuna immagine → svuota entrambi i campi
        writeCustomizationJSON('');
        return;
      }

      // Offscreen exports at configured sizes
      const fullDataUrl = exportComposite(CONFIG.EXPORT_FULL, CONFIG.EXPORT_FULL);
      const thumbDataUrl = exportComposite(CONFIG.EXPORT_THUMB, CONFIG.EXPORT_THUMB);

      const data = {
        rotation: state.rotation,
        zoom: state.scale,
        positionX: state.posX,
        positionY: state.posY,
        canvasWidth: elements.canvas.width,
        canvasHeight: elements.canvas.height,
        imageWidth: state.imgNaturalWidth,
        imageHeight: state.imgNaturalHeight,
        hasImage: state.imageLoaded,

        // NEW fields
        finalImageFull: fullDataUrl,     // big 1600x1600
        finalImageThumb: thumbDataUrl,   // small 320x320

        // legacy field kept, points to thumb for fast page loads
        finalImage: thumbDataUrl,

        timestamp: Date.now()
      };

      // Scrivi JSON in entrambi i campi (UI + form)
      writeCustomizationJSON(JSON.stringify(data));
    }

    // === Controls ===
    function rotateLeft() {
      if (!state.imageLoaded) return;
      state.rotation -= CONFIG.ROTATE_STEP;
      draw();
      saveData();
    }
    function rotateRight() {
      if (!state.imageLoaded) return;
      state.rotation += CONFIG.ROTATE_STEP;
      draw();
      saveData();
    }
    function zoomIn() {
      if (!state.imageLoaded) return;
      state.scale = clampScale(state.scale * (1 + CONFIG.ZOOM_STEP));
      draw();
      saveData();
    }
    function zoomOut() {
      if (!state.imageLoaded) return;
      state.scale = clampScale(state.scale * (1 - CONFIG.ZOOM_STEP));
      draw();
      saveData();
    }
    function resetView() {
      if (!state.imageLoaded) return;
      state.rotation = 0;
      state.scale = 1;
      state.posX = 0;
      state.posY = 0;
      draw();
      saveData();
    }

    // === Dragging ===
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

    // === Mouse wheel zoom ===
    function onWheel(e) {
      if (!state.imageLoaded) return;
      e.preventDefault();
      const delta = Math.sign(e.deltaY) > 0 ? -CONFIG.ZOOM_STEP : CONFIG.ZOOM_STEP;
      state.scale = clampScale(state.scale * (1 + delta));
      draw();
      saveData();
    }

    // === Touch drag support ===
    function onTouchStart(e) {
      if (!state.imageLoaded) return;
      e.preventDefault();
      const touch = e.touches[0];
      elements.canvas.dispatchEvent(new MouseEvent('mousedown', {
        clientX: touch.clientX,
        clientY: touch.clientY
      }));
    }
    function onTouchMove(e) {
      if (!state.imageLoaded) return;
      e.preventDefault();
      const touch = e.touches[0];
      elements.canvas.dispatchEvent(new MouseEvent('mousemove', {
        clientX: touch.clientX,
        clientY: touch.clientY
      }));
    }
    function onTouchEnd(e) {
      e.preventDefault();
      elements.canvas.dispatchEvent(new MouseEvent('mouseup', {}));
    }

    // === Bind Events ===
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

    // Init
    ui.drawEmptyCanvas();
    ui.showBody();
    ui.showLoadButton();
    ui.updateControls();

    // API opzionale, nel caso tu voglia richiamarla da altri script
    window.PE_writeCustomization = function(payload) {
      const json = (typeof payload === 'string') ? payload : JSON.stringify(payload || {});
      writeCustomizationJSON(json);
    };
    window.PE_buildAndWriteFromCanvas = function() {
      if (!state.imageLoaded) { writeCustomizationJSON(''); return; }
      try {
        const payload = {
          rotation: state.rotation,
          zoom: state.scale,
          positionX: state.posX,
          positionY: state.posY,
          canvasWidth: elements.canvas.width,
          canvasHeight: elements.canvas.height,
          imageWidth: state.imgNaturalWidth,
          imageHeight: state.imgNaturalHeight,
          hasImage: state.imageLoaded,
          // NEW: both sizes
          finalImageFull: exportComposite(CONFIG.EXPORT_FULL, CONFIG.EXPORT_FULL),
          finalImageThumb: exportComposite(CONFIG.EXPORT_THUMB, CONFIG.EXPORT_THUMB),
          // legacy field remains, points to thumb for speed
          finalImage: exportComposite(CONFIG.EXPORT_THUMB, CONFIG.EXPORT_THUMB),
          timestamp: Date.now()
        };
        writeCustomizationJSON(JSON.stringify(payload));
      } catch (e) {
        // ignore
      }
    };
  }

})();

