<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Parcel;
use App\Models\Shelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Telegram\Bot\Laravel\Facades\Telegram;

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

    // 6. Find shelf with numeric ordering by row and slot number
    $assignedShelf = $this->getLowestEmptyShelf();

    if ($assignedShelf) {
        $shelfLabel = $assignedShelf->label;
        $assignedShelf->is_occupied = true;
        $assignedShelf->save();
    } else {
        $shelfLabel = 'Counter'; 
    }

    // 7. CREATE THE DATABASE TRANSACTION ROW
    $parcel = Parcel::create([
        'unique_id' => $uniqueId,
        'tracking_number' => $request->tracking_number,
        'student_phone' => $request->student_phone,
        'student_id' => $student->student_id, 
        'shelf_label' => $shelfLabel,
        'is_paid' => false,
        'is_collected' => false,
        'paid_at' => null,
        'collected_at' => null,
    ]);

    if (!empty($student->chat_id)) {
        try {
            Telegram::sendMessage([
                'chat_id' => $student->chat_id,
                'text' => "📦 Hi {$student->name}, your parcel has arrived!\n" .
                          "Tracking: {$parcel->tracking_number}\n\n" .
                          "Please pay and collect it as soon as possible."
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram notification failed for student ' . $student->student_id . ': ' . $e->getMessage());
        }
    }

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

    private function getLowestEmptyShelf()
    {
        if (Shelf::count() === 0) {
            foreach (['A', 'B', 'C'] as $row) {
                for ($i = 1; $i <= 9; $i++) {
                    Shelf::create(['label' => $row . '-' . $i]);
                }
            }
        }

        return Shelf::where('is_occupied', false)
            ->orderByRaw("SUBSTRING_INDEX(label, '-', 1) ASC")
            ->orderByRaw("CAST(SUBSTRING_INDEX(label, '-', -1) AS UNSIGNED) ASC")
            ->first();
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

        $request->validate([
            'tracking_number' => 'required|string',
            'student_id' => 'required|string',
            'shelf_label' => 'required|string',
            'status' => 'required|in:unpaid,ready,collected',
        ]);

        $oldShelfLabel = $parcel->shelf_label;
        $newShelfLabel = $request->shelf_label;

        $parcel->tracking_number = $request->tracking_number;
        $parcel->student_id = $request->student_id;
        $parcel->shelf_label = $newShelfLabel;

        $status = $request->status;
        if ($status === 'unpaid') {
            $parcel->is_paid = false;
            $parcel->is_collected = false;
            $parcel->paid_at = null;
            $parcel->collected_at = null;
        } elseif ($status === 'ready') {
            $parcel->is_paid = true;
            $parcel->is_collected = false;
            $parcel->paid_at = $parcel->paid_at ?? now();
            $parcel->collected_at = null;
        } else {
            $parcel->is_paid = true;
            $parcel->is_collected = true;
            $parcel->paid_at = $parcel->paid_at ?? now();
            $parcel->collected_at = $parcel->collected_at ?? now();
        }

        if ($oldShelfLabel !== $newShelfLabel) {
            Shelf::where('label', $oldShelfLabel)->update(['is_occupied' => false]);
        }

        if ($status === 'collected') {
            Shelf::where('label', $newShelfLabel)->update(['is_occupied' => false]);
        } else {
            Shelf::where('label', $newShelfLabel)->update(['is_occupied' => true]);
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
        Log::info("Scanner Arming: Setting status to ready.");
        Cache::forget('latest_scan_data');
        Cache::put('pi_scanner_status', 'ready', 600); // 10 minutes
        return response()->json(['message' => 'Scanner activated!']);
    }

    public function triggerScannerAdd() 
    {
        Log::info("Scanner Arming (Add Mode): Setting status to ready_add.");
        Cache::forget('latest_scan_data');
        Cache::forget('latest_tracking_number');
        Cache::forget('latest_student_phone'); 
        Cache::forget('latest_tracking_data');
        Cache::put('pi_scanner_status', 'ready_add', 600);
        return response()->json(['message' => 'Scanner ready for Add mode! Caches cleared.']);
    }

public function receiveScan(Request $request) 
{
    $status = Cache::get('pi_scanner_status', 'idle');
    Log::info("Receive Scan Attempt: Current Status is [{$status}]");

    // Accept either tracking_number or unique_id from the scanner
    $scannedText = $request->input('tracking_number') ?? $request->input('unique_id'); 

    if ($status !== 'ready' && $status !== 'ready_add') {
        return response()->json([
            'status' => 'error',
            'message' => "Scanner is not armed (Current: {$status}). Reset first, then trigger a new scan."
        ], 409);
    }
    
    // Find the parcel by either internal Unique ID (P-XXXX) or original Tracking Number
    $parcel = Parcel::where('unique_id', $scannedText)
                    ->orWhere('tracking_number', $scannedText)
                    ->first();

    if (!$parcel) {
        return response()->json(['status' => 'error', 'message' => 'ID not found'], 404);
    }

    // Manually query the student table so it doesn't crash if relationships are missing
    $student = \App\Models\Student::where('student_id', $parcel->student_id)->first();

    // Do not persist collect scans in cache; return the live payload directly so
    // the UI cannot rehydrate an old parcel after the modal is closed.
    Cache::forget('latest_scan_data');

    Cache::put('pi_scanner_status', 'idle');
    $data = [
        'id' => $parcel->id,
        'unique_id' => $parcel->unique_id,
        'tracking_number' => $parcel->tracking_number,
        'location' => $parcel->shelf_label ?? 'Counter',
        'student_name' => $student ? $student->name : 'No Name Registered',
        'student_id' => $parcel->student_id ?? 'Unknown ID',
    ];

    return response()->json(array_merge(['status' => 'success'], $data));
}

    public function checkLatestScan() 
    {
        return response()->json(['found' => false]);
    }

    public function confirmCollection($id)
    {
        $parcel = Parcel::find($id);
        if ($parcel) {
            $parcel->is_collected = true;
            $parcel->is_paid = true;
            $parcel->paid_at = $parcel->paid_at ?? now();
            $parcel->collected_at = $parcel->collected_at ?? now();
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