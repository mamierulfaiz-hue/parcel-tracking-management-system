<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcel;
use Illuminate\Support\Facades\Auth; 

class ParcelController extends Controller
{
    // 1. Show all parcels (API use)
    public function index()
    {
        return Parcel::all();
    }

    // 2. Save a new parcel (Raspberry Pi use)
    public function store(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|unique:parcels',
            'student_phone' => 'required',
        ]);

        $parcel = Parcel::create([
            'tracking_number' => $request->tracking_number,
            'student_phone' => $request->student_phone,
            'student_id' => $request->student_id,
            'is_paid' => false, // Default
            'is_collected' => false, // Default
        ]);

        return response()->json(['message' => 'Parcel Saved!', 'data' => $parcel], 201);
    }

    // 3. Student Dashboard Logic (UPDATED)
    public function studentDashboard()
    {
        $user = Auth::user(); 

        // Get ALL parcels for this student (Essential for the list view)
        $parcels = Parcel::where('student_id', $user->student_id)->get();

        // Count pending items (Uncollected)
        $pendingCount = $parcels->where('is_collected', false)->count();

        // Calculate unpaid fee (assuming 1.00 per unpaid parcel)
        // If you have a specific 'fee' column, change 1 to $parcel->fee
        $totalPayment = $parcels->where('is_paid', false)->count() * 1; 

        // Return view with ALL data
        return view('student.dashboard', compact('parcels', 'pendingCount', 'totalPayment', 'user'));
    }

    // 4. Update Parcel Logic (FIXED FOR ADMIN EDIT)
    public function updateParcel(Request $request, $id)
    {
        $parcel = \App\Models\Parcel::find($id);

        if (!$parcel) {
            return redirect()->back()->withErrors(['msg' => 'Parcel not found!']);
        }

        // 1. Update Basic Info
        $parcel->tracking_number = $request->tracking_number;
        $parcel->shelf_label = $request->shelf_label;

        // 2. Handle Status Change from Dropdown
        $status = $request->status;

        if ($status == 'unpaid') {
            $parcel->is_paid = false;
            $parcel->is_collected = false;
        } elseif ($status == 'ready') {
            $parcel->is_paid = true;
            $parcel->is_collected = false;
        } elseif ($status == 'collected') {
            $parcel->is_paid = true;
            $parcel->is_collected = true;
            
            // Set timestamp if it wasn't collected before
            if ($parcel->isDirty('is_collected')) {
                $parcel->updated_at = now();
            }
        }

        $parcel->save();

        return redirect()->back()->with('success', 'Parcel updated successfully!');
    }

    // 5. Delete Parcel Logic
    public function deleteParcel($id)
    {
        $parcel = \App\Models\Parcel::find($id);

        if (!$parcel) {
            return redirect()->back()->withErrors(['msg' => 'Parcel not found!']);
        }

        $parcel->delete();

        return redirect()->back()->with('success', 'Parcel deleted successfully!');
    }
}