<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\ImportLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $query = Guest::query()->orderBy('last_name');

        if (request()->filled('search')) {
            $search = trim(request('search'));
            
            if (strlen($search) > 0) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('document_id', 'like', "%{$search}%");
                });
            }
        }

        $guests = $query->paginate(20)->withQueryString();

        return view('guests.index', compact('guests'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        return view('guests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $guest = Guest::query()->create($request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'document_id' => ['nullable', 'string', 'max:64'],
        ]));

        return redirect()->route('guests.show', $guest)->with('success', 'Guest created successfully.');
    }

    public function show(Guest $guest): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $guest->load('bookings');

        return view('guests.show', compact('guest'));
    }

    public function edit(Guest $guest): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        return view('guests.edit', compact('guest'));
    }

    public function import(): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        return view('guests.import');
    }

    public function processImport(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,xls,xlsx,cvs,txt', 'max:10240'],
        ]);

        try {
            $import = new \App\Imports\GuestsImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $message = "Import completed. {$import->getImported()} guests imported";
            
            if ($import->getSkipped() > 0) {
                $message .= ", {$import->getSkipped()} skipped (duplicates)";
            }

            $errors = [];
            if (count($import->getFailures()) > 0) {
                $message .= ", " . count($import->getFailures()) . " failed";
                session()->flash('import_failures', $import->getFailures());
                foreach ($import->getFailures() as $failure) {
                    $errors[] = [
                        'row' => $failure->row(),
                        'attribute' => $failure->attribute(),
                        'errors' => $failure->errors(),
                        'values' => $failure->values(),
                    ];
                }
            }

            if (count($import->getErrors()) > 0) {
                session()->flash('import_errors', $import->getErrors());
                foreach ($import->getErrors() as $error) {
                    $errors[] = ['message' => (string) $error];
                }
            }

            ImportLog::create([
                'type' => 'guests',
                'filename' => $request->file('file')->getClientOriginalName(),
                'user_id' => auth()->id(),
                'total_rows' => $import->getImported() + $import->getSkipped() + count($import->getFailures()),
                'imported' => $import->getImported(),
                'skipped' => $import->getSkipped(),
                'failed' => count($import->getFailures()) + count($import->getErrors()),
                'errors' => !empty($errors) ? $errors : null,
                'status' => count($import->getErrors()) > 0 ? 'partial' : 'completed',
            ]);

            return redirect()->route('guests.index')->with('success', $message);
        } catch (\Exception $e) {
            ImportLog::create([
                'type' => 'guests',
                'filename' => $request->file('file')->getClientOriginalName(),
                'user_id' => auth()->id(),
                'total_rows' => 0,
                'imported' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => [['message' => $e->getMessage()]],
                'status' => 'failed',
            ]);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $export = new \App\Exports\GuestsTemplateExport();
        return \Maatwebsite\Excel\Facades\Excel::download($export, 'guests-import-template.xlsx');
    }

    public function update(Request $request, Guest $guest): RedirectResponse
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $guest->update($request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'document_id' => ['nullable', 'string', 'max:64'],
        ]));

        return redirect()->route('guests.show', $guest)->with('success', 'Guest updated successfully.');
    }

    public function destroy(Guest $guest): RedirectResponse
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $guest->delete();

        return redirect()->route('guests.index')->with('success', 'Guest deleted successfully.');
    }
}
