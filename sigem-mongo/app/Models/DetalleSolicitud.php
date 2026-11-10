<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class DetalleSolicitud extends Model
{
    use HasFactory;

    protected $table = 'detalle_solicitud';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = ['cantidad', 'id_solicitud', 'id_producto', 'id_inventario'];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud', '_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'id_producto', '_id');
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', '_id');
    }
}
