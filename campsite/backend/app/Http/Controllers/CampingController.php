<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Camping;

class CampingController extends Controller
{
    public function getCampings(Request $request)
    {
        // Logika a kempingek lekéréséhez
        $campings = Camping::with('location', 'tags', 'photos')->get();

        return response()->json($campings);
    }
}
