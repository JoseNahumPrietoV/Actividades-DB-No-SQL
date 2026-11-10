<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class HistorialEstado extends Model
{
    use HasFactory;

    protected $table = 'historial_estados';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    public $timestamps = false; // Desactiva created_at/updated_at

    protected $fillable = ['id_inventario', 'id_usuario', 'estado_anterior', 'estado_nuevo', 'motivo_cambio', 'ubicacion_fisica'];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'id_inventario', '_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', '_id');
    }
}
