<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly AuditService $audit
    ) {}

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if (!empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            $this->audit->log('user.created', $user, null, $user->toArray());

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldValues = $user->toArray();

            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);

            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            } else {
                $user->syncRoles([]);
            }

            $this->audit->log('user.updated', $user, $oldValues, $user->fresh()->toArray());

            return $user;
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $oldValues = $user->toArray();
            $user->delete();
            $this->audit->log('user.deleted', $user, $oldValues, null);
        });
    }
}
