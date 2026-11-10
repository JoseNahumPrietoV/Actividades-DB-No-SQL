<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarcaMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marca_material';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = ['nombre'];

    // RELACIONES: Una marca tiene muchos materiales
    public function materiales()
    {
        return $this->hasMany(Material::class, 'id_marca', '_id');
    }
}
