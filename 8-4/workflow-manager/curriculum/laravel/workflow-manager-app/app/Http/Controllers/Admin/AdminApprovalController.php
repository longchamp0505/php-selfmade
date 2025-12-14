<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class AdminApprovalController extends Controller
{
    public function index(Request $request)
    {
        $year  = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);
        $keyword = $request->query('keyword', '');
        $selectedDepartment = $request->query('department', '');
        $selectedContract   = $request->query('contract_type', '');
        $selectedStatus     = $request->query('status', '');

        // ページネーションで取得
        $staffs = User::with('client')
            ->when($keyword, fn($q) =>
                $q->where('id', 'like', "%$keyword%")
                ->orWhere('name', 'like', "%$keyword%")
            )
            ->when($selectedDepartment, fn($q) =>
                $q->where('department', $selectedDepartment)
            )
            ->when($selectedContract, fn($q) =>
                $q->where('contract_type', $selectedContract)
            )
            ->when($selectedStatus, function ($q) use ($year, $month, $selectedStatus) {

                // ===== 完了 =====
                if ($selectedStatus === '完了') {
                    $q->whereHas('attendances', function ($a) use ($year, $month) {
                        $a->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->where('is_approved_by_clients', 1);
                    });
                }

                // ===== 未完了（未申請含む）=====
                if ($selectedStatus === '未完了') {
                    $q->where(function ($qq) use ($year, $month) {

                        // ① 勤怠が1件もない
                        $qq->whereDoesntHave('attendances', function ($a) use ($year, $month) {
                            $a->whereYear('date', $year)
                            ->whereMonth('date', $month);
                        })

                        // OR

                        // ② 勤怠はあるが未承認
                        ->orWhereHas('attendances', function ($a) use ($year, $month) {
                            $a->whereYear('date', $year)
                            ->whereMonth('date', $month)
                            ->where(function ($sub) {
                                $sub->whereNull('is_approved_by_clients')
                                    ->orWhere('is_approved_by_clients', 0);
                            });
                        });
                    });
                }
            })
            ->paginate(15);



        // PDF用の全データは別に生成する
        $staffData = [];
        foreach ($staffs as $staff) {
            $attendances = Attendance::where('user_id', $staff->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            $allApproved = $attendances->isNotEmpty() &&
                        $attendances->every(fn($a) => $a->is_approved_by_clients);

            $workMinutes = 0;
            $regularMinutes = 0;
            $dailyOverMinutes = 0;

            foreach ($attendances as $att) {
                if ($att->start_time && $att->end_time) {
                    $work = $this->timeToMinutes($att->end_time) - $this->timeToMinutes($att->start_time);
                    if ($work < 0) $work += 24*60;
                    $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;
                    $work = max($work - $break, 0);

                    $workMinutes     += $work;
                    $regularMinutes  += min($work, 480);
                    $dailyOverMinutes += max($work - 480, 0);
                }
            }

            $weeklyOverMinutes = $attendances->count() > 0
                ? $this->calculateWeeklyOvertime($attendances)
                : 0;

            $overMinutes = $dailyOverMinutes + $weeklyOverMinutes;

            $staffData[] = [
                'id'              => $staff->id,
                'name'            => $staff->name,
                'workplace'       => $staff->workplace ?? '-',
                'contract_type'   => $staff->contract_type ?? '-',
                'department'      => $staff->department ?? '-',
                'workMinutes'     => $workMinutes,
                'contractMinutes' => $regularMinutes,
                'overtimeMinutes' => $overMinutes,
                'workDays'        => $attendances->count(),
                'status'          => $allApproved ? '完了' : '未完了',
            ];
        }

        // セレクト用データ
        $departments = User::select('department')->distinct()->pluck('department');
        $contractTypes = User::select('contract_type')->distinct()->pluck('contract_type');

        return view('admin.attendance_staff_list', compact(
            'staffs',       // ← Blade側でページネーション用
            'staffData',    // ← PDF用
            'year', 'month',
            'departments', 'contractTypes',
            'selectedDepartment', 'selectedContract', 'selectedStatus', 'keyword'
        ));
    }


    private function timeToMinutes($time)
    {
        [$h, $m] = explode(':', $time);
        return ((int)$h) * 60 + (int)$m;
    }

    private function minutesToTime($minutes)
    {
        if ($minutes <= 0) {
            return '00:00';
        }

        $h = floor($minutes / 60);
        $m = $minutes % 60;

        return sprintf('%02d:%02d', $h, $m);
    }


    private function calculateWeeklyOvertime($attendances)
    {
        $weeklyOvertime = 0;
        $attendances = $attendances->sortBy('date');
        $weekMinutes = 0;

        foreach ($attendances as $index => $att) {
            $date = Carbon::parse($att->date);

            if ($date->dayOfWeek === 0) {
                $weekMinutes = 0;
            }

            if ($att->start_time && $att->end_time) {
                $work = $this->timeToMinutes($att->end_time) - $this->timeToMinutes($att->start_time);
                if ($work < 0) $work += 24*60;
                $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;
                $work = max($work - $break, 0);

                $weekMinutes += min($work, 480);
            }

            if ($date->dayOfWeek === 6 || $index === count($attendances) - 1) {
                if ($weekMinutes > 40*60) {
                    $weeklyOvertime += $weekMinutes - 40*60;
                }
            }
        }

        return $weeklyOvertime;
    }

    public function exportCsv(Request $request)
    {
        $year  = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        $filename = "勤怠データ_{$year}_{$month}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($year, $month) {
            // Excel / スプレッドシートの文字化け対策
            echo "\xEF\xBB\xBF";

            $handle = fopen('php://output', 'w');

            // ヘッダー行（← 年・月を追加）
            fputcsv($handle, [
                '年',
                '月',
                '日',
                '曜日',
                'スタッフID',
                '氏名',
                '所属',
                '契約形態',
                '区分',
                '他社勤務',
                '勤怠打刻(始業)',
                '始業',
                '勤怠打刻(終業)',
                '終業',
                '休憩',
                '備考',
                '申請',
                'クライアント承認',
                '管理者承認',
            ]);

            $staffs = User::all();

            foreach ($staffs as $staff) {

                $attendances = Attendance::where('user_id', $staff->id)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->get()
                    ->keyBy(fn($a) => $a->date);

                $start = Carbon::create($year, $month, 1);
                $end   = $start->copy()->endOfMonth();

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

                    /** @var Attendance|null $att */
                    $att = $attendances->get($date->toDateString());

                    fputcsv($handle, [
                        $year,
                        $month,
                        $date->day,
                        ['日','月','火','水','木','金','土'][$date->dayOfWeek],

                        $staff->id,
                        $staff->name,
                        $staff->department ?? '-',
                        $staff->contract_type ?? '-',

                        $att?->category ?? '',
                        ($att && $att->is_other_company_work) ? '●' : '',

                        $att?->clock_in   ? Carbon::parse($att->clock_in)->format('H:i') : '',
                        $att?->start_time ? Carbon::parse($att->start_time)->format('H:i') : '',
                        $att?->clock_out  ? Carbon::parse($att->clock_out)->format('H:i') : '',
                        $att?->end_time   ? Carbon::parse($att->end_time)->format('H:i') : '',
                        $att?->break_time ? Carbon::parse($att->break_time)->format('H:i') : '',

                        $att?->remarks ?? '',
                        ($att && $att->is_submitted) ? '申請済' : '',
                        ($att && $att->is_approved_by_clients) ? '承認' : '',
                        ($att && $att->is_approved_by_admins) ? '承認' : '',
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }



    public function exportPdf(Request $request)
    {
        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        // ★ 追加：チェックされたスタッフID
        $staffIds = $request->input('staff_select', []);

        if (empty($staffIds)) {
            return redirect()->back()->with('error', 'PDF出力するスタッフを選択してください');
        }

        // ★ ここだけ変更
        $staffs = User::with('client')
            ->whereIn('id', $staffIds)
            ->get();

        foreach ($staffs as $staff) {

            // ===== 以下は「元の計算ロジック」そのまま =====

            $attendances = Attendance::where('user_id', $staff->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get()
                ->keyBy('date');

            $attendanceData = [];
            $totalMinutes = 0;
            $overtimeDailyMinutes = 0;
            $workDays = 0;

            $start = Carbon::create($year, $month, 1);
            $end   = $start->copy()->endOfMonth();

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

                $att = $attendances->get($date->toDateString());

                $workMinutes = 0;
                $overtimeMinutes = 0;

                if ($att && $att->start_time && $att->end_time) {
                    $startMin = $this->timeToMinutes($att->start_time);
                    $endMin   = $this->timeToMinutes($att->end_time);
                    if ($endMin < $startMin) $endMin += 24 * 60;

                    $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;

                    $workMinutes = max($endMin - $startMin - $break, 0);
                    $overtimeMinutes = max($workMinutes - 480, 0);

                    $totalMinutes += $workMinutes;
                    $overtimeDailyMinutes += $overtimeMinutes;
                }

                if ($att) {
                    switch ($att->category) {
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
                    'date' => $date->toDateString(),
                    'weekday' => ['日','月','火','水','木','金','土'][$date->dayOfWeek],
                    'attendance' => $att,
                    'workMinutes' => $workMinutes,
                    'overtimeMinutes' => $overtimeMinutes,
                ];
            }

            $weeklyOvertime = $this->calculateWeeklyOvertime($attendances->values());

            // Blade用
            $staff->attendanceData = $attendanceData;
            $staff->summary = [
                'workDays' => $workDays,
                'totalMinutes' => $totalMinutes,
                'overtimeDailyMinutes' => $overtimeDailyMinutes,
                'weeklyOvertime' => $weeklyOvertime,
            ];
        }

        $pdf = PDF::loadView('pdf.admin-staff-list', compact('staffs', 'year', 'month'))
            ->setPaper('a4', 'landscape')
            ->setOption('enable-local-file-access', true);

        return $pdf->download("勤怠表_{$year}_{$month}.pdf");
    }


}
