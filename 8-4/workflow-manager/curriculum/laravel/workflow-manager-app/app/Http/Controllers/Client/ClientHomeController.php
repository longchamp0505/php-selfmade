<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice; 

class ClientHomeController extends Controller
{
    public function index()
    {
        // 最新のお知らせを1件取得
        $notice = Notice::latest()->first();

        // ビューに渡す
        return view('client.home', compact('notice'));
    }
}
