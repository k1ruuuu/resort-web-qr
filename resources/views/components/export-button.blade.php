@props(['route', 'filters' => [], 'text' => 'Export to Excel'])

<div class="btn-group" role="group">
    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-file-excel mr-1"></i> {{ $text }}
    </button>
    <ul class="dropdown-menu">
        <li>
            <h6 class="dropdown-header">Choose Format</h6>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route($route, array_merge($filters, ['format' => 'xlsx'])) }}">
                <i class="fas fa-file-excel text-success mr-1"></i> Excel (.xlsx)
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route($route, array_merge($filters, ['format' => 'xls'])) }}">
                <i class="fas fa-file-excel text-success mr-1"></i> Excel 97-2003 (.xls)
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route($route, array_merge($filters, ['format' => 'csv'])) }}">
                <i class="fas fa-file-csv text-info mr-1"></i> CSV (.csv)
            </a>
        </li>
    </ul>
</div>
