<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'camping_id',
        'camping_spot_id',
        'arrival_date',
        'departure_date',
        'status',
        'qr_code',
    ];

    protected function casts(): array
    {
        return [
            'arrival_date' => 'date',
            'departure_date' => 'date',
        ];
    }

    // Kapcsolatok
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function camping()
    {
        return $this->belongsTo(Camping::class);
    }

    public function campingSpot()
    {
        return $this->belongsTo(CampingSpot::class, 'camping_spot_id', 'spot_id');
    }
}
