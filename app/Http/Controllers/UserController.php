<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(): View
    {
        $this->authorizePermission('users.manage');

        $query = User::query()
            ->with('roles')
            ->orderBy('name');

        if (request()->filled('search')) {
            $search = trim(request('search'));
            $search = str_replace(['%', '_'], ['\%', '\_'], $search);
            if (strlen($search) > 0) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }
        }

        $users = $query->paginate(20)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorizePermission('users.manage');

        $roles = Role::query()->orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userService->create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', "User '{$user->name}' created successfully.");
    }

    public function edit(User $user): View
    {
        $this->authorizePermission('users.manage');

        $roles = Role::query()->orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizePermission('users.manage');

        if (auth()->id() === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $this->userService->delete($user);

        return redirect()
            ->route('users.index')
            ->with('success', "User '{$user->name}' deleted successfully.");
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
