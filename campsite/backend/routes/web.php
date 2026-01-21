<?php

use Illuminate\Support\Facades\Route;


Route::get('/{any}', function () {
    return view('app'); // vagy welcome, attól függ mi a blade neve
})->where('any', '.*');

