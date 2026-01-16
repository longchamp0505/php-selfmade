<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class WorkHoursController extends Controller
{
    public function index(Request $request)
    {
        $client = Auth::guard('client')->user();

        $year = $request->query(
            'year',
            now()->month >= 4 ? now()->year : now()->year - 1
        );

        $contractType = $request->query('contract_type', '');

        // 年度：4月～翌年3月
        $startDate = Carbon::create($year, 4, 1)->startOfDay();
        $endDate   = Carbon::create($year + 1, 3, 31)->endOfDay();

        // スタッフ取得
        $staffsQuery = User::where('client_id', $client->id);
        if($contractType) {
            $staffsQuery->where('contract_type', $contractType);
        }
        $staffs = $staffsQuery->get();

        // 契約形態一覧（セレクタ用）
        $contractTypes = User::where('client_id', $client->id)
            ->select('contract_type')
            ->distinct()
            ->pluck('contract_type');

        // 月別データ作成
        $months = [];
        for ($m = 4; $m <= 12; $m++) $months[] = $m;
        for ($m = 1; $m <= 3; $m++) $months[] = $m;

        $data = [];
        $totals = [
            'workMinutes' => 0,
            'contractMinutes' => 0,
            'overtimeMinutes' => 0,
            'workDays' => 0,
            'staffCount' => 0,
        ];

        foreach ($months as $monthNum) {
            $yearNum = $monthNum >= 4 ? $year : $year + 1;

            $monthData = [
                'workMinutes' => 0,
                'contractMinutes' => 0,
                'overtimeMinutes' => 0,
                'workDays' => 0,
                'staffCount' => 0,
            ];

            foreach ($staffs as $staff) {
                $attendances = Attendance::where('user_id', $staff->id)
                    ->where('is_other_company_work',1)
                    ->whereYear('date', $yearNum)
                    ->whereMonth('date', $monthNum)
                    ->get();

                if ($attendances->isEmpty()) continue;

                $monthData['staffCount'] += 1;

                $workMinutes = 0;
                $contractMinutes = 0;
                $dailyOverMinutes = 0;
                $workDays = 0;

                foreach($attendances as $att){
                    if($att->start_time && $att->end_time){
                        $work = $this->timeToMinutes($att->end_time) - $this->timeToMinutes($att->start_time);
                        if($work < 0) $work += 24*60;
                        $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;
                        $work = max($work - $break,0);

                        $workMinutes += $work;
                        $contractMinutes += min($work,480);
                        $dailyOverMinutes += max($work-480,0);
                    }

                    switch($att->category){
                        case '出勤': case '振出': case '休出': case '遅刻': case '早退':
                            $workDays += 1; break;
                        case '午前休': case '午後休':
                            $workDays += 0.5; break;
                    }
                }

                $weeklyOverMinutes = $attendances->count() > 0 ? $this->calculateWeeklyOvertime($attendances) : 0;
                $overtimeMinutes = $dailyOverMinutes + $weeklyOverMinutes;

                $monthData['workMinutes'] += $workMinutes;
                $monthData['contractMinutes'] += $contractMinutes;
                $monthData['overtimeMinutes'] += $overtimeMinutes;
                $monthData['workDays'] += $workDays;
            }

            // 合計に加算
            $totals['workMinutes'] += $monthData['workMinutes'];
            $totals['contractMinutes'] += $monthData['contractMinutes'];
            $totals['overtimeMinutes'] += $monthData['overtimeMinutes'];
            $totals['workDays'] += $monthData['workDays'];
            $totals['staffCount'] += $monthData['staffCount'];

            $data[] = $monthData;
        }



        return view('client.work_hours', compact('months','data','totals','year','contractTypes','contractType'));
    }

    private function timeToMinutes($time){
        [$h,$m] = explode(':', $time);
        return ((int)$h)*60 + (int)$m;
    }

    private function calculateWeeklyOvertime($attendances){
        $weeklyOvertime = 0;
        $attendances = $attendances->sortBy('date');
        $weekMinutes = 0;
        $weekStart = null;

        foreach($attendances as $att){
            $date = Carbon::parse($att->date);

            if(!$weekStart || $date->dayOfWeek === 0){
                $weekStart = $date->copy()->startOfWeek();
                $weekMinutes = 0;
            }

            if($att->start_time && $att->end_time){
                $work = $this->timeToMinutes($att->end_time) - $this->timeToMinutes($att->start_time);
                if($work < 0) $work += 24*60;
                $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;
                $work = max($work - $break,0);
                $weekMinutes += min($work,480);
            }

            if($date->dayOfWeek === 6 || $att === $attendances->last()){
                if($weekMinutes > 40*60) $weeklyOvertime += $weekMinutes - 40*60;
            }
        }

        return $weeklyOvertime;
    }
}
