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

        $outlets = Outlet::query()
            ->with(['property', 'facilityTemplate'])
            ->orderBy('name')
            ->paginate(request()->integer('per_page', 20));

        return $this->respondPaginated($outlets);
    }

    public function show(Outlet $outlet): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $outlet->load(['property', 'facilityTemplate']);

        return $this->respond($outlet);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $outlet = Outlet::query()->create($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'facility_template_id' => ['required', 'exists:facility_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]));

        return $this->respondCreated($outlet);
    }

    public function update(Request $request, Outlet $outlet): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $outlet->update($request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'facility_template_id' => ['required', 'exists:facility_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]));

        return $this->respond($outlet);
    }

    public function destroy(Outlet $outlet): JsonResponse
    {
        $this->authorizePermission('facilities.manage');

        $outlet->delete();

        return $this->respondMessage('Outlet deleted successfully.');
    }
}
