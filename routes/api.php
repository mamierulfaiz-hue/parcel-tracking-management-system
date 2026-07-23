<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache; 
use App\Http\Controllers\ParcelController;

Route::post('/scanner-reset', [App\Http\Controllers\ParcelController::class, 'scannerReset']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ============================================================
// 📸 RASPBERRY PI DASHCAM SCANNER ROUTES
// ============================================================
Route::get('/scanner-status', [ParcelController::class, 'scannerStatus']);
Route::get('/scanner-reset', [ParcelController::class, 'scannerReset']);

// --- COLLECT MODE ROUTES ---
Route::post('/scanner-trigger', [ParcelController::class, 'triggerScanner']);
Route::post('/scan-parcel', [ParcelController::class, 'receiveScan']);
Route::get('/check-latest-scan', [ParcelController::class, 'checkLatestScan']);
Route::post('/confirm-collection/{id}', [ParcelController::class, 'confirmCollection']);

// --- ADD MODE ROUTES ---
Route::post('/scanner-trigger-add', [ParcelController::class, 'triggerScannerAdd']);

Route::post('/cache-tracking', function (Request $request) {
    $tracking = $request->input('tracking_number');
    $phone = $request->input('student_phone'); 
    
    if ($tracking) {
        Cache::put('latest_tracking_number', $tracking, 60);
    }
    if ($phone) {
        Cache::put('latest_student_phone', $phone, 60);
        Cache::put('pi_scanner_status', 'idle'); 
    }
    return response()->json(['status' => 'success']);
});

Route::get('/check-latest-tracking', function () {
    return response()->json([
        'tracking_number' => Cache::get('latest_tracking_number'),
        'student_phone' => Cache::get('latest_student_phone')
    ]);
});