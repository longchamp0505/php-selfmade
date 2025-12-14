<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class AdminClientCreateController extends Controller
{
    // ▼ 新規作成画面
    public function create()
    {
        // ▼ 次のクライアントIDを取得（1001から）
        $lastClient = Client::orderBy('id', 'desc')->first();
        $nextId = $lastClient ? $lastClient->id + 1 : 1001;

        return view('admin.client_create', compact('nextId'));
    }

    // ▼ 登録処理
    public function store(Request $request)
    {
        // バリデーションルール
        $rules = [
            'company_name'     => 'required|string|max:100',
            'department_name'  => 'nullable|string|max:100',
            'contact'          => 'nullable|string|max:100',
            'approver1_name'   => 'nullable|string|max:100',
            'approver1_email'  => 'nullable|email|max:100',
            'approver2_name'   => 'nullable|string|max:100',
            'approver2_email'  => 'nullable|email|max:100',
            'approver3_name'   => 'nullable|string|max:100',
            'approver3_email'  => 'nullable|email|max:100',
        ];

        // カスタムメッセージ
        $messages = [
            'company_name.required'    => '会社名は必須です。',
            'company_name.max'         => '会社名は100文字以内で入力してください。',
            'department_name.max'      => '部署名は100文字以内で入力してください。',
            'contact.max'              => '連絡先は100文字以内で入力してください。',
            'approver1_name.max'       => '担当者➊の氏名は100文字以内で入力してください。',
            'approver1_email.email'    => '担当者➊のメールアドレスは正しい形式で入力してください。',
            'approver1_email.max'      => '担当者➊のメールアドレスは100文字以内で入力してください。',
            'approver2_name.max'       => '担当者➋の氏名は100文字以内で入力してください。',
            'approver2_email.email'    => '担当者➋のメールアドレスは正しい形式で入力してください。',
            'approver2_email.max'      => '担当者➋のメールアドレスは100文字以内で入力してください。',
            'approver3_name.max'       => '担当者➌の氏名は100文字以内で入力してください。',
            'approver3_email.email'    => '担当者➌のメールアドレスは正しい形式で入力してください。',
            'approver3_email.max'      => '担当者➌のメールアドレスは100文字以内で入力してください。',
        ];

        $request->validate($rules, $messages);

        // ▼ クライアントID自動採番
        $lastClient = Client::orderBy('id', 'desc')->first();
        $nextId = $lastClient ? $lastClient->id + 1 : 1001;

        // 保存
        Client::create([
            'id'               => $nextId,
            'company_name'     => $request->company_name,
            'department_name'  => $request->department_name,
            'contact'          => $request->contact,
            'approver1_name'   => $request->approver1_name,
            'approver1_email'  => $request->approver1_email,
            'approver2_name'   => $request->approver2_name,
            'approver2_email'  => $request->approver2_email,
            'approver3_name'   => $request->approver3_name,
            'approver3_email'  => $request->approver3_email,
            'password'        => Hash::make('000000'),
        ]);

        return redirect()->route('admin.client.list')->with('success', 'クライアントを登録しました');
    }
}
