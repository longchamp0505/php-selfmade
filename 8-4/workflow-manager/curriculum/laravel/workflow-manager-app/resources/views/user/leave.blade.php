@extends('layouts.app')

@section('title', '休暇申請')

@section('css')
<link rel="stylesheet" href="{{ asset('css/leave.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">休暇申請</div>
            <div class="header-right">
                <a href="{{ route('user.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <main class="attendance-main">

        <!-- 休暇申請ボタン -->
        <div class="menu">
            <a href="javascript:void(0)" id="open-leave-form">休暇申請</a>
        </div>

        <!-- ▼ 共通モーダル（新規/再申請） -->
        <div id="leave-common-modal" class="modal">
            <div class="modal-content">
                <h2 id="leave-modal-title">休暇申請</h2>

                <form id="leave-common-form" method="POST">
                    @csrf

                    <div class="form-row">
                        <label>日付</label>
                        <input type="date" name="date" id="leave-date" class="small-input" required>
                    </div>

                    <div class="form-row">
                        <label>種別</label>
                        <select name="leave_type" id="leave-type" class="small-input" required>
                            <option value="">選択してください</option>
                            <option value="有給">有給</option>
                            <option value="忌引">忌引</option>
                            <option value="特別休暇">特別休暇</option>
                            <option value="その他">その他</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label>備考</label>
                        <textarea name="note" id="leave-note" rows="3" class="large-input"></textarea>
                    </div>

                    <div class="form-row center">
                        <button class="submit-btn" id="leave-submit-btn">申請</button>
                    </div>
                </form>

            </div>
        </div>

                        <!-- サマリ -->
        <div class="summary-container">
            <div class="summary-box">
                <div class="summary-title">有給残日数</div>
                <div class="summary-value">{{ optional($paidLeave)->remaining_days ?? 0 }}日</div>
            </div>
            <div class="summary-box">
                <div class="summary-title">今年度有給取得日数</div>
                <div class="summary-value">{{ optional($paidLeave)->current_year_taken ?? 0 }}日</div>
            </div>
            <div class="summary-box">
                <div class="summary-title">有給付与日</div>
                <div class="summary-value">
                    @if(!empty($paidLeave?->granted_date))
                        {{ \Carbon\Carbon::parse($paidLeave->granted_date)->format('Y/m/d') }}
                    @else
                        -
                    @endif
                </div>
            </div>
        </div>

        <!-- 年月セレクト（元のまま） -->
        <div class="month-picker">
            <form id="year-month-form" action="{{ route('user.leave.index') }}" method="GET">
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




        <!-- 休暇一覧 -->
        <div class="attendance-table-wrapper">
            <table>
                <thead>
                    <colgroup>
                        <col style="width:10%;">  <!-- 日付 -->
                        <col style="width:10%;">  <!-- 曜日 -->
                        <col style="width:15%;">  <!-- 種別 -->
                        <col style="width:45%;">  <!-- 備考：長くなる想定で広め -->
                        <col style="width:10%;">  <!-- 承認 -->
                        <col style="width:10%;">  <!-- 再申請 -->
                    </colgroup>

                    <tr>
                        <th>日付</th>
                        <th>曜日</th>
                        <th>種別</th>
                        <th>備考</th>
                        <th>承認</th>
                        <th>再申請</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($leaves as $leave)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($leave->date)->format('n/j') }}</td>
                        <td>{{ ['日','月','火','水','木','金','土'][\Carbon\Carbon::parse($leave->date)->dayOfWeek] }}</td>
                        <td>{{ $leave->leave_type }}</td>
                        <td>{{ $leave->note }}</td>
                        <td>
                            @if($leave->is_approved_by_admins)
                                ◎
                            @elseif($leave->is_rejection)
                                ×
                            @endif
                        </td>

                        <td>
                            @if($leave->is_rejection)
                                <a href="javascript:void(0);" 
                                   class="resubmit-btn"
                                   data-id="{{ $leave->id }}">
                                   再申請
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- ▼ 差戻コメントの表示 -->
        @if ($leaves->where('is_rejection', 1)->count() > 0)
            <div class="rejection-comments">
                @foreach ($leaves->where('is_rejection', 1) as $leave)
                    <div class="rejection-item">
                        <span class="reject-line">
                            ※{{ \Carbon\Carbon::parse($leave->date)->format('n/j') }}：{{ $leave->rejection_comment }}
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
document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('leave-common-modal');
    const form = document.getElementById('leave-common-form');

    const title = document.getElementById('leave-modal-title');
    const submitBtn = document.getElementById('leave-submit-btn');

    const dateInput = document.getElementById('leave-date');
    const typeInput = document.getElementById('leave-type');
    const noteInput = document.getElementById('leave-note');

    /* ▼ 新規申請（空で開く） */
    document.getElementById('open-leave-form').addEventListener('click', () => {
        title.textContent = "休暇申請";
        submitBtn.textContent = "申請";

        form.action = "/user/leave";
        dateInput.value = "";
        typeInput.value = "";
        noteInput.value = "";

        modal.style.display = "block";
    });

    /* ▼ 再申請（元データを読み込んで開く） */
    document.querySelectorAll('.resubmit-btn').forEach(btn => {
        btn.addEventListener('click', async () => {

            const id = btn.dataset.id;

            const response = await fetch(`/user/leave/${id}/edit`);
            const data = await response.json();

            title.textContent = "再申請";
            submitBtn.textContent = "再申請";

            dateInput.value = data.date;
            typeInput.value = data.leave_type;
            noteInput.value = data.note;

            form.action = `/user/leave/${id}/resubmit`;

            modal.style.display = "block";
        });
    });

    /* ▼ 背景クリックで閉じる */
    window.addEventListener('click', e => {
        if (e.target === modal) modal.style.display = "none";
    });

    /* ▼ 年月セレクト反応（←これが無かった） */
    document.getElementById('year-select').addEventListener('change', () => {
        document.getElementById('year-month-form').submit();
    });
    document.getElementById('month-select').addEventListener('change', () => {
        document.getElementById('year-month-form').submit();
    });

});
</script>
@endsection
