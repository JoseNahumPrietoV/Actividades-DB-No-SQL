<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departamento';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = ['nombre'];

    // RELACIONES: Un departamento tiene muchas áreas
    public function areas()
    {
        return $this->hasMany(Area::class, 'id_departamento', '_id');
    }
}
