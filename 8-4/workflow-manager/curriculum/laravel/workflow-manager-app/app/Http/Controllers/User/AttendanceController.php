<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;

class AttendanceController extends Controller
{
    // 勤怠画面
    public function index()
    {
        $userId = Auth::guard('user')->id();
        $today = Carbon::today()->toDateString();

        // 今日の勤怠データを取得
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        return view('user.attendance', [
            'attendance' => $attendance,
        ]);
    }

     // 出勤
    public function start()
    {
        $today = Carbon::today()->toDateString();
        $userId = Auth::guard('user')->id();

        // 当日データがなければ作成
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $userId, 'date' => $today],
            [
                'day_of_week' => Carbon::today()->isoFormat('dd'),
                'category' => '出勤',
                'clock_in' => Carbon::now(),
            ]
        );

        // UIでボタン制御しているので重複警告は不要
        $attendance->update([
            'clock_in' => Carbon::now(),
            'category' => '出勤',
        ]);

        return redirect()->route('user.attendance')->with('success', 'おはようございます！');
    }

    // 退勤
    public function end(Request $request)
    {
        $userId = Auth::guard('user')->id();
        $now = Carbon::now();

        // 最新の未退勤レコードを取得
        $attendance = Attendance::where('user_id', $userId)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->orderBy('date', 'desc')
            ->first();

        if (!$attendance) {
            return redirect()->route('user.attendance')
                ->with('error', '出勤打刻を先にしてください');
        }

        // 日を跨いでいる場合のフラグ
        $isNextDay = $attendance->date != $now->toDateString();

        if ($isNextDay && !$request->has('confirm')) {
            // 確認画面にリダイレクト
            return redirect()->route('user.attendance')
                ->with('confirm', '日を跨いだ退勤となりますが宜しいでしょうか？');
        }

        // 退勤時間を更新
        $attendance->update([
            'clock_out' => $now,
        ]);

        return redirect()->route('user.attendance')->with('success', 'おつかれさまでした！');
    }



    public function input(Request $request)
    {
        $userId = Auth::guard('user')->id();
        $user = Auth::guard('user')->user(); // ← ここを追加

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        // 当月の勤怠データ取得
        $attendances = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        // 日ごとの計算データ作成
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
                $break = $this->timeToMinutes($breakTime);

                if ($end < $start) $end += 24 * 60; // 日跨ぎ対応

                $workMinutes = max($end - $start - $break, 0);
                $overtimeMinutes = max($workMinutes - 480, 0);

                $totalMinutes += $workMinutes;
                $overtimeDailyMinutes += $overtimeMinutes;
            }

            // 出勤日数
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
                'date' => $date,
                'weekday' => Carbon::parse($date)->locale('ja')->isoFormat('dd'),
                'attendance' => $att,
                'workMinutes' => $workMinutes,
                'overtimeMinutes' => $overtimeMinutes,
            ];
        }

        $weeklyOvertime = $this->calculateWeeklyOvertime($attendances);

        return view('user.attendance_input', [
            'year' => $year,
            'month' => $month,
            'attendanceData' => $attendanceData,
            'summary' => [
                'workDays' => $workDays,
                'totalMinutes' => $totalMinutes,
                'overtimeDailyMinutes' => $overtimeDailyMinutes,
                'weeklyOvertime' => $weeklyOvertime,
            ],
            'user' => $user, // ← これを追加
        ]);
    }


    // 時間(HH:MM) → 分
    private function timeToMinutes($time)
    {
        [$h, $m] = explode(':', $time);
        return ((int)$h) * 60 + ((int)$m);
    }

    // 分 → HH:MM
    private function minutesToTime($minutes)
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }

    // 週40時間超過計算
    private function calculateWeeklyOvertime($attendances)
    {
        $weeklyOvertime = 0;

        // 勤怠を日付順にソート
        $attendances = $attendances->sortBy('date');

        $weekStart = null;
        $weekMinutes = 0;

        foreach ($attendances as $att) {
            $date = Carbon::parse($att->date);

            // 新しい週（日曜起算）になったらリセット
            if (!$weekStart || $date->dayOfWeek === 0) {
                $weekStart = $date->copy()->startOfWeek(); // 日曜開始
                $weekMinutes = 0;
            }

            if ($att->start_time && $att->end_time) {
                $start = $this->timeToMinutes($att->start_time);
                $end   = $this->timeToMinutes($att->end_time);
                $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;

                if ($end < $start) $end += 24*60; // 日跨ぎ対応

                $workMinutes = max($end - $start - $break, 0);

                // 所定内8時間までを週時間に加算
                $weekMinutes += min($workMinutes, 480);
            }

            // 週末(日曜開始で土曜終わり)または最終日なら計算
            if ($date->dayOfWeek === 6 || $att === $attendances->last()) {
                if ($weekMinutes > 40*60) {
                    $weeklyOvertime += $weekMinutes - 40*60;
                }
            }
        }

        return $weeklyOvertime;
    }


    // Ajaxで1日ごとに申請更新
    public function submit(Request $request)
    {
        $userId = Auth::guard('user')->id();
        $date = $request->input('date');
        $category = $request->input('category');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $breakTime = $request->input('break_time');

        // 必須項目チェック（申請時のみ）
        if ($request->boolean('is_submitted')) {

            // 始業・終業が不要な区分
            $noTimeRequiredCategories = ['公休', '有給', '振休', '欠勤'];

            // 区分は常に必須
            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => '区分を選択してください。'
                ]);
            }

            // 始業・終業が必要な区分のみチェック
            if (!in_array($category, $noTimeRequiredCategories, true)) {
                if (!$startTime || !$endTime) {
                    return response()->json([
                        'status' => 'error',
                        'message' => '始業・終業を入力してください。'
                    ]);
                }
            }
        }


        $attendance = Attendance::firstOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'category' => $category,
                'day_of_week' => \Carbon\Carbon::parse($date)->isoFormat('dd'),
            ]
        );

        // 承認済みなら編集不可
        if ($attendance->is_approved_by_admins) {
            return response()->json([
                'status' => 'error',
                'message' => '承認済みの勤怠は編集できません',
                'is_approved' => true,
            ]);
        }

        // 再申請なら差戻し解除
        if ($request->boolean('is_submitted')) {
            $attendance->rejection = 0;
            $attendance->rejection_comment = null;
        }

        $attendance->fill([
            'category' => $category,
            'day_of_week' => \Carbon\Carbon::parse($date)->isoFormat('dd'),
            'is_other_company_work' => $request->boolean('is_other_company_work'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'break_time' => $breakTime,
            'remarks' => $request->input('remarks'),
            'is_submitted' => $request->boolean('is_submitted'),
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => '保存しました',
            'rejection' => $attendance->rejection,
            'rejection_comment' => $attendance->rejection_comment,
            'is_approved' => $attendance->is_approved_by_admins,
        ]);
    }



    // 勤怠サマリ取得
    public function getSummary(Request $request)
    {
        $userId = Auth::guard('user')->id();
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $attendances = Attendance::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $weeklyOvertime = $this->calculateWeeklyOvertime($attendances);

        return response()->json([
            'weeklyOvertime' => $weeklyOvertime
        ]);
    }

    // コントローラー内
    private function getAttendanceData($year, $month)
    {
        $userId = Auth::guard('user')->id();
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
                $break = $this->timeToMinutes($breakTime);

                if ($end < $start) $end += 24 * 60;

                $workMinutes = max($end - $start - $break, 0);
                $overtimeMinutes = max($workMinutes - 480, 0);

                $totalMinutes += $workMinutes;
                $overtimeDailyMinutes += $overtimeMinutes;
            }

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

    public function pdf($year, $month)
    {
        $data = $this->getAttendanceData($year, $month);

        // ログインユーザー情報を取得
        $user = Auth::guard('user')->user(); // ← ここがポイント

        return PDF::loadView('pdf.attendance-input', [
            'attendanceData' => $data['attendanceData'],
            'summary' => $data['summary'],
            'year' => $year,
            'month' => $month,
            'user' => $user, // ← PDFブレードに渡す
        ])
        ->setPaper('a4', 'landscape')
        ->setOption('enable-local-file-access', true)
        ->download("勤怠表_{$year}年{$month}月.pdf");
    }



}
