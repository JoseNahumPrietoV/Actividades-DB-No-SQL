<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre_empresa', 'rfc', 'activo', 'contacto_nombre', 'contacto_telefono', 'contacto_email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $acciones = [
                    'created' => 'creado',
                    'updated' => 'actualizado',
                    'deleted' => 'eliminado',
                ];

                return $acciones[$eventName] ?? $eventName;
            });
    }

    protected $table = 'proveedores';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = ['nombre_empresa', 'rfc', 'contacto_nombre', 'contacto_telefono', 'contacto_email', 'activo'];

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'id_proveedor', '_id');
    }
}
