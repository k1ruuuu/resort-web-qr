@props(['route', 'filters' => [], 'text' => 'Export to Excel'])

<div class="btn-group" role="group">
    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-file-earmark-excel"></i> {{ $text }}
    </button>
    <ul class="dropdown-menu">
        <li>
            <h6 class="dropdown-header">Choose Format</h6>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route($route, array_merge($filters, ['format' => 'xlsx'])) }}">
                <i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel (.xlsx)
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route($route, array_merge($filters, ['format' => 'xls'])) }}">
                <i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel 97-2003 (.xls)
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route($route, array_merge($filters, ['format' => 'csv'])) }}">
                <i class="bi bi-filetype-csv text-info"></i> CSV (.csv)
            </a>
        </li>
    </ul>
</div>
