<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'stock_actual', 'stock_minimo', 'id_marca', 'id_tipodematerial', 'id_unidad', 'requiere_control_individual'])
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

    protected $table = 'material';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = [
        'nombre', 'descripcion', 'modelo',
        'id_unidad', 'id_marca', 'id_tipodematerial',
        'requiere_control_individual', 'stock_actual', 'stock_minimo',
    ];

    // RELACIONES: Pertenece a una unidad, marca y tipo
    public function unidad()
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad', '_id');
    }

    public function marca()
    {
        return $this->belongsTo(MarcaMaterial::class, 'id_marca', '_id');
    }

    public function tipo()
    {
        return $this->belongsTo(TipoMaterial::class, 'id_tipodematerial', '_id');
    }

    // RELACIONES: Un material tiene muchos inventarios
    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'id_producto', '_id');
    }

    // Scopes
    public function scopeStockBajo($query)
    {
        return $query->whereRaw([
            '$expr' => [
                '$lt' => ['$stock_actual', '$stock_minimo']
            ]
        ]);
    }
}
