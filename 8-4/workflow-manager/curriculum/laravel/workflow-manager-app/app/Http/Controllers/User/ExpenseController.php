<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        // DBから実際の経費データを取得
        $expenses = Expense::where('user_id', auth()->id())
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();

        return view('user.expense', compact('year', 'month', 'expenses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'category' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'payee' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:255',
            'receipt_image' => 'nullable|file|mimes:jpg,png,pdf',
            'invoice_number' => 'nullable|string|max:50',
        ]);

        $expense = new Expense();
        $expense->user_id = auth()->user()->id;
        $expense->date = $request->date;
        $expense->day_of_week = \Carbon\Carbon::parse($request->date)->format('D'); // 曜日
        $expense->category = $request->category;
        $expense->amount = $request->amount;
        $expense->payee = $request->payee;
        $expense->purpose = $request->purpose;
        $expense->invoice_number = $request->invoice_number;
        $expense->is_submitted = true;
        $expense->is_approved_by_admins = false;
        $expense->is_rejection = false;

        if($request->hasFile('receipt_image')){
            $path = $request->file('receipt_image')->store('receipts', 'public');
            $expense->receipt_image = $path;
        }

        $expense->save();

        return redirect()->route('user.expense.index')->with('success', '経費を申請しました');
    }

    public function resubmit(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'category' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'payee' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:255',
            'receipt_image' => 'nullable|file|mimes:jpg,png,pdf',
            'invoice_number' => 'nullable|string|max:50',
        ]);

        $expense = Expense::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // フォームで入力された内容を更新
        $expense->date = $request->date;
        $expense->day_of_week = \Carbon\Carbon::parse($request->date)->format('D');
        $expense->category = $request->category;
        $expense->amount = $request->amount;
        $expense->payee = $request->payee;
        $expense->purpose = $request->purpose;
        $expense->invoice_number = $request->invoice_number;

        // ファイルアップロードがある場合のみ更新
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('receipts', 'public');
            $expense->receipt_image = $path;
        }

        // 再申請時の承認・差戻し状態をリセット
        $expense->is_rejection = false;
        $expense->rejection_comment = null;
        $expense->is_submitted = true;
        $expense->is_approved_by_admins = false;

        $expense->save();

        return redirect()
            ->route('user.expense.index')
            ->with('success', '再申請が完了しました。');
    }



}
