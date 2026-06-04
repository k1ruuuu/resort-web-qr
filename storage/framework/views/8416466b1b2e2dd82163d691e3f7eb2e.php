<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['url', 'size' => 220, 'alt' => 'QR Code']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['url', 'size' => 220, 'alt' => 'QR Code']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<img
    src="<?php echo e($url); ?>"
    alt="<?php echo e($alt); ?>"
    width="<?php echo e($size); ?>"
    height="<?php echo e($size); ?>"
    class="d-block mx-auto border bg-white p-1"
    <?php echo e($attributes); ?>

>
<?php /**PATH C:\jriw\resort-project\resources\views/components/qr-code.blade.php ENDPATH**/ ?>