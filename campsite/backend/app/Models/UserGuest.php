<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGuest extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'birth_date',
        'place_of_birth',
        'gender',
        'citizenship',
        'mothers_birth_name',
        'id_card_number',
        'passport_number',
        'visa_flight_number',
        'resident_permit_number',
        'date_of_entry',
        'place_of_entry',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'date_of_entry' => 'date',
        ];
    }

    // Kapcsolatok
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
