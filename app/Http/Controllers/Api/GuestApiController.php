<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestApiController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorizePermission('guests.manage');

        $guests = Guest::query()
            ->withCount('bookings')
            ->orderBy('last_name')
            ->paginate(request()->integer('per_page', 20));

        return $this->respondPaginated($guests);
    }

    public function show(Guest $guest): JsonResponse
    {
        $this->authorizePermission('guests.manage');

        $guest->load(['bookings.property', 'bookings.room']);

        return $this->respond($guest);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('guests.manage');

        $guest = Guest::query()->create($request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'document_id' => ['nullable', 'string', 'max:100'],
        ]));

        return $this->respondCreated($guest);
    }

    public function update(Request $request, Guest $guest): JsonResponse
    {
        $this->authorizePermission('guests.manage');

        $guest->update($request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'document_id' => ['nullable', 'string', 'max:100'],
        ]));

        return $this->respond($guest);
    }

    public function destroy(Guest $guest): JsonResponse
    {
        $this->authorizePermission('guests.manage');

        $guest->delete();

        return $this->respondMessage('Guest deleted successfully.');
    }
}
