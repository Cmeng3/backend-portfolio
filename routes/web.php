<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['service' => 'portfolio-api', 'health' => '/api/v1/health']);
});
