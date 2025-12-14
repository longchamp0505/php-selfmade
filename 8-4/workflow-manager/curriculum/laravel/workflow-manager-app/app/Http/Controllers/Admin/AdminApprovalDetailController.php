<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class AdminApprovalDetailController extends Controller
{
    /**
     * 承認画面表示
     */
    public function index($staff, Request $request)
    {
        $user = User::findOrFail($staff); // IDからユーザー取得

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $data = $this->getAttendanceData($user->id, $year, $month);

        return view('admin.approval', [
            'user' => $user,
            'year' => $year,
            'month' => $month,
            'attendanceData' => $data['attendanceData'],
            'summary' => $data['summary'],
        ]);
    }

    /**
     * 勤怠データ取得＋計算ロジック（スタッフController準拠）
     */
    private function getAttendanceData($userId, $year, $month)
    {
        $attendances = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $attendanceData = [];
        $totalMinutes = 0;
        $overtimeDailyMinutes = 0;
        $workDays = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->toDateString();
            $att = $attendances->firstWhere('date', $date);

            $startTime = $att->start_time ?? null;
            $endTime   = $att->end_time ?? null;
            $breakTime = $att->break_time ?? null;

            $workMinutes = 0;
            $overtimeMinutes = 0;

            if ($startTime && $endTime) {
                $start = $this->timeToMinutes($startTime);
                $end   = $this->timeToMinutes($endTime);
                $break = $breakTime ? $this->timeToMinutes($breakTime) : 0;

                if ($end < $start) $end += 24 * 60; // 日跨ぎ対応

                $workMinutes = max($end - $start - $break, 0);
                $overtimeMinutes = max($workMinutes - 480, 0); // 日8時間超過

                $totalMinutes += $workMinutes;
                $overtimeDailyMinutes += $overtimeMinutes;
            }

            // 出勤日数計算
            if ($att) {
                switch ($att->category) {
                    case '出勤': case '振出': case '休出': case '遅刻': case '早退':
                        $workDays += 1; break;
                    case '午前休': case '午後休':
                        $workDays += 0.5; break;
                }
            }

            $attendanceData[] = [
                'date' => $date,
                'weekday' => Carbon::parse($date)->locale('ja')->isoFormat('dd'),
                'attendance' => $att,
                'workMinutes' => $workMinutes,
                'overtimeMinutes' => $overtimeMinutes,
            ];
        }

        // 週40時間超過計算
        $weeklyOvertime = $this->calculateWeeklyOvertime($attendances);

        return [
            'attendanceData' => $attendanceData,
            'summary' => [
                'workDays' => $workDays,
                'totalMinutes' => $totalMinutes,
                'overtimeDailyMinutes' => $overtimeDailyMinutes,
                'weeklyOvertime' => $weeklyOvertime,
            ],
        ];
    }

    /**
     * 時間(HH:MM) → 分
     */
    private function timeToMinutes($time)
    {
        [$h, $m] = explode(':', $time);
        return ((int)$h) * 60 + ((int)$m);
    }

    /**
     * 週40時間超過計算
     */
    private function calculateWeeklyOvertime($attendances)
    {
        $weeklyOvertime = 0;
        $attendances = $attendances->sortBy('date');

        $weekStart = null;
        $weekMinutes = 0;

        foreach ($attendances as $att) {
            $date = Carbon::parse($att->date);

            // 日曜開始で週リセット
            if (!$weekStart || $date->dayOfWeek === 0) {
                $weekStart = $date->copy()->startOfWeek();
                $weekMinutes = 0;
            }

            if ($att->start_time && $att->end_time) {
                $start = $this->timeToMinutes($att->start_time);
                $end   = $this->timeToMinutes($att->end_time);
                $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;
                if ($end < $start) $end += 24*60;

                $workMinutes = max($end - $start - $break, 0);
                $weekMinutes += min($workMinutes, 480); // 所定内8時間まで
            }

            // 週末(土曜)または最終日で超過分を集計
            if ($date->dayOfWeek === 6 || $att === $attendances->last()) {
                if ($weekMinutes > 40*60) {
                    $weeklyOvertime += $weekMinutes - 40*60;
                }
            }
        }

        return $weeklyOvertime;
    }

    /**
     * PDF出力
     */
    public function pdf($staff, $year, $month)
    {
        $user = User::findOrFail($staff);

        $data = $this->getAttendanceData($user->id, $year, $month);

        return PDF::loadView('pdf.attendance-input', [
            'attendanceData' => $data['attendanceData'],
            'summary' => $data['summary'],
            'year' => $year,
            'month' => $month,
            'user' => $user,
        ])
        ->setPaper('a4', 'landscape')
        ->setOption('enable-local-file-access', true)
        ->download("勤怠表_{$user->name}_{$year}年{$month}月.pdf");
    }

    public function updateApproval(Request $request, $staff)
    {
        $data = $request->all();
        $datesApproved = $data['approved'] ?? [];
        $datesUnapproved = $data['unapproved'] ?? [];
        $datesRejected = $data['rejected'] ?? [];
        $datesUnrejected = $data['unrejected'] ?? [];
        $rejectionComments = $data['rejection_comment'] ?? [];

        // 承認
        foreach($datesApproved as $date){
            Attendance::where('user_id', $staff)
                ->where('date', $date)
                ->update([
                    'is_approved_by_admins' => 1,
                    'rejection' => 0,
                    'admins_approved_at' => now(),
                    'rejection_comment' => null,
                ]);
        }

        // 承認解除
        foreach($datesUnapproved as $date){
            Attendance::where('user_id', $staff)
                ->where('date', $date)
                ->update([
                    'is_approved_by_admins' => 0,
                    'admins_approved_at' => null,
                ]);
        }

        // 差戻
        foreach($datesRejected as $date){
            Attendance::where('user_id', $staff)
                ->where('date', $date)
                ->update([
                    'is_approved_by_admins' => 0,
                    'rejection' => 1,
                    'rejection_comment' => $rejectionComments[$date] ?? null,
                    'admins_approved_at' => null,
                ]);
        }

        // 差戻解除
        foreach($datesUnrejected as $date){
            Attendance::where('user_id', $staff)
                ->where('date', $date)
                ->update([
                    'rejection' => 0,
                    'rejection_comment' => null,
                ]);
        }

        return response()->json(['status'=>'success']);
    }


}
