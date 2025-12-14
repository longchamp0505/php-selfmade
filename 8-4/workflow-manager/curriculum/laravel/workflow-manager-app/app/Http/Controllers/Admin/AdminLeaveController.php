<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class AdminLeaveController extends Controller
{
    // ▼ 一覧表示
    public function index(Request $request)
    {
        $year  = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;

        $departments   = User::select('department')->distinct()->pluck('department');
        $contractTypes = User::select('contract_type')->distinct()->pluck('contract_type');

        $query = Leave::with(['user', 'user.paidLeave'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        // キーワード検索
        if ($request->keyword) {
            $keyword = $request->keyword;
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('id', 'like', "%$keyword%")
                  ->orWhere('name', 'like', "%$keyword%");
            });
        }

        // 所属
        if ($request->department) {
            $query->whereHas('user', fn($q) => $q->where('department', $request->department));
        }

        // 契約形態
        if ($request->contract_type) {
            $query->whereHas('user', fn($q) => $q->where('contract_type', $request->contract_type));
        }

        // ステータス
        if ($request->status === '承認') {
            $query->where('is_approved_by_admins', 1);
        } elseif ($request->status === '差戻') {
            $query->where('is_rejection', 1);
        } elseif ($request->status === '未処理') {
            $query->where('is_approved_by_admins', 0)->where('is_rejection', 0);
        }

        $leaves = $query->orderBy('date', 'desc')->paginate(20);

        // Blade 用に配列化
        $leavesData = $leaves->map(function ($leave) {
            return [
                'id'                     => $leave->id,
                'user_id'                => $leave->user->id,
                'name'                   => $leave->user->name,
                'department'             => $leave->user->department,
                'workplace'              => $leave->user->workplace,
                'contract_type'          => $leave->user->contract_type,
                'leave_type'             => $leave->leave_type,
                'date'                   => $leave->date,
                'note'                   => $leave->note,
                'remaining_days'         => $leave->user->paidLeave->remaining_days ?? 0,
                'current_year_taken'     => $leave->user->paidLeave->current_year_taken ?? 0,
                'is_approved_by_admins'  => $leave->is_approved_by_admins,
                'is_rejection'           => $leave->is_rejection,
            ];
        })->toArray();

        return view('admin.leave_list', [
            'year'               => $year,
            'month'              => $month,
            'departments'        => $departments,
            'contractTypes'      => $contractTypes,
            'selectedDepartment' => $request->department,
            'selectedContract'   => $request->contract_type,
            'selectedStatus'     => $request->status,
            'keyword'            => $request->keyword,
            'leaves'             => $leaves,
            'leavesData'         => $leavesData,
        ]);
    }

    // ▼ CSV出力
    public function exportCsv(Request $request)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $leaves = Leave::with('user')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->orderBy('user_id')
            ->get();

        $response = new StreamedResponse(function () use ($leaves) {

            $fp = fopen('php://output', 'w');

            // Excel用BOM
            fwrite($fp, "\xEF\xBB\xBF");

            $weekdayMap = [
                'Sun' => '日',
                'Mon' => '月',
                'Tue' => '火',
                'Wed' => '水',
                'Thu' => '木',
                'Fri' => '金',
                'Sat' => '土',
            ];

            fputcsv($fp, [
                'スタッフID',
                'スタッフ名',
                '所属',
                '年',
                '月',
                '日',
                '曜日',
                '休暇種別',
                '備考',
                '申請状況',
                '承認状況',
                '差戻',
                '差戻コメント',
                '管理者承認日時',
                '作成日時',
                '更新日時',
            ]);

            foreach ($leaves as $leave) {
                $date = Carbon::parse($leave->date);

                fputcsv($fp, [
                    $leave->user_id,
                    $leave->user->name ?? '',
                    $leave->user->department ?? '',
                    $date->year,
                    $date->month,
                    $date->day,
                    $weekdayMap[$date->format('D')] ?? '',
                    $leave->leave_type,
                    $leave->note,
                    $leave->is_submitted ? '申請済' : '未申請',
                    $leave->is_approved_by_admins ? '承認' : '未承認',
                    $leave->is_rejection ? 'あり' : '',
                    $leave->rejection_comment,
                    $leave->admins_approved_at,
                    $leave->created_at,
                    $leave->updated_at,
                ]);
            }

            fclose($fp);
        });

        $fileName = "休暇申請_{$year}_{$month}.csv";

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            "attachment; filename={$fileName}"
        );

        return $response;
    }

    public function approve(Request $request)
    {
        $leaveId = $request->leave_id;
        $approve = $request->approve; // 1 or 0

        $leave = Leave::find($leaveId);
        if ($leave) {
            $leave->is_approved_by_admins = $approve;
            $leave->admins_approved_at = $approve ? now() : null;
            $leave->save();
        }

        return response()->json(['success' => true]);
    }

}