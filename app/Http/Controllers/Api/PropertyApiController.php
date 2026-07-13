<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyApiController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorizePermission('properties.manage');

        $properties = Property::query()
            ->withCount(['rooms', 'bookings'])
            ->orderBy('name')
            ->paginate(request()->integer('per_page', 20));

        return $this->respondPaginated($properties);
    }

    public function show(Property $property): JsonResponse
    {
        $this->authorizePermission('properties.manage');

        $property->loadCount(['rooms', 'bookings']);

        return $this->respond($property);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('properties.manage');

        $property = Property::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:properties'],
            'timezone' => ['required', 'string', 'timezone'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]));

        return $this->respondCreated($property);
    }

    public function update(Request $request, Property $property): JsonResponse
    {
        $this->authorizePermission('properties.manage');

        $property->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:properties,code,' . $property->id],
            'timezone' => ['required', 'string', 'timezone'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]));

        return $this->respond($property);
    }

    public function destroy(Property $property): JsonResponse
    {
        $this->authorizePermission('properties.manage');

        $property->delete();

        return $this->respondMessage('Property deleted successfully.');
    }
}
