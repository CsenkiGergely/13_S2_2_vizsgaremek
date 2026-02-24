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
    ];

    // Kapcsolatok
    public function campingComments()
    {
        return $this->belongsTo(Camping::class, 'camping_id');
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

    // Rekurzív gyerekek betöltése (több szintű válaszokhoz)
    public function childrenRecursive()
    {
        return $this->replies()->with('user', 'childrenRecursive');
    }
}
