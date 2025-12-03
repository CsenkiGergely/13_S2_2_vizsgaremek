<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampingSpot extends Model
{
    protected $primaryKey = 'spot_id';

    protected $fillable = [
        'camping_id',
        'name',
        'type',
        'capacity',
        'price_per_night',
        'is_available',
        'description',
        'row',
        'column',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'rating' => 'float',
        ];
    }

    // Kapcsolatok
    public function camping()
    {
        return $this->belongsTo(Camping::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'camping_spot_id', 'spot_id');
    }
}
