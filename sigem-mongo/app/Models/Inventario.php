<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Inventario extends Model
{
    use HasFactory, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['num_serie', 'estado', 'ubicacion_fisica', 'tipo_propiedad', 'id_usuario', 'estado_registro', 'aprobado', 'aprobado_por', 'fecha_aprobacion', 'fecha_baja'])
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

    protected $table = 'inventario';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'num_serie', 'id_producto', 'id_usuario', 'id_proveedor', 'estado', 'estado_registro',
        'tipo_propiedad', 'ubicacion_fisica', 'fecha_registro', 'fecha_factura', 'num_factura',
        'fecha_baja', 'fecha_inicio_renta', 'fecha_fin_renta', 'observaciones_renta',
        'observaciones_generales', 'garantia_fecha_fin', 'garantia_estado',
        'aprobado', 'aprobado_por', 'fecha_aprobacion',
    ];

    // Convertir fechas automáticamente a objetos Carbon
    protected $casts = [
        'fecha_registro' => 'date',
        'fecha_factura' => 'date',
        'fecha_baja' => 'date',
        'fecha_inicio_renta' => 'date',
        'fecha_fin_renta' => 'date',
        'garantia_fecha_fin' => 'date',
        'aprobado' => 'boolean',
        'fecha_aprobacion' => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class, 'id_producto', '_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', '_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', '_id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por', '_id');
    }

    public function detallesSolicitud()
    {
        return $this->hasMany(DetalleSolicitud::class, 'id_inventario', '_id');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'id_inventario', '_id');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstado::class, 'id_inventario', '_id');
    }

    // Scopes de estado
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'Disponible');
    }

    public function scopeAsignados($query)
    {
        return $query->where('estado', 'Asignado');
    }

    public function scopeEnMantenimiento($query)
    {
        return $query->where('estado', 'En Mantenimiento');
    }

    public function scopeDanados($query)
    {
        return $query->whereIn('estado', ['Dañado', 'Baja']);
    }
}
