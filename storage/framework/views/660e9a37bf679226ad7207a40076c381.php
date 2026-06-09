<?php $__env->startSection('title', 'Scan QR Code'); ?>
<?php $__env->startSection('page_title', 'Scan QR Code'); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow">
            <div class="card-body">
                <!-- Step 1: Select Outlet and Scan/Input QR -->
                <div id="scanner-section">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Outlet Location</label>
                        <select id="outlet-select" class="form-select" required>
                            <option value="">Select an outlet...</option>
                            <?php $__currentLoopData = $outlets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outlet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($outlet->id); ?>"><?php echo e($outlet->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="mb-3 text-center">
                        <button type="button" class="btn btn-primary" id="start-camera-btn">
                            <i class="fas fa-camera"></i> Start Camera
                        </button>
                        <button type="button" class="btn btn-secondary d-none" id="stop-camera-btn">
                            <i class="fas fa-stop-circle"></i> Stop Camera
                        </button>
                        <button type="button" class="btn btn-info" id="switch-camera-btn" title="Switch Camera">
                            <i class="fas fa-sync-alt"></i> Switch Camera
                        </button>
                    </div>

                    <div id="camera-container" class="mb-3 d-none position-relative rounded border bg-dark overflow-hidden" style="width: 100%; max-height: 480px;">
                        <video id="camera-stream" autoplay playsinline muted width="100%" height="auto" style="display: block; transform: scaleX(-1);"></video>
                        <canvas id="camera-canvas" style="display: none;"></canvas>
                        <div id="scanner-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; border: 3px solid rgba(0, 255, 0, 0.3); pointer-events: none;"></div>
                        <div id="camera-loading" class="d-none position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; text-align: center;">
                            <div class="spinner-border text-light mb-2" role="status">
                                <span class="visually-hidden">Loading camera...</span>
                            </div>
                            <p>Initializing camera...</p>
                        </div>
                    </div>

                    <div id="detected-qr" class="alert alert-success d-none mb-3" role="alert">
                        <strong><i class="fas fa-check-circle"></i> QR Code Detected!</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Manual Entry (Optional)</label>
                        <div class="input-group">
                            <input type="text" id="qr-code-input" class="form-control" placeholder="Paste secure token or QR code string here...">
                            <button class="btn btn-primary" type="button" id="verify-manual-btn">Verify</button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Verification Panel (Displays Guest Stay & Facility Statuses) -->
                <div id="verification-section" class="d-none">
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="h6 mb-0 font-weight-bold" id="verify-guest-name">Guest Name</h5>
                            <span class="small" id="verify-stay-details">Stay Details</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Room:</strong> <span id="verify-room-label">Room</span></p>
                            <p class="mb-1"><strong>Booking Code:</strong> <span id="verify-booking-code">Reference</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Total Pax Quota:</strong> <span id="verify-total-pax">Quota</span></p>
                        </div>
                    </div>

                    <h6 class="font-weight-bold border-bottom pb-2 mb-3">Select Facility to Redeem</h6>
                    <div id="facility-list" class="mb-3">
                        <!-- Facilities will be dynamically loaded here -->
                    </div>

                    <div id="redemption-input-block" class="d-none">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Pax to Redeem</label>
                            <input type="number" id="pax-used-input" value="1" min="1" max="50" class="form-control">
                            <div class="form-text text-muted">Cannot exceed remaining facility quota.</div>
                        </div>

                        <button type="button" class="btn btn-success w-100 py-2" id="redeem-btn">
                            <i class="fas fa-check-circle"></i> Confirm Redemption
                        </button>
                    </div>

                    <!-- Redemption History -->
                    <div class="mt-4">
                        <h6 class="font-weight-bold border-bottom pb-2 mb-2">Recent Redemptions for this stay</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered mb-0 small" id="history-table">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Facility</th>
                                        <th>Pax</th>
                                        <th>Outlet</th>
                                        <th>Staff</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- History loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="text-center mt-4 border-top pt-3">
                        <button type="button" class="btn btn-secondary" id="cancel-verify-btn">
                            <i class="fas fa-times"></i> Cancel & Scan Again
                        </button>
                    </div>
                </div>

                <!-- Step 3: Success/Error Result -->
                <div id="result-section" class="d-none text-center py-4">
                    <div id="result-icon" class="mb-3"></div>
                    <h3 class="h4 font-weight-bold" id="result-title">Result</h3>
                    <div id="result-message" class="alert my-3" role="alert"></div>
                    <div id="result-details" class="mb-4 text-left p-3 bg-light rounded border border-2"></div>
                    <button type="button" class="btn btn-primary" id="scan-again-btn">
                        <i class="fas fa-sync-alt"></i> Scan Another QR Code
                    </button>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body text-muted small">
                <h6 class="text-dark mb-2"><i class="fas fa-mobile-alt"></i> Camera Access Help:</h6>
                <ul class="mb-0">
                    <li><strong>Mobile:</strong> Click "Start Camera" to use your front camera. Click "Switch Camera" to use the rear camera.</li>
                    <li><strong>QR Format:</strong> Encodes the secure internal token. The system verifies this token against the database before proceeding.</li>
                    <li>You can also manually paste the QR code string or secure token in the text field.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.facility-card {
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}
.facility-card.active {
    border-color: #0d6efd !important;
    background-color: #f8f9fa;
    box-shadow: 0 0.125rem 0.25rem rgba(13, 110, 253, 0.075);
}
.facility-card:hover:not(.disabled) {
    cursor: pointer;
    border-color: #adb5bd;
}
.facility-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #f8f9fa;
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const videoElement = document.getElementById('camera-stream');
    const canvasElement = document.getElementById('camera-canvas');
    const outletSelect = document.getElementById('outlet-select');
    const qrCodeInput = document.getElementById('qr-code-input');
    const verifyManualBtn = document.getElementById('verify-manual-btn');
    const paxUsedInput = document.getElementById('pax-used-input');
    const redeemBtn = document.getElementById('redeem-btn');
    const startCameraBtn = document.getElementById('start-camera-btn');
    const stopCameraBtn = document.getElementById('stop-camera-btn');
    const switchCameraBtn = document.getElementById('switch-camera-btn');
    const cameraContainer = document.getElementById('camera-container');
    const detectedQrDiv = document.getElementById('detected-qr');
    const scannerSection = document.getElementById('scanner-section');
    const verificationSection = document.getElementById('verification-section');
    const resultSection = document.getElementById('result-section');
    
    const verifyGuestName = document.getElementById('verify-guest-name');
    const verifyStayDetails = document.getElementById('verify-stay-details');
    const verifyRoomLabel = document.getElementById('verify-room-label');
    const verifyBookingCode = document.getElementById('verify-booking-code');
    const verifyTotalPax = document.getElementById('verify-total-pax');
    const facilityList = document.getElementById('facility-list');
    const redemptionInputBlock = document.getElementById('redemption-input-block');
    const historyTableBody = document.querySelector('#history-table tbody');
    const cancelVerifyBtn = document.getElementById('cancel-verify-btn');
    
    const resultIcon = document.getElementById('result-icon');
    const resultTitle = document.getElementById('result-title');
    const resultMessage = document.getElementById('result-message');
    const resultDetails = document.getElementById('result-details');
    const scanAgainBtn = document.getElementById('scan-again-btn');

    let stream = null;
    let isScanning = false;
    let detectedQrCode = null;
    let facingMode = 'user'; 
    let cameraLoading = document.getElementById('camera-loading');
    
    let currentVoucherCode = null;
    let selectedFacilityId = null;
    let selectedFacilityRemaining = 0;

    startCameraBtn.addEventListener('click', startCamera);
    stopCameraBtn.addEventListener('click', stopCamera);
    switchCameraBtn.addEventListener('click', switchCamera);
    verifyManualBtn.addEventListener('click', verifyManual);
    redeemBtn.addEventListener('click', redeemFacility);
    scanAgainBtn.addEventListener('click', resetScanFlow);
    cancelVerifyBtn.addEventListener('click', resetScanFlow);

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        startCameraBtn.disabled = true;
        startCameraBtn.title = 'Camera API not supported';
    }

    async function startCamera() {
        if (!outletSelect.value) {
            alert('Please select an outlet location first.');
            return;
        }

        try {
            cameraLoading.classList.remove('d-none');
            
            const constraints = {
                video: {
                    facingMode: facingMode,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            stream = await navigator.mediaDevices.getUserMedia(constraints);
            videoElement.srcObject = stream;
            
            cameraContainer.classList.remove('d-none');
            startCameraBtn.classList.add('d-none');
            stopCameraBtn.classList.remove('d-none');
            isScanning = true;

            if (videoElement.readyState >= 2) {
                cameraLoading.classList.add('d-none');
                scanQrCode();
            } else {
                videoElement.addEventListener('canplay', function onCanPlay() {
                    cameraLoading.classList.add('d-none');
                    videoElement.removeEventListener('canplay', onCanPlay);
                    scanQrCode();
                }, { once: true });
            }

            videoElement.play();

        } catch (error) {
            cameraLoading.classList.add('d-none');
            console.error('Camera error:', error);
            alert('Could not open camera. Please ensure permissions are granted and no other apps are using it.');
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
        await new Promise(resolve => setTimeout(resolve, 300));
        await startCamera();
    }

    function scanQrCode() {
        if (!isScanning) return;

        const canvas = canvasElement;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        
        canvas.width = videoElement.videoWidth || 640;
        canvas.height = videoElement.videoHeight || 480;

        if (canvas.width > 0 && canvas.height > 0) {
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert'
            });

            if (code && code.data !== detectedQrCode) {
                detectedQrCode = code.data;
                qrCodeInput.value = detectedQrCode;
                detectedQrDiv.classList.remove('d-none');
                
                stopCamera();
                verifyCode(detectedQrCode);
            }
        }

        if (isScanning) {
            requestAnimationFrame(scanQrCode);
        }
    }

    function verifyManual() {
        const val = qrCodeInput.value.trim();
        if (!val) {
            alert('Please paste or scan a QR code.');
            return;
        }
        if (!outletSelect.value) {
            alert('Please select an outlet location first.');
            return;
        }
        verifyCode(val);
    }

    async function verifyCode(code) {
        currentVoucherCode = code;
        selectedFacilityId = null;
        
        verifyManualBtn.disabled = true;
        verifyManualBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const response = await fetch('<?php echo e(route("vouchers.scan.verify")); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ qr_code: code })
            });

            const res = await response.json();

            if (res.success) {
                displayVerification(res.data);
            } else {
                showRedemptionError(res.message);
            }

        } catch (error) {
            showRedemptionError('An error occurred during verification: ' + error.message);
        } finally {
            verifyManualBtn.disabled = false;
            verifyManualBtn.textContent = 'Verify';
        }
    }

    function displayVerification(data) {
        scannerSection.classList.add('d-none');
        verificationSection.classList.remove('d-none');

        verifyGuestName.textContent = data.guest_name;
        verifyStayDetails.textContent = `Check-In: ${data.check_in} | Check-Out: ${data.check_out}`;
        verifyRoomLabel.textContent = data.room_name + ` (${data.room_code})`;
        verifyBookingCode.textContent = data.booking_code;
        verifyTotalPax.textContent = `${data.total_pax} Pax (based on Pax + Extra Bed)`;

        facilityList.innerHTML = '';
        redemptionInputBlock.classList.add('d-none');

        data.facilities.forEach(facility => {
            const card = document.createElement('div');
            const isDisabled = !facility.is_available || facility.quota_remaining <= 0;
            
            card.className = `card mb-2 facility-card ${isDisabled ? 'disabled' : ''}`;
            card.dataset.id = facility.facility_template_id;
            card.dataset.remaining = facility.quota_remaining;

            card.innerHTML = `
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 font-weight-bold text-dark">${facility.name}</h6>
                        <small class="text-muted">Quota: ${facility.quota_total} | Used: ${facility.quota_used}</small>
                    </div>
                    <div>
                        <span class="badge bg-${facility.quota_remaining > 0 ? 'success' : 'danger'} px-3 py-2">
                            ${facility.quota_remaining} Remaining
                        </span>
                    </div>
                </div>
            `;

            if (!isDisabled) {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.facility-card').forEach(c => c.classList.remove('active'));
                    card.classList.add('active');

                    selectedFacilityId = facility.facility_template_id;
                    selectedFacilityRemaining = facility.quota_remaining;
                    paxUsedInput.value = 1;
                    paxUsedInput.max = facility.quota_remaining;

                    redemptionInputBlock.classList.remove('d-none');
                });
            }

            facilityList.appendChild(card);
        });

        historyTableBody.innerHTML = '';
        if (data.history.length === 0) {
            historyTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No redemptions logged yet.</td></tr>';
        } else {
            data.history.forEach(log => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${log.date} ${log.time}</td>
                    <td><strong>${log.facility}</strong></td>
                    <td>${log.pax}</td>
                    <td>${log.outlet}</td>
                    <td>${log.staff}</td>
                `;
                historyTableBody.appendChild(tr);
            });
        }
    }

    async function redeemFacility() {
        const outletId = outletSelect.value;
        const paxUsed = parseInt(paxUsedInput.value) || 1;

        if (!selectedFacilityId) {
            alert('Please select a facility to redeem.');
            return;
        }

        if (paxUsed > selectedFacilityRemaining) {
            alert(`Cannot redeem more than the remaining quota (${selectedFacilityRemaining}).`);
            return;
        }

        redeemBtn.disabled = true;
        redeemBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Redeeming...';

        try {
            const response = await fetch('<?php echo e(route("vouchers.scan.process")); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    outlet_id: outletId,
                    qr_code: currentVoucherCode,
                    facility_template_id: selectedFacilityId,
                    pax_used: paxUsed,
                })
            });

            const res = await response.json();

            if (res.success) {
                showRedemptionSuccess(res.data);
            } else {
                showRedemptionError(res.message);
            }

        } catch (error) {
            showRedemptionError('An error occurred during redemption: ' + error.message);
        } finally {
            redeemBtn.disabled = false;
            redeemBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Redemption';
        }
    }

    function showRedemptionSuccess(data) {
        verificationSection.classList.add('d-none');
        resultSection.classList.remove('d-none');

        resultIcon.innerHTML = '<i class="fas fa-check-circle fa-5x text-success"></i>';
        resultTitle.textContent = 'Redemption Successful';
        resultMessage.className = 'alert alert-success';
        resultMessage.innerHTML = 'Facility redeemed successfully.';
        
        resultDetails.innerHTML = `
            <div class="row g-2">
                <div class="col-6 text-muted">Guest:</div>
                <div class="col-6 font-weight-bold">${data.guest}</div>
                
                <div class="col-6 text-muted">Facility Redeemed:</div>
                <div class="col-6 font-weight-bold">${data.facility}</div>
                
                <div class="col-6 text-muted">Pax Used:</div>
                <div class="col-6 font-weight-bold">${data.pax_used}</div>
                
                <div class="col-6 text-muted">Remaining Today:</div>
                <div class="col-6 font-weight-bold text-success">${data.remaining_quota}</div>
                
                <div class="col-6 text-muted">Timestamp:</div>
                <div class="col-6 font-weight-bold">${data.date} ${data.time}</div>
            </div>
        `;
    }

    function showRedemptionError(message) {
        scannerSection.classList.add('d-none');
        verificationSection.classList.add('d-none');
        resultSection.classList.remove('d-none');

        resultIcon.innerHTML = '<i class="fas fa-times-circle fa-5x text-danger"></i>';
        resultTitle.textContent = 'Redemption Failed';
        resultMessage.className = 'alert alert-danger';
        resultMessage.textContent = message;
        resultDetails.innerHTML = '';
    }

    function resetScanFlow() {
        detectedQrCode = null;
        qrCodeInput.value = '';
        paxUsedInput.value = '1';
        selectedFacilityId = null;
        currentVoucherCode = null;
        
        detectedQrDiv.classList.add('d-none');
        verificationSection.classList.add('d-none');
        resultSection.classList.add('d-none');
        scannerSection.classList.remove('d-none');
        
        if (outletSelect.value) {
            startCamera();
        }
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/vouchers/scan.blade.php ENDPATH**/ ?>