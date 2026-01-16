<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Client;
use App\Models\Admin;
use App\Services\PaidLeaveUpdateService;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        // URLからユーザータイプを判定
        $path = $request->path(); 
        $type = explode('/', $path)[1]; // user, client, admin

        return view('login', ['type' => $type]);
    }

    public function login(Request $request)
    {
        $type = $request->input('type');

        // ログイン先 Guard 判定
        $guard = match($type) {
            'user' => 'user',
            'client' => 'client',
            'admin' => 'admin',
            default => null,
        };

        if (!$guard) {
            return back()->withErrors(['type' => '不正なログインタイプです']);
        }

        if (Auth::guard($guard)->attempt([
            'id' => $request->id,
            'password' => $request->password,
        ])) {

            // ログイン成功時に有給更新
            $paidLeaveService = new PaidLeaveUpdateService();

            switch($type){
                case 'user':
                    $user = Auth::guard($guard)->user();
                    $paidLeaveService->updateForUser($user);
                    return redirect()->route('user.home')
                        ->with('success', 'ログイン時に有給情報を更新しました');
                case 'client':
                    return redirect()->route('client.home');
                case 'admin':
                    $paidLeaveService->updateForAll();
                    return redirect()->route('admin.home')
                        ->with('success', '全スタッフの有給情報を更新しました');
            }
        }

        return back()->withErrors(['id' => 'IDまたはパスワードが間違っています']);
    }

    public function logout(Request $request)
    {
        $type = $request->input('type');

        // Guard 取得
        $guard = match($type) {
            'user' => 'user',
            'client' => 'client',
            'admin' => 'admin',
            default => null,
        };

        if ($guard) {
            Auth::guard($guard)->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['type' => $type]);
    }
}
