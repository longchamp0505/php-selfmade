@extends('layouts.app')

@section('title', '勤怠修正')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-attendance-edit.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    @php
        $hasClient = !empty($user->client_id);
    @endphp

    <!-- ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">勤怠修正</div>
            <div class="header-right">
                <a href="{{ route('admin.attendance.approval', ['staff' => $user->id]) }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <div class="approval-header">
        <div class="staff-display">
            <span class="label">対象スタッフ</span>
            <span class="name">{{ $user->name }}</span>
        </div>
    </div>

    <div class="attendance-table-wrapper">
        <table>
            <colgroup>
                <col style="width:5%">
                <col style="width:4%">
                <col style="width:7%">
                @if($hasClient)<col style="width:5%">@endif
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:6%">
                <col style="width:22%">
                <col style="width:5%">
                @if($hasClient)<col style="width:5%">@endif
            </colgroup>

            <thead>
                <tr>
                    <th>日付</th>
                    <th>曜日</th>
                    <th>区分</th>
                    @if($hasClient)<th>他社</th>@endif
                    <th>勤怠打刻</th>
                    <th>始業</th>
                    <th>終業打刻</th>
                    <th>終業</th>
                    <th>休憩</th>
                    <th>実働</th>
                    <th>時間外</th>
                    <th>備考</th>
                    <th>申請</th>
                    @if($hasClient)<th>承認①</th>@endif
                </tr>
            </thead>

            <tbody>
                @foreach($attendanceData as $data)
                    @php
                        $att = $data['attendance'] ?? null;
                        $isAdminApproved = $att->is_approved_by_admins ?? false;
                        $isRejected = $att->rejection ?? false;
                        $minutesToTime = fn($min) => sprintf('%02d:%02d', floor($min/60), $min%60);
                        $workMinutes = $data['workMinutes'] ?? 0;
                        $overtimeMinutes = $data['overtimeMinutes'] ?? 0;
                    @endphp
                    <tr 
                        data-rejection="{{ $att->rejection ?? 0 }}" 
                        data-approved-by-clients="{{ $att->is_approved_by_clients ?? 0 }}" 
                        data-approved-by-admins="{{ $att->is_approved_by_admins ?? 0 }}" 
                        data-rejection-comment="{{ $att->rejection_comment ?? '' }}"
                    >
                        <td>{{ \Carbon\Carbon::parse($data['date'])->format('n/j') }}</td>
                        <td>{{ $data['weekday'] ?? '' }}</td>

                        <td>
                            <select class="category-select" {{ $isAdminApproved ? 'disabled' : '' }}>
                                <option value="" {{ !$att ? 'selected' : '' }}>選択</option>
                                @foreach(['出勤','公休','有給','午前休','午後休','振出','振休','休出','欠勤','遅刻','早退'] as $cat)
                                    <option value="{{ $cat }}" {{ $att && ($att->category ?? '') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </td>

                        @if($hasClient)
                        <td>
                            <input type="checkbox" class="other-company-checkbox" {{ $att && ($att->is_other_company_work ?? false) ? 'checked' : '' }} {{ $isAdminApproved ? 'disabled' : '' }}>
                        </td>
                        @endif

                        <td>{{ $att && ($att->clock_in ?? null) ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '' }}</td>

                        <td>
                            <input type="time" class="start-time" 
                                value="{{ $att && ($att->start_time ?? null) ? \Carbon\Carbon::parse($att->start_time)->format('H:i') : ($att && ($att->clock_in ?? null) ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '') }}" 
                                {{ $isAdminApproved ? 'disabled' : '' }}>
                        </td>

                        <td>{{ $att && ($att->clock_out ?? null) ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '' }}</td>

                        <td>
                            <input type="time" class="end-time" 
                                value="{{ $att && ($att->end_time ?? null) ? \Carbon\Carbon::parse($att->end_time)->format('H:i') : ($att && ($att->clock_out ?? null) ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '') }}" 
                                {{ $isAdminApproved ? 'disabled' : '' }}>
                        </td>

                        <td><input type="time" class="break-time" value="{{ $att->break_time ?? '' }}" {{ $isAdminApproved ? 'disabled' : '' }}></td>

                        <td>{{ $workMinutes > 0 ? $minutesToTime($workMinutes) : '' }}</td>
                        <td>{{ $overtimeMinutes > 0 ? $minutesToTime($overtimeMinutes) : '' }}</td>

                        <td>
                            <input type="text" class="remarks" value="{{ $att->remarks ?? '' }}" {{ $isAdminApproved ? 'disabled' : '' }}>
                            @if($isRejected && ($att->rejection_comment ?? false))
                                <div class="rejection-comment">{{ $att->rejection_comment }}</div>
                            @endif
                        </td>

                        <td>
                            <input type="checkbox" class="submit-checkbox" data-date="{{ $data['date'] }}" {{ $att && ($att->is_submitted ?? false) ? 'checked' : '' }} {{ $isAdminApproved || ($att && ($att->is_approved_by_clients ?? false)) ? 'disabled' : '' }}>
                        </td>

                        @if($hasClient)
                        <td>
                            <input type="checkbox" class="client-approve-checkbox" 
                                data-date="{{ $data['date'] }}"
                                {{ $att && ($att->is_approved_by_clients ?? false) ? 'checked' : '' }}
                                {{ $isAdminApproved ? 'disabled' : '' }}>
                        </td>
                        @endif

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@section('js')
<script>
function setRowState(row, isSubmitted, isRejected, isAdminApproved, rejectionComment='') {
    row.classList.remove('approved','rejected','admin-approved');

    if(isAdminApproved) row.classList.add('admin-approved');    // admin承認済み=青
    else if(isRejected) row.classList.add('rejected');          // 差戻し=ピンク
    else if(isSubmitted) row.classList.add('approved');         // 申請済み=緑

    // 備考欄に差戻しコメント
    const remarksTd = row.querySelector('.remarks')?.parentElement;
    if(remarksTd){
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
    }

    // 入力制御
    const inputDisabled = isAdminApproved || isSubmitted;
    row.querySelectorAll('.start-time, .end-time, .break-time, .category-select, .other-company-checkbox, .remarks')
        .forEach(i => i.disabled = inputDisabled);

    // 申請チェック
    const submitCheckbox = row.querySelector('.submit-checkbox');
    const clientCheckbox = row.querySelector('.client-approve-checkbox');
    if(submitCheckbox){
        submitCheckbox.disabled = isAdminApproved || (clientCheckbox?.checked ?? false);
    }

    // 承認①は管理者承認済みなら編集不可
    if(clientCheckbox) clientCheckbox.disabled = isAdminApproved;
}

// ページ読み込み時に行状態を初期化
document.querySelectorAll('tbody tr').forEach(row => {
    const submitCheckbox = row.querySelector('.submit-checkbox');
    const clientCheckbox = row.querySelector('.client-approve-checkbox');

    const isSubmitted = submitCheckbox?.checked ? 1 : 0;
    const isRejected = row.dataset.rejection == '1';
    const isAdminApproved = row.dataset.approvedByAdmins == '1';
    const rejectionComment = row.dataset.rejectionComment || '';

    setRowState(row, isSubmitted, isRejected, isAdminApproved, rejectionComment);

    // 申請チェック
    submitCheckbox?.addEventListener('change', function() {
        const date = this.dataset.date;
        const category = row.querySelector('.category-select')?.value || '';
        const startTime = row.querySelector('.start-time')?.value || '';
        const endTime = row.querySelector('.end-time')?.value || '';
        const breakTime = row.querySelector('.break-time')?.value || '';
        const isOther = row.querySelector('.other-company-checkbox')?.checked ? 1 : 0;
        const remarks = row.querySelector('.remarks')?.value || '';
        const isSubmittedNow = this.checked ? 1 : 0;

        // 承認①チェック時は申請不可
        if(clientCheckbox?.checked){
            alert("承認①がONのため申請は修正できません。まず承認①をOFFにしてください。");
            this.checked = !this.checked;
            return;
        }

        // 必須チェック
        if(isSubmittedNow && (!category || !startTime || !endTime || !breakTime)){
            alert("区分・始業・終業・休憩は必須です。入力してください。");
            this.checked = false;
            return;
        }

        fetch("{{ route('admin.attendance.submit', ['staff'=>$user->id]) }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                date,
                category,
                is_other_company_work: isOther,
                start_time: startTime,
                end_time: endTime,
                break_time: breakTime,
                remarks,
                is_submitted: isSubmittedNow
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status !== 'success'){
                alert(data.message);
                submitCheckbox.checked = !isSubmittedNow; // 元に戻す
            }
            setRowState(row, submitCheckbox.checked, data.rejection==1, isAdminApproved, data.rejection_comment || '');
        });
    });

    // 承認①チェック
    clientCheckbox?.addEventListener('change', function() {
        const date = this.dataset.date;
        const isApprovedByClient = this.checked ? 1 : 0;

        fetch("{{ route('admin.attendance.submit', ['staff'=>$user->id]) }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                date,
                is_approved_by_clients: isApprovedByClient
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status !== 'success'){
                alert(data.message);
                clientCheckbox.checked = !clientCheckbox.checked;
            }
            // 申請チェックの編集可否を即時反映
            const submitCheckbox = row.querySelector('.submit-checkbox');
            if(submitCheckbox){
                submitCheckbox.disabled = isAdminApproved || clientCheckbox.checked;
            }
        });
    });
});

// 休憩時間初期値
document.querySelectorAll('.break-time').forEach(input=>{
    input.addEventListener('focus', ()=>{
        if(!input.value){
            input.value = '01:00';
            input.dispatchEvent(new Event('change',{bubbles:true}));
        }
    });
});
</script>
@endsection
