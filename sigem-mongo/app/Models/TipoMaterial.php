<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_material';

    protected $primaryKey = '_id';
    protected $keyType = 'string';

    protected $fillable = ['nombre'];

    public function materiales()
    {
        return $this->hasMany(Material::class, 'id_tipodematerial', '_id');
    }
}
