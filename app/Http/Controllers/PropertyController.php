<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('properties.manage'), 403);

        $properties = Property::query()->withCount(['rooms', 'bookings'])->paginate(20);

        return view('properties.index', compact('properties'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('properties.manage'), 403);

        return view('properties.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('properties.manage'), 403);

        $property = Property::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:properties'],
            'timezone' => ['required', 'string', 'timezone'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]));

        return redirect()->route('properties.show', $property)->with('success', 'Property created successfully.');
    }

    public function show(Property $property): View
    {
        abort_unless(auth()->user()?->can('properties.manage'), 403);

        $property->loadCount(['rooms', 'bookings']);

        return view('properties.show', compact('property'));
    }

    public function edit(Property $property): View
    {
        abort_unless(auth()->user()?->can('properties.manage'), 403);

        return view('properties.edit', compact('property'));
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        abort_unless(auth()->user()?->can('properties.manage'), 403);

        $property->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:properties,code,' . $property->id],
            'timezone' => ['required', 'string', 'timezone'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]));

        return redirect()->route('properties.show', $property)->with('success', 'Property updated successfully.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        abort_unless(auth()->user()?->can('properties.manage'), 403);

        $property->delete();

        return redirect()->route('properties.index')->with('success', 'Property deleted successfully.');
    }
}
