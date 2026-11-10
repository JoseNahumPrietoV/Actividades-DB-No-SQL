<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Area;
use App\Models\User;

class AreaPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->tipo_usuario === RoleEnum::ADMIN->value;
    }

    private function isShared(User $user): bool
    {
        return $this->isAdmin($user) || $user->tipo_usuario === RoleEnum::SERVICIO_TIPO->value;
    }

    public function viewAny(User $user): bool
    {
        return $this->isShared($user);
    }

    public function view(User $user, Area $modelInstance): bool
    {
        return $this->isShared($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Area $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Area $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Area $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Area $modelInstance): bool
    {
        return $this->isAdmin($user);
    }
}
