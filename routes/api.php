<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache; // 👈 CRITICAL: Needed for temporary memory
use App\Http\Controllers\ParcelController;
use App\Models\Parcel;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Standard Parcel Routes
Route::get('/parcels', [ParcelController::class, 'index']);
Route::post('/parcels', [ParcelController::class, 'store']);


// ==========================================
// 1️⃣ RASPBERRY PI SCANNER ROUTE
// ==========================================
Route::post('/scan-parcel', function (Request $request) {
    
    // 1. Get the Tracking Number from the Pi
    $tracking_number = $request->input('tracking_number');

    // 2. Find the Parcel
    $parcel = Parcel::where('tracking_number', $tracking_number)->first();

    // 3. Error Checking
    if (!$parcel) {
        return response()->json(['status' => 'error', 'message' => 'Parcel not found!'], 404);
    }
    if (!$parcel->is_paid) {
        return response()->json(['status' => 'error', 'message' => 'Student has not paid yet!'], 400);
    }
    if ($parcel->is_collected) {
        return response()->json(['status' => 'error', 'message' => 'Parcel already collected!'], 400);
    }

    // 4. ✅ SUCCESS (But don't collect yet!)
    // Instead of saving to DB, we save to CACHE for 30 seconds.
    // This allows the laptop to "see" the scan without finalizing it.
    Cache::put('latest_scan_id', $parcel->id, 30);

    return response()->json([
        'status' => 'success', 
        'message' => 'Ready for Confirmation', 
        'student' => $parcel->student_id
    ]);
});


// ==========================================
// 2️⃣ POLLING ROUTE (Laptop Checks This)
// ==========================================
Route::get('/check-latest-scan', function () {
    // 1. Check if the Pi put anything in the Cache recently
    $id = Cache::get('latest_scan_id');

    if ($id) {
        // 2. If found, get the full details from Database
        $parcel = Parcel::find($id);
        
        // Only return if it exists and hasn't been collected yet
        if ($parcel && !$parcel->is_collected) {
            return response()->json([
                'found' => true,
                'id' => $parcel->id,
                'tracking_number' => $parcel->tracking_number,
                'student_name' => $parcel->student_id, 
                'location' => $parcel->shelf_label ?? 'Counter' 
            ]);
        }
    }

    // 3. If cache is empty, return false
    return response()->json(['found' => false]);
});


// ==========================================
// 3️⃣ CONFIRMATION ROUTE (Clicking the Button)
// ==========================================
Route::post('/confirm-collection/{id}', function ($id) {
    // 1. Find the parcel
    $parcel = Parcel::find($id);
    
    if ($parcel) {
        // 2. Mark as collected PERMANENTLY
        $parcel->is_collected = true;
        $parcel->save();
        
        // 3. Clear the cache (so the popup doesn't appear again)
        Cache::forget('latest_scan_id'); 
        
        return response()->json(['status' => 'success', 'message' => 'Collection Finalized']);
    }
    
    return response()->json(['status' => 'error', 'message' => 'Parcel not found'], 404);
});