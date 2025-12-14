@extends('layouts.app')

@section('title', '勤怠承認')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-approval.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    @php
        $hasClient = !empty($user->client_id);  // ← クライアントIDの有無
    @endphp

    <!-- ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">承認画面</div>
            <div class="header-right">
                <a href="{{ route('admin.attendance.approval.pdf', ['staff' => $user->id, 'year' => $year, 'month' => $month]) }}" class="pdf-btn">PDF</a>
                <a href="{{ route('admin.staff.list') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- 承認画面ヘッダー -->
    <div class="approval-header">
        <div class="staff-display">
            <span class="label">対象スタッフ</span>
            <span class="name">{{ $user->name }}</span>
        </div>

        <form method="GET" action="{{ route('admin.attendance.approval', ['staff' => $user->id]) }}" class="month-picker">
            <select name="year" onchange="this.form.submit()">
                @for($y = now()->year -1; $y <= now()->year +1; $y++)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}年</option>
                @endfor
            </select>
            <select name="month" onchange="this.form.submit()">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ $m }}月</option>
                @endfor
            </select>
        </form>
    </div>

    <!-- 差戻コメントモーダル -->
    <div id="rejection-modal" class="modal">
        <div class="modal-content">
            <h2>差戻確認</h2>
            <p>コメント</p>
            <textarea id="reject-comment" rows="4" placeholder="差戻理由を入力" class="reject-textarea"></textarea>
            <div class="modal-footer">
                <button id="reject-ok" class="modal-btn ok-btn" type="button">OK</button>
                <button id="reject-cancel" class="modal-btn cancel-btn" type="button">キャンセル</button>
            </div>
        </div>
    </div>

    <!-- 勤怠表 -->
    <div class="attendance-table-wrapper">
        <table style="width:100%">
            <colgroup>
                <col style="width:7%">
                <col style="width:5%">
                <col style="width:7%">
                @if($hasClient)
                    <col style="width:5%"> <!-- 他社 -->
                @endif
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:19%">
                <col style="width:5%">
                @if($hasClient)
                    <col style="width:5%"> <!-- 承認① -->
                @endif
                <col style="width:5%">
            </colgroup>
            <thead>
                <tr>
                    <th>日付</th>
                    <th>曜日</th>
                    <th>区分</th>

                    @if($hasClient)
                        <th>他社</th>
                    @endif

                    <th>始業打刻</th>
                    <th>始業</th>
                    <th>終業打刻</th>
                    <th>終業</th>
                    <th>休憩</th>
                    <th>実働</th>
                    <th>時間外</th>
                    <th>備考</th>

                    <th>差戻</th>

                    @if($hasClient)
                        <th>承認①</th>
                    @endif

                    <th>承認<input type="checkbox" id="approve-all"></th>
                </tr>
            </thead>

            <tbody>
            @foreach($attendanceData as $data)
            @php
                $att = $data['attendance'];
                $minutesToTime = fn($min) => sprintf('%02d:%02d', floor($min/60), $min%60);
                $isSubmitted = $att?->is_submitted ?? 0; // 申請済みフラグ
            @endphp

            <tr>
                <td>{{ \Carbon\Carbon::parse($data['date'])->format('n/j') }}</td>
                <td>{{ $data['weekday'] }}</td>
                
                {{-- 区分～備考 --}}
                <td>{{ $isSubmitted ? ($att?->category ?? '') : '' }}</td>
                
                @if($hasClient)
                    <td>{{ $isSubmitted ? ($att?->is_other_company_work ? '●' : '') : '' }}</td>
                @endif

                <td>{{ $isSubmitted && $att?->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '' }}</td>
                <td>{{ $isSubmitted && $att?->start_time ? \Carbon\Carbon::parse($att->start_time)->format('H:i') : '' }}</td>
                <td>{{ $isSubmitted && $att?->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '' }}</td>
                <td>{{ $isSubmitted && $att?->end_time ? \Carbon\Carbon::parse($att->end_time)->format('H:i') : '' }}</td>
                <td>{{ $isSubmitted && $att?->break_time ? \Carbon\Carbon::parse($att->break_time)->format('H:i') : '' }}</td>
                <td>{{ $isSubmitted && $data['workMinutes'] > 0 ? $minutesToTime($data['workMinutes']) : '' }}</td>
                <td>{{ $isSubmitted && $data['overtimeMinutes'] > 0 ? $minutesToTime($data['overtimeMinutes']) : '' }}</td>
                <td>{{ $isSubmitted ? ($att?->remarks ?? '') : '' }}</td>

                {{-- 差戻 --}}
                <td>
                    <input type="checkbox"
                        class="reject-checkbox"
                        name="rejected[]"
                        value="{{ $data['date'] }}"
                        {{ $att && $att->rejection ? 'checked' : '' }}
                        {{ !$isSubmitted || ($att && $att->is_other_company_work == 1) ? 'disabled' : '' }}>
                </td>

                {{-- クライアント承認 --}}
                @if($hasClient)
                <td>
                    @if(!$att || !$isSubmitted)
                        -
                    @else
                        @if($att->rejection == 1)
                            △
                        @elseif($att->is_approved_by_clients == 1)
                            ◎
                        @else
                            ×
                        @endif
                    @endif
                </td>
                @endif

                {{-- 管理者承認 --}}
                <td>
                    <input type="checkbox"
                        class="approve-checkbox"
                        name="approved[]"
                        value="{{ $data['date'] }}"
                        {{ $att && $att->is_approved_by_admins ? 'checked' : '' }}
                        {{ !$isSubmitted || ($hasClient && $att && $att->is_other_company_work && !$att->is_approved_by_clients) ? 'disabled' : '' }}>
                </td>

            </tr>
            @endforeach
            </tbody>

        </table>
    </div>

    <!-- ボタン -->
    <div class="approval-btn-area">
        <a href="{{ route('admin.attendance.edit', ['staff' => $user->id]) }}" class="edit-btn">修正</a>


    </div>

    <!-- サマリー -->
    <div class="summary-container">
        <div class="summary-box">
            <div class="summary-title">出勤日数</div>
            <div class="summary-value">{{ $summary['workDays'] }}日</div>
        </div>

        <div class="summary-box">
            <div class="summary-title">総稼働時間</div>
            <div class="summary-value">
                {{ sprintf('%02d:%02d', floor($summary['totalMinutes']/60), $summary['totalMinutes']%60) }}
            </div>
        </div>

        <div class="summary-box">
            <div class="summary-title">時間外合計（➊+➋）</div>
            <div class="summary-value">
                {{ sprintf('%02d:%02d', floor(($summary['overtimeDailyMinutes'] + $summary['weeklyOvertime'])/60), ($summary['overtimeDailyMinutes'] + $summary['weeklyOvertime'])%60) }}
            </div>
        </div>

        <div class="summary-box">
            <div class="summary-title">➊ 日8時間超過</div>
            <div class="summary-value">
                {{ sprintf('%02d:%02d', floor($summary['overtimeDailyMinutes']/60), $summary['overtimeDailyMinutes']%60) }}
            </div>
        </div>

        <div class="summary-box">
            <div class="summary-title">➋ 週40時間超過</div>
            <div class="summary-value">
                {{ sprintf('%02d:%02d', floor($summary['weeklyOvertime']/60), $summary['weeklyOvertime']%60) }}
            </div>
        </div>
    </div>

</div>
@endsection


@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {

    const modal = document.getElementById('rejection-modal');
    const commentBox = document.getElementById('reject-comment');
    const okBtn = document.getElementById('reject-ok');
    const cancelBtn = document.getElementById('reject-cancel');
    let currentRejectDate = null;

    // 行色変更
    function updateRowColor(row){
        const approveBox = row.querySelector('.approve-checkbox');
        const rejectBox  = row.querySelector('.reject-checkbox');
        row.classList.toggle('approved', approveBox.checked);
        row.classList.toggle('rejected', rejectBox.checked);
    }

    // ページロード時にすべての行を初期色付け
    document.querySelectorAll('tbody tr').forEach(row => updateRowColor(row));

    // DB更新
    function sendRowUpdate(row){
        const approveBox = row.querySelector('.approve-checkbox');
        const rejectBox  = row.querySelector('.reject-checkbox');
        const date = approveBox.value;
        const comment = row.querySelector(`textarea[data-date="${date}"]`)?.value || '';

        fetch("{{ route('admin.attendance.approval.update', ['staff' => $user->id]) }}", {
            method:"POST",
            headers:{
                "X-CSRF-TOKEN":"{{ csrf_token() }}",
                "Content-Type":"application/json"
            },
            body:JSON.stringify({
                approved: approveBox.checked ? [date] : [],
                unapproved: !approveBox.checked ? [date] : [],
                rejected: rejectBox.checked ? [date] : [],
                unrejected: !rejectBox.checked ? [date] : [],
                rejection_comment: rejectBox.checked ? { [date]: comment } : {}
            })
        }).then(res=>res.json()).then(data=>{
            if(data.status!=='success') alert(data.message);
        });
    }

    // 個別承認
    document.querySelectorAll('.approve-checkbox').forEach(box=>{
        box.addEventListener('change', function(){
            const row = this.closest('tr');
            if(this.checked){
                const rejectBox = row.querySelector('.reject-checkbox');
                rejectBox.checked = false;
            }
            updateRowColor(row);
            sendRowUpdate(row);
        });
    });

    // 個別差戻
    document.querySelectorAll('.reject-checkbox').forEach(box=>{
        box.addEventListener('change', function(){
            const row = this.closest('tr');

            // 他社勤務ならチェックを戻す
            const attOther = row.querySelector('.approve-checkbox').disabled; // 他社勤務なら approveBox.disabled は true
            if(attOther){
                this.checked = false;
                return;
            }

            if(this.checked){
                const approveBox = row.querySelector('.approve-checkbox');
                approveBox.checked = false;
                currentRejectDate = this.value;
                modal.style.display = 'block';
                commentBox.value = row.querySelector(`textarea[data-date="${currentRejectDate}"]`)?.value || '';
            } else {
                updateRowColor(row);
                sendRowUpdate(row);
            }
        });
    });


    // モーダル OK
    okBtn.addEventListener('click', function(){
        const comment = commentBox.value.trim();
        if(!comment){ alert("差戻理由を入力してください"); return; }
        const box = document.querySelector(`input.reject-checkbox[value="${currentRejectDate}"]`);
        if(box){
            const row = box.closest('tr');
            let textArea = row.querySelector(`textarea[data-date="${currentRejectDate}"]`);
            if(!textArea){
                textArea = document.createElement('textarea');
                textArea.style.display='none';
                textArea.setAttribute('data-date', currentRejectDate);
                row.appendChild(textArea);
            }
            textArea.value = comment;
            box.checked = true;
            updateRowColor(row);
            sendRowUpdate(row);
        }
        modal.style.display='none';
        commentBox.value='';
    });

    // モーダル キャンセル
    cancelBtn.addEventListener('click', function(){
        if(currentRejectDate){
            const box = document.querySelector(`input.reject-checkbox[value="${currentRejectDate}"]`);
            if(box){
                box.checked = false;
                const row = box.closest('tr');
                updateRowColor(row);
                sendRowUpdate(row);
            }
        }
        modal.style.display='none';
        commentBox.value='';
    });

    window.addEventListener('click', function(e){
        if(e.target === modal) cancelBtn.click();
    });

    // TH全選択
    const approveAll = document.getElementById('approve-all');
    approveAll.addEventListener('change', function(){
        const checked = this.checked;
        document.querySelectorAll('.approve-checkbox').forEach(cb=>{
            if(!cb.disabled) cb.checked = checked;
            updateRowColor(cb.closest('tr'));
            sendRowUpdate(cb.closest('tr'));
        });
    });

});

</script>
@endsection
