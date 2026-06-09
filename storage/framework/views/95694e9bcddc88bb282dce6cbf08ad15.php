<?php $__env->startSection('title', 'Redeem Voucher (Manual)'); ?>
<?php $__env->startSection('page_title', 'Redeem Voucher (Manual)'); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow">
            <div class="card-body">
                <!-- Step 1: Input QR / Secure Token -->
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

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Secure Token / QR Code Text</label>
                        <div class="input-group">
                            <input type="text" id="qr-code-input" class="form-control" placeholder="Enter secure token or QR code string (e.g., Budi+TH01+Treehouse01+2026-06-05)...">
                            <button class="btn btn-primary" type="button" id="verify-manual-btn">Verify Voucher</button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Verification Panel -->
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
                            <i class="fas fa-times"></i> Cancel & Try Another
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
                        <i class="fas fa-arrow-left"></i> Enter Another Voucher
                    </button>
                </div>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const outletSelect = document.getElementById('outlet-select');
    const qrCodeInput = document.getElementById('qr-code-input');
    const verifyManualBtn = document.getElementById('verify-manual-btn');
    const paxUsedInput = document.getElementById('pax-used-input');
    const redeemBtn = document.getElementById('redeem-btn');
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

    let currentVoucherCode = null;
    let selectedFacilityId = null;
    let selectedFacilityRemaining = 0;

    verifyManualBtn.addEventListener('click', verifyManual);
    redeemBtn.addEventListener('click', redeemFacility);
    scanAgainBtn.addEventListener('click', resetScanFlow);
    cancelVerifyBtn.addEventListener('click', resetScanFlow);

    function verifyManual() {
        const val = qrCodeInput.value.trim();
        if (!val) {
            alert('Please enter a secure token or QR code text.');
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
            verifyManualBtn.textContent = 'Verify Voucher';
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
        qrCodeInput.value = '';
        paxUsedInput.value = '1';
        selectedFacilityId = null;
        currentVoucherCode = null;
        
        verificationSection.classList.add('d-none');
        resultSection.classList.add('d-none');
        scannerSection.classList.remove('d-none');
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/vouchers/redeem.blade.php ENDPATH**/ ?>