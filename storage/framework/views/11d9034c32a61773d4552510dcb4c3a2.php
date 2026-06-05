<?php $__env->startSection('title', 'Voucher Delivery Settings'); ?>
<?php $__env->startSection('page_title', 'Voucher Delivery Settings'); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-sliders-h me-2"></i> WhatsApp Voucher Delivery Configuration
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(route('settings.delivery.update')); ?>">
                    <?php echo csrf_field(); ?>
                    
                    <!-- Delivery Method Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-qrcode text-info"></i> Voucher Delivery Method
                        </h5>
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label font-weight-bold">Choose How to Deliver Voucher</label>
                                <select name="delivery_method" id="deliveryMethod" class="form-select <?php $__errorArgs = ['delivery_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <option value="qr_image" <?php echo e(old('delivery_method', $settings['delivery_method']) === 'qr_image' ? 'selected' : ''); ?>>
                                        QR Code Image Attachment
                                    </option>
                                    <option value="public_link" <?php echo e(old('delivery_method', $settings['delivery_method']) === 'public_link' ? 'selected' : ''); ?>>
                                        Public Guest Card Link
                                    </option>
                                </select>
                                <?php $__errorArgs = ['delivery_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        
                        <!-- Method Descriptions -->
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

                    <!-- Delivery Timing Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-clock text-warning"></i> Delivery Timing
                        </h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Enable Automatic Delivery</label>
                                <select name="automatic_enabled" class="form-select <?php $__errorArgs = ['automatic_enabled'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <option value="1" <?php echo e(old('automatic_enabled', $settings['automatic_enabled']) === '1' ? 'selected' : ''); ?>>Yes (Send instantly on Check-In)</option>
                                    <option value="0" <?php echo e(old('automatic_enabled', $settings['automatic_enabled']) === '0' ? 'selected' : ''); ?>>No</option>
                                </select>
                                <div class="form-text">If enabled, vouchers are sent automatically as soon as guest checks in.</div>
                                <?php $__errorArgs = ['automatic_enabled'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Enable Scheduled Delivery</label>
                                <select name="scheduled_enabled" class="form-select <?php $__errorArgs = ['scheduled_enabled'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <option value="1" <?php echo e(old('scheduled_enabled', $settings['scheduled_enabled']) === '1' ? 'selected' : ''); ?>>Yes</option>
                                    <option value="0" <?php echo e(old('scheduled_enabled', $settings['scheduled_enabled']) === '0' ? 'selected' : ''); ?>>No</option>
                                </select>
                                <div class="form-text">Sends vouchers at the configured time on check-in day. (Ignored if Automatic is enabled)</div>
                                <?php $__errorArgs = ['scheduled_enabled'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Default Delivery Time</label>
                                <input type="text" name="default_time" class="form-control <?php $__errorArgs = ['default_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('default_time', $settings['default_time'])); ?>" placeholder="08:00" required>
                                <div class="form-text">Format: HH:MM (24-hour). Example: 08:00, 12:00, 16:00.</div>
                                <?php $__errorArgs = ['default_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Timezone</label>
                                <input type="text" name="timezone" class="form-control <?php $__errorArgs = ['timezone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('timezone', $settings['timezone'])); ?>" placeholder="Asia/Jakarta" required>
                                <div class="form-text">System timezone used for scheduled tasks (e.g. Asia/Jakarta, WIB).</div>
                                <?php $__errorArgs = ['timezone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp API Configuration Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-brands fa-whatsapp text-success"></i> WhatsApp API Configuration
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">WhatsApp Provider</label>
                                <input type="text" name="whatsapp_provider" class="form-control <?php $__errorArgs = ['whatsapp_provider'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('whatsapp_provider', $settings['whatsapp_provider'])); ?>" required>
                                <?php $__errorArgs = ['whatsapp_provider'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Fonnte API Token</label>
                                <input type="password" name="fonnte_token" class="form-control <?php $__errorArgs = ['fonnte_token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       value="<?php echo e(old('fonnte_token', $settings['fonnte_token'])); ?>" placeholder="Enter Fonnte token...">
                                <div class="form-text">Leave blank or use mock token for simulated local testing.</div>
                                <?php $__errorArgs = ['fonnte_token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Message Template Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-comment-dots text-primary"></i> Message Template
                        </h5>
                        <label class="form-label font-weight-bold">WhatsApp Message Template</label>
                        <textarea name="message_template" id="messageTemplate" rows="8" class="form-control <?php $__errorArgs = ['message_template'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required><?php echo e(old('message_template', $settings['message_template'])); ?></textarea>
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
                        <?php $__errorArgs = ['message_template'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="invalid-feedback"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="border-top pt-3 text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i> Save Configurations
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deliveryMethod = document.getElementById('deliveryMethod');
    const qrImageInfo = document.getElementById('qrImageInfo');
    const publicLinkInfo = document.getElementById('publicLinkInfo');
    const voucherLinkBadge = document.getElementById('voucherLinkBadge');
    const voucherLinkNote = document.getElementById('voucherLinkNote');
    const messageTemplate = document.getElementById('messageTemplate');
    
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
            
            // Check if {voucher_link} is in the template
            if (!messageTemplate.value.includes('{voucher_link}')) {
                const shouldAdd = confirm('The message template does not contain {voucher_link}. Would you like to add it at the end?');
                if (shouldAdd) {
                    messageTemplate.value += '\n\nAkses Guest Card Anda:\n{voucher_link}';
                }
            }
        }
    }
    
    // Initial load
    updateMethodInfo();
    
    // On change
    deliveryMethod.addEventListener('change', updateMethodInfo);
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/settings/delivery.blade.php ENDPATH**/ ?>