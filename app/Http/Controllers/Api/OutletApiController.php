<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Outlet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutletApiController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $outlets = $this->applyPropertyScope(Outlet::query())
            ->with(['property', 'facilityTemplates'])
            ->orderBy('name')
            ->paginate(min(request()->integer('per_page', 20), 100));

        return $this->respondPaginated($outlets);
    }

    public function show(Outlet $outlet): JsonResponse
    {
        $this->authorizePermission('facilities.manage');
        $this->authorizePropertyAccess($outlet);

        $outlet->load(['property', 'facilityTemplates']);

        return $this->respond($outlet);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $outlet = Outlet::query()->create($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'facility_template_ids' => ['required', 'array', 'min:1'],
            'facility_template_ids.*' => ['integer', 'exists:facility_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]));

        $outlet->facilityTemplates()->sync($request->validated('facility_template_ids', []));

        $outlet->load('facilityTemplates');

        return $this->respondCreated($outlet);
    }

    public function update(Request $request, Outlet $outlet): JsonResponse
    {
        $this->authorizePermission('facilities.manage');
        $this->authorizePropertyAccess($outlet);

        $outlet->update($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'facility_template_ids' => ['required', 'array', 'min:1'],
            'facility_template_ids.*' => ['integer', 'exists:facility_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]));

        $outlet->facilityTemplates()->sync($request->validated('facility_template_ids', []));

        return $this->respond($outlet->load('facilityTemplates'));
    }

    public function destroy(Outlet $outlet): JsonResponse
    {
        $this->authorizePermission('facilities.manage');
        $this->authorizePropertyAccess($outlet);

        $outlet->facilityTemplates()->detach();
        $outlet->delete();

        return $this->respondMessage('Outlet deleted successfully.');
    }
}
