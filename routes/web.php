<?php

use App\Http\Controllers\ParcelController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Parcel;
use App\Models\Student;
use App\Models\Shelf;
use App\Models\User;

// ==========================================
// 1. PUBLIC ROUTES (Login System)
// ==========================================
Route::get('/', function () { return view('login-slider'); })->name('login');
Route::get('/login', function () { return redirect('/'); });
Route::get('/student/login', function () { return redirect('/'); });

Route::post('/login', function (Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        return redirect('/dashboard'); 
    }
    return back()->withErrors(['msg' => 'Invalid Admin Credentials']);
});

Route::post('/student/login', function (Request $request) {
    if (Auth::guard('student')->attempt(['student_id' => $request->student_id, 'password' => $request->password])) {
        return redirect('/student/dashboard');
    }
    return back()->withErrors(['msg' => 'Invalid Student ID or Password']);
});

Route::post('/logout', function () { Auth::logout(); return redirect('/'); });
Route::post('/student/logout', function () { Auth::guard('student')->logout(); return redirect('/'); });

// ==========================================
// 2. ADMIN DASHBOARD & ACTION ROUTING (Protected Web Session Routes)
// ==========================================
Route::middleware(['auth'])->group(function () {

    // Connected directly to the Controller to prevent route bypass
    Route::get('/dashboard', [ParcelController::class, 'index'])->name('dashboard');

    // Secured Web Routes to support your SweetAlert session popups
    Route::post('/admin/parcels', [ParcelController::class, 'store']);
    Route::put('/admin/update-parcel/{id}', [ParcelController::class, 'updateParcel']);
    Route::delete('/admin/delete-parcel/{id}', [ParcelController::class, 'deleteParcel']);

    // --- STUDENT DATABASE MANAGERS ---
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

    Route::post('/admin/delete-student/{id}', function ($id) {
        Student::destroy($id);
        return back()->with('success', 'Student Removed');
    });

    Route::get('/admin/check-parcel-details/{unique_id}', function ($unique_id) {
        $parcel = Parcel::where('unique_id', $unique_id)->first();
        if (!$parcel) return response()->json(['status' => 'error', 'message' => 'Parcel ID Not Found!']);
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
});

// ==========================================
// 3. STUDENT PORTAL OVERVIEW (Protected)
// ==========================================
Route::middleware(['auth:student'])->prefix('student')->group(function () {
    Route::get('/dashboard', function () {
        $student = Auth::guard('student')->user();
        $myParcels = Parcel::where('student_id', $student->student_id)->get();
        $pendingCount = Parcel::where('student_id', $student->student_id)->where('is_collected', false)->count();
        $unpaidParcelsCount = Parcel::where('student_id', $student->student_id)->where('is_paid', false)->count();
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
// 4. NETWORK UTILITY TOOLS
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
