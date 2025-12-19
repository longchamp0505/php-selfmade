@extends('layouts.app')

@section('title', '勤怠承認')

@section('css')
<link rel="stylesheet" href="{{ asset('css/approval.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">勤怠承認</div>
            <div class="header-right">
                <a href="{{ route('client.staff.list') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- 承認画面ヘッダー -->
    <div class="approval-header">
        <div class="staff-display">
            <span class="label">対象スタッフ</span>
            <span class="name">{{ $staff->name }}</span>
        </div>
        <div class="approval-title">稼働報告</div>
        <form method="GET" action="{{ route('client.staff.approval', $staff->id) }}" class="month-picker">
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

    <!-- 差戻コメント モーダル -->
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
        @php
        $categoryMap = [
            '出勤' => '稼働',
            '公休' => '非稼働',
            '有給' => '非稼働',
            '午前休' => '稼働',
            '午後休' => '稼働',
            '振出' => '振替稼働',
            '振休' => '振替非稼働',
            '休出' => '稼働',
            '欠勤' => '欠員',
            '遅刻' => '遅刻',
            '早退' => '早期終業',
        ];
        $minutesToTime = fn($min) => sprintf('%02d:%02d', floor($min/60), $min%60);
        @endphp

        <table>
            <colgroup>
                <col style="width:5%"><col style="width:4%"><col style="width:7%">
                <col style="width:6%"><col style="width:6%"><col style="width:6%">
                <col style="width:6%"><col style="width:6%"><col style="width:6%">
                <col style="width:6%"><col style="width:22%"><col style="width:5%"><col style="width:5%">
            </colgroup>

            <thead>
                <tr>
                    <th>日付</th><th>曜日</th><th>区分</th>
                    <th>始業打刻</th><th>始業</th><th>終業打刻</th><th>終業</th>
                    <th>休憩</th><th>実働</th><th>契約外</th><th>備考</th>
                    <th>差戻</th><th>承認</th>
                </tr>
            </thead>

            <tbody>
                @foreach($attendanceData as $data)
                    @php
                    $att = $data['attendance'] ?? null;
                    $isSubmitted = $att && ($att->is_submitted ?? 0);
                    $isOtherWork = $att && ($att->is_other_company_work ?? 0);
                    @endphp

                    <tr>
                        <td>{{ \Carbon\Carbon::parse($data['date'])->format('n/j') }}</td>
                        <td>{{ $data['weekday'] }}</td>
                        <td>
                            @if($att)
                                @if(!$isOtherWork)
                                    非稼働
                                @else
                                    {{ $categoryMap[$att->category ?? ''] ?? '' }}
                                @endif
                            @endif
                        </td>

                        <td>{{ ($att && $isOtherWork) ? ($att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '') : '' }}</td>
                        <td>{{ ($att && $isOtherWork) ? ($att->start_time ? \Carbon\Carbon::parse($att->start_time)->format('H:i') : '') : '' }}</td>
                        <td>{{ ($att && $isOtherWork) ? ($att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '') : '' }}</td>
                        <td>{{ ($att && $isOtherWork) ? ($att->end_time ? \Carbon\Carbon::parse($att->end_time)->format('H:i') : '') : '' }}</td>
                        <td>{{ ($att && $isOtherWork) ? ($att->break_time ? \Carbon\Carbon::parse($att->break_time)->format('H:i') : '') : '' }}</td>
                        <td>{{ ($att && $isOtherWork) ? ($data['workMinutes'] > 0 ? $minutesToTime($data['workMinutes']) : '') : '' }}</td>
                        <td>{{ ($att && $isOtherWork) ? ($data['overtimeMinutes'] > 0 ? $minutesToTime($data['overtimeMinutes']) : '') : '' }}</td>
                        <td>{{ ($att && $isOtherWork) ? ($att->remarks ?? '') : '' }}</td>


                        {{-- 差戻 --}}
                        <td>
                            <input type="checkbox" name="rejected[]" value="{{ $data['date'] }}"
                                {{ $att && $att->rejection ? 'checked' : '' }}
                                @if(!$isSubmitted || ($att && $att->is_approved_by_admins)) disabled @endif
                            >
                            @if($att && $att->rejection_comment)
                                <textarea style="display:none" data-date="{{ $data['date'] }}">{{ $att->rejection_comment }}</textarea>
                            @endif
                        </td>

                        {{-- 承認 --}}
                        <td>
                            <input type="checkbox" name="approved[]" value="{{ $data['date'] }}"
                                {{ $att && $att->is_approved_by_clients ? 'checked' : '' }}
                                @if(!$isSubmitted || ($att && $att->is_approved_by_admins)) disabled @endif
                            >
                        </td>

                    </tr>

                @endforeach
            </tbody>
        </table>
    </div>

    <!-- サマリー -->
    <div class="summary-container">
        <div class="summary-left">
            <div class="summary-box">
                <div class="summary-title">出勤日数</div>
                <div class="summary-value">{{ $summary['workDays'] }}日</div>
            </div>
            <div class="summary-box">
                <div class="summary-title">総稼働時間</div>
                <div class="summary-value">{{ sprintf('%02d:%02d', floor($summary['totalMinutes']/60), $summary['totalMinutes']%60) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-title">時間外合計</div>
                <div class="summary-value">{{ sprintf('%02d:%02d', floor(($summary['overtimeDailyMinutes']+$summary['weeklyOvertime'])/60), ($summary['overtimeDailyMinutes']+$summary['weeklyOvertime'])%60) }}</div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentRejectDate = null;
    const modal = document.getElementById('rejection-modal');
    const commentBox = document.getElementById('reject-comment');
    const okBtn = document.getElementById('reject-ok');
    const cancelBtn = document.getElementById('reject-cancel');

    const approveBoxes = document.querySelectorAll('input[name="approved[]"]');
    const rejectBoxes  = document.querySelectorAll('input[name="rejected[]"]');

    function updateRowState(row){
        const approvedBox = row.querySelector('input[name="approved[]"]');
        const rejectedBox = row.querySelector('input[name="rejected[]"]');
        row.classList.toggle('approved', approvedBox.checked);
        row.classList.toggle('rejected', rejectedBox.checked);

        // 申請済みか確認
        const isSubmitted = !approvedBox.disabled && !rejectedBox.disabled;
        if(!isSubmitted) return;

        // 承認中は差戻禁止
        rejectedBox.disabled = approvedBox.checked || rejectedBox.disabled;
        // 差戻中は承認禁止
        approvedBox.disabled = rejectedBox.checked || approvedBox.disabled;
    }

    document.querySelectorAll('tbody tr').forEach(row => updateRowState(row));

    function sendRowUpdate(row) {
        const approvedBox = row.querySelector('input[name="approved[]"]');
        const rejectedBox = row.querySelector('input[name="rejected[]"]');
        const date = approvedBox.value;
        const commentInput = row.querySelector(`textarea[data-date="${date}"]`);
        const comment = commentInput ? commentInput.value : '';

        fetch("{{ route('client.staff.approval.update', $staff->id) }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                approved: approvedBox.checked ? [date] : [],
                unapproved: !approvedBox.checked ? [date] : [],
                rejected: rejectedBox.checked ? [date] : [],
                unrejected: !rejectedBox.checked ? [date] : [],
                rejection_comment: rejectedBox.checked ? { [date]: comment } : {}
            })
        }).then(res => res.json())
          .then(data => {
              if(data.status !== 'success') alert(data.message);
          });
    }

    approveBoxes.forEach(box => {
        box.addEventListener('change', function() {
            const row = this.closest('tr');
            updateRowState(row);
            sendRowUpdate(row);
        });
    });

    rejectBoxes.forEach(box => {
        box.addEventListener('change', function() {
            const row = this.closest('tr');
            if(this.checked){
                currentRejectDate = this.value;
                modal.style.display = 'block';
                commentBox.value = row.querySelector(`textarea[data-date="${currentRejectDate}"]`)?.value || '';
            } else {
                updateRowState(row);
                sendRowUpdate(row);
            }
        });
    });

    okBtn.addEventListener('click', function() {
        const comment = commentBox.value.trim();
        if(!comment){ alert("差戻理由を入力してください。"); return; }
        const box = document.querySelector(`input[name="rejected[]"][value="${currentRejectDate}"]`);
        if(box){
            const row = box.closest('tr');
            let textArea = row.querySelector(`textarea[data-date="${currentRejectDate}"]`);
            if(!textArea){
                textArea = document.createElement('textarea');
                textArea.style.display = 'none';
                textArea.setAttribute('data-date', currentRejectDate);
                row.appendChild(textArea);
            }
            textArea.value = comment;
            box.checked = true;
            updateRowState(row);
            sendRowUpdate(row);
        }
        modal.style.display = 'none';
        commentBox.value = "";
    });

    cancelBtn.addEventListener('click', function() {
        if(currentRejectDate){
            const box = document.querySelector(`input[name="rejected[]"][value="${currentRejectDate}"]`);
            if(box){
                box.checked = false;
                const row = box.closest('tr');
                updateRowState(row);
                sendRowUpdate(row);
            }
        }
        modal.style.display = 'none';
        commentBox.value = "";
    });

    window.addEventListener('click', function(e){
        if(e.target === modal) cancelBtn.click();
    });

});
</script>
@endsection
