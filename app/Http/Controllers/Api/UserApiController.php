<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserApiController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorizePermission('users.manage');

        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(request()->integer('per_page', 20));

        return $this->respondPaginated($users);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorizePermission('users.manage');

        $user->load('roles');

        return $this->respond($user);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('users.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::query()->create($data);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $this->respondCreated($user->load('roles'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission('users.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $this->respond($user->fresh()->load('roles'));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorizePermission('users.manage');

        if ($user->id === auth()->id()) {
            return $this->respondError('You cannot delete your own account.', 403);
        }

        $user->delete();

        return $this->respondMessage('User deleted successfully.');
    }
}
