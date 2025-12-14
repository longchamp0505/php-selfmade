<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;


class AdminClientController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;
        $status  = $request->status;

        // 基本のクエリ
        $query = Client::query();

        // キーワード検索（ID or 会社名）
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('id', 'LIKE', "%{$keyword}%")
                  ->orWhere('company_name', 'LIKE', "%{$keyword}%");
            });
        }

        // 稼働人数（Userテーブルのclient_id一致数）をサブクエリで付与
        $query->withCount(['users']);

        // ステータス絞り込み
        if ($status === 'approved') {
            $query->having('users_count', '>', 0);
        } elseif ($status === 'pending') {
            $query->having('users_count', '=', 0);
        }

        $clients = $query->paginate(20);

        return view('admin.client_list', [
            'clients' => $clients,
            'keyword' => $keyword,
            'selectedStatus' => $status,
        ]);
    }

    public function edit($client_id, Request $request)
    {
        $client = Client::findOrFail($client_id);

        $year = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        // ★ 稼働スタッフデータを admin 側で作成
        $staffData = $this->getStaffMonthlyData($client->id, $year, $month);

        return view('admin.client_edit', compact('client', 'year', 'month', 'staffData'));
    }

    /**
     * クライアント基本情報の更新
     */
    public function update(Request $request, $client_id)
    {
        $client = Client::findOrFail($client_id);

        $client->update([
            'company_name'      => $request->company_name,
            'department_name'   => $request->department_name,
            'contact'           => $request->contact, // ← 修正
            'approver1_name'    => $request->approver1_name,
            'approver1_email'   => $request->approver1_email,
            'approver2_name'    => $request->approver2_name,
            'approver2_email'   => $request->approver2_email,
            'approver3_name'    => $request->approver3_name,
            'approver3_email'   => $request->approver3_email,
        ]);

        return back()->with('success', 'クライアント情報を更新しました。');
    }


    /**
     * パスワードリセット
     */
    public function resetPassword(Request $request, $client_id)
    {
        $client = Client::findOrFail($client_id);

        $newPassword = '000000';

        $client->update([
            'password' => Hash::make($newPassword),
        ]);

        return back()->with('success', 'パスワードをリセットしました。');
    }

    /**
     * クライアントに紐づくスタッフの月次稼働データ
     */
    private function getStaffMonthlyData($clientId, $year, $month)
    {
        // クライアントのスタッフ
        $staffs = User::where('client_id', $clientId)->get();

        $staffData = [];

        foreach ($staffs as $staff) {
            // 他社作業のみ
            $attendances = Attendance::where('user_id', $staff->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('is_other_company_work', 1)
                ->get();

            $allApproved = $attendances->isNotEmpty() && $attendances->every(
                fn($a) => $a->is_approved_by_clients
            );

            $workMinutes = 0;
            $regularMinutes = 0;
            $dailyOverMinutes = 0;

            foreach ($attendances as $att) {
                if ($att->start_time && $att->end_time) {
                    $work = $this->timeToMinutes($att->end_time) - $this->timeToMinutes($att->start_time);
                    if ($work < 0) $work += 1440;

                    $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;

                    $work = max($work - $break, 0);

                    $workMinutes += $work;
                    $regularMinutes += min($work, 480);
                    $dailyOverMinutes += max($work - 480, 0);
                }
            }

            $weeklyOverMinutes = $attendances->count() > 0
                ? $this->calculateWeeklyOvertime($attendances)
                : 0;

            $overMinutes = $dailyOverMinutes + $weeklyOverMinutes;

            $staffData[] = [
                'id'               => $staff->id,
                'name'             => $staff->name,
                'contract_type'    => $staff->contract_type ?? 'ー',
                'workMinutes'      => $workMinutes,
                'contractMinutes'  => $regularMinutes,
                'overtimeMinutes'  => $overMinutes,
                'workDays'         => $attendances->count(),
                'status'           => $allApproved ? '完了' : '未完了',
            ];
        }

        return $staffData;
    }

    private function timeToMinutes($time)
    {
        [$h, $m] = explode(':', $time);
        return $h * 60 + $m;
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
                if ($work < 0) $work += 1440;

                $break = $att->break_time ? $this->timeToMinutes($att->break_time) : 0;
                $weekMinutes += min(max($work - $break, 0), 480);
            }

            if ($date->dayOfWeek === 6 || $att === $attendances->last()) {
                if ($weekMinutes > 2400) {
                    $weeklyOvertime += $weekMinutes - 2400;
                }
            }
        }

        return $weeklyOvertime;
    }

    public function staffApproval($client_id, $staff_id, Request $request)
    {
        $client = Client::findOrFail($client_id);

        $year  = $request->query('year', now()->year);
        $month = $request->query('month', now()->month);

        // 月次データを取得
        $staffData = $this->getStaffMonthlyData($client_id, $year, $month);

        // 表示したいスタッフ1名だけ抽出
        $staff = collect($staffData)->firstWhere('id', $staff_id);

        if (!$staff) {
            abort(404, 'スタッフデータが存在しません');
        }

        return view('admin.client_staff_approval', compact(
            'client',
            'staff',
            'year',
            'month'
        ));
    }

}
