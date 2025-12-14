<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Client;
use App\Models\Admin;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        // URLからユーザータイプを判定
        $path = $request->path(); // 例: login/user
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
            switch($type){
                case 'user':
                    return redirect()->route('user.home');
                case 'client':
                    return redirect()->route('client.home');
                case 'admin':
                    return redirect()->route('admin.home');
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
