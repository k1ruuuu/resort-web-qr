<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['route', 'filters' => [], 'text' => 'Export to Excel']));

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

foreach (array_filter((['route', 'filters' => [], 'text' => 'Export to Excel']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="btn-group" role="group">
    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-file-earmark-excel"></i> <?php echo e($text); ?>

    </button>
    <ul class="dropdown-menu">
        <li>
            <h6 class="dropdown-header">Choose Format</h6>
        </li>
        <li>
            <a class="dropdown-item" href="<?php echo e(route($route, array_merge($filters, ['format' => 'xlsx']))); ?>">
                <i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel (.xlsx)
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="<?php echo e(route($route, array_merge($filters, ['format' => 'xls']))); ?>">
                <i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel 97-2003 (.xls)
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="<?php echo e(route($route, array_merge($filters, ['format' => 'csv']))); ?>">
                <i class="bi bi-filetype-csv text-info"></i> CSV (.csv)
            </a>
        </li>
    </ul>
</div>
<?php /**PATH C:\jriw\resort-web-qr\resources\views/components/export-button.blade.php ENDPATH**/ ?>