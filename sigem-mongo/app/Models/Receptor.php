<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receptor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'receptor';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = ['nombre', 'apellido_paterno', 'apellido_materno', 'email', 'telefono', 'id_area'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area', '_id');
    }

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_receptor', '_id');
    }
}
