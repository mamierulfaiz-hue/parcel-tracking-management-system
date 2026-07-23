<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Parcel;
use App\Models\Shelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ParcelController extends Controller
{
    // 1. Show all parcels (Admin Dashboard Overview)
    public function index()
    {
        Cache::put('pi_scanner_status', 'idle');
        
        $parcels = Parcel::all();
        $totalInStorage = Parcel::where('is_collected', false)->count();
        $totalDelivered = Parcel::where('is_collected', true)->count();
        $totalUnpaid = Parcel::where('is_paid', false)->count();
        $emptyShelves = Shelf::where('is_occupied', false)->count();

        $students = Student::all(); 
        
        return view('dashboard', compact(
            'parcels', 
            'students', 
            'totalInStorage', 
            'totalDelivered', 
            'totalUnpaid',
            'emptyShelves'
        ));
    }

    // 2. Save a new parcel manually (Raspberry Pi 'Add' mode)
// 2. Save a new parcel manually (Raspberry Pi 'Add' mode)
public function store(Request $request)
{
    // 1. Validate the tracking number strictly
    $request->validate([
        'tracking_number' => 'required|unique:parcels,tracking_number',
        'student_phone' => 'required',
    ], [
        'tracking_number.unique' => 'This tracking number has already been registered!',
    ]);

    // 2. Normalize Phone Number Variants (+60, 60, 0)
    $cleanPhone = preg_replace('/[^0-9]/', '', $request->student_phone);

    if (str_starts_with($cleanPhone, '601')) {
        $variant60 = $cleanPhone;
        $variant0  = '0' . substr($cleanPhone, 2); 
    } elseif (str_starts_with($cleanPhone, '01')) {
        $variant60 = '60' . substr($cleanPhone, 1); 
        $variant0  = $cleanPhone;
    } else {
        $variant60 = '60' . $cleanPhone;
        $variant0  = '0' . $cleanPhone;
    }

    // 3. Search database using standardized formats
    $student = Student::where('phone', $variant0)
                      ->orWhere('phone', $variant60)
                      ->first();

    // 4. Check if phone number exists in student database
    if (!$student) {
        $this->clearScannerCaches();
        return redirect()->back()->withErrors(['student_phone' => 'The phone number (' . $request->student_phone . ') was not found in the Database!'])->withInput();
    }

    // 5. GENERATE A UNIQUE ID (e.g., P-ABCD)
    do {
        $uniqueId = 'P-' . strtoupper(Str::random(4));
    } while (Parcel::where('unique_id', $uniqueId)->exists());

    // 6. Find shelf
    $assignedShelf = Shelf::where('is_occupied', false)->orderBy('label', 'asc')->first();

    if ($assignedShelf) {
        $shelfLabel = $assignedShelf->label;
        $assignedShelf->is_occupied = true;
        $assignedShelf->save();
    } else {
        $shelfLabel = 'Counter'; 
    }

    // 7. CREATE THE DATABASE TRANSACTION ROW
    Parcel::create([
        'unique_id' => $uniqueId,
        'tracking_number' => $request->tracking_number,
        'student_phone' => $request->student_phone,
        'student_id' => $student->student_id, 
        'shelf_label' => $shelfLabel,
        'is_paid' => false,
        'is_collected' => false,
    ]);

    $this->clearScannerCaches();

    return redirect()->back()->with('success', 'Parcel Saved Successfully onto Shelf ' . $shelfLabel . '!');
}

    // NEW INTERCEPT ENDPOINT: Stores real-time Pi streams into cache
    public function cacheTracking(Request $request)
    {
        $data = Cache::get('latest_tracking_data', [
            'tracking_number' => null,
            'student_phone' => null
        ]);

        if ($request->has('tracking_number')) {
            $data['tracking_number'] = $request->input('tracking_number');
        }
        
        if ($request->has('student_phone')) {
            $data['student_phone'] = $request->input('student_phone');
        }

        Cache::put('latest_tracking_data', $data, 120);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // NEW INTERCEPT ENDPOINT: Delays live cache array directly to JavaScript fetch loops
    public function checkLatestTracking()
    {
        $data = Cache::get('latest_tracking_data', [
            'tracking_number' => null,
            'student_phone' => null
        ]);

        return response()->json($data);
    }

    private function clearScannerCaches()
    {
        Cache::forget('latest_scan_data');
        Cache::forget('latest_tracking_number');
        Cache::forget('latest_student_phone');
        Cache::forget('latest_tracking_data');
        Cache::put('pi_scanner_status', 'idle');
    }

    // 4. Manual Admin Update
    public function updateParcel(Request $request, $id)
    {
        $parcel = Parcel::find($id);
        if (!$parcel) return redirect()->back()->withErrors(['msg' => 'Parcel not found!']);

        $oldShelfLabel = $parcel->shelf_label;
        $parcel->tracking_number = $request->tracking_number;
        $parcel->shelf_label = $request->shelf_label;

        $status = $request->status;
        if ($status == 'unpaid' || $status == 'ready') {
            $parcel->is_paid = ($status == 'ready');
            $parcel->is_collected = false;
            
            // If the admin manually edits the shelf location label string
            if ($oldShelfLabel !== $request->shelf_label) {
                Shelf::where('label', $oldShelfLabel)->update(['is_occupied' => false]);
                Shelf::where('label', $request->shelf_label)->update(['is_occupied' => true]);
            }
        } elseif ($status == 'collected') {
            $parcel->is_paid = true;
            $parcel->is_collected = true;

            // Free up the shelf space immediately if marked delivered manually
            Shelf::where('label', $parcel->shelf_label)->update(['is_occupied' => false]);
        }

        $parcel->save();
        return redirect()->back()->with('success', 'Parcel updated successfully!');
    }

    // 5. Delete Parcel Record completely
    public function deleteParcel($id)
    {
        $parcel = Parcel::find($id);
        if ($parcel) {
            // Free the shelf immediately before deleting the record
            Shelf::where('label', $parcel->shelf_label)->update(['is_occupied' => false]);
            $parcel->delete();
        }
        return redirect()->back()->with('success', 'Parcel deleted successfully!');
    }

    // ==========================================
    // RASPBERRY PI HARDWARE SCANNER ENDPOINTS
    // ==========================================
    public function scannerStatus() 
    {
        return response()->json(['status' => Cache::get('pi_scanner_status', 'idle')]);
    }

    public function triggerScanner() 
    {
        Cache::put('pi_scanner_status', 'ready', 120);
        return response()->json(['message' => 'Scanner activated!']);
    }

    public function triggerScannerAdd() 
    {
        Cache::forget('latest_scan_data');
        Cache::forget('latest_tracking_number');
        Cache::forget('latest_student_phone'); 
        Cache::forget('latest_tracking_data');
        Cache::put('pi_scanner_status', 'ready_add', 120); 
        return response()->json(['message' => 'Scanner ready for Add mode! Caches cleared.']);
    }

public function receiveScan(Request $request) 
{
    // Accept either tracking_number or unique_id from the scanner
    $scannedText = $request->input('tracking_number') ?? $request->input('unique_id'); 
    
    // Find the parcel
    $parcel = Parcel::where('unique_id', $scannedText)->first();

    if (!$parcel) {
        return response()->json(['status' => 'error', 'message' => 'ID not found'], 404);
    }

    // Manually query the student table so it doesn't crash if relationships are missing
    $student = \App\Models\Student::where('student_id', $parcel->student_id)->first();

    // Cache the complete data payload
    Cache::forget('latest_scan_data');
    Cache::put('latest_scan_data', [
        'id' => $parcel->id,
        'unique_id' => $parcel->unique_id, 
        'tracking_number' => $parcel->tracking_number, 
        'location' => $parcel->shelf_label ?? 'Counter',
        'student_name' => $student ? $student->name : 'No Name Registered', 
        'student_id' => $parcel->student_id ?? 'Unknown ID', 
    ], 60);

    Cache::put('pi_scanner_status', 'idle'); 
    return response()->json(['status' => 'success']);
}

    public function checkLatestScan() 
    {
        $data = Cache::get('latest_scan_data');
        return $data ? response()->json(array_merge(['found' => true], $data)) : response()->json(['found' => false]);
    }

    public function confirmCollection($id)
    {
        $parcel = Parcel::find($id);
        if ($parcel) {
            $parcel->is_collected = true;
            $parcel->is_paid = true;
            $parcel->save();

            // Free the shelf space immediately
            Shelf::where('label', $parcel->shelf_label)->update(['is_occupied' => false]);
            
            // Clean out tracking loops
            $this->clearScannerCaches();

            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error'], 404);
    }

    public function scannerReset()
    {
        Cache::put('pi_scanner_status', 'idle');
        Cache::forget('latest_scan_data');
        Cache::forget('latest_tracking_number'); 
        Cache::forget('latest_student_phone'); 
        Cache::forget('latest_tracking_data');
        return response()->json(['status' => 'idle']);
    }
}