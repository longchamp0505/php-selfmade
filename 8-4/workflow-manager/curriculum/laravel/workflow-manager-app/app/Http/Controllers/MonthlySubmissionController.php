<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Client;
use App\Mail\MonthlyAttendanceSubmitted;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class MonthlySubmissionController extends Controller
{
    public function submitMonthly(Request $request)
    {
        $user = auth()->user();

        // client_id がない場合は対象外
        if (!$user->client_id) {
            abort(403);
        }

        $year  = $request->year;
        $month = $request->month;

        // 月初・月末
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = Carbon::create($year, $month, 1)->endOfMonth();

        // 未申請の勤怠が1件でもあればNG
        $notSubmittedExists = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->where('is_submitted', 0)
            ->exists();

        if ($notSubmittedExists) {
            return back()->withErrors('未申請の勤怠があるため月次申請できません');
        }

        // クライアント取得
        $client = Client::findOrFail($user->client_id);

        // 送信先メール（重複・空除外）
        $emails = collect([
            $client->approver1_email,
            $client->approver2_email,
            $client->approver3_email,
        ])->filter()->unique()->values()->toArray();

        // メール送信のみ
        Mail::to($emails)->send(
            new MonthlyAttendanceSubmitted($user, $client, $year, $month)
        );

        return back()->with('success', '月次申請を送信しました');
    }
}
