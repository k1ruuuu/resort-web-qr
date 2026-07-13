<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\FacilityTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityApiController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $facilities = FacilityTemplate::query()
            ->with('property')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(request()->integer('per_page', 20));

        return $this->respondPaginated($facilities);
    }

    public function show(FacilityTemplate $facility): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $facility->load('property');

        return $this->respond($facility);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $facility = FacilityTemplate::query()->create($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]));

        return $this->respondCreated($facility);
    }

    public function update(Request $request, FacilityTemplate $facility): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $facility->update($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]));

        return $this->respond($facility);
    }

    public function destroy(FacilityTemplate $facility): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $facility->delete();

        return $this->respondMessage('Facility deleted successfully.');
    }
}
