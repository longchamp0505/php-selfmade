<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PaidLeave;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class AdminUserCreateController extends Controller
{
    /**
     * ▼ 新規作成画面
     */
    public function create()
    {
        $employmentTypes = [
            '正社員', '契約社員', '派遣', 'アルバイト', 'パート'
        ];

        $contractTypes = [
            '常駐', 'SES', '業務委託', '派遣'
        ];

        $clients = Client::all();

        // ▼ 新規 ID を計算（4桁ゼロ埋め）
        $lastUser = User::orderBy('id', 'desc')->first();
        $nextIdNumber = $lastUser ? ((int)$lastUser->id + 1) : 1;
        $nextId = str_pad($nextIdNumber, 4, '0', STR_PAD_LEFT);

        return view('admin.staff_create', compact(
            'employmentTypes', 'contractTypes', 'clients', 'nextId'
        ));
    }

    /**
     * ▼ 登録処理
     */
    public function store(Request $request)
    {
        // バリデーションルール
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
            'last_year_granted' => 'required|integer|min:0|max:999',   // ➋ 前年度付与日数
            'last_year_taken' => 'required|integer|min:0|max:999',     // ➍ 前年度取得日数
            'two_years_ago_granted' => 'required|integer|min:0|max:999', // ➎ 一昨年度付与日数
            'two_years_ago_carried' => 'required|integer|min:0|max:999', // ➏ 一昨年度繰越日数
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
            'current_year_taken.integer' => '➊ 当年度取得日数は整数で入力してください。',
            'current_year_taken.min' => '➊ 当年度取得日数は0以上で入力してください。',
            'current_year_taken.max' => '➊ 当年度取得日数は999以下で入力してください。',
            'last_year_granted.required' => '➋ 前年度付与日数は必須です。',
            'last_year_taken.required' => '➍ 前年度取得日数は必須です。',
            'two_years_ago_granted.required' => '➎ 一昨年度付与日数は必須です。',
            'two_years_ago_carried.required' => '➏ 一昨年度繰越日数は必須です。',
            'granted_date.required' => '有給付与日は必須です。',
            'granted_date.date' => '有給付与日は正しい日付形式で入力してください。',
        ];

        // バリデーション実行
        $request->validate($rules, $messages);

        // --------------------
        // 前年度繰越・有給残・失効予定日数計算
        // --------------------
        $lastYearCarried = $request->two_years_ago_granted + $request->two_years_ago_carried - $request->last_year_taken;
        $remainingDays = $request->last_year_granted + $lastYearCarried - $request->current_year_taken;
        $expirationDays = max(0, $lastYearCarried - $request->current_year_taken);

        // マイナスチェックのエラーを残す
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

        // ▼ 新規 ID 計算（4桁ゼロ埋め）
        $lastUser = User::orderBy('id', 'desc')->first();
        $nextIdNumber = $lastUser ? ((int)$lastUser->id + 1) : 1;
        $nextId = str_pad($nextIdNumber, 4, '0', STR_PAD_LEFT);

        // ▼ ユーザー作成
        $user = User::create([
            'id' => $nextId,
            'name' => $request->name,
            'name_kana' => $request->name_kana,
            'hire_date' => $request->hire_date,
            'department' => $request->department,
            'scheduled_days' => $request->scheduled_days,
            'employment_type' => $request->employment_type,
            'workplace' => $request->workplace,
            'contract_type' => $request->contract_type,
            'client_id' => $request->client_id ?: null,
            'password' => Hash::make('000000'),
        ]);

        // ▼ 有給情報作成
        PaidLeave::create([
            'user_id' => $user->id,
            'remaining_days' => $remainingDays,
            'granted_date' => $request->granted_date,
            'current_year_taken' => $request->current_year_taken,
            'last_year_granted' => $request->last_year_granted,   // ➋
            'last_year_taken' => $request->last_year_taken,       // ➍
            'last_year_carried' => $lastYearCarried,
            'two_years_ago_granted' => $request->two_years_ago_granted,
            'two_years_ago_carried' => $request->two_years_ago_carried,
            'expiration_days' => $expirationDays,
        ]);

        return redirect()
            ->route('admin.user.list')
            ->with('success', 'スタッフを登録しました（初期PW：000000）');
    }
}
