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

        $employmentTypes = ['正社員', '契約社員', '派遣', 'アルバイト', 'パート'];
        $contractTypes   = ['常駐', 'SES', '業務委託', '派遣'];
        $clients         = Client::all();

        return view('admin.staff_edit', compact(
            'user', 'employmentTypes', 'contractTypes', 'clients'
        ));
    }

    // ▼ 更新処理
    public function update(Request $request, $user_id)
    {
        $user = User::with('paidLeave')->findOrFail($user_id);

        // --------------------
        // バリデーション
        // --------------------
        $rules = [
            'name' => 'required|string|max:100',
            'name_kana' => 'required|string|max:100',
            'hire_date' => 'required|date',
            'department' => 'nullable|string|max:100',
            'scheduled_days' => 'required|integer|min:1|max:5',
            'employment_type' => 'required|string|max:50',
            'workplace' => 'required|string|max:100',
            'contract_type' => 'required|string|max:50',
            'client_id' => 'nullable|exists:clients,id',
            'current_year_taken' => 'required|integer|min:0|max:999',
            'last_year_taken' => 'required|integer|min:0|max:999',
            'last_year_granted' => 'required|integer|min:0|max:999',
            'two_years_ago_granted' => 'required|integer|min:0|max:999',
            'two_years_ago_carried' => 'required|integer|min:0|max:999',
            'granted_date' => 'required|date',
        ];

        $messages = [
            'name.required' => '氏名は必須です。',
            'name.max' => '氏名は100文字以内で入力してください。',
            'name_kana.required' => 'フリガナは必須です。',
            'name_kana.max' => 'フリガナは100文字以内で入力してください。',
            'hire_date.required' => '入社日は必須です。',
            'hire_date.date' => '入社日は正しい日付形式で入力してください。',
            'department.max' => '所属は100文字以内で入力してください。',
            'scheduled_days.required' => '所定労働日数は必須です。',
            'scheduled_days.integer' => '所定労働日数は整数で入力してください。',
            'scheduled_days.min' => '所定労働日数は1以上で入力してください。',
            'scheduled_days.max' => '所定労働日数は5以下で入力してください。',
            'employment_type.required' => '雇用形態は必須です。',
            'workplace.required' => '勤務先は必須です。',
            'contract_type.required' => '契約形態は必須です。',
            'client_id.exists' => '勤怠承認者①に正しい値を選択してください。',
            'current_year_taken.required' => '➊ 当年度取得日数は必須です。',
            'last_year_taken.required' => '➍ 前年度取得日数は必須です。',
            'last_year_granted.required' => '➋ 前年度付与日数は必須です。',
            'two_years_ago_granted.required' => '➎ 一昨年度付与日数は必須です。',
            'two_years_ago_carried.required' => '➏ 一昨年度繰越日数は必須です。',
            'granted_date.required' => '有給付与日は必須です。',
            'granted_date.date' => '有給付与日は正しい日付形式で入力してください。',
        ];

        $request->validate($rules, $messages);

        // --------------------
        // User更新
        // --------------------
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

        $user->touch();

        if ($user->paidLeave) {
            // --------------------
            // 有給情報計算（PHPで強制再計算）
            // --------------------
            $twoYearsAgoG = (int)$request->two_years_ago_granted;
            $twoYearsAgoC = (int)$request->two_years_ago_carried;
            $lastYearTakenInput = (int)$request->last_year_taken;
            $currentYearTaken = (int)$request->current_year_taken;
            $lastYearGrantedInput = (int)$request->last_year_granted;

            $lastYearCarried = $twoYearsAgoG + $twoYearsAgoC - $lastYearTakenInput;
            $remainingDays = $lastYearGrantedInput + $lastYearCarried - $currentYearTaken;
            $expirationDays = max(0, $lastYearCarried - $currentYearTaken);

            // --------------------
            // マイナスチェック
            // --------------------
            $errors = [];
            if ($lastYearCarried < 0) {
                $errors['last_year_carried'] = '前年度繰越日数がマイナスになります。';
            }
            if ($remainingDays < 0) {
                $errors['remaining_days'] = '有給残日数がマイナスになります。';
            }

            if ($errors) {
                return back()->withErrors($errors)->withInput();
            }

            // --------------------
            // PaidLeave更新
            // --------------------
            $user->paidLeave->update([
                'granted_date'           => $request->granted_date,
                'current_year_taken'     => $currentYearTaken,
                'last_year_taken'        => $lastYearTakenInput,
                'last_year_granted'      => $lastYearGrantedInput,
                'last_year_carried'      => $lastYearCarried,
                'remaining_days'         => $remainingDays,
                'expiration_days'        => $expirationDays,
                'two_years_ago_granted'  => $twoYearsAgoG,
                'two_years_ago_carried'  => $twoYearsAgoC,
            ]);

            $user->paidLeave->touch();
        }

        return redirect()
            ->route('admin.user.edit', ['user_id' => $user->id])
            ->with('success', '変更が完了しました');
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
