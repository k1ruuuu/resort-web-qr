<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomApiController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorizePermission('rooms.manage');

        $rooms = Room::query()
            ->with(['property', 'area', 'roomType'])
            ->orderBy('number')
            ->paginate(request()->integer('per_page', 20));

        return $this->respondPaginated($rooms);
    }

    public function show(Room $room): JsonResponse
    {
        $this->authorizePermission('rooms.manage');

        $room->load(['property', 'area', 'roomType']);

        return $this->respond($room);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('rooms.manage');

        $room = Room::query()->create($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'number' => ['required', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['in:available,occupied,maintenance'],
            'bed_type' => ['nullable', 'string', 'max:32'],
            'room_view' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:64'],
        ]));

        return $this->respondCreated($room);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $this->authorizePermission('rooms.manage');

        $room->update($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'number' => ['required', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
            'room_type_id' => ['required', 'exists:room_types,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['in:available,occupied,maintenance'],
            'bed_type' => ['nullable', 'string', 'max:32'],
            'room_view' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:64'],
        ]));

        return $this->respond($room);
    }

    public function destroy(Room $room): JsonResponse
    {
        $this->authorizePermission('rooms.manage');

        $room->delete();

        return $this->respondMessage('Room deleted successfully.');
    }
}
