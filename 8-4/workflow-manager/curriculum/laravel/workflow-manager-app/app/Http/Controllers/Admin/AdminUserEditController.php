<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PaidLeave;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class AdminUserEditController extends Controller
{
    // ▼ 編集画面
    public function edit($user_id)
    {
        $user = User::with('paidLeave')->findOrFail($user_id);

        $employmentTypes = [
            '正社員', '契約社員', '派遣', 'アルバイト', 'パート'
        ];

        $contractTypes = [
            '常駐', 'SES', '業務委託', '派遣'
        ];

        $clients = Client::all();

        return view('admin.staff_edit', compact(
            'user', 'employmentTypes', 'contractTypes', 'clients'
        ));
    }

    // ▼ 更新処理
    public function update(Request $request, $user_id)
    {
        $user = User::with('paidLeave')->findOrFail($user_id);

        // --- User更新 ---
        $user->update([
            'name'            => $request->name,
            'name_kana'       => $request->name_kana,
            'hire_date'       => $request->hire_date,
            'department'      => $request->department,
            'scheduled_days'  => $request->scheduled_days,
            'employment_type' => $request->employment_type,
            'workplace'       => $request->workplace,
            'contract_type'   => $request->contract_type,
            'client_id'       => $request->client_id ?: null,
            'retire_date'     => $request->retire_date,
        ]);

        // ★ users.updated_at を更新
        $user->touch();

        // --- PaidLeave更新 ---
        if ($user->paidLeave) {
            $user->paidLeave->update([
                'remaining_days'         => $request->remaining_days,
                'granted_date'           => $request->granted_date,
                'current_year_taken'     => $request->current_year_taken,
                'last_year_taken'        => $request->last_year_taken,
                'last_year_carried'      => $request->last_year_carried,
                'two_years_ago_taken'    => $request->two_years_ago_taken,
                'two_years_ago_granted'  => $request->two_years_ago_granted,
                'two_years_ago_carried'  => $request->two_years_ago_carried,
                'next_expiration_date'   => $request->next_expiration_date,
                'expiration_days'        => $request->expiration_days,
            ]);

            // ★ updated_at を更新
            $user->paidLeave->touch();
        }


        return redirect()
            ->route('admin.user.edit', ['user_id' => $user->id])
            ->with('success', '更新しました');
    }

    // ▼ パスワード初期化
    public function resetPassword($user_id)
    {
        $user = User::findOrFail($user_id);

        $newPassword = '000000';

        $user->password = Hash::make($newPassword);
        $user->save();

        return redirect()
            ->route('admin.user.edit', ['user_id' => $user->id])
            ->with('success', "パスワードを初期化しました：{$newPassword}");
    }
}
