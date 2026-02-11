<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campsite extends Model
{
    protected $fillable = [
        'name',
        'location',
        'rating',
        'reviews',
        'price',
        'image',
        'tags',
        'location_types',
        'featured'
    ];

    protected $casts = [
        'tags' => 'array',
        'location_types' => 'array',
        'featured' => 'boolean',
        'rating' => 'float',
        'price' => 'integer',
        'reviews' => 'integer'
    ];
}
