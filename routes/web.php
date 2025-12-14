<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'API Resyde - Backend para aplicación móvil',
        'version' => '1.0.0',
    ]);
});

