/**
 * Product Editor JavaScript
 * Handles image upload, manipulation, and preview for WooCommerce products
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
      hiddenData: document.getElementById('pe-data'),
      statusMessage: document.getElementById('pe-status-message')
    };

    if (!elements.canvas || !elements.hiddenData || !elements.fileInput || !elements.loadButton) {
      console.error('Photo Editor: Required DOM elements not found');
      return;
    }

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
      FIT_PADDING: 0.8
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
    borderImg.src = peVars.borderImageUrl;
    borderImg.onload = () => {
      console.log("Border image loaded:", borderImg.src);
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
      if (borderImg.complete) {
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

    // Load image from file
    function loadImage(file) {
      if (!file) {
        clearImage();
        return;
      }
      if (!file.type.startsWith('image/')) {
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
      if (elements.hiddenData) elements.hiddenData.value = '';

      ui.showStatus(STRINGS.imageCleared, 'success');
    }

    // Save editor state
    function saveData() {
      if (!state.imageLoaded || !state.img || !elements.hiddenData) {
        if (elements.hiddenData) elements.hiddenData.value = '';
        return;
      }

      let dataUrl = '';
      try {
        dataUrl = elements.canvas.toDataURL('image/png');
      } catch (err) {
        console.warn('Photo Editor: Could not generate canvas data URL:', err);
      }

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
        finalImage: dataUrl,
        timestamp: Date.now()
      };

      elements.hiddenData.value = JSON.stringify(data);
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
    elements.btnRotateLeft.addEventListener('click', rotateLeft);
    elements.btnRotateRight.addEventListener('click', rotateRight);
    elements.btnZoomIn.addEventListener('click', zoomIn);
    elements.btnZoomOut.addEventListener('click', zoomOut);
    elements.btnReset.addEventListener('click', resetView);
    elements.btnClear.addEventListener('click', clearImage);

    elements.canvas.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
    elements.canvas.addEventListener('wheel', onWheel, { passive: false });

    elements.canvas.addEventListener('touchstart', onTouchStart);
    elements.canvas.addEventListener('touchmove', onTouchMove);
    elements.canvas.addEventListener('touchend', onTouchEnd);

    // Init
    ui.drawEmptyCanvas();
    ui.showBody();
    ui.showLoadButton();
    ui.updateControls();
  }

})();

