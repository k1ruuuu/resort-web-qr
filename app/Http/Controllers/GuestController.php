<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $query = Guest::query()->orderBy('last_name');

        // Search filter with SQL injection protection
        if (request()->filled('search')) {
            $search = request('search');
            // SECURITY FIX: Sanitize search input to prevent SQL injection
            $search = preg_replace('/[^\w\s@.\-+]/', '', $search);
            $search = trim($search);
            
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

            if (count($import->getFailures()) > 0) {
                $message .= ", " . count($import->getFailures()) . " failed";
                session()->flash('import_failures', $import->getFailures());
            }

            if (count($import->getErrors()) > 0) {
                session()->flash('import_errors', $import->getErrors());
            }

            return redirect()->route('guests.index')->with('success', $message);
        } catch (\Exception $e) {
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
