<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\MonthlySubmissionController;
use App\Http\Controllers\User\UserHomeController;
use App\Http\Controllers\Client\ClientHomeController;
use App\Http\Controllers\Admin\AdminHomeController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\ExpenseController;
use App\Http\Controllers\User\LeaveController;
use App\Http\Controllers\User\AttendanceViewController;
use App\Http\Controllers\Client\UserListController;
use App\Http\Controllers\Client\WorkHoursController;
use App\Http\Controllers\Admin\AdminApprovalController;
use App\Http\Controllers\Admin\AdminApprovalDetailController;
use App\Http\Controllers\Admin\AdminAttendanceEditController;
use App\Http\Controllers\Admin\AdminLeaveController;
use App\Http\Controllers\Admin\AdminExpenseController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminUserEditController;
use App\Http\Controllers\Admin\AdminUserRequestController;
use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\AdminUserCreateController;
use App\Http\Controllers\Admin\AdminClientCreateController;
use App\Http\Controllers\Admin\AdminNoticeController;


/*
|--------------------------------------------------------------------------
| ✅ ログイン（URLで user / client / admin を判断）
|--------------------------------------------------------------------------
*/
Route::get('/login/{type}', [LoginController::class, 'showLoginForm'])
    ->where('type', 'user|client|admin')
    ->name('login');

Route::post('/login/{type}', [LoginController::class, 'login'])
    ->where('type', 'user|client|admin')
    ->name('login.submit');

/*
|--------------------------------------------------------------------------
| ✅ ログアウト（各 Guard に対応）
|--------------------------------------------------------------------------
*/
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| ✅ パスワード変更
|--------------------------------------------------------------------------
*/
Route::get('/password/change/{type}', [PasswordController::class, 'showChangeForm'])
    ->where('type', 'user|client|admin')
    ->name('password.change');

Route::post('/password/change/{type}', [PasswordController::class, 'update'])
    ->where('type', 'user|client|admin')
    ->name('password.update');

/*
|--------------------------------------------------------------------------
| ✅ ユーザー：勤怠機能（auth:user）
|--------------------------------------------------------------------------
*/
Route::prefix('user')->name('user.')->middleware('auth:user')->group(function () {
    Route::get('/home', [UserHomeController::class, 'index'])->name('home');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendance/end', [AttendanceController::class, 'end'])->name('attendance.end');
    Route::get('/attendance/input', [AttendanceController::class, 'input'])->name('attendance.input');
    Route::post('/attendance/submit', [AttendanceController::class, 'submit'])->name('attendance.submit');
    // サマリ取得用 Ajax ルート
    Route::get('/attendance/summary', [AttendanceController::class, 'getSummary'])->name('attendance.getSummary');

    Route::get('/attendance/pdf/{year}/{month}', [AttendanceController::class, 'pdf'])->name('attendance.pdf');

     // 勤怠閲覧
    Route::get('/attendance/view', [AttendanceViewController::class, 'index'])->name('attendance.view');

    Route::post('/attendance/monthly-submit', [MonthlySubmissionController::class, 'submitMonthly'])->name('attendance.monthly.submit');

});

/*
|--------------------------------------------------------------------------
| ✅ ユーザー：経費機能（auth:user）
|--------------------------------------------------------------------------
*/

Route::prefix('user')->name('user.')->middleware('auth')->group(function () {
    // 経費申請一覧
    Route::get('/expense', [ExpenseController::class, 'index'])->name('expense.index');

    // 申請処理
    Route::post('expense', [ExpenseController::class, 'store'])->name('expense.store');

    // 再申請処理
    Route::post('/expense/{id}/resubmit', [ExpenseController::class, 'resubmit'])->name('expense.resubmit');
});

/*
|--------------------------------------------------------------------------
| ✅ ユーザー：休暇機能（auth:user）
|--------------------------------------------------------------------------
*/


Route::prefix('user')->name('user.')->middleware(['auth'])->group(function() {
    Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('leave', [LeaveController::class, 'store'])->name('leave.store');

    // 再申請 — JSON 取得用
    Route::get('leave/{id}/edit', [LeaveController::class, 'edit'])->name('leave.edit');

    // 再申請 POST 実行
    Route::post('leave/{id}/resubmit', [LeaveController::class, 'resubmit'])->name('leave.resubmit');
});




/*
|--------------------------------------------------------------------------
| ✅ クライアントホーム（auth:client）
|--------------------------------------------------------------------------
*/
Route::prefix('client')->name('client.')->middleware('auth:client')->group(function () {
    Route::get('/home', [ClientHomeController::class, 'index'])->name('home');

     // 対象スタッフ一覧（年月セレクト＋一覧表示）
    Route::get('/staff-list', [UserListController::class, 'index'])->name('staff.list');

     // PDF出力
    Route::get('/staff-list/pdf', [UserListController::class, 'exportPdf'])->name('staff.export.pdf');

    Route::get('/staff/{id}/approval', [UserListController::class, 'approval'])->name('staff.approval');

    Route::post('/client/staff/{id}/approval/update', [UserListController::class, 'updateApproval'])
    ->name('staff.approval.update');

    Route::get('/work-hours', [WorkHoursController::class, 'index'])
    ->name('work-hours.index');

    Route::post('/staff-list/pdf', [UserListController::class, 'exportPdf'])
    ->name('client.staff.export.pdf');



});

/*
|--------------------------------------------------------------------------
| ✅ 管理者ホーム（auth:admin）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::get('/home', [AdminHomeController::class, 'index'])->name('home');

    // 一覧
    Route::get('/staff', [AdminApprovalController::class, 'index'])->name('staff.list');

    // CSV出力
    Route::get('/export/csv', [AdminApprovalController::class, 'exportCsv'])->name('export.csv');

    // PDF出力（選択スタッフ）
    Route::post('/approval/pdf', [AdminApprovalController::class, 'exportPdf'])->name('approval.pdf');

    // 勤怠承認画面
    Route::get('attendance/approval/{staff}', [AdminApprovalDetailController::class, 'index'])
        ->name('attendance.approval');

    // PDF出力
    Route::get('attendance/approval/{staff}/pdf/{year}/{month}', [AdminApprovalDetailController::class, 'pdf'])
        ->name('attendance.approval.pdf');

    Route::post('attendance/{staff}/update', [AdminApprovalDetailController::class, 'updateApproval'])->name('attendance.approval.update');

    // 勤怠修正画面
    Route::get('attendance/edit/{staff}', [AdminAttendanceEditController::class, 'index'])
        ->name('attendance.edit');

    // 勤怠データ保存（submit）
    Route::post('attence/edit/{staff}/submit', [AdminAttendanceEditController::class, 'submit'])
        ->name('attendance.submit');

    
    // アカウント発行（スタッフ）
    Route::get('/user/create', [AdminUserCreateController::class, 'create'])->name('user.create');
    Route::post('/user/store', [AdminUserCreateController::class, 'store'])->name('user.store');

    // アカウント発行nda（クライアント）
    Route::get('/client/create', [AdminClientCreateController::class, 'create'])->name('client.create');
    Route::post('/client/store', [AdminClientCreateController::class, 'store'])->name('client.store');

    // 休暇承認
    Route::post('/leave/approve', [AdminLeaveController::class, 'approve'])->name('leave.approve');

    // 休暇差戻
    Route::post('/leave/reject', [AdminLeaveController::class, 'reject'])->name('leave.reject');

    // 休暇申請一覧
    Route::get('/leaves', [AdminLeaveController::class, 'index'])->name('leave.list');

    // CSV出力
    Route::get('/leaves/csv', [AdminLeaveController::class, 'exportCsv'])->name('leave.export.csv');

    // 経費一覧（あなたの Blade に対応）
    Route::get('/expense/list', [AdminExpenseController::class, 'list'])
        ->name('expense.list');

    // CSV 出力
    Route::get('/expense/export/csv', [AdminExpenseController::class, 'exportCsv'])
        ->name('expense.export.csv');

    // ユーザー別：詳細画面
    Route::get('/user/{user_id}', [AdminExpenseController::class, 'userList'])->name('expense.user.list');

    // 承認
    Route::post('/approve', [AdminExpenseController::class, 'approve'])->name('approve');

    // 差戻
    Route::post('/reject', [AdminExpenseController::class, 'reject'])->name('reject');

    // スタッフ一覧
    Route::get('users', [AdminUserController::class, 'index'])->name('user.list');

    // スタッフ編集・詳細
    Route::get('/users/{user_id}/edit', [AdminUserEditController::class, 'edit'])->name('user.edit');

    Route::put('/users/{user_id}/update', [AdminUserEditController::class, 'update'])->name('user.update');

    Route::post('/users/{user_id}/password/reset', [AdminUserEditController::class, 'resetPassword'])->name('user.password.reset');

    // スタッフの申請・勤怠一覧
    Route::get('/user/{user_id}/requests', [AdminUserRequestController::class, 'index'])
    ->name('user.requests');

    // クライアント一覧
    Route::get('/clients', [AdminClientController::class, 'index'])->name('client.list');

    // クライアント編集・詳細
    Route::get('/clients/{client_id}/edit', [AdminClientController::class, 'edit'])
        ->name('client.edit');

    Route::put('/clients/{client_id}/update', [AdminClientController::class, 'update'])
        ->name('client.update');

    Route::post('/clients/{client_id}/password/reset', [AdminClientController::class, 'resetPassword'])
        ->name('client.password.reset');

    // お知らせ管理
    Route::get('/notice', [AdminNoticeController::class, 'edit'])->name('notice.edit');
    Route::put('/notice', [AdminNoticeController::class, 'update'])->name('notice.update');






});


