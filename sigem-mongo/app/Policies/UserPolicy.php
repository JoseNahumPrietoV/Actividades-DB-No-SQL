<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

class UserPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->tipo_usuario === RoleEnum::ADMIN->value;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, User $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, User $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, User $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, User $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, User $modelInstance): bool
    {
        return $this->isAdmin($user);
    }
}
