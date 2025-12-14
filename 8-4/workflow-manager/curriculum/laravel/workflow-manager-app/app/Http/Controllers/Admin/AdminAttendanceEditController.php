<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceEditController extends Controller
{
    // 勤怠修正画面表示
    public function index($staff)
    {
        $user = User::findOrFail($staff);
        $year = request('year', now()->year);
        $month = request('month', now()->month);

        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $attendanceData = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->toDateString();
            $att = $attendances->firstWhere('date', $date);

            $attendanceData[] = [
                'date' => $date,
                'weekday' => Carbon::parse($date)->locale('ja')->isoFormat('dd'),
                'attendance' => $att,
            ];
        }

        return view('admin.attendance_edit', compact('user','year','month','attendanceData'));
    }

    // 勤怠データ保存（申請や修正）
    public function submit(Request $request, $staff)
    {
        $data = $request->all();

        // 承認①の更新かどうか
        if (isset($data['is_approved_by_clients'])) {
            $att = Attendance::firstOrNew([
                'user_id' => $staff,
                'date' => $data['date'],
            ]);

            $att->is_approved_by_clients = $data['is_approved_by_clients'];

            // 承認した場合は差戻しリセット
            if ($data['is_approved_by_clients'] == 1) {
                $att->rejection = 0;
                $att->rejection_comment = null;
            }

            $att->save();

            return response()->json([
                'status' => 'success',
                'is_approved_by_clients' => $att->is_approved_by_clients,
                'rejection' => $att->rejection,
                'rejection_comment' => $att->rejection_comment,
            ]);
        }

        // 申請・修正データの場合
        $att = Attendance::firstOrNew([
            'user_id' => $staff,
            'date' => $data['date'],
        ]);

        $isSubmitted = $data['is_submitted'] ?? 0;

        // 申請の場合は必須チェック
        if ($isSubmitted) {
            if (empty($data['category']) || empty($data['start_time']) || empty($data['end_time']) || empty($data['break_time'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => '申請する場合は区分・始業・終業・休憩をすべて入力してください',
                ]);
            }

            // 再申請なら差戻し解除
            $att->rejection = 0;
            $att->rejection_comment = null;
        }

        // 値を更新
        $att->category = $data['category'] ?? $att->category;
        $att->is_other_company_work = $data['is_other_company_work'] ?? 0;
        $att->start_time = $data['start_time'] ?? null;
        $att->end_time = $data['end_time'] ?? null;
        $att->break_time = $data['break_time'] ?? null;
        $att->remarks = $data['remarks'] ?? null;
        $att->is_submitted = $isSubmitted;

        $att->save();

        return response()->json([
            'status' => 'success',
            'is_approved_by_admins' => $att->is_approved_by_admins,
            'is_approved_by_clients' => $att->is_approved_by_clients,
            'rejection' => $att->rejection,
            'rejection_comment' => $att->rejection_comment,
        ]);
    }
}
