<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampingPhoto extends Model
{
    protected $primaryKey = 'photo_id';

    protected $fillable = [
        'camping_id',
        'photo_url',
        'caption',
    ];



    // Kapcsolatok
    public function camping()
    {
        return $this->belongsTo(Camping::class);
    }
}
