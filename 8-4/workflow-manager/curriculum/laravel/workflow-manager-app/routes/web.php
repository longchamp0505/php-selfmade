<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\MonthlySubmissionController;

use App\Http\Controllers\User\UserHomeController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\AttendanceViewController;
use App\Http\Controllers\User\ExpenseController;
use App\Http\Controllers\User\LeaveController;

use App\Http\Controllers\Client\ClientHomeController;
use App\Http\Controllers\Client\UserListController;
use App\Http\Controllers\Client\WorkHoursController;

use App\Http\Controllers\Admin\AdminHomeController;
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
| ログイン
|--------------------------------------------------------------------------
*/

// ログイン画面表示（user / client / admin）
Route::get('/login/{type}', [LoginController::class, 'showLoginForm'])
    ->where('type', 'user|client|admin')
    ->name('login');

// ログイン処理
Route::post('/login/{type}', [LoginController::class, 'login'])
    ->where('type', 'user|client|admin')
    ->name('login.submit');

// ログアウト処理
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


/*
|--------------------------------------------------------------------------
| パスワード変更
|--------------------------------------------------------------------------
*/

// パスワード変更画面表示
Route::get('/password/change/{type}', [PasswordController::class, 'showChangeForm'])
    ->where('type', 'user|client|admin')
    ->name('password.change');

// パスワード変更処理
Route::post('/password/change/{type}', [PasswordController::class, 'update'])
    ->where('type', 'user|client|admin')
    ->name('password.update');


/*
|--------------------------------------------------------------------------
| ユーザー（auth:user）
|--------------------------------------------------------------------------
*/
Route::prefix('user')->name('user.')->middleware('auth:user')->group(function () {

    // ユーザーホーム画面
    Route::get('/home', [UserHomeController::class, 'index'])->name('home');

    // 勤怠一覧表示
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');

    // 出勤打刻
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');

    // 退勤打刻
    Route::post('/attendance/end', [AttendanceController::class, 'end'])->name('attendance.end');

    // 勤怠入力画面表示
    Route::get('/attendance/input', [AttendanceController::class, 'input'])->name('attendance.input');

    // 勤怠登録処理
    Route::post('/attendance/submit', [AttendanceController::class, 'submit'])->name('attendance.submit');

    // 勤怠サマリ取得（Ajax）
    Route::get('/attendance/summary', [AttendanceController::class, 'getSummary'])->name('attendance.getSummary');

    // 勤怠PDF出力
    Route::get('/attendance/pdf/{year}/{month}', [AttendanceController::class, 'pdf'])->name('attendance.pdf');

    // 勤怠閲覧専用画面
    Route::get('/attendance/view', [AttendanceViewController::class, 'index'])->name('attendance.view');

    // 勤怠月次申請
    Route::post('/attendance/monthly-submit', [MonthlySubmissionController::class, 'submitMonthly'])
        ->name('attendance.monthly.submit');

    // 経費申請一覧表示
    Route::get('/expense', [ExpenseController::class, 'index'])->name('expense.index');

    // 経費申請登録
    Route::post('/expense', [ExpenseController::class, 'store'])->name('expense.store');

    // 経費再申請
    Route::post('/expense/{id}/resubmit', [ExpenseController::class, 'resubmit'])
        ->name('expense.resubmit');

    // 休暇申請一覧表示
    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');

    // 休暇申請登録
    Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');

    // 休暇再申請用データ取得
    Route::get('/leave/{id}/edit', [LeaveController::class, 'edit'])->name('leave.edit');

    // 休暇再申請処理
    Route::post('/leave/{id}/resubmit', [LeaveController::class, 'resubmit'])
        ->name('leave.resubmit');
});


/*
|--------------------------------------------------------------------------
| クライアント（auth:client）
|--------------------------------------------------------------------------
*/
Route::prefix('client')->name('client.')->middleware('auth:client')->group(function () {

    // クライアントホーム画面
    Route::get('/home', [ClientHomeController::class, 'index'])->name('home');

    // 担当スタッフ一覧表示
    Route::get('/staff-list', [UserListController::class, 'index'])->name('staff.list');

    // スタッフ勤怠PDF出力
    Route::get('/staff-list/pdf', [UserListController::class, 'exportPdf'])->name('staff.export.pdf');
    Route::post('/staff-list/pdf', [UserListController::class, 'exportPdf']) ->name('client.staff.export.pdf');

    // スタッフ勤怠承認画面
    Route::get('/staff/{id}/approval', [UserListController::class, 'approval'])->name('staff.approval');

    // スタッフ勤怠承認更新
    Route::post('/client/staff/{id}/approval/update', [UserListController::class, 'updateApproval']) ->name('staff.approval.update');

    // 稼働時間一覧表示
    Route::get('/work-hours', [WorkHoursController::class, 'index'])->name('work-hours.index');
});


/*
|--------------------------------------------------------------------------
| 管理者（auth:admin）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {

    // 管理者ホーム画面
    Route::get('/home', [AdminHomeController::class, 'index'])->name('home');

    // 勤怠承認対象スタッフ一覧
    Route::get('/staff', [AdminApprovalController::class, 'index'])->name('staff.list');

    // 勤怠CSV出力
    Route::get('/export/csv', [AdminApprovalController::class, 'exportCsv'])->name('export.csv');

    // 勤怠PDF出力
    Route::post('/approval/pdf', [AdminApprovalController::class, 'exportPdf'])->name('approval.pdf');

    // スタッフ別勤怠承認画面
    Route::get('/attendance/approval/{staff}', [AdminApprovalDetailController::class, 'index'])
        ->name('attendance.approval');

    // スタッフ別勤怠PDF出力
    Route::get('/attendance/approval/{staff}/pdf/{year}/{month}', [AdminApprovalDetailController::class, 'pdf'])
        ->name('attendance.approval.pdf');

    // 勤怠承認更新
    Route::post('/attendance/{staff}/update', [AdminApprovalDetailController::class, 'updateApproval'])
        ->name('attendance.approval.update');

    // 勤怠修正画面
    Route::get('/attendance/edit/{staff}', [AdminAttendanceEditController::class, 'index'])
        ->name('attendance.edit');

    // 勤怠修正登録
    Route::post('/attendance/edit/{staff}/submit', [AdminAttendanceEditController::class, 'submit'])
        ->name('attendance.submit');

    // スタッフ作成画面
    Route::get('/user/create', [AdminUserCreateController::class, 'create'])->name('user.create');

    // スタッフ作成処理
    Route::post('/user/store', [AdminUserCreateController::class, 'store'])->name('user.store');

    // クライアント作成画面
    Route::get('/client/create', [AdminClientCreateController::class, 'create'])->name('client.create');

    // クライアント作成処理
    Route::post('/client/store', [AdminClientCreateController::class, 'store'])->name('client.store');

    // スタッフ一覧表示
    Route::get('/users', [AdminUserController::class, 'index'])->name('user.list');

    // スタッフの申請・勤怠一覧
    Route::get('/user/{user_id}/requests', [AdminUserRequestController::class, 'index'])
        ->name('user.requests');


    // スタッフ編集画面
    Route::get('/users/{user_id}/edit', [AdminUserEditController::class, 'edit'])->name('user.edit');

    // スタッフ更新処理
    Route::put('/users/{user_id}/update', [AdminUserEditController::class, 'update'])->name('user.update');

    // スタッフパスワード初期化
    Route::post('/users/{user_id}/password/reset', [AdminUserEditController::class, 'resetPassword'])
        ->name('user.password.reset');

    // クライアント一覧表示
    Route::get('/clients', [AdminClientController::class, 'index'])->name('client.list');

    // クライアント編集画面
    Route::get('/clients/{client_id}/edit', [AdminClientController::class, 'edit'])->name('client.edit');

    // クライアント更新処理
    Route::put('/clients/{client_id}/update', [AdminClientController::class, 'update'])
        ->name('client.update');

    // クライアントパスワード初期化
    Route::post('/clients/{client_id}/password/reset', [AdminClientController::class, 'resetPassword'])
        ->name('client.password.reset');

    // 休暇申請一覧
    Route::get('/leaves', [AdminLeaveController::class, 'index'])->name('leave.list');

    // 休暇承認
    Route::post('/leave/approve', [AdminLeaveController::class, 'approve'])->name('leave.approve');

    // 休暇差戻
    Route::post('/leave/reject', [AdminLeaveController::class, 'reject'])->name('leave.reject');

    // 休暇CSV出力
    Route::get('/leaves/csv', [AdminLeaveController::class, 'exportCsv'])->name('leave.export.csv');

    // 経費一覧
    Route::get('/expense/list', [AdminExpenseController::class, 'list'])->name('expense.list');

    // 経費CSV出力
    Route::get('/expense/export/csv', [AdminExpenseController::class, 'exportCsv'])
        ->name('expense.export.csv');

    // スタッフ別経費一覧
    Route::get('/user/{user_id}', [AdminExpenseController::class, 'userList'])
        ->name('expense.user.list');

    // 経費承認
    Route::post('/approve', [AdminExpenseController::class, 'approve'])->name('approve');

    // 経費差戻
    Route::post('/reject', [AdminExpenseController::class, 'reject'])->name('reject');

    // お知らせ編集画面
    Route::get('/notice', [AdminNoticeController::class, 'edit'])->name('notice.edit');

    // お知らせ更新
    Route::put('/notice', [AdminNoticeController::class, 'update'])->name('notice.update');
});
