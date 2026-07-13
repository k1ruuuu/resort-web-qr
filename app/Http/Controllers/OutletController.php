<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutletRequest;
use App\Http\Requests\UpdateOutletRequest;
use App\Models\Outlet;
use App\Models\Property;
use App\Models\FacilityTemplate;
use App\Services\OutletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OutletController extends Controller
{
    public function __construct(
        private readonly OutletService $outletService
    ) {}

    public function index(): View
    {
        $this->authorizePermission('facilities.manage');

        $outlets = Outlet::query()
            ->with(['property', 'facilityTemplate'])
            ->orderBy('name')
            ->paginate(20);

        return view('outlets.index', compact('outlets'));
    }

    public function create(): View
    {
        $this->authorizePermission('facilities.manage');

        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();
        $facilityTemplates = FacilityTemplate::query()->where('is_active', true)->orderBy('name')->get();

        return view('outlets.create', compact('properties', 'facilityTemplates'));
    }

    public function store(StoreOutletRequest $request): RedirectResponse
    {
        $outlet = $this->outletService->create($request->validated());

        return redirect()
            ->route('outlets.index')
            ->with('success', "Outlet '{$outlet->name}' created successfully.");
    }

    public function edit(Outlet $outlet): View
    {
        $this->authorizePermission('facilities.manage');

        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();
        $facilityTemplates = FacilityTemplate::query()->where('is_active', true)->orderBy('name')->get();

        return view('outlets.edit', compact('outlet', 'properties', 'facilityTemplates'));
    }

    public function update(UpdateOutletRequest $request, Outlet $outlet): RedirectResponse
    {
        $this->outletService->update($outlet, $request->validated());

        return redirect()
            ->route('outlets.index')
            ->with('success', "Outlet '{$outlet->name}' updated successfully.");
    }

    public function destroy(Outlet $outlet): RedirectResponse
    {
        $this->authorizePermission('facilities.manage');

        $this->outletService->delete($outlet);

        return redirect()
            ->route('outlets.index')
            ->with('success', "Outlet '{$outlet->name}' deleted successfully.");
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
