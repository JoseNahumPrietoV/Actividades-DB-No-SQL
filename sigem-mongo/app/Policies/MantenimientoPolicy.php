<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Mantenimiento;
use App\Models\User;

class MantenimientoPolicy
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

    public function view(User $user, Mantenimiento $modelInstance): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isShared($user) && $modelInstance->id_usuario_solicita === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isShared($user);
    }

    public function update(User $user, Mantenimiento $modelInstance): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isShared($user) 
            && $modelInstance->id_usuario_solicita === $user->id 
            && $modelInstance->estado === 'Solicitado';
    }

    public function delete(User $user, Mantenimiento $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Mantenimiento $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Mantenimiento $modelInstance): bool
    {
        return $this->isAdmin($user);
    }
}
