<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\PaidLeave;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * 休暇申請一覧
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        $leaves = Leave::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date', 'desc')
            ->get();

        $paidLeave = PaidLeave::where('user_id', $user->id)->first();

        return view('user.leave', [
            'leaves' => $leaves,
            'year'   => (int)$year,
            'month'  => (int)$month,
            'paidLeave' => $paidLeave,
        ]);
    }

    /**
     * 休暇申請保存
     */
    public function store(Request $request)
    {
        $request->validate([
            'date'       => 'required|date',
            'leave_type' => 'required|string|max:50',
            'note'       => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $dayOfWeek = Carbon::parse($request->date)->dayOfWeek;

        if (Leave::where('user_id', $user->id)
            ->whereDate('date', $request->date)
            ->exists()
        ) {
            return back()->with('error', 'その日はすでに休暇申請があります');
        }

        Leave::create([
            'user_id'    => $user->id,
            'date'       => $request->date,
            'day_of_week'=> $dayOfWeek,
            'leave_type' => $request->leave_type,
            'note'       => $request->note,
            'is_submitted' => true,
            'is_approved_by_admins' => false,
            'is_rejection' => false,
        ]);

        return redirect()->route('user.leave.index')
            ->with('success', '休暇を申請しました');
    }

    public function edit($id)
    {
        $leave = Leave::findOrFail($id);

        return response()->json([
            'id' => $leave->id,
            'date' => $leave->date,
            'leave_type' => $leave->leave_type,
            'note' => $leave->note,
        ]);
    }

    /**
     * 再申請内容保存
     */
    public function resubmit(Request $request, $id)
    {
        $user = Auth::user();
        $leave = Leave::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'date'       => 'required|date',
            'leave_type' => 'required|string|max:50',
            'note'       => 'nullable|string|max:1000',
        ]);

        if (Leave::where('user_id', $user->id)
            ->whereDate('date', $request->date)
            ->where('id', '!=', $leave->id)
            ->exists()
        ) {
            return back()->with('error', 'その日はすでに休暇申請があります');
        }

        $leave->update([
            'date'       => $request->date,
            'day_of_week'=> Carbon::parse($request->date)->dayOfWeek,
            'leave_type' => $request->leave_type,
            'note'       => $request->note,
            'is_rejection' => false,
            'is_approved_by_admins' => false,
            'admins_approved_at' => null,
            'is_submitted' => true,
        ]);

        return redirect()->route('user.leave.index')->with('success', '休暇を再申請しました');
    }

}
