<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;


class AdminExpenseController extends Controller
{
    public function list(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        // 検索条件
        $keyword = $request->input('keyword');
        $department = $request->input('department');
        $contract = $request->input('contract_type');
        $status = $request->input('status');

        // 対象月の経費データを取得
        $query = Expense::with('user')
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        // ▼ ステータス絞り込み
        if ($status === 'approved') {
            $query->where('is_approved_by_admins', 1);
        } elseif ($status === 'pending') {
            $query->where('is_approved_by_admins', 0);
        }

        $expenses = $query->get();

        // ユーザーごとに集計
        $summary = [];

        foreach ($expenses as $exp) {
            $uid = $exp->user_id;

            if (!isset($summary[$uid])) {
                $summary[$uid] = [
                    'user_id' => $uid,
                    'name' => $exp->user->name ?? '',
                    'department' => $exp->user->department ?? '',
                    'workplace' => $exp->user->workplace ?? '',
                    'contract_type' => $exp->user->contract_type ?? '',
                    'total_amount' => 0,
                    'total_count' => 0,
                    'approved_count' => 0,
                    'pending_count' => 0,
                ];
            }

            $summary[$uid]['total_amount'] += $exp->amount;
            $summary[$uid]['total_count'] += 1;

            if ($exp->is_approved_by_admins) {
                $summary[$uid]['approved_count'] += 1;
            } else {
                $summary[$uid]['pending_count'] += 1;
            }
        }

        // ▼ 検索（ID / 氏名）
        if (!empty($keyword)) {
            $summary = array_filter($summary, function ($row) use ($keyword) {
                return str_contains($row['name'], $keyword) ||
                       str_contains($row['user_id'], $keyword);
            });
        }

        // ▼ 所属
        if (!empty($department)) {
            $summary = array_filter($summary, fn($row) => $row['department'] === $department);
        }

        // ▼ 契約形態
        if (!empty($contract)) {
            $summary = array_filter($summary, fn($row) => $row['contract_type'] === $contract);
        }

        // 配列 → ページネーション化
        $summary = array_values($summary);
        $perPage = 20;
        $page = $request->input('page', 1);
        $paged = array_slice($summary, ($page - 1) * $perPage, $perPage);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paged,
            count($summary),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.expense_list', [
            'year' => $year,
            'month' => $month,
            'expenseSummary' => $paged,
            'expensePaginator' => $paginator,
            'keyword' => $keyword,
            'selectedDepartment' => $department,
            'selectedContract' => $contract,
            'selectedStatus' => $status,
            'departments' => User::distinct()->pluck('department'),
            'contractTypes' => User::distinct()->pluck('contract_type')
        ]);
    }

     /** ユーザー別一覧 */
    public function userList($user_id, Request $request)
    {
        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $user = User::findOrFail($user_id);

        $expenseList = Expense::where('user_id', $user_id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->paginate(30);

        return view('admin.expense_user_list', [
            'user' => $user,
            'expenseList' => $expenseList,
            'year' => $year,
            'month' => $month,
        ]);
    }

    /** 承認 */
    public function approve(Request $request)
    {
        $exp = Expense::find($request->expense_id);
        if (!$exp) return response()->json(['success' => false]);

        $exp->is_approved_by_admins = $request->approve;
        if ($request->approve) {
            $exp->is_rejection = 0; // 承認したら差戻解除
        }
        $exp->save();

        return response()->json(['success' => true]);
    }

    /** 差戻 */
    public function reject(Request $request)
    {
        $exp = Expense::find($request->expense_id);
        if (!$exp) {
            return response()->json(['success' => false]);
        }

        if (($request->has('cancel') && $request->cancel) || 
            ($request->has('is_rejection') && $request->is_rejection === false)) {
            // 差戻解除
            $exp->is_rejection = 0;
            $exp->rejection_comment = null;
            // 承認状態は維持
        } else {
            // 差戻設定
            $exp->is_rejection = 1;
            $exp->rejection_comment = $request->rejection_comment ?? '';
            $exp->is_approved_by_admins = 0;
        }


        $exp->save();

        return response()->json(['success' => true]);
    }


    public function exportCsv(Request $request)
    {
        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        // ▼ 選択月の全スタッフ・全経費（1経費=1行）
        $expenses = Expense::with('user')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->orderBy('user_id')
            ->get();

        $response = new StreamedResponse(function () use ($expenses) {

            $fp = fopen('php://output', 'w');

            // ▼ Excel文字化け防止（UTF-8 BOM）
            fwrite($fp, "\xEF\xBB\xBF");

            // ▼ 曜日（日本語）
            $weekdayMap = [
                'Sun' => '日',
                'Mon' => '月',
                'Tue' => '火',
                'Wed' => '水',
                'Thu' => '木',
                'Fri' => '金',
                'Sat' => '土',
            ];

            // ▼ CSVヘッダー（※ 経費IDなし／年・月・日 分離）
            fputcsv($fp, [
                'スタッフID',
                'スタッフ名',
                '所属',
                '年',
                '月',
                '日',
                '曜日',
                '種別',
                '金額',
                '支払先',
                '用途',
                '領収書番号',
                '申請状況',
                '承認状況',
                '差戻',
                '差戻コメント',
                '作成日時',
                '更新日時',
            ]);

            foreach ($expenses as $exp) {

                $date = Carbon::parse($exp->date);
                $weekday = $weekdayMap[$date->format('D')] ?? '';

                fputcsv($fp, [
                    $exp->user_id,
                    $exp->user->name ?? '',
                    $exp->user->department ?? '',
                    $date->year,
                    $date->month,
                    $date->day,
                    $weekday,
                    $exp->category,
                    number_format($exp->amount), // ★ 円マークなし・カンマあり
                    $exp->payee,
                    $exp->purpose,
                    $exp->invoice_number,
                    $exp->is_submitted ? '申請済' : '未申請',
                    $exp->is_approved_by_admins ? '承認' : '未承認',
                    $exp->is_rejection ? 'あり' : '',
                    $exp->rejection_comment,
                    $exp->created_at,
                    $exp->updated_at,
                ]);
            }

            fclose($fp);
        });

        $fileName = "経費精算_{$year}_{$month}.csv";

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            "attachment; filename={$fileName}"
        );

        return $response;
    }



}
