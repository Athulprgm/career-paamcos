@props([
    'name' => 'profile_image',
    'value' => null,
    'id' => 'profile_image_cropper_' . uniqid(),
    'label' => 'Profile Photo',
    'hint' => 'Upload a professional photo. Crop & adjust zoom or rotation before saving.',
])

<div class="profile-image-uploader-wrapper mb-4" id="{{ $id }}_wrapper">
    <label class="form-label fw-semibold d-block mb-2">
        <i class="bi bi-camera me-1 text-primary"></i> {{ $label }}
    </label>

    <div class="card border border-dashed p-3 bg-light-subtle rounded-3">
        <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
            <!-- Avatar Frame / Preview -->
            <div class="position-relative">
                <div class="profile-avatar-frame shadow-sm rounded-circle border border-2 border-white overflow-hidden bg-dark d-flex align-items-center justify-content-center"
                     style="width: 96px; height: 96px; min-width: 96px; cursor: pointer;"
                     id="{{ $id }}_avatar_frame"
                     title="Click to select or change photo">
                    @if(!empty($value))
                        <img src="{{ $value }}"
                             alt="Profile Photo"
                             class="w-100 h-100 object-fit-cover"
                             id="{{ $id }}_preview_img">
                        <div class="avatar-placeholder d-none" id="{{ $id }}_placeholder">
                            <i class="bi bi-person text-white-50 fs-1"></i>
                        </div>
                    @else
                        <img src=""
                             alt="Profile Photo"
                             class="w-100 h-100 object-fit-cover d-none"
                             id="{{ $id }}_preview_img">
                        <div class="avatar-placeholder" id="{{ $id }}_placeholder">
                            <i class="bi bi-person text-white-50 fs-1"></i>
                        </div>
                    @endif
                </div>
                <button type="button"
                        class="btn btn-dark btn-sm rounded-circle position-absolute bottom-0 end-0 p-1 lh-1 shadow"
                        style="width: 28px; height: 28px;"
                        id="{{ $id }}_trigger_btn"
                        title="Upload photo">
                    <i class="bi bi-camera-fill" style="font-size: 0.75rem;"></i>
                </button>
            </div>

            <!-- Upload Controls & Action Buttons -->
            <div class="flex-grow-1 text-center text-sm-start">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1 justify-content-center justify-content-sm-start">
                    <button type="button" class="btn btn-sm btn-outline-dark fw-medium" id="{{ $id }}_choose_btn">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Choose Photo
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-medium {{ empty($value) ? 'd-none' : '' }}" id="{{ $id }}_adjust_btn">
                        <i class="bi bi-crop me-1"></i> Crop & Adjust
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger fw-medium {{ empty($value) ? 'd-none' : '' }}" id="{{ $id }}_remove_btn">
                        <i class="bi bi-trash3 me-1"></i> Remove
                    </button>
                </div>
                <div class="small text-muted" id="{{ $id }}_status_text">
                    {{ $hint }}
                </div>
                <div class="small text-success fw-semibold mt-1 d-none" id="{{ $id }}_crop_success_badge">
                    <i class="bi bi-check-circle-fill me-1"></i> Cropped photo ready to save
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Native Inputs -->
    <input type="file"
           name="profile_image_file"
           id="{{ $id }}_file_input"
           accept="image/png,image/jpeg,image/jpg,image/webp"
           class="d-none">

    <input type="hidden"
           name="{{ $name }}"
           id="{{ $id }}_base64_input"
           value="">

    <input type="hidden"
           name="remove_profile_image"
           id="{{ $id }}_remove_input"
           value="0">

    <!-- Crop & Adjust Modal -->
    <div class="modal fade" id="{{ $id }}_modal" tabindex="-1" aria-labelledby="{{ $id }}_modal_label" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-crop text-primary fs-5"></i>
                        <div>
                            <h6 class="modal-title fw-bold mb-0 text-white" id="{{ $id }}_modal_label">Crop & Adjust Profile Photo</h6>
                            <small class="text-white-50">Drag image to position, zoom, and rotate for the ideal avatar</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <div class="row g-4 align-items-center">
                        <!-- Main Cropping Workspace -->
                        <div class="col-lg-8">
                            <div class="crop-workspace-container rounded-3 border overflow-hidden position-relative"
                                 style="background-color: #0b1120; height: 380px; display: flex; align-items: center; justify-content: center;">
                                <img id="{{ $id }}_cropper_img" src="" alt="Source Image" style="max-width: 100%; display: block;">
                            </div>
                        </div>

                        <!-- Live Preview Panel -->
                        <div class="col-lg-4 text-center">
                            <div class="card border shadow-sm p-3 bg-white h-100 d-flex flex-column justify-content-center align-items-center">
                                <span class="text-muted small fw-bold text-uppercase mb-2">Live Avatar Preview</span>

                                <!-- 120px Circular Avatar Preview -->
                                <div class="mb-3 position-relative">
                                    <div class="img-preview-circle border border-3 border-dark shadow rounded-circle overflow-hidden mx-auto"
                                         id="{{ $id }}_preview_large"
                                         style="width: 120px; height: 120px;">
                                    </div>
                                    <span class="badge bg-dark position-absolute bottom-0 start-50 translate-middle-x" style="font-size: 0.65rem;">
                                        Profile View
                                    </span>
                                </div>

                                <!-- 44px Small Thumbnail Preview -->
                                <div class="d-flex align-items-center gap-2 mt-2 pt-2 border-top w-100 justify-content-center">
                                    <div class="img-preview-thumb border border-2 border-secondary rounded-circle overflow-hidden"
                                         id="{{ $id }}_preview_small"
                                         style="width: 44px; height: 44px;">
                                    </div>
                                    <div class="text-start">
                                        <div class="small fw-semibold text-dark">Table / Badge</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">44 × 44 px</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Adjustment Toolbar Controls -->
                    <div class="card border shadow-sm mt-3 bg-white p-3">
                        <div class="row g-3 align-items-center">
                            <!-- Zoom Slider & Buttons -->
                            <div class="col-md-6 border-end-md">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-semibold text-dark">
                                        <i class="bi bi-zoom-in me-1 text-primary"></i> Zoom Level
                                    </span>
                                    <span class="small text-muted font-monospace" id="{{ $id }}_zoom_label">1.0x</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-2" id="{{ $id }}_zoom_out" title="Zoom Out">
                                        <i class="bi bi-dash-lg"></i>
                                    </button>
                                    <input type="range" class="form-range flex-grow-1" id="{{ $id }}_zoom_range" min="0.2" max="3" step="0.05" value="1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-2" id="{{ $id }}_zoom_in" title="Zoom In">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Rotation & Orientation Controls -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="small fw-semibold text-dark">
                                        <i class="bi bi-arrow-repeat me-1 text-primary"></i> Rotate & Align
                                    </span>
                                    <span class="small text-muted font-monospace" id="{{ $id }}_rotate_label">0°</span>
                                </div>
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="{{ $id }}_rotate_left" title="Rotate -90°">
                                        <i class="bi bi-arrow-counterclockwise"></i> -90°
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="{{ $id }}_rotate_right" title="Rotate +90°">
                                        <i class="bi bi-arrow-clockwise"></i> +90°
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="{{ $id }}_flip_h" title="Flip Horizontal">
                                        <i class="bi bi-symmetry-vertical"></i> Flip H
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="{{ $id }}_reset_btn" title="Reset view">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white py-2 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-dark btn-sm px-4 fw-semibold" id="{{ $id }}_apply_btn">
                        <i class="bi bi-check2-circle me-1"></i> Apply & Use Photo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@pushOnce('styles')
<link rel="stylesheet" href="{{ asset('vendor/cropperjs/cropper.min.css') }}">
<style>
    .cropper-view-box,
    .cropper-face {
        border-radius: 50%;
    }
    .cropper-view-box {
        outline: 2px solid var(--color-primary, #14532D);
        outline-color: rgba(16, 185, 129, 0.85);
    }
    .profile-avatar-frame:hover {
        opacity: 0.92;
        transition: opacity 0.2s ease;
    }
    .img-preview-circle,
    .img-preview-thumb {
        background-color: var(--surface-subtle, #f1f5f9);
        background-image: linear-gradient(45deg, var(--border-color, #e2e8f0) 25%, transparent 25%),
                          linear-gradient(-45deg, var(--border-color, #e2e8f0) 25%, transparent 25%),
                          linear-gradient(45deg, transparent 75%, var(--border-color, #e2e8f0) 75%),
                          linear-gradient(-45deg, transparent 75%, var(--border-color, #e2e8f0) 75%);
        background-size: 16px 16px;
        background-position: 0 0, 0 8px, 8px -8px, -8px 0px;
    }
</style>
@endPushOnce

@pushOnce('scripts')
<script src="{{ asset('vendor/cropperjs/cropper.min.js') }}"></script>
@endPushOnce

@push('scripts')
<script>
(function() {
    const id = "{{ $id }}";
    const fileInput = document.getElementById(id + '_file_input');
    const base64Input = document.getElementById(id + '_base64_input');
    const removeInput = document.getElementById(id + '_remove_input');
    const previewImg = document.getElementById(id + '_preview_img');
    const placeholder = document.getElementById(id + '_placeholder');
    const chooseBtn = document.getElementById(id + '_choose_btn');
    const triggerBtn = document.getElementById(id + '_trigger_btn');
    const avatarFrame = document.getElementById(id + '_avatar_frame');
    const adjustBtn = document.getElementById(id + '_adjust_btn');
    const removeBtn = document.getElementById(id + '_remove_btn');
    const successBadge = document.getElementById(id + '_crop_success_badge');
    const statusText = document.getElementById(id + '_status_text');

    const modalEl = document.getElementById(id + '_modal');
    let modalInstance = null;
    const cropperImg = document.getElementById(id + '_cropper_img');
    let cropper = null;
    let originalImageSrc = previewImg.src || null;
    let flipH = 1;
    let currentZoom = 1;
    let currentRotate = 0;

    const zoomRange = document.getElementById(id + '_zoom_range');
    const zoomLabel = document.getElementById(id + '_zoom_label');
    const rotateLabel = document.getElementById(id + '_rotate_label');

    function ensureCropperLoaded(onReady) {
        if (typeof window.Cropper !== 'undefined') {
            if (typeof onReady === 'function') onReady();
            return;
        }

        let scriptTag = document.getElementById('global-cropper-js');
        if (!scriptTag) {
            scriptTag = document.createElement('script');
            scriptTag.id = 'global-cropper-js';
            scriptTag.src = "{{ asset('vendor/cropperjs/cropper.min.js') }}";
            scriptTag.onload = function() {
                if (typeof window.Cropper !== 'undefined') {
                    if (typeof onReady === 'function') onReady();
                } else {
                    fallbackCdn(onReady);
                }
            };
            scriptTag.onerror = function() {
                fallbackCdn(onReady);
            };
            document.head.appendChild(scriptTag);
        } else {
            let checks = 0;
            const interval = setInterval(function() {
                checks++;
                if (typeof window.Cropper !== 'undefined') {
                    clearInterval(interval);
                    if (typeof onReady === 'function') onReady();
                } else if (checks > 40) {
                    clearInterval(interval);
                    fallbackCdn(onReady);
                }
            }, 50);
        }

        function fallbackCdn(cb) {
            const cdnTag = document.createElement('script');
            cdnTag.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js';
            cdnTag.onload = function() {
                if (typeof window.Cropper !== 'undefined' && typeof cb === 'function') {
                    cb();
                }
            };
            document.head.appendChild(cdnTag);
        }
    }

    // Preload library on idle
    ensureCropperLoaded();

    function getModal() {
        if (!modalInstance && window.bootstrap && window.bootstrap.Modal) {
            modalInstance = new bootstrap.Modal(modalEl);
        }
        return modalInstance;
    }

    function openFilePicker() {
        ensureCropperLoaded();
        fileInput.value = '';
        fileInput.click();
    }

    chooseBtn?.addEventListener('click', openFilePicker);
    triggerBtn?.addEventListener('click', openFilePicker);
    avatarFrame?.addEventListener('click', openFilePicker);

    fileInput?.addEventListener('change', function(e) {
        const file = e.target.files?.[0];
        if (!file) return;

        if (!file.type.match('image.*')) {
            alert('Please select an image file (PNG, JPG, JPEG, WebP).');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(evt) {
            originalImageSrc = evt.target.result;
            openCropModal(originalImageSrc);
        };
        reader.readAsDataURL(file);
    });

    adjustBtn?.addEventListener('click', function() {
        const activeSrc = originalImageSrc || previewImg.src;
        if (activeSrc) {
            openCropModal(activeSrc);
        }
    });

    removeBtn?.addEventListener('click', function() {
        previewImg.src = '';
        previewImg.classList.add('d-none');
        placeholder.classList.remove('d-none');
        base64Input.value = '';
        fileInput.value = '';
        removeInput.value = '1';
        adjustBtn.classList.add('d-none');
        removeBtn.classList.add('d-none');
        successBadge.classList.add('d-none');
        statusText.textContent = 'Photo removed. Click to upload a new photo.';
        originalImageSrc = null;
    });

    function openCropModal(imageSrc) {
        ensureCropperLoaded();
        cropperImg.src = imageSrc;
        const modal = getModal();
        if (modal) {
            modal.show();
        }
    }

    modalEl?.addEventListener('shown.bs.modal', function() {
        ensureCropperLoaded(function() {
            initCropper();
        });
    });

    modalEl?.addEventListener('hidden.bs.modal', function() {
        destroyCropper();
    });

    function initCropper() {
        destroyCropper();

        if (typeof Cropper === 'undefined') {
            console.warn('Waiting for Cropper.js to finish initialization...');
            ensureCropperLoaded(function() {
                initCropper();
            });
            return;
        }

        flipH = 1;
        currentRotate = 0;
        currentZoom = 1;
        zoomRange.value = 1;
        zoomLabel.textContent = '1.0x';
        rotateLabel.textContent = '0°';

        cropper = new Cropper(cropperImg, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.9,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            preview: [
                document.getElementById(id + '_preview_large'),
                document.getElementById(id + '_preview_small')
            ],
            ready: function() {
                const canvasData = cropper.getCanvasData();
                currentZoom = 1;
            },
            zoom: function(e) {
                if (e.detail && e.detail.ratio) {
                    currentZoom = Math.min(3, Math.max(0.1, e.detail.ratio));
                    zoomRange.value = currentZoom.toFixed(2);
                    zoomLabel.textContent = currentZoom.toFixed(1) + 'x';
                }
            }
        });
    }

    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    // Zoom Controls
    zoomRange?.addEventListener('input', function(e) {
        if (!cropper) return;
        const val = parseFloat(e.target.value);
        cropper.zoomTo(val);
        zoomLabel.textContent = val.toFixed(1) + 'x';
    });

    document.getElementById(id + '_zoom_in')?.addEventListener('click', function() {
        if (!cropper) return;
        cropper.zoom(0.15);
    });

    document.getElementById(id + '_zoom_out')?.addEventListener('click', function() {
        if (!cropper) return;
        cropper.zoom(-0.15);
    });

    // Rotate Controls
    document.getElementById(id + '_rotate_left')?.addEventListener('click', function() {
        if (!cropper) return;
        cropper.rotate(-90);
        currentRotate = (currentRotate - 90) % 360;
        rotateLabel.textContent = currentRotate + '°';
    });

    document.getElementById(id + '_rotate_right')?.addEventListener('click', function() {
        if (!cropper) return;
        cropper.rotate(90);
        currentRotate = (currentRotate + 90) % 360;
        rotateLabel.textContent = currentRotate + '°';
    });

    // Flip Control
    document.getElementById(id + '_flip_h')?.addEventListener('click', function() {
        if (!cropper) return;
        flipH = flipH === 1 ? -1 : 1;
        cropper.scaleX(flipH);
    });

    // Reset Control
    document.getElementById(id + '_reset_btn')?.addEventListener('click', function() {
        if (!cropper) return;
        cropper.reset();
        flipH = 1;
        currentRotate = 0;
        currentZoom = 1;
        zoomRange.value = 1;
        zoomLabel.textContent = '1.0x';
        rotateLabel.textContent = '0°';
    });

    // Apply Crop
    document.getElementById(id + '_apply_btn')?.addEventListener('click', function() {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: 480,
            height: 480,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });

        if (!canvas) {
            alert('Could not process the crop. Please try again.');
            return;
        }

        const croppedBase64 = canvas.toDataURL('image/jpeg', 0.92);

        // Update preview and hidden base64 input
        previewImg.src = croppedBase64;
        previewImg.classList.remove('d-none');
        placeholder.classList.add('d-none');
        base64Input.value = croppedBase64;
        removeInput.value = '0';

        // Toggle buttons
        adjustBtn.classList.remove('d-none');
        removeBtn.classList.remove('d-none');
        successBadge.classList.remove('d-none');
        statusText.textContent = 'Cropped photo applied. It will be uploaded on submit.';

        // Close modal
        const modal = getModal();
        if (modal) {
            modal.hide();
        }
    });
})();
</script>
@endpush
