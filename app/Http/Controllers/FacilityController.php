<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Models\FacilityTemplate;
use App\Models\Property;
use App\Services\FacilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function __construct(
        private readonly FacilityService $facilityService
    ) {}

    public function index(): View
    {
        $this->authorizePermission('facilities.manage');

        $facilities = FacilityTemplate::query()
            ->with('property')
            ->orderBy('name')
            ->paginate(20);

        return view('facilities.index', compact('facilities'));
    }

    public function create(): View
    {
        $this->authorizePermission('facilities.manage');

        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();

        return view('facilities.create', compact('properties'));
    }

    public function store(StoreFacilityRequest $request): RedirectResponse
    {
        $facility = $this->facilityService->create($request->validated());

        return redirect()
            ->route('facilities.index')
            ->with('success', "Facility '{$facility->name}' created successfully.");
    }

    public function edit(FacilityTemplate $facility): View
    {
        $this->authorizePermission('facilities.manage');

        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();

        return view('facilities.edit', compact('facility', 'properties'));
    }

    public function update(UpdateFacilityRequest $request, FacilityTemplate $facility): RedirectResponse
    {
        $this->facilityService->update($facility, $request->validated());

        return redirect()
            ->route('facilities.index')
            ->with('success', "Facility '{$facility->name}' updated successfully.");
    }

    public function destroy(FacilityTemplate $facility): RedirectResponse
    {
        $this->authorizePermission('facilities.manage');

        $this->facilityService->delete($facility);

        return redirect()
            ->route('facilities.index')
            ->with('success', "Facility '{$facility->name}' deleted successfully.");
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
