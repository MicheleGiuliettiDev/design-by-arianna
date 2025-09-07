/**
 * Product Editor JavaScript
 * Handles image upload, manipulation, and preview for WooCommerce products
 * UPDATED: DPR-aware + locked export math (uses last draw CSS size), utilities moved on top.
 *          Thumbnail is a screenshot of the visible canvas (exact match).
 *          Full image is re-rendered at high resolution (proportional transforms) for clear downloads.
 */
(function() {
  'use strict';

  // =========================
  // Utilities (top)
  // =========================
  function degreesToRadians(deg) {
    return deg * Math.PI / 180;
  }

  /**
   * Make canvas bitmap match its displayed size (CSS px * DPR) and
   * scale the context so drawing coords are in CSS pixels.
   * Returns current CSS width/height and DPR.
   */
  function resizeCanvasToDisplaySize(canvas, ctx) {
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();

    const cssW = Math.max(1, Math.round(rect.width));
    const cssH = Math.max(1, Math.round(rect.height));
    const needW = Math.round(cssW * dpr);
    const needH = Math.round(cssH * dpr);

    if (canvas.width !== needW || canvas.height !== needH) {
      canvas.width = needW;
      canvas.height = needH;
    }

    // Draw in CSS pixels
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    return { cssW, cssH, dpr };
  }

  // Clear the FULL pixel buffer regardless of current transform
  function clearFull(ctx, canvas) {
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.restore();
  }

  /**
   * Screenshot the *visible* canvas and resample to target size.
   * This guarantees pixel-identical composition (zoom/pos/rotation).
   */
  function snapshotFromScreenToSize(canvas, targetW, targetH) {
    const srcW = canvas.width;   // device pixels
    const srcH = canvas.height;

    const off = document.createElement('canvas');
    off.width = targetW;
    off.height = targetH;

    const octx = off.getContext('2d', { alpha: true });
    octx.imageSmoothingEnabled = true;
    octx.imageSmoothingQuality = 'high';

    octx.drawImage(canvas, 0, 0, srcW, srcH, 0, 0, targetW, targetH);

    try { return off.toDataURL('image/png'); }
    catch (e) { console.warn('Canvas snapshot failed:', e); return ''; }
  }

  /**
   * Compute a high-res target size for downloads.
   * Goal: as clear as possible without going absurdly huge.
   * - If the user zoomed OUT (scale < 1), we can safely render larger than the on-screen size
   *   up to approximately 1/scale to approach the source's native detail.
   * - If the user zoomed IN (scale > 1), we keep at least the current canvas CSS size (no downscale).
   * - Hard clamp within sane bounds to avoid memory issues.
   */
  function computeFullTargetSize(state, minEdge = 1600, maxEdge = 4096) {
    const cssW = Math.max(1, state.viewCssW || 0);
    const base = cssW || minEdge;

    const nonUpscaleFactor = 1 / Math.max(0.01, state.scale); // >= 1 when scale <= 1
    const candidate = Math.round(base * Math.max(1, nonUpscaleFactor));

    return Math.max(minEdge, Math.min(maxEdge, candidate));
  }

  /**
   * High-res re-render that mirrors the on-screen composition but at a larger size.
   * IMPORTANT: we scale BOTH the position and the effective scale by the same factor
   * (target/css size) so the composition is identical, just with more pixels.
   */
  function exportHighRes(state, borderImg, targetSize) {
    const cssW = Math.max(1, state.viewCssW || 1);
    const cssH = Math.max(1, state.viewCssH || 1);
    const fx = targetSize / cssW;
    const fy = targetSize / cssH; // typically square; still support non-uniform just in case

    const off = document.createElement('canvas');
    off.width = targetSize;
    off.height = targetSize;
    const octx = off.getContext('2d', { alpha: true });
    octx.imageSmoothingEnabled = true;
    octx.imageSmoothingQuality = 'high';

    if (state.img && state.imageLoaded) {
      octx.save();
      octx.translate(targetSize / 2, targetSize / 2);
      octx.translate(state.posX * fx, state.posY * fy);
      octx.rotate(degreesToRadians(state.rotation));
      // Scale up proportionally so the image-to-canvas ratio stays identical
      octx.scale(state.scale * fx, state.scale * fy);

      octx.drawImage(
        state.img,
        -state.imgNaturalWidth / 2,
        -state.imgNaturalHeight / 2,
        state.imgNaturalWidth,
        state.imgNaturalHeight
      );
      octx.restore();
    }

    if (borderImg && borderImg.complete && borderImg.naturalWidth) {
      octx.drawImage(borderImg, 0, 0, targetSize, targetSize);
    }

    try { return off.toDataURL('image/png'); }
    catch (e) { console.warn('High-res export failed:', e); return ''; }
  }

  /**
   * (Kept) Math-based exporter at arbitrary size, using last CSS viewport for ratios.
   * Not required for the new high-res path, but preserved to avoid removing logic.
   */
  function exportCompositeMath(state, borderImg, targetW, targetH) {
    const cssW = Math.max(1, state.viewCssW || 1);
    const cssH = Math.max(1, state.viewCssH || 1);
    const off = document.createElement('canvas');
    off.width = targetW;
    off.height = targetH;
    const octx = off.getContext('2d');

    const fx = targetW / cssW;
    const fy = targetH / cssH;

    if (state.img && state.imageLoaded) {
      octx.save();
      octx.translate(targetW / 2, targetH / 2);
      octx.translate(state.posX * fx, state.posY * fy);
      octx.rotate(degreesToRadians(state.rotation));
      octx.scale(state.scale, state.scale); // (kept as-is)
      octx.drawImage(
        state.img,
        -state.imgNaturalWidth / 2,
        -state.imgNaturalHeight / 2,
        state.imgNaturalWidth,
        state.imgNaturalHeight
      );
      octx.restore();
    }

    if (borderImg && borderImg.complete && borderImg.naturalWidth) {
      octx.drawImage(borderImg, 0, 0, targetW, targetH);
    }

    try { return off.toDataURL('image/png'); }
    catch (e) { console.warn('exportCompositeMath failed:', e); return ''; }
  }

  // =========================
  // Boot
  // =========================
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
      hiddenDataUI: document.getElementById('pe-data-ui'),
      hiddenDataForm: document.getElementById('pe-data'),
      statusMessage: document.getElementById('pe-status-message')
    };

    elements.canvas.style.touchAction = 'none';

    if (!elements.canvas || !elements.fileInput || !elements.loadButton) {
      console.error('Photo Editor: Required DOM elements not found');
      return;
    }

    const productForm = document.querySelector('form.cart');
    const ctx = elements.canvas.getContext('2d');

    // ===== State
    const state = {
      img: null,
      imgNaturalWidth: 0,
      imgNaturalHeight: 0,
      rotation: 0,
      scale: 1,
      posX: 0, // CSS px
      posY: 0, // CSS px
      isDragging: false,
      dragStartX: 0,
      dragStartY: 0,
      imageLoaded: false,

      // last draw viewport (CSS px) — used by export to keep 1:1
      viewCssW: 0,
      viewCssH: 0,
      viewDpr: 1
    };

    // ===== Config
    const CONFIG = {
      ROTATE_STEP: 90,
      ZOOM_STEP: 0.2,
      ZOOM_MIN: 0.1,
      ZOOM_MAX: 5,
      FIT_PADDING: 1.0,          // full-bleed by default
      EXPORT_FULL_MIN: 1600,      // minimum full edge
      EXPORT_FULL_MAX: 4096,      // hard cap to avoid memory issues
      EXPORT_THUMB: 320           // cart/checkout thumb
    };

    // ===== i18n fallback
    const STRINGS = (typeof peVars !== 'undefined' && peVars.strings) ? peVars.strings : {
      imageLoaded: 'Image loaded successfully!',
      imageCleared: 'Image cleared.',
      invalidFile: 'Please select a valid image file.',
      loadError: 'Error loading image. Please try another file.'
    };

    // ===== Border overlay
    const borderImg = new Image();
    borderImg.src = (typeof peVars !== 'undefined' && peVars.borderImageUrl) ? peVars.borderImageUrl : '';
    borderImg.onload = () => draw();

    // =========================
    // UI helpers
    // =========================
    const ui = {
      showBody() { if (elements.bodyBox) elements.bodyBox.style.display = 'block'; },
      hideBody() { if (elements.bodyBox) elements.bodyBox.style.display = 'none'; },
      showLoadButton() { if (elements.loadButton) elements.loadButton.style.display = 'block'; },
      hideLoadButton() { if (elements.loadButton) elements.loadButton.style.display = 'none'; },
      updateControls() {
        [elements.btnRotateLeft, elements.btnRotateRight, elements.btnZoomIn,
        elements.btnZoomOut, elements.btnReset, elements.btnClear].forEach(btn => {
          if (btn) btn.disabled = !state.imageLoaded;
        });
        elements.canvas.classList.toggle('pe-canvas-draggable', !!state.imageLoaded);
      },
      showStatus(message, type = 'success') {
        if (!elements.statusMessage) return;
        elements.statusMessage.textContent = message;
        elements.statusMessage.className = `pe-status-message pe-status-${type}`;
        elements.statusMessage.style.display = 'block';
        setTimeout(() => { elements.statusMessage.style.display = 'none'; }, 3000);
      },
      clearCanvas() { clearFull(ctx, elements.canvas); },
      drawEmptyCanvas() {
        elements.canvas.classList.add('pe-empty-canvas');
        this.clearCanvas();
      }
    };

    // =========================
    // Hidden fields sync
    // =========================
    function writeCustomizationJSON(json) {
      if (elements.hiddenDataUI) elements.hiddenDataUI.value = json;
      if (elements.hiddenDataForm) elements.hiddenDataForm.value = json;
    }

    function wireHiddenSync() {
      if (!elements.hiddenDataForm) elements.hiddenDataForm = document.getElementById('pe-data');
      if (!elements.hiddenDataUI) elements.hiddenDataUI = document.getElementById('pe-data-ui');

      if (elements.hiddenDataForm) {
        elements.hiddenDataForm.addEventListener('input', () => {
          if (elements.hiddenDataUI && elements.hiddenDataUI.value !== elements.hiddenDataForm.value) {
            elements.hiddenDataUI.value = elements.hiddenDataForm.value;
          }
        });
      }
      if (elements.hiddenDataUI) {
        elements.hiddenDataUI.addEventListener('input', () => {
          if (elements.hiddenDataForm && elements.hiddenDataForm.value !== elements.hiddenDataUI.value) {
            elements.hiddenDataForm.value = elements.hiddenDataUI.value;
          }
        });
      }

      if (productForm) {
        const syncAndRebuild = () => {
          if (typeof window.PE_buildAndWriteFromCanvas === 'function') {
            window.PE_buildAndWriteFromCanvas();
          }
          if (elements.hiddenDataUI && elements.hiddenDataForm) {
            elements.hiddenDataForm.value = elements.hiddenDataUI.value;
          }
        };
        productForm.addEventListener('submit', syncAndRebuild);
        const addBtn = productForm.querySelector('.single_add_to_cart_button');
        if (addBtn) addBtn.addEventListener('click', syncAndRebuild);
      }
    }
    wireHiddenSync();
    document.addEventListener('DOMContentLoaded', wireHiddenSync);

    // =========================
    // Drawing
    // =========================
    function draw() {
      // 1) Size + DPR scale
      const { cssW, cssH, dpr } = resizeCanvasToDisplaySize(elements.canvas, ctx);

      // 2) Remember the EXACT size we just drew with
      state.viewCssW = cssW;
      state.viewCssH = cssH;
      state.viewDpr = dpr;

      // 3) Clear and paint
      ui.clearCanvas();

      if (state.img && state.imageLoaded) {
        elements.canvas.classList.remove('pe-empty-canvas');

        ctx.save();
        const cx = cssW / 2;
        const cy = cssH / 2;
        ctx.translate(cx, cy);
        ctx.translate(state.posX, state.posY);   // CSS px
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

      if (borderImg && borderImg.complete && borderImg.naturalWidth) {
        ctx.drawImage(borderImg, 0, 0, cssW, cssH);
      }
    }

    function fitImageToCanvas() {
      if (!state.img) return;

      // Use the last known CSS size (draw set it). If first time, measure once.
      if (!state.viewCssW || !state.viewCssH) {
        const rect = elements.canvas.getBoundingClientRect();
        state.viewCssW = Math.max(1, Math.round(rect.width));
        state.viewCssH = Math.max(1, Math.round(rect.height));
      }

      const cssW = state.viewCssW;
      const cssH = state.viewCssH;

      const iw = state.imgNaturalWidth;
      const ih = state.imgNaturalHeight;

      const scaleX = (cssW * CONFIG.FIT_PADDING) / iw;
      const scaleY = (cssH * CONFIG.FIT_PADDING) / ih;

      state.scale = Math.min(scaleX, scaleY, 1);
      state.rotation = 0;
      state.posX = 0;
      state.posY = 0;

      draw();
    }

    function clampScale(value) {
      return Math.max(CONFIG.ZOOM_MIN, Math.min(CONFIG.ZOOM_MAX, value));
    }

    // =========================
    // Load / Clear / Save
    // =========================
    function loadImage(file) {
      if (!file) { clearImage(); return; }
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
        img.onerror = () => ui.showStatus(STRINGS.loadError, 'error');
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

    function saveData() {
      if (!state.imageLoaded || !state.img) { writeCustomizationJSON(''); return; }

      // Ensure the canvas contains the latest preview frame (with border)
      draw();

      // === New: FULL image is high-res re-render for crisp downloads ===
      const targetFullEdge = computeFullTargetSize(state, CONFIG.EXPORT_FULL_MIN, CONFIG.EXPORT_FULL_MAX);
      const fullDataUrl = exportHighRes(state, borderImg, targetFullEdge);

      // === Thumbnail is a screen snapshot to guarantee visual parity ===
      const thumbDataUrl = snapshotFromScreenToSize(elements.canvas, CONFIG.EXPORT_THUMB, CONFIG.EXPORT_THUMB);

      const cssW = state.viewCssW || Math.round(elements.canvas.getBoundingClientRect().width);
      const cssH = state.viewCssH || Math.round(elements.canvas.getBoundingClientRect().height);

      const data = {
        rotation: state.rotation,
        zoom: state.scale,
        positionX: state.posX,             // CSS px
        positionY: state.posY,             // CSS px
        canvasWidth: Math.round(cssW),    // CSS px reference
        canvasHeight: Math.round(cssH),    // CSS px reference
        imageWidth: state.imgNaturalWidth,
        imageHeight: state.imgNaturalHeight,
        hasImage: state.imageLoaded,

        // Exports
        finalImageFull: fullDataUrl,      // high-res recomposition
        finalImageThumb: thumbDataUrl,     // screenshot thumb (exact match)
        // Legacy field kept for backward-compat
        finalImage: thumbDataUrl,

        timestamp: Date.now()
      };

      writeCustomizationJSON(JSON.stringify(data));
    }

    // =========================
    // Controls
    // =========================
    function rotateLeft() { if (!state.imageLoaded) return; state.rotation -= CONFIG.ROTATE_STEP; draw(); saveData(); }
    function rotateRight() { if (!state.imageLoaded) return; state.rotation += CONFIG.ROTATE_STEP; draw(); saveData(); }
    function zoomIn() { if (!state.imageLoaded) return; state.scale = clampScale(state.scale * (1 + CONFIG.ZOOM_STEP)); draw(); saveData(); }
    function zoomOut() { if (!state.imageLoaded) return; state.scale = clampScale(state.scale * (1 - CONFIG.ZOOM_STEP)); draw(); saveData(); }
    function resetView() { if (!state.imageLoaded) return; state.rotation = 0; state.scale = 1; state.posX = 0; state.posY = 0; draw(); saveData(); }

    // =========================
    // Pointer interactions
    // =========================
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
      state.posX += dx; // CSS px
      state.posY += dy; // CSS px
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

    // =========================
    // Pointer Events (unified mouse/touch/pen)
    // =========================
    const activePointers = new Map();
    let gesture = {
      mode: 'none',          // 'none' | 'pan' | 'pinch'
      lastX: 0,
      lastY: 0,
      // For pinch:
      startScale: 1,
      prevScale: 1,
      startPosX: 0,
      startPosY: 0,
      startDist: 0,
      centerX: 0,
      centerY: 0
    };

    function getPointerCenterAndDist() {
      const pts = Array.from(activePointers.values());
      if (pts.length < 2) return { cx: 0, cy: 0, dist: 0 };
      const p0 = pts[0], p1 = pts[1];
      const cx = (p0.clientX + p1.clientX) / 2;
      const cy = (p0.clientY + p1.clientY) / 2;
      const dx = p1.clientX - p0.clientX;
      const dy = p1.clientY - p0.clientY;
      const dist = Math.hypot(dx, dy);
      return { cx, cy, dist };
    }

    function toCanvasCoords(clientX, clientY) {
      // Convert client coords to canvas CSS space (origin at canvas top-left)
      const rect = elements.canvas.getBoundingClientRect();
      const x = clientX - rect.left;
      const y = clientY - rect.top;
      return { x, y, rect };
    }

    function onPointerDown(e) {
      if (!state.imageLoaded) return;
      elements.canvas.setPointerCapture?.(e.pointerId);
      activePointers.set(e.pointerId, { clientX: e.clientX, clientY: e.clientY });

      if (activePointers.size === 1) {
        // start pan
        gesture.mode = 'pan';
        gesture.lastX = e.clientX;
        gesture.lastY = e.clientY;
        elements.canvas.classList.add('pe-canvas-dragging');
      } else if (activePointers.size === 2) {
        // start pinch
        const { cx, cy, dist } = getPointerCenterAndDist();
        const { x, y, rect } = toCanvasCoords(cx, cy);
        gesture.mode = 'pinch';
        gesture.startScale = state.scale;
        gesture.prevScale = state.scale;
        gesture.startPosX = state.posX;
        gesture.startPosY = state.posY;
        gesture.startDist = Math.max(1, dist);
        // Center relative to canvas center in CSS px (your transforms use canvas center)
        gesture.centerX = x - rect.width / 2;
        gesture.centerY = y - rect.height / 2;
      }

      e.preventDefault();
    }

    function onPointerMove(e) {
      if (!state.imageLoaded) return;
      if (!activePointers.has(e.pointerId)) return;

      // Update tracked pointer position
      activePointers.set(e.pointerId, { clientX: e.clientX, clientY: e.clientY });

      if (gesture.mode === 'pan' && activePointers.size === 1) {
        const dx = e.clientX - gesture.lastX;
        const dy = e.clientY - gesture.lastY;
        gesture.lastX = e.clientX;
        gesture.lastY = e.clientY;

        state.posX += dx; // CSS px
        state.posY += dy; // CSS px
        draw(); // fast preview; save on end
      } else if (gesture.mode === 'pinch' && activePointers.size >= 2) {
        const { cx, cy, dist } = getPointerCenterAndDist();
        const newScaleRaw = gesture.startScale * (dist / Math.max(1, gesture.startDist));
        const newScale = clampScale(newScaleRaw);

        // Zoom around the gesture center to keep it visually stable:
        // Apply incremental scale factor relative to previous frame.
        const factor = newScale / gesture.prevScale;
        state.posX = gesture.centerX - (gesture.centerX - state.posX) * factor;
        state.posY = gesture.centerY - (gesture.centerY - state.posY) * factor;

        state.scale = newScale;
        gesture.prevScale = newScale;

        draw(); // fast preview; save on end
      }

      e.preventDefault();
    }

    function endPointer(e) {
      if (activePointers.has(e.pointerId)) {
        activePointers.delete(e.pointerId);
      }

      if (activePointers.size === 0) {
        // gesture ends
        elements.canvas.classList.remove('pe-canvas-dragging');
        if (gesture.mode !== 'none') {
          gesture.mode = 'none';
          saveData(); // commit the final frame + exports
        }
      } else if (activePointers.size === 1 && gesture.mode === 'pinch') {
        // fallback to pan if one finger remains
        const remaining = Array.from(activePointers.values())[0];
        gesture.mode = 'pan';
        gesture.lastX = remaining.clientX;
        gesture.lastY = remaining.clientY;
      }

      e.preventDefault();
    }

    // Bind pointer events
    elements.canvas.addEventListener('pointerdown', onPointerDown, { passive: false });
    elements.canvas.addEventListener('pointermove', onPointerMove, { passive: false });
    elements.canvas.addEventListener('pointerup', endPointer, { passive: false });
    elements.canvas.addEventListener('pointercancel', endPointer, { passive: false });
    elements.canvas.addEventListener('pointerleave', endPointer, { passive: false });

    // =========================
    // Bind events
    // =========================
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

    elements.canvas.addEventListener('mouseleave', onMouseUp);

    // Redraw on resize; save with *current* viewport afterwards
    window.addEventListener('resize', () => { draw(); saveData(); });

    // =========================
    // Init
    // =========================
    ui.drawEmptyCanvas();
    ui.showBody();
    ui.showLoadButton();
    ui.updateControls();

    // Public API (kept)
    window.PE_writeCustomization = function(payload) {
      const json = (typeof payload === 'string') ? payload : JSON.stringify(payload || {});
      writeCustomizationJSON(json);
    };

    window.PE_buildAndWriteFromCanvas = function() {
      if (!state.imageLoaded) { writeCustomizationJSON(''); return; }
      try {
        // Ensure last frame is present
        draw();

        // Full = high-res re-render; Thumb = screen snapshot
        const targetFullEdge = computeFullTargetSize(state, CONFIG.EXPORT_FULL_MIN, CONFIG.EXPORT_FULL_MAX);
        const full = exportHighRes(state, borderImg, targetFullEdge);
        const thumb = snapshotFromScreenToSize(elements.canvas, CONFIG.EXPORT_THUMB, CONFIG.EXPORT_THUMB);

        const payload = {
          rotation: state.rotation,
          zoom: state.scale,
          positionX: state.posX,
          positionY: state.posY,
          canvasWidth: Math.round(state.viewCssW || elements.canvas.getBoundingClientRect().width),
          canvasHeight: Math.round(state.viewCssH || elements.canvas.getBoundingClientRect().height),
          imageWidth: state.imgNaturalWidth,
          imageHeight: state.imgNaturalHeight,
          hasImage: state.imageLoaded,
          finalImageFull: full,
          finalImageThumb: thumb,
          finalImage: thumb, // legacy
          timestamp: Date.now()
        };
        writeCustomizationJSON(JSON.stringify(payload));
      } catch (_) { /* ignore */ }
    };

    // (Kept) expose math-based exporter for debugging/compat if needed
    window.PE_exportCompositeMath = function(target) {
      const size = parseInt(target, 10) || 1600;
      return exportCompositeMath(state, borderImg, size, size);
    };
  }
})();

