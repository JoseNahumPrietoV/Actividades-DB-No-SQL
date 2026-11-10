<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
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

    public function view(User $user, Material $modelInstance): bool
    {
        return $this->isShared($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Material $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Material $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Material $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Material $modelInstance): bool
    {
        return $this->isAdmin($user);
    }
}
