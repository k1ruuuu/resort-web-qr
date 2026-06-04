<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $guests = Guest::query()->orderBy('last_name')->paginate(20);

        return view('guests.index', compact('guests'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        return view('guests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $guest = Guest::query()->create($request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'document_id' => ['nullable', 'string', 'max:64'],
        ]));

        return redirect()->route('guests.show', $guest)->with('success', 'Guest created successfully.');
    }

    public function show(Guest $guest): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $guest->load('bookings');

        return view('guests.show', compact('guest'));
    }

    public function edit(Guest $guest): View
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        return view('guests.edit', compact('guest'));
    }

    public function update(Request $request, Guest $guest): RedirectResponse
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $guest->update($request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'document_id' => ['nullable', 'string', 'max:64'],
        ]));

        return redirect()->route('guests.show', $guest)->with('success', 'Guest updated successfully.');
    }

    public function destroy(Guest $guest): RedirectResponse
    {
        abort_unless(auth()->user()?->can('guests.manage'), 403);

        $guest->delete();

        return redirect()->route('guests.index')->with('success', 'Guest deleted successfully.');
    }
}
