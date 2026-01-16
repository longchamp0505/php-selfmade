<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceViewController extends Controller
{
    /**
     * 勤怠閲覧画面
     */
    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();
        $year = $request->query(
            'year',
            now()->month >= 4 ? now()->year : now()->year - 1
        );


        // 月番号を年度順（4月～翌年3月）
        $monthsOrder = array_merge(range(4, 12), range(1, 3));

        // ユーザーの勤怠取得（年度内）
        $attendances = Attendance::where('user_id', $user->id)
            ->where(function($q) use ($year) {
                // 4月～12月
                $q->where(function($q2) use ($year) {
                    $q2->whereYear('date', $year)->whereMonth('date', '>=', 4);
                })
                // 1月～3月
                ->orWhere(function($q2) use ($year) {
                    $q2->whereYear('date', $year + 1)->whereMonth('date', '<=', 3);
                });
            })
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

        foreach ($monthsOrder as $m) {
            $yearNum = $m >= 4 ? $year : $year + 1;
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
                    case '出勤': case '振出': case '休出': case '遅刻': case '早退':
                        $monthSummary['workDays'] += 1; break;
                    case '午前休': case '午後休':
                        $monthSummary['workDays'] += 0.5; break;
                    case '有給':
                        $monthSummary['paidLeaveDays'] += 1; break;
                    case '欠勤':
                        $monthSummary['absentDays'] += 1; break;
                }

                if ($att->category === '遅刻') $monthSummary['lateCount']++;
                if ($att->category === '早退') $monthSummary['earlyLeaveCount']++;

                $targetCategories = ['出勤','遅刻','早退','振出','休出','午前休','午後休'];
                if (in_array($att->category, $targetCategories)) {
                    $clockInExists  = !empty($att->clock_in);
                    $clockOutExists = !empty($att->clock_out);
                    if (!($clockInExists && $clockOutExists)) $monthSummary['missingClock']++;
                }
            }

            // 週40時間超
            if ($monthData->count() > 0) {
                $monthSummary['weeklyOverMinutes'] = $this->calculateWeeklyOvertime($monthData);
            }

            $monthSummary['overMinutes'] = $monthSummary['dailyOverMinutes'] + $monthSummary['weeklyOverMinutes'];

            $months[$m] = $monthSummary;

            foreach ($summary as $key => $val) {
                if (isset($monthSummary[$key])) $summary[$key] += $monthSummary[$key];
            }
        }

        $summary['totalOverMinutes'] = $summary['dailyOverMinutes'] + $summary['weeklyOverMinutes'];

        return view('user.attendance_view', [
            'year' => $year,
            'months' => $months,
            'summary' => $summary,
        ]);
    }

    /**
     * HH:MM → 分
     */
    private function timeToMinutes($time)
    {
        [$h,$m] = explode(':', $time);
        return ((int)$h)*60 + ((int)$m);
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

                // 週40h以内のみ加算
                $weekMinutes += min($workMinutes, 480);
            }

            // 土曜または最終日の場合、週超計算
            if ($date->dayOfWeek === 6 || $att === $attendances->last()) {
                if ($weekMinutes > 40*60) {
                    $weeklyOvertime += $weekMinutes - 40*60;
                }
            }
        }

        return $weeklyOvertime;
    }
}
