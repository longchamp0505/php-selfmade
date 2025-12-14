<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice;

class AdminNoticeController extends Controller
{
    // ▼ 編集画面
    public function edit()
    {
        $notice = Notice::first(); // お知らせは1件管理
        return view('admin.notice', compact('notice'));
    }

    // ▼ 更新処理
    public function update(Request $request)
    {
        $request->validate([
            'content_staff'  => 'nullable|string',
            'content_client' => 'nullable|string',
        ]);

        $notice = Notice::first();

        if (!$notice) {
            $notice = Notice::create([
                'content_staff'  => $request->content_staff,
                'content_client' => $request->content_client,
            ]);
        } else {
            $notice->update([
                'content_staff'  => $request->content_staff,
                'content_client' => $request->content_client,
            ]);
        }

        return redirect()->route('admin.notice.edit')->with('success', 'お知らせを更新しました');
    }
}
