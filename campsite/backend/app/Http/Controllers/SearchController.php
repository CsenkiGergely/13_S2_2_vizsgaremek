<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        error_log("bejutott");
        $query = $request->input('q');
        error_log($query);
        $results = Item::where('title', 'like', "%{$query}%")
                       ->orWhere('description', 'like', "%{$query}%")
                       ->get();

        return response()->json($results);
    }
}
