<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'city',
        'zip_code',
        'street_address',
        'latitude',
        'longitude',
    ];

    // Kapcsolatok
    public function campings()
    {
        return $this->hasMany(Camping::class);
    }
}
