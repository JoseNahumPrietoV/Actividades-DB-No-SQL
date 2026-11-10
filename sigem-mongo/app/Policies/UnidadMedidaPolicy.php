<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\UnidadMedida;
use App\Models\User;

class UnidadMedidaPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->tipo_usuario === RoleEnum::ADMIN->value;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, UnidadMedida $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, UnidadMedida $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, UnidadMedida $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, UnidadMedida $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, UnidadMedida $modelInstance): bool
    {
        return $this->isAdmin($user);
    }
}
