<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camping extends Model
{
    protected $fillable = [
        'user_id',
        'camping_name',
        'owner_first_name',
        'owner_last_name',
        'location_id',
        'description',
        'company_name',
        'tax_id',
        'billing_address',
    ];

    // Kapcsolatok
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function tags()
    {
        return $this->hasMany(CampingTag::class);
    }

    public function spots()
    {
        return $this->hasMany(CampingSpot::class);
    }

    public function photos()
    {
        return $this->hasMany(CampingPhoto::class);
    }

    public function entranceGates()
    {
        return $this->hasMany(EntranceGate::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Szamitott mezok
    public function getAverageRating()
    {
        $average = $this->comments()->avg('rating');

        return $average !== null ? round($average, 1) : null;
    }

    public function getReviewsCount()
    {
        return $this->comments()->count();
    }

    public function getMinPriceAttribute()
    {
        return $this->spots()->min('price_per_night');
    }

        public function getMaxPriceAttribute()
    {
        return $this->spots()->max('price_per_night');
    }
}