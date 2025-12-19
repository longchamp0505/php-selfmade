<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Client;
use App\Models\Admin;

class PasswordController extends Controller
{
    public function showChangeForm($type)
    {
        return view('password-change', ['type' => $type]);
    }

    public function update(Request $request, $type)
    {
        $request->validate(
            [
                'id' => 'required|string',
                'old_password' => 'required|string',
                'password' => 'required|string|min:4|confirmed',
            ],
            [
                
                'password.confirmed' => 'パスワード確認が一致しません。',
            ]
        );

        switch ($type) {
            case 'user':
                $model = User::class;
                break;
            case 'client':
                $model = Client::class;
                break;
            case 'admin':
                $model = Admin::class;
                break;
            default:
                return back()->withErrors(['type' => '不正なタイプです']);
        }

        $user = $model::where('id', $request->id)->first();

        if (!$user) {
            return back()->withErrors(['id' => '該当ユーザーが存在しません']);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return back()->withErrors(['old_password' => '現在のパスワードが違います']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('login', ['type' => $type])
            ->with('success', 'パスワードを変更しました。ログインし直してください。');
    }

}
