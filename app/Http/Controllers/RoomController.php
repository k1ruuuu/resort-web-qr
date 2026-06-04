<?php

namespace App\Http\Controllers;

use App\Models\Property;
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

        $rooms = Room::query()
            ->with(['property', 'area', 'roomType'])
            ->orderBy('number')
            ->paginate(20);

        return view('rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();

        return view('rooms.create', compact('properties'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $room = Room::query()->create($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'number' => ['required', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['in:available,occupied,maintenance'],
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
            'number' => ['required', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['in:available,occupied,maintenance'],
        ]));

        return redirect()->route('rooms.show', $room)->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        abort_unless(auth()->user()?->can('rooms.manage'), 403);

        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Room deleted successfully.');
    }
}
