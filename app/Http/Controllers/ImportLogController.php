<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use Illuminate\View\View;

class ImportLogController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('import_logs.view'), 403);

        $logs = ImportLog::query()
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('reports.import_logs', compact('logs'));
    }

    public function show(ImportLog $importLog): View
    {
        abort_unless(auth()->user()?->can('import_logs.view'), 403);

        $importLog->load('user');

        return view('reports.import_log_show', ['log' => $importLog]);
    }
}
