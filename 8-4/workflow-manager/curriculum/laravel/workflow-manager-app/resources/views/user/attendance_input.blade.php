@extends('layouts.app')

@section('title', '勤怠入力')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-input.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">勤怠入力</div>
            <div class="header-right">
                <a href="{{ route('user.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- 年月セレクト & PDF -->
    <form id="monthForm" method="GET" action="{{ route('user.attendance.input') }}" class="month-picker">
        <select name="year" class="year-select"
            onchange="document.getElementById('monthForm').submit();">
            @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}年</option>
            @endfor
        </select>

        <select name="month" class="month-select"
            onchange="document.getElementById('monthForm').submit();">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ $m }}月</option>
            @endfor
        </select>
    </form>
    <div class="pdf-button-wrapper">
        <a href="{{ route('user.attendance.pdf', ['year' => $year, 'month' => $month]) }}"
        class="pdf-btn">
        PDF
        </a>
    </div>

    <!-- 表 -->
    <div class="attendance-table-wrapper">
        <table>
            <colgroup>
                <col style="width:5%"> <!-- 日付 -->
                <col style="width:4%"> <!-- 曜日 -->
                <col style="width:7%"> <!-- 区分 -->
                @if($user->client_id)
                    <col style="width:5%"> <!-- 他社 -->
                @endif
                <col style="width:6%"> <!-- 勤怠打刻 -->
                <col style="width:6%" > <!-- 始業 -->
                <col style="width:6%"> <!-- 終業打刻 -->
                <col style="width:6%"> <!-- 終業 -->
                <col style="width:6%"> <!-- 休憩 -->
                <col style="width:6%" > <!-- 実働 -->
                <col style="width:6%"> <!-- 時間外 -->
                <col style="width:22%"> <!-- 備考 -->
                <col style="width:5%"> <!-- 申請 -->
                @if($user->client_id)
                    <col style="width:5%"> <!-- 承認① -->
                @endif
                <col style="width:5%"> <!-- 承認② -->
            </colgroup>

            <thead>
                <tr>
                    <th class="th-date">日付</th>
                    <th class="th-weekday">曜日</th>
                    <th class="th-category">区分</th>
                    @if($user->client_id)
                        <th class="th-other">他社</th>
                    @endif
                    <th class="th-clockin">勤怠打刻</th>
                    <th class="th-start">始業</th>
                    <th class="th-clockout">終業打刻</th>
                    <th class="th-end">終業</th>
                    <th class="th-break">休憩</th>
                    <th class="th-work">実働</th>
                    <th class="th-overtime">時間外</th>
                    <th class="th-remarks">備考</th>
                    <th class="th-submit">申請</th>
                    @if($user->client_id)
                        <th class="th-approve1">承認①</th>
                    @endif
                    <th class="th-approve2">承認②</th>
                </tr>
            </thead>

            <tbody>
                @foreach($attendanceData as $data)
                    @php
                        $att = $data['attendance'];
                        $isApproved = $att && $att->is_approved_by_admins;
                        $isRejected = $att && $att->rejection;
                        $minutesToTime = fn($min) => sprintf('%02d:%02d', floor($min/60), $min%60);
                    @endphp
                    <tr 
                        data-rejection="{{ $att->rejection ?? 0 }}" 
                        data-approved-by-clients="{{ $att->is_approved_by_clients ?? 0 }}" 
                        data-approved-by-admins="{{ $att->is_approved_by_admins ?? 0 }}" 
                        data-rejection-comment="{{ $att->rejection_comment ?? '' }}"
                    >
                        <td class="td-date">{{ \Carbon\Carbon::parse($data['date'])->format('n/j') }}</td>
                        <td class="td-weekday">{{ $data['weekday'] }}</td>
                        <td class="td-category">
                            <select class="category-select" {{ $isApproved ? 'disabled' : '' }}>
                                <option value="" {{ !$att ? 'selected' : '' }}>選択</option>
                                @foreach(['出勤','公休','有給','午前休','午後休','振出','振休','休出','欠勤','遅刻','早退'] as $cat)
                                    <option value="{{ $cat }}" {{ $att && $att->category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </td>

                        @if($user->client_id)
                            <td class="td-other">
                                <input type="checkbox" class="other-company-checkbox" {{ $att && $att->is_other_company_work ? 'checked' : '' }} {{ $isApproved ? 'disabled' : '' }}>
                            </td>
                        @endif

                        <td class="td-clockin">{{ $att && $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '' }}</td>
                        <td class="td-start">
                            <input type="time" class="start-time" 
                                value="{{ $att && $att->start_time ? \Carbon\Carbon::parse($att->start_time)->format('H:i') : ($att && $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '') }}" 
                                {{ $isApproved ? 'disabled' : '' }}>
                        </td>
                        <td class="td-clockout">{{ $att && $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '' }}</td>
                        <td class="td-end">
                            <input type="time" class="end-time" 
                                value="{{ $att && $att->end_time ? \Carbon\Carbon::parse($att->end_time)->format('H:i') : ($att && $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '') }}" 
                                {{ $isApproved ? 'disabled' : '' }}>
                        </td>
                        <td class="td-break"><input type="time" class="break-time" value="{{ $att->break_time ?? '' }}" {{ $isApproved ? 'disabled' : '' }}></td>
                        <td class="td-work">{{ $data['workMinutes'] > 0 ? $minutesToTime($data['workMinutes']) : '' }}</td>
                        <td class="td-overtime">{{ $data['overtimeMinutes'] > 0 ? $minutesToTime($data['overtimeMinutes']) : '' }}</td>
                        <td class="td-remarks">
                            <input type="text" class="remarks" value="{{ $att->remarks ?? '' }}" {{ $isApproved ? 'disabled' : '' }}>
                            @if($isRejected && $att->rejection_comment)
                                <div class="rejection-comment">{{ $att->rejection_comment }}</div>
                            @endif
                        </td>
                        <td class="td-submit"><input type="checkbox" class="submit-checkbox" data-date="{{ $data['date'] }}" {{ $att && $att->is_submitted ? 'checked' : '' }} {{ $isApproved ? 'disabled' : '' }}></td>

                        @if($user->client_id)
                            <td class="td-approve1"></td>
                        @endif
                        <td class="td-approve2"></td>
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
                <div class="summary-value" id="summary-workdays">{{ $summary['workDays'] }}日</div>
            </div>
            <div class="summary-box">
                <div class="summary-title">総稼働時間</div>
                <div class="summary-value" id="summary-work">{{ sprintf('%02d:%02d', floor($summary['totalMinutes']/60), $summary['totalMinutes']%60) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-title">時間外合計（➊+➋）</div>
                <div class="summary-value" id="summary-overtime-total">{{ sprintf('%02d:%02d', floor(($summary['overtimeDailyMinutes']+$summary['weeklyOvertime'])/60), ($summary['overtimeDailyMinutes']+$summary['weeklyOvertime'])%60) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-title">➊ 日8時間超過</div>
                <div class="summary-value" id="summary-daily-overtime">{{ sprintf('%02d:%02d', floor($summary['overtimeDailyMinutes']/60), $summary['overtimeDailyMinutes']%60) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-title">➋ 週40時間超過</div>
                <div class="summary-value" id="summary-weekly-overtime">{{ sprintf('%02d:%02d', floor($summary['weeklyOvertime']/60), $summary['weeklyOvertime']%60) }}</div>
            </div>

        </div>
        <div class="summary-right">
            <button class="submit-btn">月次申請</button>
        </div>
    </div>

</div>
@endsection

@section('js')
<script>
function timeToMinutes(time) {
    if (!time) return 0;
    const [h, m] = time.split(':').map(Number);
    return h * 60 + m;
}

function minutesToTime(minutes) {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}`;
}

// 勤務時間・サマリ更新
function updateRowAndSummary(row) {
    const startTime = row.querySelector('.start-time').value;
    const endTime = row.querySelector('.end-time').value;
    const breakTime = row.querySelector('.break-time').value;

    let workMinutes = 0, overtimeMinutes = 0;
    if(startTime && endTime){
        let startMin = timeToMinutes(startTime);
        let endMin = timeToMinutes(endTime);
        const breakMin = timeToMinutes(breakTime);
        if(endMin < startMin) endMin += 1440;
        workMinutes = Math.max(endMin - startMin - breakMin,0);
        overtimeMinutes = Math.max(workMinutes - 480,0);
    }

    row.querySelector('.td-work').textContent = workMinutes>0 ? minutesToTime(workMinutes) : '';
    row.querySelector('.td-overtime').textContent = overtimeMinutes>0 ? minutesToTime(overtimeMinutes) : '';

    // サマリ更新
    let totalWork = 0, totalOvertimeDaily = 0;
    document.querySelectorAll('tbody tr').forEach(r => {
        const workText = r.querySelector('.td-work').textContent;
        const otText = r.querySelector('.td-overtime').textContent;
        if(workText) totalWork += timeToMinutes(workText);
        if(otText) totalOvertimeDaily += timeToMinutes(otText);
    });
    document.querySelector('#summary-work').textContent = minutesToTime(totalWork);
    document.querySelector('#summary-daily-overtime').textContent = minutesToTime(totalOvertimeDaily);

    fetch("{{ route('user.attendance.getSummary') }}?year={{ $year }}&month={{ $month }}")
        .then(res => res.json())
        .then(data => {
            const weeklyOvertime = data.weeklyOvertime;
            document.querySelector('#summary-weekly-overtime').textContent = minutesToTime(weeklyOvertime);
            document.querySelector('#summary-overtime-total').textContent = minutesToTime(totalOvertimeDaily + weeklyOvertime);
        });
}

function setRowState(row, isSubmitted, isRejected, isApproved, rejectionComment = '') {
    // --- 色付け ---
    row.classList.remove('submitted','rejected','approved');
    if(isApproved){
        row.classList.add('approved');
    } else if(isRejected){
        row.classList.add('rejected');
    } else if(isSubmitted){
        row.classList.add('submitted');
    }

    // 差戻コメント
    const remarksTd = row.querySelector('.td-remarks');
    let commentDiv = remarksTd.querySelector('.rejection-comment');
    if(isRejected && rejectionComment){
        if(!commentDiv){
            commentDiv = document.createElement('div');
            commentDiv.classList.add('rejection-comment');
            remarksTd.appendChild(commentDiv);
        }
        commentDiv.textContent = rejectionComment;
    } else if(commentDiv){
        commentDiv.remove();
    }

    // 承認①・②表示（列番号依存なし）
    const approve1Td = row.querySelector('.td-approve1');
    if(approve1Td){
        approve1Td.textContent = row.dataset.approvedByClients == '1' 
            ? '◎' 
            : (isRejected && row.querySelector('.other-company-checkbox')?.checked ? '×' : '');
    }
    const approve2Td = row.querySelector('.td-approve2');
    if(approve2Td){
        approve2Td.textContent = row.dataset.approvedByAdmins == '1' 
            ? '◎' 
            : (isRejected && !row.querySelector('.other-company-checkbox')?.checked ? '×' : '');
    }

    // 入力制御
    const inputDisabled = isApproved;
    row.querySelectorAll('.start-time, .end-time, .break-time, .category-select, .other-company-checkbox, .remarks').forEach(input => {
        input.disabled = inputDisabled;
    });

    const submitCheckbox = row.querySelector('.submit-checkbox');
    if(submitCheckbox){
        submitCheckbox.disabled = isApproved;
    }
}

const hasClient = {{ $user->client_id ? 'true' : 'false' }};

// 初期表示
document.querySelectorAll('tbody tr').forEach(row => {
    const checkbox = row.querySelector('.submit-checkbox');
    const isSubmitted = checkbox.checked;
    const isRejected = row.dataset.rejection == '1';
    const isApproved = row.dataset.approvedByClients == '1' || row.dataset.approvedByAdmins == '1';
    const rejectionComment = row.dataset.rejectionComment || '';

    setRowState(row, isSubmitted, isRejected, isApproved, rejectionComment);

    checkbox.addEventListener('change', function() {
        const category = row.querySelector('.category-select').value;
        const start = row.querySelector('.start-time').value;
        const end = row.querySelector('.end-time').value;

        // 必須項目チェック
        if(this.checked && (!category || !start || !end)){
            alert('必須項目（区分・始業・終業）を入力してください');
            this.checked = false;
            return;
        }

        const date = this.dataset.date;
        const isOther = row.querySelector('.other-company-checkbox')?.checked ? 1 : 0;
        const remarks = row.querySelector('.remarks').value;
        const isSubmitted = this.checked ? 1 : 0;

        fetch("{{ route('user.attendance.submit') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN":"{{ csrf_token() }}",
                "Content-Type":"application/json"
            },
            body: JSON.stringify({
                date,
                category,
                is_other_company_work:isOther,
                start_time: start,
                end_time: end,
                break_time: row.querySelector('.break-time').value,
                remarks,
                is_submitted: isSubmitted
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status !== 'success'){
                alert(data.message);
                checkbox.checked = !checkbox.checked;
            }

            const rejection = data.rejection == 1;
            const isApproved = data.is_approved_by_clients == 1 || data.is_approved_by_admins == 1;
            const rejectionComment = data.rejection_comment || '';

            setRowState(row, isSubmitted, rejection, isApproved, rejectionComment);
            updateRowAndSummary(row);
        });
    });
});

// 休憩時間初期値
document.querySelectorAll('.break-time').forEach(input => {
    input.addEventListener('focus', () => {
        if(!input.value){
            input.value = '01:00';
            input.dispatchEvent(new Event('change', {bubbles:true}));
        }
    });
});
</script>
@endsection
