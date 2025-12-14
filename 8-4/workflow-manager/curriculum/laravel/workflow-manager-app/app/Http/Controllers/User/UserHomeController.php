<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice; 

class UserHomeController extends Controller
{
    public function index()
    {
        // 最新のお知らせを1件取得
        $notice = Notice::latest()->first();

        // ビューに渡す
        return view('user.home', compact('notice'));
    }
}
