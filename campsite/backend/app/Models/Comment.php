<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'camping_id',
        'user_id',
        'parent_id',
        'rating',
        'comment',
        'upload_date',
    ];

    protected function casts(): array
    {
        return [
            'upload_date' => 'date',
        ];
    }

    // Kapcsolatok
    public function campingComments()
    {
        return $this->belongsTo(Camping::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Szülő komment (válasz esetén)
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    // Gyerek kommentek (válaszok)
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}
