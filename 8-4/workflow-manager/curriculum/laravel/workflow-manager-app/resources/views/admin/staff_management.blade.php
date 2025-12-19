@extends('layouts.app')

@section('title', 'スタッフ申請・勤怠')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">スタッフ申請・勤怠</div>
            <div class="header-right">
                <a href="{{ route('admin.user.list') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- ▼ 年月セレクト -->
    <div class="month-picker">
        <form id="month-form" action="{{ route('admin.user.requests', ['user_id' => $user->id]) }}" method="GET">
            <select name="year" onchange="this.form.submit();">
                @for ($y = now()->year-1; $y <= now()->year+1; $y++)
                    <option value="{{ $y }}" @if($y == $year) selected @endif>{{ $y }}年</option>
                @endfor
            </select>
            <select name="month" onchange="this.form.submit();">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @if($m == $month) selected @endif>{{ $m }}月</option>
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


    <!-- ▼ 休暇情報テーブル -->
    <div class="attendance-table-wrapper">
        <h3>■ 休暇情報</h3>
        <form action="{{ route('admin.leave.approve') }}" method="POST">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">

            <table>
                <colgroup>
                    <col style="width:7%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:7%">
                    <col style="width:7%">
                    <col style="width:7%">
                    <col style="width:20%">
                    <col style="width:6%">
                    <col style="width:6%">
                    <col style="width:5%">
                    <col style="width:5%">
                </colgroup>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>氏名</th>
                        <th>所属</th>
                        <th>勤務先</th>
                        <th>契約形態</th>
                        <th>申請種別</th>
                        <th>申請日</th>
                        <th>コメント</th>
                        <th>有給残</th>
                        <th>今年度</th>
                        <th>差戻</th> 
                        <th>承認</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($leavesData as $row)
                    <tr class="@if($row['is_rejection']) rejected @elseif($row['is_approved_by_admins']) approved @endif">
                        <td>{{ $row['user_id'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['department'] ?? '' }}</td>
                        <td>{{ $row['workplace'] ?? '' }}</td>
                        <td>{{ $row['contract_type'] ?? '' }}</td>
                        <td>{{ $row['leave_type'] }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($row['date'])->format('n/j') }}
                            ({{ \Carbon\Carbon::parse($row['date'])->locale('ja')->isoFormat('ddd') }})
                        </td>

                        <td>{{ $row['note'] ?? '' }}</td>
                        <td>{{ $row['remaining_days'] ?? 0 }}日</td>
                        <td>{{ $row['current_year_taken'] ?? 0 }}日</td>
                        <td>
                            <input type="checkbox"
                                class="leave-reject-checkbox"
                                data-id="{{ $row['id'] }}"
                                @if($row['is_rejection']) checked @endif
                                @if($row['is_approved_by_admins']) disabled @endif>
                        </td>

                        <td>
                            <input type="checkbox" class="leave-approve-checkbox" data-id="{{ $row['id'] }}"
                                @if($row['is_approved_by_admins']) checked @endif>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" style="text-align:center;">データがありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    </div>


    <!-- ▼ 経費情報テーブル -->
    <div class="attendance-table-wrapper">
        <h3>■ 経費情報</h3>
        <table>
            <colgroup>
                <col style="width:7%">
                <col style="width:7%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:15%">
                <col style="width:21%">
                <col style="width:10%">
                <col style="width:8%">
                <col style="width:8%">
            </colgroup>
            <thead>
                <tr>
                    <th>日付</th>
                    <th>曜日</th>
                    <th>種別</th>
                    <th>金額（税込）</th>
                    <th>支払先</th>
                    <th>用途</th>
                    <th>画像</th>
                    <th>差戻</th>
                    <th>承認</th>
                </tr>
            </thead>

            <tbody>
                @forelse($expenseList as $exp)
                <tr class="
                    @if($exp->is_rejection == 1) rejected
                    @elseif($exp->is_approved_by_admins == 1) approved
                    @endif
                ">
                    <td>{{ \Carbon\Carbon::parse($exp->date)->format('n/j') }}</td>
                    <td>{{ \Carbon\Carbon::parse($exp->date)->locale('ja')->isoFormat('ddd') }}</td>
                    <td>{{ $exp->category }}</td>
                    <td>¥{{ number_format($exp->amount) }}</td>
                    <td>{{ $exp->payee }}</td>
                    <td>{{ $exp->purpose }}</td>
                    <td>
                        @if($exp->receipt_image)
                            <a href="{{ asset('storage/'.$exp->receipt_image) }}" target="_blank">表示</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <input type="checkbox"
                            class="expense-reject-checkbox"
                            data-id="{{ $exp->id }}"
                            @if($exp->is_rejection == 1) checked @endif
                            @if($exp->is_approved_by_admins == 1) disabled @endif>
                    </td>
                    <td>
                        <input type="checkbox"
                            class="expense-approve-checkbox"
                            data-id="{{ $exp->id }}"
                            @if($exp->is_approved_by_admins == 1) checked @endif>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;">データがありません</td>
                </tr>
                @endforelse
            </tbody>

        </table>
    </div>


    <!-- 勤怠一覧テーブル -->
    <div class="attendance-table-wrapper">
        <h3>■ 勤怠情報</h3>
        <table>
            <colgroup>
                <col style="width:8%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:8%">
                <col style="width:8%">
                <col style="width:8%">
                <col style="width:8%">
                <col style="width:8%">
                <col style="width:8%">
            </colgroup>
            <thead>
                <tr>
                    <th>該当月</th>
                    <th>勤務時間</th>
                    <th>所定内</th>
                    <th>時間外</th>
                    <th>出勤日数</th>
                    <th>有給日数</th>
                    <th>欠勤日数</th>
                    <th>遅刻回数</th>
                    <th>早退回数</th>
                    <th>打刻漏れ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($months as $month => $data)
                <tr>
                    <td>{{ $month }}月</td>

                    {{-- 勤務時間 --}}
                    <td>{{ floor($data['workMinutes']/60) }}:{{ sprintf('%02d', $data['workMinutes']%60) }}</td>

                    {{-- 所定内 --}}
                    <td>{{ floor($data['regularMinutes']/60) }}:{{ sprintf('%02d', $data['regularMinutes']%60) }}</td>

                    {{-- 時間外（1日超 + 週超） --}}
                    <td>{{ floor($data['overMinutes']/60) }}:{{ sprintf('%02d', $data['overMinutes']%60) }}</td>

                    <td>{{ $data['workDays'] }}</td>
                    <td>{{ $data['paidLeaveDays'] }}</td>
                    <td>{{ $data['absentDays'] }}</td>
                    <td>{{ $data['lateCount'] }}</td>
                    <td>{{ $data['earlyLeaveCount'] }}</td>
                    <td>{{ $data['missingClock'] }}</td>
                </tr>
                @endforeach

                <!-- 合計 -->
                <tr class="total-row">
                    <td>合計</td>

                    <td>{{ floor($summary['workMinutes']/60) }}:{{ sprintf('%02d', $summary['workMinutes']%60) }}</td>

                    <td>{{ floor($summary['regularMinutes']/60) }}:{{ sprintf('%02d', $summary['regularMinutes']%60) }}</td>

                    <td>{{ floor($summary['totalOverMinutes']/60) }}:{{ sprintf('%02d', $summary['totalOverMinutes']%60) }}</td>

                    <td>{{ $summary['workDays'] }}</td>
                    <td>{{ $summary['paidLeaveDays'] }}</td>
                    <td>{{ $summary['absentDays'] }}</td>
                    <td>{{ $summary['lateCount'] }}</td>
                    <td>{{ $summary['earlyLeaveCount'] }}</td>
                    <td>{{ $summary['missingClock'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection

@section('js')
<script>
document.addEventListener("DOMContentLoaded", () => {

    /* =========================
     * 共通：モーダル操作
     * ========================= */
    let targetId = null;
    let targetType = null; // leave / expense
    const modal = document.getElementById("rejection-modal");
    const commentBox = document.getElementById("reject-comment");

    const openModal = (id, type) => {
        targetId = id;
        targetType = type;
        commentBox.value = "";
        modal.style.display = "block";
    };

    const closeModal = () => {
        modal.style.display = "none";
        targetId = null;
        targetType = null;
    };

    document.getElementById("reject-cancel").addEventListener("click", () => {
        // キャンセル時はチェック外す
        if(targetId && targetType){
            const cb = document.querySelector(`[data-id="${targetId}"].reject-${targetType}`);
            if(cb) cb.checked = false;
        }
        closeModal();
    });


    /* =========================
     * 差戻チェックボックス処理
     * ========================= */
    const setupRejectCheckbox = (type, url) => {
        document.querySelectorAll(`.${type}-reject-checkbox`).forEach(cb => {
            cb.addEventListener("change", function () {
                const id = this.dataset.id;
                const approveCB = document.querySelector(`.${type}-approve-checkbox[data-id="${id}"]`);

                // 承認済みなら差戻不可
                if (approveCB && approveCB.checked) {
                    alert(`承認済みの${type === 'leave' ? '申請' : '経費'}は差戻できません`);
                    this.checked = false;
                    return;
                }

                // チェックON → モーダル表示
                if (this.checked) {
                    openModal(id, type);
                    return;
                }

                // チェックOFF → 差戻解除としてPOST
                fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        [`${type}_id`]: id,
                        rejection_comment: "",
                        cancel: 1
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(!data.success){
                        alert("差戻解除に失敗しました");
                        cb.checked = true;
                        return;
                    }

                    // 承認の有効化
                    const approveCB = document.querySelector(`.${type}-approve-checkbox[data-id="${id}"]`);
                    if(approveCB) approveCB.disabled = false;
                })
                .catch(() => {
                    alert("通信エラー");
                    cb.checked = true;
                });
            });
        });
    };

    setupRejectCheckbox("leave", "{{ route('admin.leave.reject') }}");
    setupRejectCheckbox("expense", "{{ route('admin.reject') }}");


    /* =========================
     * 承認チェックボックス処理
     * ========================= */
    const setupApproveCheckbox = (type, url) => {
        document.querySelectorAll(`.${type}-approve-checkbox`).forEach(cb => {
            cb.addEventListener("change", function () {
                const id = this.dataset.id;
                const approveState = this.checked ? 1 : 0;

                // 差戻中は承認不可
                const rejectCB = document.querySelector(`.${type}-reject-checkbox[data-id="${id}"]`);
                if(rejectCB && rejectCB.checked){
                    alert("差戻中の申請は承認できません");
                    this.checked = false;
                    return;
                }

                fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        [`${type}_id`]: id,
                        approve: approveState
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if(!data.success){
                        alert(data.message ?? "承認処理に失敗しました");
                        cb.checked = !cb.checked;
                        return;
                    }

                    // 承認済みなら差戻無効化
                    if(rejectCB) rejectCB.disabled = approveState === 1;
                })
                .catch(() => {
                    alert("通信エラー");
                    cb.checked = !cb.checked;
                });
            });
        });
    };

    setupApproveCheckbox("leave", "{{ route('admin.leave.approve') }}");
    setupApproveCheckbox("expense", "{{ route('admin.approve') }}");


    /* =========================
     * モーダル OK ボタン処理
     * ========================= */
    document.getElementById("reject-ok").addEventListener("click", () => {
        const comment = commentBox.value.trim();
        if(!comment) return alert("コメントを入力してください");
        if(!targetId || !targetType) return;

        const url = targetType === "leave"
            ? "{{ route('admin.leave.reject') }}"
            : "{{ route('admin.reject') }}";

        const payload = targetType === "leave"
            ? { leave_id: targetId, rejection_comment: comment }
            : { expense_id: targetId, rejection_comment: comment };

        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if(!data.success){
                alert("差戻に失敗しました");
                closeModal();
                return;
            }

            const cb = document.querySelector(`[data-id="${targetId}"].reject-${targetType}`);
            if(cb) cb.checked = true;

            // 承認は押せなくする
            const approveCB = document.querySelector(`.${targetType}-approve-checkbox[data-id="${targetId}"]`);
            if(approveCB) approveCB.disabled = true;

            closeModal();
        })
        .catch(() => {
            alert("通信エラー");
            closeModal();
        });
    });

});
</script>
@endsection
