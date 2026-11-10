<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    /**
     * Los atributos que se pueden asignar en masa.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'apellido_paterno',
        'apellido_materno',
        'num_control',
        'carrera',
        'RFC',
        'tipo_usuario',
        'activo',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Obtener los atributos que deben ser convertidos.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * Determina si el usuario posee el rol especificado (compatible con Spatie / NoSQL).
     *
     * @param  string|\BackedEnum|array  $roles
     * @return bool
     */
    public function hasRole(mixed $roles): bool
    {
        $currentRole = $this->tipo_usuario ?? $this->rol;

        if ($currentRole === null) {
            return false;
        }

        if ($currentRole instanceof \BackedEnum) {
            $currentRole = $currentRole->value;
        }

        if (! is_array($roles) && ! ($roles instanceof \Illuminate\Support\Collection)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            if ($role instanceof \BackedEnum) {
                $role = $role->value;
            }

            if (strcasecmp((string) $role, (string) $currentRole) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determina si el usuario posee cualquiera de los roles dados.
     */
    public function hasAnyRole(mixed ...$roles): bool
    {
        $flattened = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $flattened = array_merge($flattened, $role);
            } else {
                $flattened[] = $role;
            }
        }

        return $this->hasRole($flattened);
    }

    /**
     * Compatibilidad con chequeos de permisos de Spatie en entorno NoSQL.
     */
    public function hasPermissionTo(mixed $permission, ?string $guardName = null): bool
    {
        if ($this->hasRole([RoleEnum::ADMIN->value, 'Administrador', 'admin'])) {
            return true;
        }

        if ($permission === RoleEnum::PERM_ACCESS_SERVICIO) {
            return $this->hasRole([RoleEnum::SERVICIO_SOCIAL->value, RoleEnum::SERVICIO_TIPO->value, 'Servicio Social', 'Servicio']);
        }

        return false;
    }

    /**
     * Método requerido por Filament para permitir el acceso al panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->activo === false) {
            return false;
        }

        if ($panel->getId() === 'admin') {
            return $this->hasRole([RoleEnum::ADMIN->value, 'Administrador', 'admin']);
        }

        if ($panel->getId() === 'servicio-social') {
            return $this->hasRole([RoleEnum::SERVICIO_SOCIAL->value, RoleEnum::SERVICIO_TIPO->value, 'Servicio Social', 'Servicio']);
        }

        return false;
    }

    // ==========================================
    // RELACIONES CON OTROS MODELOS
    // ==========================================

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'id_usuario', '_id');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_usuario', '_id');
    }

    public function autorizaciones()
    {
        return $this->hasMany(Solicitud::class, 'autorizado_por', '_id');
    }

    public function mantenimientosSolicitados()
    {
        return $this->hasMany(Mantenimiento::class, 'id_usuario_solicita', '_id');
    }

    public function historialEstados()
    {
        return $this->hasMany(HistorialEstado::class, 'id_usuario', '_id');
    }
}
