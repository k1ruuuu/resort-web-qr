<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Models\Property;
use App\Models\ImportLog;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $rooms = $this->applyPropertyScope(Room::query())
            ->with(['property', 'area', 'roomType'])
            ->orderBy('number')
            ->paginate(20);

        return view('rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();
        $roomTypes = \App\Models\RoomType::query()->orderBy('name')->get();
        $areas = \App\Models\Area::query()->orderBy('name')->get();

        return view('rooms.create', compact('properties', 'roomTypes', 'areas'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $room = Room::query()->create($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'number' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('rooms', 'number')->where('property_id', $request->property_id)],
            'code' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => [\Illuminate\Validation\Rule::enum(RoomStatus::class)],
            'bed_type' => ['nullable', 'string', 'max:32'],
            'room_view' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:64'],
        ]));

        return redirect()->route('rooms.show', $room)->with('success', 'Room created successfully.');
    }

    public function show(Room $room): View
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $room->load(['property', 'area', 'roomType']);

        return view('rooms.show', compact('room'));
    }

    public function edit(Room $room): View
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $room->load(['property', 'area', 'roomType']);
        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();
        $roomTypes = $room->property->roomTypes()->orderBy('name')->get();
        $areas = $room->property->areas()->orderBy('name')->get();

        return view('rooms.edit', compact('room', 'properties', 'roomTypes', 'areas'));
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $room->update($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'number' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('rooms', 'number')->where('property_id', $request->property_id)->ignore($room->id)],
            'code' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => [\Illuminate\Validation\Rule::enum(RoomStatus::class)],
            'bed_type' => ['nullable', 'string', 'max:32'],
            'room_view' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:64'],
        ]));

        return redirect()->route('rooms.show', $room)->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Room deleted successfully.');
    }

    public function import(): View
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        return view('rooms.import');
    }

    public function processImport(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,xls,xlsx,cvs,txt', 'max:10240'],
        ]);

        try {
            $import = new \App\Imports\RoomsImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $message = "Import completed. {$import->getImported()} rooms imported";

            if ($import->getSkipped() > 0) {
                $message .= ", {$import->getSkipped()} skipped (duplicates or errors)";
            }

            $errors = [];
            if (count($import->getFailures()) > 0) {
                $message .= ', ' . count($import->getFailures()) . ' failed';
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
                'type' => 'rooms',
                'filename' => $request->file('file')->getClientOriginalName(),
                'user_id' => auth()->id(),
                'total_rows' => $import->getImported() + $import->getSkipped() + count($import->getFailures()),
                'imported' => $import->getImported(),
                'skipped' => $import->getSkipped(),
                'failed' => count($import->getFailures()) + count($import->getErrors()),
                'errors' => !empty($errors) ? $errors : null,
                'status' => count($import->getErrors()) > 0 ? 'partial' : 'completed',
            ]);

            return redirect()->route('rooms.index')->with('success', $message);
        } catch (\Exception $e) {
            ImportLog::create([
                'type' => 'rooms',
                'filename' => $request->file('file')->getClientOriginalName(),
                'user_id' => auth()->id(),
                'total_rows' => 0,
                'imported' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => [['message' => $e->getMessage()]],
                'status' => 'failed',
            ]);
            \Log::error('Room import failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Import failed. Please check the file and try again.');
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $export = new \App\Exports\RoomsTemplateExport();

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'rooms-import-template.xlsx');
    }
}
