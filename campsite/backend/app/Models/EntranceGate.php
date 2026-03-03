<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntranceGate extends Model
{
    protected $fillable = [
        'camping_id',
        'name',
        'auth_token',
        'timestamp',
        'gate_id',
        'opening_time',
        'closing_time',
    ];

    protected $hidden = ['auth_token'];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
        ];
    }

    // Kapcsolatok
    public function camping()
    {
        return $this->belongsTo(Camping::class);
    }
}
