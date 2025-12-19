@extends('layouts.app')

@section('title', '経費申請')

@section('css')
<link rel="stylesheet" href="{{ asset('css/expense.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">経費申請</div>
            <div class="header-right">
                <a href="{{ route('user.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- メイン -->
    <main class="attendance-main">

        <!-- 経費申請ボタン -->
        <div class="menu">
            <a href="javascript:void(0)" id="open-expense-form">経費申請</a>
        </div>

        <!-- モーダル（新規 & 再申請 共通） -->
        <div id="expense-modal" class="modal">
            <div class="modal-content">
                <h2 id="modal-title">経費申請</h2>

                <form id="expense-form" action="{{ route('user.expense.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="expense_id" id="expense-id">

                    <!-- 日付 -->
                    <div class="form-row">
                        <label for="date">日付</label>
                        <input type="date" name="date" id="date" class="small-input" required>
                    </div>

                    <!-- 種別 -->
                    <div class="form-row">
                        <label for="category">種別</label>
                        <select name="category" id="category" class="small-input" required>
                            <option value="">選択してください</option>
                            <option value="交通費">交通費</option>
                            <option value="宿泊費">宿泊費</option>
                            <option value="出張費">出張費</option>
                            <option value="交際費">交際費</option>
                            <option value="会議費">会議費</option>
                            <option value="消耗品費">消耗品費</option>
                            <option value="備品購入">備品購入</option>
                            <option value="その他">その他</option>
                        </select>
                    </div>

                    <!-- 金額 -->
                    <div class="form-row">
                        <label for="amount">金額</label>
                        <div class="amount-input">
                            <span class="prefix">¥</span>
                            <input type="text" name="amount" id="amount" required>
                            <span class="suffix">(税込)</span>
                        </div>
                    </div>

                    <!-- 支払先 -->
                    <div class="form-row">
                        <label for="payee">支払先</label>
                        <input type="text" name="payee" id="payee" class="large-input" required>
                    </div>

                    <!-- 用途 -->
                    <div class="form-row">
                        <label for="purpose">用途</label>
                        <textarea name="purpose" id="purpose" rows="3" class="large-input"></textarea>
                    </div>

                    <!-- ファイル選択 -->
                    <div class="form-group file-row">
                        <div class="left-area">
                            <button type="button" class="file-btn">ファイル選択</button>
                        </div>
                        <div class="right-area">
                            <input type="text" class="file-name-display" readonly>
                            <input type="file" id="receipt_image" name="receipt_image" style="display:none;">
                            <div id="existing-image" style="margin-top:5px;"></div>
                        </div>
                    </div>

                    <!-- 登録番号 -->
                    <div class="form-row file-row">
                        <label for="invoice_number">登録番号</label>
                        <div class="invoice-input-wrapper right-area">
                            <span class="prefix">T</span>
                            <input type="text" id="invoice_number" name="invoice_number" placeholder="1234567890123">
                        </div>
                    </div>

                    <!-- 申請ボタン -->
                    <div class="form-row center">
                        <button type="submit" class="submit-btn" id="submit-btn">申請</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 年月セレクト -->
        <div class="month-picker">
            <form id="year-month-form" action="{{ route('user.expense.index') }}" method="GET">
                <select name="year" id="year-select">
                    @for ($y = now()->year -1; $y <= now()->year +1; $y++)
                        <option value="{{ $y }}" @if($y == $year) selected @endif>{{ $y }}年</option>
                    @endfor
                </select>
                <select name="month" id="month-select">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @if($m == $month) selected @endif>{{ $m }}月</option>
                    @endfor
                </select>
            </form>
        </div>

        <!-- 経費一覧 -->
        <div class="attendance-table-wrapper">
            <table>
                <colgroup>
                    <col style="width:8%">
                    <col style="width:6%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:14%">
                    <col style="width:34%">
                    <col style="width:6%">
                    <col style="width:6%">
                    <col style="width:6%">
                </colgroup>

                <thead>
                    <tr>
                        <th>日付</th>
                        <th>曜</th>
                        <th>種別</th>
                        <th>金額</th>
                        <th>支払先</th>
                        <th>用途</th>
                        <th>画像</th>
                        <th>承認</th>
                        <th>再申請</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($expense->date)->format('n/j') }}</td>
                        <td>
                            @php
                                $wd = ['日','月','火','水','木','金','土'];
                                $d = \Carbon\Carbon::parse($expense->date)->dayOfWeek;
                            @endphp
                            {{ $wd[$d] }}
                        </td>
                        <td>{{ $expense->category }}</td>
                        <td>{{ number_format($expense->amount) }}</td>
                        <td>{{ $expense->payee }}</td>
                        <td>{{ $expense->purpose }}</td>

                        <td>
                            @if($expense->receipt_image)
                                <a href="{{ asset('storage/'.$expense->receipt_image) }}" target="_blank">表示</a>
                            @else - @endif
                        </td>

                        <td>
                            @if($expense->is_approved_by_admins)
                                ◎
                            @elseif($expense->is_rejection)
                                ×
                            @endif
                        </td>

                        <td>
                            @if($expense->is_rejection)
                                <a href="javascript:void(0)"
                                    class="resubmit-btn"
                                    data-id="{{ $expense->id }}"
                                    data-date="{{ $expense->date }}"
                                    data-category="{{ $expense->category }}"
                                    data-amount="{{ $expense->amount }}"
                                    data-payee="{{ $expense->payee }}"
                                    data-purpose="{{ $expense->purpose }}"
                                    data-receipt="{{ $expense->receipt_image ? asset('storage/'.$expense->receipt_image) : '' }}"
                                >
                                    再申請
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9">データがありません</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 差戻コメント -->
        @if ($expenses->where('is_rejection', 1)->count() > 0)
            <div class="rejection-comments">
                @foreach ($expenses->where('is_rejection', 1) as $expense)
                    <div class="rejection-item">
                        <span class="reject-line">
                            ※{{ \Carbon\Carbon::parse($expense->date)->format('n/j') }}：{{ $expense->rejection_comment }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

    </main>
</div>
@endsection


@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ------------------------------
    // 要素取得
    // ------------------------------
    const modal = document.getElementById('expense-modal');
    const openBtn = document.getElementById('open-expense-form');
    const form = document.getElementById('expense-form');
    const title = document.getElementById('modal-title');
    const submitBtn = document.getElementById('submit-btn');
    const fileBtn = document.querySelector('.file-btn');
    const fileInput = document.getElementById('receipt_image');
    const fileNameDisplay = document.querySelector('.file-name-display');
    const existingDiv = document.getElementById('existing-image');
    const amountInput = document.getElementById('amount');

    // ------------------------------
    // フォームリセット
    // ------------------------------
    function resetForm(){
        form.reset();
        fileNameDisplay.value = "";
        existingDiv.style.display = "block";
        existingDiv.innerHTML = "";
    }

    // ------------------------------
    // 新規申請
    // ------------------------------
    openBtn.addEventListener('click', () => {
        resetForm();
        title.textContent = "経費申請";
        submitBtn.textContent = "申請";
        form.action = "{{ route('user.expense.store') }}";
        modal.style.display = "block";
    });

    // ------------------------------
    // 再申請
    // ------------------------------
    document.querySelectorAll('.resubmit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            resetForm();
            title.textContent = "再申請";
            submitBtn.textContent = "再申請";
            const id = btn.dataset.id;
            form.action = `/user/expense/${id}/resubmit`;

            // 既存値セット
            document.getElementById('date').value = btn.dataset.date;
            document.getElementById('category').value = btn.dataset.category;
            document.getElementById('amount').value = Number(btn.dataset.amount).toLocaleString();
            document.getElementById('payee').value = btn.dataset.payee;
            document.getElementById('purpose').value = btn.dataset.purpose;

            // 既存画像表示
            if (btn.dataset.receipt) {
                existingDiv.innerHTML = `<a href="${btn.dataset.receipt}" target="_blank">現在の画像</a>`;
                existingDiv.style.display = "block";
            }

            modal.style.display = "block";
        });
    });

    // ------------------------------
    // ファイル選択
    // ------------------------------
    fileBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', function() {
        const allowed = ['image/jpeg','image/png','application/pdf'];
        const file = this.files[0];

        if(file && !allowed.includes(file.type)){
            alert('添付できるファイルは JPG, PNG, PDF のみです。');
            this.value = ''; // 選択をリセット
            fileNameDisplay.value = '';
            existingDiv.style.display = 'block';
            return;
        }

        fileNameDisplay.value = file ? file.name : '';
        existingDiv.style.display = file ? 'none' : 'block';
    });



    // ------------------------------
    // モーダル外クリックで閉じる
    // ------------------------------
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = "none";
    });

    // ------------------------------
    // 金額カンマ処理
    // ------------------------------
    amountInput.addEventListener('input', function () {
        let value = this.value.replace(/,/g, '').replace(/\D/g, '');
        this.value = Number(value).toLocaleString();
    });

    form.addEventListener('submit', () => {
        amountInput.value = amountInput.value.replace(/,/g, "");
    });

    // ------------------------------
    // 年月セレクト送信
    // ------------------------------
    document.getElementById('year-select').addEventListener('change', () => {
        document.getElementById('year-month-form').submit();
    });
    document.getElementById('month-select').addEventListener('change', () => {
        document.getElementById('year-month-form').submit();
    });

});
</script>
@endsection
