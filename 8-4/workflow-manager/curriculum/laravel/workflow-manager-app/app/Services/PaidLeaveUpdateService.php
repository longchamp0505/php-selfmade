<?php

namespace App\Services;

use App\Models\User;
use App\Models\PaidLeave;
use App\Models\PaidLeaveHistory;
use Carbon\Carbon;

class PaidLeaveUpdateService
{
    /**
     * 勤続年数と所定労働日数から付与日数を計算
     */
    private function calculateGrantDays(User $user)
    {
        $hireDate = Carbon::parse($user->hire_date);
        $today = Carbon::today();
        $months = $hireDate->diffInMonths($today);

        // 勤続年数に応じた週5日換算付与日数
        if ($months < 6) {
            $baseDays = 0;
        } elseif ($months < 18) {
            $baseDays = 10;
        } elseif ($months < 30) {
            $baseDays = 11;
        } elseif ($months < 42) {
            $baseDays = 12;
        } elseif ($months < 54) {
            $baseDays = 14;
        } elseif ($months < 66) {
            $baseDays = 16;
        } elseif ($months < 78) {
            $baseDays = 18;
        } else {
            $baseDays = 20;
        }

        // 所定労働日数で比例計算（週2～5対応）
        $scheduledDays = $user->scheduled_days ?: 5; // nullの場合は5日換算
        if ($scheduledDays < 2) return 0; // 週1以下は付与なし

        $grantDays = round($baseDays * $scheduledDays / 5);

        return $grantDays;
    }

    /**
     * 特定ユーザーの有給更新
     */
    public function updateForUser(User $user)
    {
        $paidLeave = $user->paidLeave;
        if (!$paidLeave) return;

        $today = Carbon::today();
        $grantedDate = Carbon::parse($paidLeave->granted_date);

        // 付与日が過ぎていたら更新
        if ($today->gt($grantedDate)) {

            // --------------------
            // 履歴保存
            // --------------------
            PaidLeaveHistory::create([
                'paid_leave_id'        => $paidLeave->id,
                'user_id'              => $user->id,
                'remaining_days'       => $paidLeave->remaining_days,
                'granted_date'         => $paidLeave->granted_date,
                'current_year_taken'   => $paidLeave->current_year_taken,
                'last_year_granted'    => $paidLeave->last_year_granted,
                'last_year_carried'    => $paidLeave->last_year_carried,
                'last_year_taken'      => $paidLeave->last_year_taken,
                'two_years_ago_granted'=> $paidLeave->two_years_ago_granted,
                'two_years_ago_carried'=> $paidLeave->two_years_ago_carried,
                'next_expiration_date' => $paidLeave->next_expiration_date,
                'expiration_days'      => $paidLeave->expiration_days,
            ]);

            // --------------------
            // 新規付与日数計算
            // --------------------
            $grantDays = $this->calculateGrantDays($user);

            // --------------------
            // 前年度・一昨年度情報の更新
            // --------------------
            $paidLeave->two_years_ago_granted = $paidLeave->last_year_granted;
            $paidLeave->two_years_ago_carried = $paidLeave->last_year_carried;

            $paidLeave->last_year_granted = $grantDays;
            $paidLeave->last_year_carried = $paidLeave->remaining_days; // 前年度繰越
            $paidLeave->last_year_taken   = $paidLeave->current_year_taken;

            $paidLeave->current_year_taken = 0;
            $paidLeave->remaining_days    += $grantDays;

            // --------------------
            // 次回有給付与日・失効予定日
            // --------------------
            $nextGrantedDate = $grantedDate->copy()->addYear();
            $paidLeave->granted_date = $nextGrantedDate;
            $paidLeave->next_expiration_date = $nextGrantedDate;

            // --------------------
            // 失効予定日数（マイナスは0）
            // --------------------
            $expirationDays = $paidLeave->last_year_carried - $paidLeave->current_year_taken;
            $paidLeave->expiration_days = max(0, $expirationDays);

            $paidLeave->save();
        }
    }

    /**
     * 全ユーザーの有給更新
     */
    public function updateForAll()
    {
        $users = User::with('paidLeave')->get();
        foreach ($users as $user) {
            $this->updateForUser($user);
        }
    }
}
