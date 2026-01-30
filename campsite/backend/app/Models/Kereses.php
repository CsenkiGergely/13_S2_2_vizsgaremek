<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    // Ha a táblád neve nem 'items', megadhatod:
    // protected $table = 'my_table';

    // Megadhatod a kereshető mezőket
    protected $fillable = ['title', 'description', 'category'];
}
