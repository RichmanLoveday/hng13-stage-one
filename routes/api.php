<?php

use App\Http\Controllers\Api\StringAnalyzerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/strings/filter-by-natural-language', [StringAnalyzerController::class, "filterByNaturalLanguage"]);
Route::apiResource('strings', StringAnalyzerController::class);
