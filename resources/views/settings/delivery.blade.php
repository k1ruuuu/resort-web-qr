@extends('layouts.app')
@section('title', 'Voucher Delivery Settings')
@section('page_title', 'Voucher Delivery Settings')
@section('content')
<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-sliders-h me-2"></i> WhatsApp Voucher Delivery Configuration
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.delivery.update') }}">
                    @csrf
                    
                    <!-- WhatsApp Delivery Toggle -->
                    <div class="mb-4">
                        <div class="card border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1">
                                            <i class="fab fa-whatsapp text-success me-2"></i>
                                            WhatsApp Delivery Function
                                        </h5>
                                        <p class="text-muted mb-0">
                                            <small>Enable or disable automatic WhatsApp voucher delivery</small>
                                        </p>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="spinner-border spinner-border-sm text-primary me-3" id="toggleSpinner" style="display: none;"></div>
                                        <div class="form-check form-switch" style="font-size: 1.5rem;">
                                            <input 
                                                class="form-check-input" 
                                                type="checkbox" 
                                                role="switch" 
                                                id="whatsappEnabled" 
                                                name="whatsapp_enabled"
                                                value="1"
                                                {{ old('whatsapp_enabled', $settings['whatsapp_enabled'] ?? '1') === '1' ? 'checked' : '' }}
                                                onchange="saveWhatsAppToggle(this)">
                                            <label class="form-check-label ms-2" for="whatsappEnabled" style="font-size: 1rem;">
                                                <span id="toggleLabel">{{ old('whatsapp_enabled', $settings['whatsapp_enabled'] ?? '1') === '1' ? 'Active' : 'Inactive' }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-warning mt-3 mb-0" id="inactiveWarning" style="display: none;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Warning:</strong> WhatsApp delivery is currently disabled. Vouchers will not be sent automatically.
                                </div>
                                <div class="alert alert-success mt-3 mb-0" id="toggleSuccess" style="display: none;">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Saved!</strong> <span id="toggleSuccessMessage"></span>
                                </div>
                                <div class="alert alert-danger mt-3 mb-0" id="toggleError" style="display: none;">
                                    <i class="fas fa-times-circle"></i>
                                    <strong>Error:</strong> <span id="toggleErrorMessage"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Settings Container -->
                    <div id="whatsappSettingsContainer">
                    
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-qrcode text-info"></i> Voucher Delivery Method
                        </h5>
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label font-weight-bold">Choose How to Deliver Voucher</label>
                                <select name="delivery_method" id="deliveryMethod" class="form-select @error('delivery_method') is-invalid @enderror" required>
                                    <option value="qr_image" {{ old('delivery_method', $settings['delivery_method']) === 'qr_image' ? 'selected' : '' }}>
                                        <i class="fas fa-image"></i> QR Code Image Attachment
                                    </option>
                                    <option value="public_link" {{ old('delivery_method', $settings['delivery_method']) === 'public_link' ? 'selected' : '' }}>
                                        <i class="fas fa-link"></i> Public Guest Card Link
                                    </option>
                                </select>
                                @error('delivery_method')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3" id="qrImageInfo" style="display: none;">
                            <i class="fas fa-info-circle"></i>
                            <strong>QR Code Image:</strong> The QR code will be sent as an image attachment in WhatsApp. 
                            The guest can scan it directly from their phone or show it at the outlet.
                        </div>
                        <div class="alert alert-info mt-3" id="publicLinkInfo" style="display: none;">
                            <i class="fas fa-info-circle"></i>
                            <strong>Public Guest Card Link:</strong> A web link to the guest card will be sent. 
                            When opened, it displays the guest's information and QR code on a webpage. 
                            Use placeholder <span class="badge bg-secondary font-monospace">{voucher_link}</span> in your message template.
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-clock text-warning"></i> Delivery Timing
                        </h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Enable Automatic Delivery</label>
                                <select name="automatic_enabled" class="form-select @error('automatic_enabled') is-invalid @enderror" required>
                                    <option value="1" {{ old('automatic_enabled', $settings['automatic_enabled']) === '1' ? 'selected' : '' }}>Yes (Send instantly on Check-In)</option>
                                    <option value="0" {{ old('automatic_enabled', $settings['automatic_enabled']) === '0' ? 'selected' : '' }}>No</option>
                                </select>
                                <div class="form-text">If enabled, vouchers are sent automatically as soon as guest checks in.</div>
                                @error('automatic_enabled')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Enable Scheduled Delivery</label>
                                <select name="scheduled_enabled" class="form-select @error('scheduled_enabled') is-invalid @enderror" required>
                                    <option value="1" {{ old('scheduled_enabled', $settings['scheduled_enabled']) === '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('scheduled_enabled', $settings['scheduled_enabled']) === '0' ? 'selected' : '' }}>No</option>
                                </select>
                                <div class="form-text">Sends vouchers at the configured time on check-in day. Can work together with automatic delivery.</div>
                                @error('scheduled_enabled')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Both delivery methods can be enabled simultaneously:
                            <ul class="mb-0 mt-2">
                                <li><strong>Automatic only:</strong> Sends immediately on check-in</li>
                                <li><strong>Scheduled only:</strong> Sends at configured time on check-in day</li>
                                <li><strong>Both enabled:</strong> Sends immediately on check-in AND schedules another delivery for configured time</li>
                            </ul>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Default Delivery Time</label>
                                <input type="text" name="default_time" class="form-control @error('default_time') is-invalid @enderror" 
                                       value="{{ old('default_time', $settings['default_time']) }}" placeholder="08:00" required>
                                <div class="form-text">Format: HH:MM (24-hour). Example: 08:00, 12:00, 16:00.</div>
                                @error('default_time')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Timezone</label>
                                <input type="text" name="timezone" class="form-control @error('timezone') is-invalid @enderror" 
                                       value="{{ old('timezone', $settings['timezone']) }}" placeholder="Asia/Jakarta" required>
                                <div class="form-text">System timezone used for scheduled tasks (e.g. Asia/Jakarta, WIB).</div>
                                @error('timezone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fab fa-whatsapp text-success"></i> WhatsApp API Configuration
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">WhatsApp Provider</label>
                                <input type="text" name="whatsapp_provider" class="form-control @error('whatsapp_provider') is-invalid @enderror" 
                                       value="{{ old('whatsapp_provider', $settings['whatsapp_provider']) }}" required>
                                @error('whatsapp_provider')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Fonnte API Token</label>
                                <input type="password" name="fonnte_token" class="form-control @error('fonnte_token') is-invalid @enderror" 
                                       value="{{ old('fonnte_token', $settings['fonnte_token']) }}" placeholder="Enter Fonnte token...">
                                <div class="form-text">Leave blank or use mock token for simulated local testing.</div>
                                @error('fonnte_token')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-phone text-info"></i> Phone Number Filter
                        </h5>
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label font-weight-bold">Delivery Filter Mode</label>
                                <select name="phone_filter_mode" id="phoneFilterMode" class="form-select @error('phone_filter_mode') is-invalid @enderror" required>
                                    <option value="global" {{ old('phone_filter_mode', $settings['phone_filter_mode']) === 'global' ? 'selected' : '' }}>
                                        <i class="fas fa-globe"></i> Allow All Numbers (Global)
                                    </option>
                                    <option value="indonesian_only" {{ old('phone_filter_mode', $settings['phone_filter_mode']) === 'indonesian_only' ? 'selected' : '' }}>
                                        <i class="fas fa-flag"></i> Indonesian Numbers Only (+62)
                                    </option>
                                </select>
                                @error('phone_filter_mode')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3" id="globalFilterInfo">
                            <i class="fas fa-info-circle"></i>
                            <strong>Global Mode:</strong> Messages will be sent to all phone numbers regardless of country code.
                        </div>
                        <div class="alert alert-warning mt-3" id="indonesianOnlyInfo" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Indonesian Only Mode:</strong> Only Indonesian phone numbers will receive messages. 
                            Phone numbers must start with +62, 62, 08, or 8. Foreign numbers will be blocked and logged.
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-comment-dots text-primary"></i> Message Template
                        </h5>
                        <label class="form-label font-weight-bold">WhatsApp Message Template</label>
                        <textarea name="message_template" id="messageTemplate" rows="8" class="form-control @error('message_template') is-invalid @enderror" required>{{ old('message_template', $settings['message_template']) }}</textarea>
                        <div class="form-text text-muted mt-2">
                            <strong>Available variables:</strong><br>
                            <span class="badge bg-secondary font-monospace me-1">{guest_name}</span>
                            <span class="badge bg-secondary font-monospace me-1">{room_code}</span>
                            <span class="badge bg-secondary font-monospace me-1">{total_pax}</span>
                            <span class="badge bg-warning font-monospace me-1" id="voucherLinkBadge">{voucher_link}</span>
                            <small class="d-block mt-1 text-info" id="voucherLinkNote">
                                <i class="fas fa-lightbulb"></i> Use {voucher_link} when "Public Guest Card Link" method is selected
                            </small>
                        </div>
                        @error('message_template')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="border-top pt-3 text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i> Save Configurations
                        </button>
                    </div>
                    
                    </div><!-- End whatsappSettingsContainer -->
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function saveWhatsAppToggle(checkbox) {
    const isEnabled = checkbox.checked;
    const spinner = document.getElementById('toggleSpinner');
    const successAlert = document.getElementById('toggleSuccess');
    const successMessage = document.getElementById('toggleSuccessMessage');
    const errorAlert = document.getElementById('toggleError');
    const errorMessage = document.getElementById('toggleErrorMessage');
    
    // Show spinner
    spinner.style.display = 'inline-block';
    checkbox.disabled = true;
    
    // Hide previous messages
    successAlert.style.display = 'none';
    errorAlert.style.display = 'none';
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Send AJAX request
    fetch('{{ route("settings.delivery.toggle-whatsapp") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            enabled: isEnabled ? 1 : 0
        })
    })
    .then(response => response.json())
    .then(data => {
        spinner.style.display = 'none';
        checkbox.disabled = false;
        
        if (data.success) {
            // Update UI
            toggleWhatsAppSettings(isEnabled);
            
            // Show success message
            successMessage.textContent = data.message;
            successAlert.style.display = 'block';
            
            // Hide success message after 3 seconds
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, 3000);
        } else {
            // Revert checkbox
            checkbox.checked = !isEnabled;
            
            // Show error message
            errorMessage.textContent = data.message || 'Failed to save toggle state.';
            errorAlert.style.display = 'block';
        }
    })
    .catch(error => {
        spinner.style.display = 'none';
        checkbox.disabled = false;
        
        // Revert checkbox
        checkbox.checked = !isEnabled;
        
        // Show error message
        errorMessage.textContent = 'Network error. Please try again.';
        errorAlert.style.display = 'block';
        
        console.error('Error:', error);
    });
}

function toggleWhatsAppSettings(isEnabled) {
    const container = document.getElementById('whatsappSettingsContainer');
    const label = document.getElementById('toggleLabel');
    const warning = document.getElementById('inactiveWarning');
    
    if (isEnabled) {
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
        label.textContent = 'Active';
        label.classList.remove('text-danger');
        label.classList.add('text-success');
        warning.style.display = 'none';
    } else {
        container.style.opacity = '0.5';
        container.style.pointerEvents = 'none';
        label.textContent = 'Inactive';
        label.classList.remove('text-success');
        label.classList.add('text-danger');
        warning.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize toggle state
    const whatsappToggle = document.getElementById('whatsappEnabled');
    toggleWhatsAppSettings(whatsappToggle.checked);
    
    const deliveryMethod = document.getElementById('deliveryMethod');
    const qrImageInfo = document.getElementById('qrImageInfo');
    const publicLinkInfo = document.getElementById('publicLinkInfo');
    const voucherLinkBadge = document.getElementById('voucherLinkBadge');
    const voucherLinkNote = document.getElementById('voucherLinkNote');
    const messageTemplate = document.getElementById('messageTemplate');
    
    // Phone filter mode info
    const phoneFilterMode = document.getElementById('phoneFilterMode');
    const globalFilterInfo = document.getElementById('globalFilterInfo');
    const indonesianOnlyInfo = document.getElementById('indonesianOnlyInfo');
    
    function updateMethodInfo() {
        const selectedMethod = deliveryMethod.value;
        
        if (selectedMethod === 'qr_image') {
            qrImageInfo.style.display = 'block';
            publicLinkInfo.style.display = 'none';
            voucherLinkBadge.style.display = 'none';
            voucherLinkNote.style.display = 'none';
        } else {
            qrImageInfo.style.display = 'none';
            publicLinkInfo.style.display = 'block';
            voucherLinkBadge.style.display = 'inline-block';
            voucherLinkNote.style.display = 'block';
            
            if (!messageTemplate.value.includes('{voucher_link}')) {
                const shouldAdd = confirm('The message template does not contain {voucher_link}. Would you like to add it at the end?');
                if (shouldAdd) {
                    messageTemplate.value += '\n\nAkses Guest Card Anda:\n{voucher_link}';
                }
            }
        }
    }
    
    function updatePhoneFilterInfo() {
        const selectedMode = phoneFilterMode.value;
        
        if (selectedMode === 'global') {
            globalFilterInfo.style.display = 'block';
            indonesianOnlyInfo.style.display = 'none';
        } else {
            globalFilterInfo.style.display = 'none';
            indonesianOnlyInfo.style.display = 'block';
        }
    }
    
    updateMethodInfo();
    updatePhoneFilterInfo();
    
    deliveryMethod.addEventListener('change', updateMethodInfo);
    phoneFilterMode.addEventListener('change', updatePhoneFilterInfo);
});
</script>
@endpush
@endsection
