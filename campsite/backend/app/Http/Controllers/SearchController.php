<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');

        $results = Item::where('title', 'like', "%{$query}%")
                       ->orWhere('description', 'like', "%{$query}%")
                       ->get();

        return response()->json($results);
    }
}
