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
            <p class="mb-1"><strong>Outlet:</strong> <span id="verify-outlet-name">-</span></p>
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
