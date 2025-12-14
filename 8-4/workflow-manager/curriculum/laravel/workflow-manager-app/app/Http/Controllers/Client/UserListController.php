<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class UserListController extends Controller
{
    public function index(Request $request)
    {
        $client = Auth::guard('client')->user();
        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        // ログイン中クライアントに属するスタッフ取得
        $staffs = User::where('client_id', $client->id)->get();

        $staffData = [];

        foreach ($staffs as $staff) {
            // 該当月の他社作業勤怠
            $attendances = Attendance::where('user_id', $staff->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('is_other_company_work', 1)
                ->get();

            // 月全体承認判定
            $allApproved = $attendances->isNotEmpty() && $attendances->every(fn($a) => $a->is_approved_by_clients);

            $workMinutes = 0;
            $regularMinutes = 0;
            $dailyOverMinutes = 0;

            foreach ($attendances as $att) {
                if ($att->start_time && $att->end_time) {
                    $work = $this->timeToMinutes($att->end_time) - $this->timeToMinutes($att->start_time);
                    if ($work < 0) $work += 24*60;
                    $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;
                    $work = max($work - $break, 0);

                    $workMinutes += $work;
                    $regularMinutes += min($work, 480);
                    $dailyOverMinutes += max($work - 480, 0);
                }
            }

            $weeklyOverMinutes = $attendances->count() > 0 ? $this->calculateWeeklyOvertime($attendances) : 0;
            $overMinutes = $dailyOverMinutes + $weeklyOverMinutes;

            $staffData[] = [
                'id'               => $staff->id,
                'name'             => $staff->name,
                'contract_type'    => $staff->contract_type ?? '-',
                'workMinutes'      => $workMinutes,
                'contractMinutes'  => $regularMinutes,
                'overtimeMinutes'  => $overMinutes,
                'workDays'         => $attendances->count(),
                'status'           => $allApproved ? '完了' : '未完了',
            ];
        }

        return view('client.user_list', compact('staffData', 'year', 'month'));
    }

    private function timeToMinutes($time)
    {
        [$h, $m] = explode(':', $time);
        return ((int)$h) * 60 + (int)$m;
    }

    private function calculateWeeklyOvertime($attendances)
    {
        $weeklyOvertime = 0;
        $attendances = $attendances->sortBy('date');
        $weekStart = null;
        $weekMinutes = 0;

        foreach ($attendances as $att) {
            $date = Carbon::parse($att->date);

            if (!$weekStart || $date->dayOfWeek === 0) {
                $weekStart = $date->copy()->startOfWeek();
                $weekMinutes = 0;
            }

            if ($att->start_time && $att->end_time) {
                $work = $this->timeToMinutes($att->end_time) - $this->timeToMinutes($att->start_time);
                if ($work < 0) $work += 24*60;
                $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;
                $work = max($work - $break, 0);

                $weekMinutes += min($work, 480);
            }

            if ($date->dayOfWeek === 6 || $att === $attendances->last()) {
                if ($weekMinutes > 40*60) {
                    $weeklyOvertime += $weekMinutes - 40*60;
                }
            }
        }

        return $weeklyOvertime;
        }

    public function approval($id, Request $request)
    {
        $client = Auth::guard('client')->user();
        $staff = User::where('client_id', $client->id)->findOrFail($id);

        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        // --- 計算用：他社勤務のみ ---
        $attendancesForCalc = Attendance::where('user_id', $staff->id)
            ->where('is_other_company_work', 1)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // --- 表示用：自社勤務も含む ---
        $attendancesForDisplay = Attendance::where('user_id', $staff->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $attendanceData = [];
        $workDays = 0;
        $totalMinutes = 0;
        $overtimeDailyMinutes = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->toDateString();

            // 表示用の勤怠を取得
            $att = $attendancesForDisplay->firstWhere('date', $date);

            // 計算用勤怠を取得（他社勤務のみ）
            $attCalc = $attendancesForCalc->firstWhere('date', $date);

            $workMinutes = 0;
            $overtimeMinutes = 0;

            if ($attCalc && $attCalc->start_time && $attCalc->end_time) {
                $start = $this->timeToMinutes($attCalc->start_time);
                $end   = $this->timeToMinutes($attCalc->end_time);
                $break = $attCalc->break_time ? $this->timeToMinutes($attCalc->break_time) : 0;

                if ($end < $start) $end += 24 * 60;

                $workMinutes = max($end - $start - $break, 0);
                $overtimeMinutes = max($workMinutes - 480, 0);

                $totalMinutes += $workMinutes;
                $overtimeDailyMinutes += $overtimeMinutes;
            }

            if ($attCalc) {
                switch ($attCalc->category) {
                    case '出勤':
                    case '振出':
                    case '休出':
                    case '遅刻':
                    case '早退':
                        $workDays += 1;
                        break;
                    case '午前休':
                    case '午後休':
                        $workDays += 0.5;
                        break;
                }
            }

            $attendanceData[] = [
                'date' => $date,
                'weekday' => Carbon::parse($date)->locale('ja')->isoFormat('dd'),
                'attendance' => $att, // 表示用
                'workMinutes' => $workMinutes,
                'overtimeMinutes' => $overtimeMinutes,
            ];
        }

        $weeklyOvertime = $this->calculateWeeklyOvertime($attendancesForCalc);

        $summary = [
            'workDays' => $workDays,
            'totalMinutes' => $totalMinutes,
            'overtimeDailyMinutes' => $overtimeDailyMinutes,
            'weeklyOvertime' => $weeklyOvertime,
        ];

        return view('client.approval', compact('staff', 'attendanceData', 'summary', 'year', 'month'));
    }


    public function updateApproval(Request $request, $userId)
    {
        // 承認処理
        if ($request->approved) {
            Attendance::where('user_id', $userId)
                ->whereIn('date', $request->approved)
                ->update([
                    'is_approved_by_clients' => 1,
                    'clients_approved_at' => now(),
                    'rejection' => 0,
                    'rejection_comment' => null
                ]);
        }

        // 差戻処理
        if ($request->rejected) {
            foreach ($request->rejected as $date) {
                Attendance::where('user_id', $userId)
                    ->where('date', $date)
                    ->update([
                        'rejection' => 1,
                        'is_approved_by_clients' => 0,
                        'clients_approved_at' => null,
                        'rejection_comment' => $request->rejection_comment[$date] ?? null
                    ]);
            }
        }

        return back()->with('success', '承認処理が完了しました');
    }

    public function exportPdf(Request $request)
    {
        $client = Auth::guard('client')->user();
        $staffIds = $request->input('staff_select', []); // チェックされたスタッフID
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        if (empty($staffIds)) {
            return back()->with('error', 'PDF出力するスタッフを選択してください。');
        }

        $staffs = User::where('client_id', $client->id)
            ->whereIn('id', $staffIds)
            ->get();

        $allStaffData = [];

        foreach ($staffs as $staff) {
            $attendancesForDisplay = Attendance::where('user_id', $staff->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            $attendancesForCalc = $attendancesForDisplay->where('is_other_company_work', 1);

            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
            $attendanceData = [];
            $workDays = 0;
            $totalMinutes = 0;
            $overtimeDailyMinutes = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::create($year, $month, $day)->toDateString();
                $att = $attendancesForDisplay->firstWhere('date', $date);
                $attCalc = $attendancesForCalc->firstWhere('date', $date);

                $workMinutes = 0;
                $overtimeMinutes = 0;

                if ($attCalc && $attCalc->start_time && $attCalc->end_time) {
                    $start = $this->timeToMinutes($attCalc->start_time);
                    $end = $this->timeToMinutes($attCalc->end_time);
                    $break = $attCalc->break_time ? $this->timeToMinutes($attCalc->break_time) : 0;
                    if ($end < $start) $end += 24*60;

                    $workMinutes = max($end - $start - $break, 0);
                    $overtimeMinutes = max($workMinutes - 480, 0);

                    $totalMinutes += $workMinutes;
                    $overtimeDailyMinutes += $overtimeMinutes;
                }

                if ($attCalc) {
                    switch ($attCalc->category) {
                        case '出勤':
                        case '振出':
                        case '休出':
                        case '遅刻':
                        case '早退':
                            $workDays += 1;
                            break;
                        case '午前休':
                        case '午後休':
                            $workDays += 0.5;
                            break;
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

            $weeklyOvertime = $this->calculateWeeklyOvertime($attendancesForCalc);

            $allStaffData[] = (object)[
                'id' => $staff->id,
                'name' => $staff->name,
                'attendanceData' => $attendanceData,
                'summary' => [
                    'workDays' => $workDays,
                    'totalMinutes' => $totalMinutes,
                    'overtimeDailyMinutes' => $overtimeDailyMinutes,
                    'weeklyOvertime' => $weeklyOvertime,
                ],
            ];
        }

        $pdf = PDF::loadView('pdf.client-staff-list', [
            'staffs' => $allStaffData, // Blade 側で foreach します
            'year' => $year,
            'month' => $month,
        ])
        ->setPaper('a4', 'landscape')
        ->setOption('enable-local-file-access', true);

        return $pdf->download("勤怠表_{$year}年{$month}月.pdf");
    }





}
