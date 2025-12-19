<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Leave;
use App\Models\Expense;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminUserRequestController extends Controller
{
    /**
     * ユーザーの勤怠・休暇・経費をまとめて表示
     */
    public function index($user_id, Request $request)
    {
        // 年度開始年を取得
        $year  = $request->input('year', now()->month >= 4 ? now()->year : now()->year - 1);
        $month = $request->input('month', now()->month);

        // -------------------------
        // ユーザー取得
        // -------------------------
        $user = User::with('paidLeave')->findOrFail($user_id);

        // -------------------------
        // 勤怠データ（月別集計）
        // -------------------------
        $startDate = Carbon::create($year, 4, 1)->startOfDay();
        $endDate   = Carbon::create($year + 1, 3, 31)->endOfDay();

        $attendances = Attendance::where('user_id', $user_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->date)->month);

        $months = [];
        $summary = [
            'workMinutes'      => 0,
            'regularMinutes'   => 0,
            'dailyOverMinutes' => 0,
            'weeklyOverMinutes'=> 0,
            'totalOverMinutes' => 0,
            'workDays'         => 0,
            'paidLeaveDays'    => 0,
            'absentDays'       => 0,
            'lateCount'        => 0,
            'earlyLeaveCount'  => 0,
            'missingClock'     => 0,
        ];

        // 年度順：4月～3月
        $monthOrder = array_merge(range(4,12), range(1,3));

        foreach ($monthOrder as $m) {
            $monthData = $attendances->get($m, collect());

            $monthSummary = [
                'workMinutes'      => 0,
                'regularMinutes'   => 0,
                'dailyOverMinutes' => 0,
                'weeklyOverMinutes'=> 0,
                'overMinutes'      => 0,
                'workDays'         => 0,
                'paidLeaveDays'    => 0,
                'absentDays'       => 0,
                'lateCount'        => 0,
                'earlyLeaveCount'  => 0,
                'missingClock'     => 0,
            ];

            foreach ($monthData as $att) {
                $work = 0;
                $dailyOver = 0;

                if ($att->start_time && $att->end_time) {
                    $start = $this->timeToMinutes($att->start_time);
                    $end   = $this->timeToMinutes($att->end_time);
                    $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;

                    if ($end < $start) $end += 24*60;

                    $work = max($end - $start - $break, 0);
                    $dailyOver = max($work - 480, 0);
                }

                $monthSummary['workMinutes'] += $work;
                $monthSummary['regularMinutes'] += ($work - $dailyOver);
                $monthSummary['dailyOverMinutes'] += $dailyOver;

                switch ($att->category) {
                    case '出勤':
                    case '振出':
                    case '休出':
                    case '遅刻':
                    case '早退':
                        $monthSummary['workDays'] += 1;
                        break;
                    case '午前休':
                    case '午後休':
                        $monthSummary['workDays'] += 0.5;
                        break;
                    case '有給':
                        $monthSummary['paidLeaveDays'] += 1;
                        break;
                    case '欠勤':
                        $monthSummary['absentDays'] += 1;
                        break;
                }

                if ($att->category === '遅刻') $monthSummary['lateCount']++;
                if ($att->category === '早退') $monthSummary['earlyLeaveCount']++;

                $targetCategories = ['出勤','遅刻','早退','振出','休出','午前休','午後休'];
                if (in_array($att->category, $targetCategories)) {
                    $clockInExists  = !empty($att->clock_in);
                    $clockOutExists = !empty($att->clock_out);
                    if (!($clockInExists && $clockOutExists)) {
                        $monthSummary['missingClock']++;
                    }
                }
            }

            if ($monthData->count() > 0) {
                $monthSummary['weeklyOverMinutes'] = $this->calculateWeeklyOvertime($monthData);
            }

            $monthSummary['overMinutes'] = $monthSummary['dailyOverMinutes'] + $monthSummary['weeklyOverMinutes'];

            $months[$m] = $monthSummary;

            foreach ($summary as $key => $val) {
                if (isset($monthSummary[$key])) {
                    $summary[$key] += $monthSummary[$key];
                }
            }
        }

        $summary['totalOverMinutes'] = $summary['dailyOverMinutes'] + $summary['weeklyOverMinutes'];

        // -------------------------
        // 休暇データ（月単位）
        // -------------------------
        $leaves = Leave::where('user_id', $user_id)
            ->whereYear('date', $month >= 4 ? $year : $year + 1) // 年度跨ぎ考慮
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        $leavesData = $leaves->map(function ($leave) {
            $user = $leave->user;

            return [
                'id' => $leave->id,
                'user_id' => $leave->user_id,
                'name' => $user->name ?? '',
                'department' => $user->department ?? '',
                'workplace' => $user->workplace ?? '',
                'contract_type' => $user->contract_type ?? '',
                'leave_type' => $leave->leave_type,
                'date' => $leave->date,
                'note' => $leave->note,
                'is_approved_by_admins' => $leave->is_approved_by_admins,
                'is_rejection' => $leave->is_rejection,
            ];
        })->toArray();

        // -------------------------
        // 経費データ（月単位）
        // -------------------------
        $expenseList = Expense::where('user_id', $user_id)
            ->whereYear('date', $month >= 4 ? $year : $year + 1)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        // -------------------------
        // Blade に渡す
        // -------------------------
        return view('admin.staff_management', [
            'user'        => $user,
            'year'        => $year,
            'months'      => $months,
            'month'       => $month,
            'summary'     => $summary,
            'leaves'      => $leaves,
            'leavesData'  => $leavesData,
            'expenseList' => $expenseList,
        ]);
    }

    private function timeToMinutes($time)
    {
        [$h, $m] = explode(':', $time);
        return ((int)$h) * 60 + ((int)$m);
    }

    private function calculateWeeklyOvertime($attendances)
    {
        $weeklyOvertime = 0;
        $attendances = $attendances->sortBy('date');
        $weekMinutes = 0;

        foreach ($attendances as $att) {
            $date = Carbon::parse($att->date);

            if ($date->dayOfWeek === 0) { // 日曜リセット
                $weekMinutes = 0;
            }

            if ($att->start_time && $att->end_time) {
                $start = $this->timeToMinutes($att->start_time);
                $end   = $this->timeToMinutes($att->end_time);
                $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;

                if ($end < $start) $end += 24*60;
                $workMinutes = max($end - $start - $break, 0);
                $weekMinutes += min($workMinutes, 480);
            }

            if ($date->dayOfWeek === 6 || $att === $attendances->last()) {
                if ($weekMinutes > 480*5/5) { // 40h超
                    $weeklyOvertime += $weekMinutes - 480*5/5;
                }
            }
        }

        return $weeklyOvertime;
    }
}
