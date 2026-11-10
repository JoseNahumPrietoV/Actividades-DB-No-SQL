<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'area';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = ['nombre', 'id_departamento'];

    // RELACIONES: Un área pertenece a un departamento
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'id_departamento', '_id');
    }

    // RELACIONES: Un área tiene muchos receptores
    public function receptores()
    {
        return $this->hasMany(Receptor::class, 'id_area', '_id');
    }
}
