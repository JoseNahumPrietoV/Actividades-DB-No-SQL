<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Receptor;
use App\Models\User;

class ReceptorPolicy
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

    public function view(User $user, Receptor $modelInstance): bool
    {
        return $this->isShared($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Receptor $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Receptor $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Receptor $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Receptor $modelInstance): bool
    {
        return $this->isAdmin($user);
    }
}
