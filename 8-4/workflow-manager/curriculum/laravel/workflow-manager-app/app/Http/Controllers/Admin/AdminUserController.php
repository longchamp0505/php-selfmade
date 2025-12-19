<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $selectedDepartment = $request->input('department');
        $selectedContract = $request->input('contract_type');
        $paidLeave5days = $request->input('paid_leave_5days');
        $retired = $request->input('retired');

        // ベースクエリ
        $query = User::with('paidLeave');

        // デフォルトは在籍のみ
        if($retired === '1') {
            $query->whereNotNull('retire_date'); // 退職済み
        } else {
            $query->whereNull('retire_date'); // 在籍
        }

        // キーワード検索
        if($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('id', 'like', "%{$keyword}%")
                  ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        // 部署・契約形態フィルタ
        if($selectedDepartment) $query->where('department', $selectedDepartment);
        if($selectedContract) $query->where('contract_type', $selectedContract);

        // 有給5日取得フィルタ
        if ($request->has('paid_leave_5days')) {
            if ($request->paid_leave_5days == 1) {
                // 5日以上取得
                $query->whereHas('paidLeave', function($q) {
                    $q->where('current_year_taken', '>=', 5);
                });
            } elseif ($request->paid_leave_5days == 0) {
                // 5日未取得
                $query->whereHas('paidLeave', function($q) {
                    $q->where('current_year_taken', '<', 5);
                });
            }
        }

        $users = $query->orderBy('id')->paginate(15)->appends($request->query());

        // 部署・契約形態のセレクト用
        $departments = User::select('department')->distinct()->pluck('department');
        $contractTypes = User::select('contract_type')->distinct()->pluck('contract_type');

        return view('admin.staff_list', compact(
            'users', 'keyword', 'selectedDepartment', 'selectedContract',
            'paidLeave5days', 'retired', 'departments', 'contractTypes'
        ));
    }

}
