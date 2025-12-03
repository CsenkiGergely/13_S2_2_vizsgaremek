<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampingTag extends Model
{
    protected $fillable = [
        'camping_id',
        'tag',
    ];

    // Kapcsolatok
    public function camping()
    {
        return $this->belongsTo(Camping::class);
    }
}
