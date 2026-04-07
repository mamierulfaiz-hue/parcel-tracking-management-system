<?php

use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Parcel;
use App\Models\Student;
use App\Models\User;
use App\Models\Shelf;
use App\Http\Controllers\ParcelController; // Make sure this line is at the top

// Add these to your routes
Route::put('/admin/update-parcel/{id}', [ParcelController::class, 'updateParcel']);
Route::delete('/admin/delete-parcel/{id}', [ParcelController::class, 'deleteParcel']);

// ==========================================
// 1. PUBLIC ROUTES (Login System)
// ==========================================

Route::get('/', function () {
    return view('login-slider');
})->name('login');

Route::get('/login', function () { return redirect('/'); });
Route::get('/student/login', function () { return redirect('/'); });

// Handle Admin Login
Route::post('/login', function (Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        return redirect('/dashboard'); 
    }
    return back()->withErrors(['msg' => 'Invalid Admin Credentials']);
});

// Handle Student Login
Route::post('/student/login', function (Request $request) {
    if (Auth::guard('student')->attempt(['student_id' => $request->student_id, 'password' => $request->password])) {
        return redirect('/student/dashboard');
    }
    return back()->withErrors(['msg' => 'Invalid Student ID or Password']);
});

// Logouts
Route::post('/logout', function () { Auth::logout(); return redirect('/'); });
Route::post('/student/logout', function () { Auth::guard('student')->logout(); return redirect('/'); });


// ==========================================
// 2. ADMIN DASHBOARD (Protected)
// ==========================================
Route::middleware(['auth'])->group(function () {

    // Show Dashboard
    Route::get('/dashboard', function () {
        $parcels = Parcel::all();
        $students = Student::all();
        $shelves = Shelf::all(); 

        // Calculate Stats
        $totalInStorage = Parcel::where('is_collected', false)->count();
        $totalUnpaid = Parcel::where('is_paid', false)->count();
        $emptyShelves = Shelf::where('is_occupied', false)->count();

        return view('dashboard', [
            'parcels' => $parcels, 
            'students' => $students,
            'totalInStorage' => $totalInStorage,
            'totalUnpaid' => $totalUnpaid,
            'emptyShelves' => $emptyShelves
        ]);
    })->name('dashboard');

    // --- STUDENT DB ROUTES ---
    
    // Add Student
    Route::post('/admin/add-student', function (Request $request) {
        $request->validate([
            'name' => 'required',
            'student_id' => 'required|unique:students',
            'phone' => 'required',
            'room_number' => 'required',
            'ic_number' => 'required',
        ]);

        Student::create([
            'name' => $request->name,
            'student_id' => $request->student_id,
            'phone' => $request->phone,
            'room_number' => $request->room_number,
            'ic_number' => $request->ic_number,
            'password' => Hash::make($request->ic_number)
        ]);

        return back()->with('success', 'Student Added (Password is IC)');
    });

    // Delete Student
    Route::post('/admin/delete-student/{id}', function ($id) {
        Student::destroy($id);
        return back()->with('success', 'Student Removed');
    });

    // --- PARCEL ROUTES ---

    // 1. Add Parcel (Secure Notification Mode)
    Route::post('/add-parcel', function (Request $request) {
        $request->validate([
            'tracking_number' => 'required|unique:parcels',
            'student_phone' => 'required',
        ]);

        // Find Student
        $student = Student::where('phone', $request->student_phone)->first();
        if (!$student) {
            return back()->withErrors(['msg' => 'Student not found! Please register them in Student DB first.']);
        }

        // Find Empty Shelf
        $freeShelf = Shelf::where('is_occupied', false)->first();
        if (!$freeShelf) {
            return back()->withErrors(['msg' => 'STORAGE FULL! No empty shelves available.']);
        }

        // Generate Unique ID
        do {
            $generatedID = 'P-' . strtoupper(Str::random(4)); 
        } while (Parcel::where('unique_id', $generatedID)->exists());

        // Create Parcel
        Parcel::create([
            'tracking_number' => $request->tracking_number,
            'unique_id' => $generatedID,
            'student_phone' => $request->student_phone,
            'student_id' => $student->student_id,
            'shelf_label' => $freeShelf->label,
        ]);

        // Occupy Shelf
        $freeShelf->is_occupied = true;
        $freeShelf->save();

        // Send Secure Telegram Message (Hidden ID & Shelf)
        if ($student->chat_id) {
            try {
                Telegram::sendMessage([
                    'chat_id' => $student->chat_id,
                    'text' => "📦 *PARCEL ARRIVED!*\n\n" .
                              "Tracking: `{$request->tracking_number}`\n" .
                              "Status: *Unpaid (RM 1.00)*\n\n" .
                              "🔒 _Collection Code & Shelf location are hidden._\n" .
                              "👉 Login to Dashboard to pay & unlock.",
                    'parse_mode' => 'Markdown'
                ]);
            } catch (\Exception $e) {
                // Ignore internet errors
            }
        }

        return back()->with('success', 'Parcel Added! System ID: ' . $generatedID . ' | Shelf: ' . $freeShelf->label);
    });

    // 2. API: Get Parcel Details for Collection Popup
    Route::get('/admin/check-parcel-details/{unique_id}', function ($unique_id) {
        $parcel = Parcel::where('unique_id', $unique_id)->first();
        
        if (!$parcel) {
            return response()->json(['status' => 'error', 'message' => 'Parcel ID Not Found!']);
        }

        $student = Student::where('student_id', $parcel->student_id)->first();

        return response()->json([
            'status' => 'success',
            'id' => $parcel->id, 
            'unique_id' => $parcel->unique_id,
            'tracking_number' => $parcel->tracking_number,
            'shelf' => $parcel->shelf_label,
            'student_name' => $student ? $student->name : 'Unknown Student',
            'is_paid' => $parcel->is_paid,
            'is_collected' => $parcel->is_collected
        ]);
    });

    // 3. Mark Collected (The Confirm Action)
    Route::post('/collect-parcel/{id}', function ($id) {
        $parcel = Parcel::find($id);
        $parcel->is_collected = true;
        $parcel->save();

        if ($parcel->shelf_label) {
            $shelf = Shelf::where('label', $parcel->shelf_label)->first();
            if ($shelf) {
                $shelf->is_occupied = false;
                $shelf->save();
            }
        }

        return back()->with('success', 'Parcel Collected. Shelf is now empty.');
    });
});

// ==========================================
// 3. STUDENT DASHBOARD (Protected)
// ==========================================
Route::middleware(['auth:student'])->prefix('student')->group(function () {

    Route::get('/dashboard', function () {
        $student = Auth::guard('student')->user();
        
        $myParcels = Parcel::where('student_id', $student->student_id)->get();

        $pendingCount = Parcel::where('student_id', $student->student_id)
                              ->where('is_collected', false) 
                              ->count();

        $unpaidParcelsCount = Parcel::where('student_id', $student->student_id)
                                    ->where('is_paid', false)
                                    ->count();
        $totalPayment = $unpaidParcelsCount * 1.00;

        return view('student-dashboard', [
            'parcels' => $myParcels, 
            'student' => $student,
            'pendingCount' => $pendingCount,
            'totalPayment' => $totalPayment
        ]);
    })->name('student.dashboard');

    Route::get('/pay/{id}', function ($id) {
        $parcel = Parcel::find($id);
        return view('payment-gateway', ['parcel' => $parcel]);
    });

    Route::post('/process-payment/{id}', function ($id) {
        $parcel = Parcel::find($id);
        $parcel->is_paid = true;
        $parcel->save();
        return redirect('/student/dashboard')->with('success', 'Payment Successful!');
    });
});

// ==========================================
// 4. UTILITIES & API
// ==========================================

Route::get('/admin/check-student/{phone}', function ($phone) {
    $student = Student::where('phone', $phone)->first();
    if ($student) {
        return response()->json([
            'status' => 'found',
            'name' => $student->name,
            'room' => $student->room_number,
            'id' => $student->student_id
        ]);
    }
    return response()->json(['status' => 'not_found']);
});

Route::get('/setup-shelves', function () {
    $rows = ['A', 'B', 'C'];
    $cols = 10; 
    foreach ($rows as $row) {
        for ($i = 1; $i <= $cols; $i++) {
            $label = $row . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            Shelf::firstOrCreate(['label' => $label]);
        }
    }
    return "Shelves Created! (A-01 to C-10)";
});

// ==========================================
// 5. API FOR RASPBERRY PI CAMERA
// ==========================================
Route::post('/api/scan-parcel', function (Request $request) {
    // The Python script sends 'tracking_number' (which is the QR data)
    $code = $request->input('tracking_number'); 

    // Search by Courier Tracking Number OR System Unique ID
    $parcel = Parcel::where('tracking_number', $code)
                    ->orWhere('unique_id', $code)
                    ->first();

    if ($parcel) {
        // 1. Mark as Collected
        $parcel->is_collected = true;
        $parcel->save();

        // 2. Free up the Shelf
        if ($parcel->shelf_label) {
            $shelf = Shelf::where('label', $parcel->shelf_label)->first();
            if ($shelf) {
                $shelf->is_occupied = false;
                $shelf->save();
            }
        }
        
        return response()->json([
            'status' => 'success', 
            'message' => 'Collected! Shelf ' . $parcel->shelf_label . ' is now empty.',
            'student_id' => $parcel->student_id
        ]);
    } else {
        return response()->json(['status' => 'error', 'message' => 'Parcel Not Found'], 404);
    }
});

