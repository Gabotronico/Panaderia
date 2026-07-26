<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MermaInsumo extends Model
{
    protected $table = 'mermas_insumos';

    protected $fillable = [
        'insumo_id',
        'user_id',
        'cantidad',
        'motivo',
        'observaciones',
        'fecha',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'fecha'    => 'date',
    ];

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
