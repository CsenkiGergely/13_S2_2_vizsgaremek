<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Camping;

class CampingController extends Controller
{
    public function getCampings(Request $request)
    {
        // lekérés lapozzással 2/ oldalanként (teszt)
        // http://127.0.0.1:8000/api/campings?search=asd&page=2
        $camping = Camping::with(['photos', 'location', 'tags']);

        $camping->when($request->search, function ($query, $search) {
            $search = mb_strtolower($search);

            $query->whereRaw('LOWER(camping_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
        });
        //http://127.0.0.1:8000/api/campings?min_price=1500&max_price=4000
        $camping->when($request->min_price || $request->max_price, function ($full) use ($request) {
            $full->whereHas('spots', function ($price) use ($request) {
                if ($request->min_price) {
                    $price->where('price_per_night', '>=', $request->min_price);
                }
                if ($request->max_price) {
                    $price->where('price_per_night', '<=', $request->max_price);
                }
            });
        });

        $campings = $camping->paginate(2); // 2 egyszerre
        return response()->json($campings);
    }


}
