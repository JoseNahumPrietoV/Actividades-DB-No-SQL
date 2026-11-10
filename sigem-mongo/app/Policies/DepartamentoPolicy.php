<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Departamento;
use App\Models\User;

class DepartamentoPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->tipo_usuario === RoleEnum::ADMIN->value;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Departamento $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Departamento $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Departamento $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Departamento $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Departamento $modelInstance): bool
    {
        return $this->isAdmin($user);
    }
}
