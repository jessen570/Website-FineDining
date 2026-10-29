<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $dbOk = false;
    $dbDriver = config('database.default');
    $dbError = null;

    try {
        DB::connection()->getPdo();
        $dbOk = true;
    } catch (\Throwable $e) {
        $dbError = $e->getMessage();
    }

    return response()->json([
        'status' => 'ok',
        'app' => 'Maison Aster ERP Backend API',
        'version' => '1.0.0',
        'database' => [
            'driver' => $dbDriver,
            'connected' => $dbOk,
            'error' => $dbError,
        ],
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
