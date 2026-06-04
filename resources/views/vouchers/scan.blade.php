@extends('layouts.app')
@section('title', 'Scan QR Code')
@section('page_title', 'Scan QR Code')
@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <div id="scanner-section">
                        <div class="mb-3">
                            <label class="form-label">Outlet</label>
                            <select id="outlet-select" class="form-select" required>
                                <option value="">Select an outlet...</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" id="start-camera-btn">
                                <i class="fas fa-camera"></i> Start Camera
                            </button>
                            <button type="button" class="btn btn-secondary d-none" id="stop-camera-btn">
                                <i class="fas fa-stop-circle"></i> Stop Camera
                            </button>
                            <button type="button" class="btn btn-info" id="switch-camera-btn" title="Use rear camera on mobile">
                                <i class="fas fa-sync-alt"></i> Switch Camera
                            </button>
                        </div>

                        <div id="camera-container" class="mb-3 d-none" style="position: relative; width: 100%; overflow: hidden; border-radius: 0.25rem; border: 2px solid #dee2e6; background: #000;">
                            <video id="camera-stream" autoplay playsinline muted width="100%" height="auto" style="display: block; transform: scaleX(-1); width: 100%; height: auto;"></video>
                            <canvas id="camera-canvas" style="display: none;"></canvas>
                            <div id="scanner-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; border: 3px solid rgba(0, 255, 0, 0.3);"></div>
                            <div id="camera-loading" class="d-none" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; text-align: center;">
                                <div class="spinner-border text-light mb-2" role="status">
                                    <span class="visually-hidden">Loading camera...</span>
                                </div>
                                <p>Initializing camera...</p>
                            </div>
                        </div>

                        <div id="detected-qr" class="alert alert-success d-none" role="alert">
                            <strong>QR Code Detected!</strong>
                            <p id="detected-code" class="mb-0 mt-2" style="word-break: break-all;"></p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Manual Entry (Optional)</label>
                            <input type="text" id="qr-code-input" class="form-control" placeholder="Or paste QR code here: BookingID+Room+FacilityCode+Date">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pax Used</label>
                            <input type="number" id="pax-used-input" value="1" min="1" class="form-control">
                        </div>

                        <button type="button" class="btn btn-success" id="redeem-btn" disabled>
                            <i class="fas fa-check-circle"></i> Redeem Voucher
                        </button>
                    </div>

                    <div id="result-section" class="d-none">
                        <div id="result-message" class="alert mb-3" role="alert"></div>
                        <div id="result-details" class="mb-3"></div>
                        <button type="button" class="btn btn-primary" id="scan-again-btn">
                            <i class="fas fa-sync-alt"></i> Scan Another
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body text-muted small">
                    <h6 class="text-dark mb-2">📱 Camera Access Help:</h6>
                    <ul class="mb-0">
                        <li><strong>Mobile:</strong> Click "Start Camera" to use your front camera. Click "Switch Camera" to use the rear camera.</li>
                        <li><strong>Desktop/Laptop:</strong> Click "Start Camera" to use your webcam. Click "Switch Camera" to use rear camera if available.</li>
                        <li><strong>QR Format:</strong> BookingID+Room+FacilityCode+Date (e.g., 123+J-01+BREAKFAST+2026-06-04)</li>
                        <li>You can also manually paste the QR code in the text field below.</li>
                    </ul>
                    
                    <h6 class="text-dark mt-3 mb-2">🔧 Camera Not Working? Troubleshooting:</h6>
                    <ul class="mb-0">
                        <li><strong>Permission Denied:</strong> Check browser camera permissions. Click the lock icon in your address bar and allow camera access.</li>
                        <li><strong>Camera Not Found:</strong> Make sure your camera/webcam is connected and not disabled in device settings.</li>
                        <li><strong>Camera In Use:</strong> Close other apps using your camera (video calls, other browser tabs, etc.)</li>
                        <li><strong>Browser Issue:</strong> Use Chrome, Firefox, Safari, or Edge. Camera access requires HTTPS on most browsers.</li>
                        <li><strong>Check Console:</strong> Open browser Developer Tools (F12) → Console tab to see detailed error messages.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const videoElement = document.getElementById('camera-stream');
    const canvasElement = document.getElementById('camera-canvas');
    const outletSelect = document.getElementById('outlet-select');
    const qrCodeInput = document.getElementById('qr-code-input');
    const paxUsedInput = document.getElementById('pax-used-input');
    const redeemBtn = document.getElementById('redeem-btn');
    const startCameraBtn = document.getElementById('start-camera-btn');
    const stopCameraBtn = document.getElementById('stop-camera-btn');
    const switchCameraBtn = document.getElementById('switch-camera-btn');
    const cameraContainer = document.getElementById('camera-container');
    const detectedQrDiv = document.getElementById('detected-qr');
    const detectedCodeDiv = document.getElementById('detected-code');
    const scannerSection = document.getElementById('scanner-section');
    const resultSection = document.getElementById('result-section');
    const resultMessage = document.getElementById('result-message');
    const resultDetails = document.getElementById('result-details');
    const scanAgainBtn = document.getElementById('scan-again-btn');

    let stream = null;
    let isScanning = false;
    let detectedQrCode = null;
    let facingMode = 'user'; // 'user' for front, 'environment' for rear
    let cameraLoading = document.getElementById('camera-loading');

    startCameraBtn.addEventListener('click', startCamera);
    stopCameraBtn.addEventListener('click', stopCamera);
    switchCameraBtn.addEventListener('click', switchCamera);
    redeemBtn.addEventListener('click', redeemVoucher);
    scanAgainBtn.addEventListener('click', scanAgain);
    qrCodeInput.addEventListener('input', updateRedeemButton);

    // Check camera availability on page load
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        startCameraBtn.disabled = true;
        startCameraBtn.title = 'Your browser does not support camera access. Please use a modern browser like Chrome, Firefox, Safari, or Edge.';
        console.warn('Camera API not supported');
    }

    async function startCamera() {
        try {
            cameraLoading.classList.remove('d-none');
            
            const constraints = {
                video: {
                    facingMode: facingMode,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            console.log('Requesting camera access with constraints:', constraints);
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log('Camera stream obtained:', stream);
            
            videoElement.srcObject = stream;
            console.log('Video source object set');
            
            cameraContainer.classList.remove('d-none');
            startCameraBtn.classList.add('d-none');
            stopCameraBtn.classList.remove('d-none');
            isScanning = true;

            // Wait for video to be ready and playing
            if (videoElement.readyState >= 2) {
                // Video is already ready
                console.log('Video already ready');
                cameraLoading.classList.add('d-none');
                scanQrCode();
            } else {
                // Wait for canplay event
                videoElement.addEventListener('canplay', function onCanPlay() {
                    console.log('Video can play');
                    cameraLoading.classList.add('d-none');
                    videoElement.removeEventListener('canplay', onCanPlay);
                    scanQrCode();
                }, { once: true });
            }

            // Ensure video plays
            videoElement.play().then(() => {
                console.log('Video play started');
            }).catch(error => {
                console.error('Video play error:', error);
                alert('Could not play video stream: ' + error.message);
            });

        } catch (error) {
            cameraLoading.classList.add('d-none');
            console.error('Camera error:', error);
            let errorMessage = 'Camera access error';
            
            if (error.name === 'NotAllowedError') {
                errorMessage = 'Camera permission denied. Please allow camera access in your browser settings.';
            } else if (error.name === 'NotFoundError') {
                errorMessage = 'No camera found. Please check if your camera is connected and not in use.';
            } else if (error.name === 'NotReadableError') {
                errorMessage = 'Camera is in use by another application. Please close other apps using the camera.';
            } else if (error.name === 'SecurityError') {
                errorMessage = 'Camera access blocked for security reasons. This page must use HTTPS.';
            } else {
                errorMessage = error.message || errorMessage;
            }
            
            alert(errorMessage);
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        cameraContainer.classList.add('d-none');
        startCameraBtn.classList.remove('d-none');
        stopCameraBtn.classList.add('d-none');
        isScanning = false;
        detectedQrDiv.classList.add('d-none');
    }

    async function switchCamera() {
        stopCamera();
        facingMode = facingMode === 'user' ? 'environment' : 'user';
        console.log('Switching camera to facing mode:', facingMode);
        await new Promise(resolve => setTimeout(resolve, 500));
        await startCamera();
    }

    function scanQrCode() {
        if (!isScanning) return;

        const canvas = canvasElement;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;

        ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
        
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert'
        });

        if (code && code.data !== detectedQrCode) {
            detectedQrCode = code.data;
            qrCodeInput.value = detectedQrCode;
            detectedCodeDiv.textContent = detectedQrCode;
            detectedQrDiv.classList.remove('d-none');
            updateRedeemButton();
        }

        requestAnimationFrame(scanQrCode);
    }

    function updateRedeemButton() {
        const hasQrCode = qrCodeInput.value.trim().length > 0;
        const hasOutlet = outletSelect.value.length > 0;
        redeemBtn.disabled = !(hasQrCode && hasOutlet);
    }

    async function redeemVoucher() {
        const outletId = outletSelect.value;
        const qrCode = qrCodeInput.value.trim();
        const paxUsed = parseInt(paxUsedInput.value) || 1;

        if (!outletId || !qrCode) {
            alert('Please select an outlet and provide a QR code');
            return;
        }

        redeemBtn.disabled = true;
        redeemBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
            const response = await fetch('{{ route("vouchers.scan.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    outlet_id: outletId,
                    qr_code: qrCode,
                    pax_used: paxUsed,
                })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess(data.data);
            } else {
                showError(data.message);
            }

        } catch (error) {
            showError('An error occurred: ' + error.message);
            console.error(error);
        } finally {
            redeemBtn.disabled = false;
            redeemBtn.innerHTML = '<i class="bi bi-check-circle"></i> Redeem Voucher';
        }
    }

    function showSuccess(voucher) {
        scannerSection.classList.add('d-none');
        resultSection.classList.remove('d-none');
        
        resultMessage.className = 'alert alert-success';
        resultMessage.innerHTML = '<strong>✓ Voucher Redeemed Successfully!</strong>';
        
        const guest = voucher.booking?.guest;
        const facility = voucher.facility_template;
        
        resultDetails.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="fw-bold">Guest:</td>
                        <td>${guest?.first_name || 'N/A'} ${guest?.last_name || ''}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Facility:</td>
                        <td>${facility?.name || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Valid Date:</td>
                        <td>${voucher.valid_date}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Quota Remaining:</td>
                        <td>${voucher.quota_remaining}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Status:</td>
                        <td><span class="badge bg-${voucher.status === 'redeemed' ? 'success' : 'info'}">${voucher.status}</span></td>
                    </tr>
                </table>
            </div>
        `;
    }

    function showError(message) {
        scannerSection.classList.add('d-none');
        resultSection.classList.remove('d-none');
        
        resultMessage.className = 'alert alert-danger';
        resultMessage.innerHTML = `<strong>✗ Error:</strong> ${message}`;
        resultDetails.innerHTML = '';
    }

    function scanAgain() {
        detectedQrCode = null;
        qrCodeInput.value = '';
        paxUsedInput.value = '1';
        detectedQrDiv.classList.add('d-none');
        
        scannerSection.classList.remove('d-none');
        resultSection.classList.add('d-none');
        
        if (outletSelect.value) {
            startCamera();
        }
    }
});
</script>
@endpush
