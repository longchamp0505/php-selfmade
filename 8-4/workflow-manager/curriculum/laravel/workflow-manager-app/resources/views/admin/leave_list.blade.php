@extends('layouts.app')

@section('title', '休暇申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">休暇申請一覧</div>
            <div class="header-right">
                <a href="{{ route('admin.leave.export.csv', ['year'=>$year, 'month'=>$month]) }}" class="csv-btn">CSV出力</a>
                <a href="{{ route('admin.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- ▼ 年月セレクト -->
    <div class="month-picker">
        <form id="month-form" action="{{ route('admin.leave.list') }}" method="GET">
            <select name="year" onchange="this.form.submit();">
                @for ($y = now()->year-1; $y <= now()->year+1; $y++)
                    <option value="{{ $y }}" @if($y == $year) selected @endif>{{ $y }}年</option>
                @endfor
            </select>
            <select name="month" onchange="this.form.submit();">
                @for ($m=1; $m<=12; $m++)
                    <option value="{{ $m }}" @if($m == $month) selected @endif>{{ $m }}月</option>
                @endfor
            </select>
        </form>
    </div>

    <!-- ▼ 検索条件 -->
    <form method="GET" action="{{ route('admin.leave.list') }}" class="search-box">
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">

        <div class="search-box">
            <div class="search-input-wrapper">
                <input type="text" name="keyword" value="{{ $keyword ?? '' }}" placeholder="ID or 氏名">
                <button type="submit" class="search-btn">🔍</button>
            </div>

            <select name="department" onchange="this.form.submit();">
                <option value="">所属</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept }}" @if($selectedDepartment == $dept) selected @endif>{{ $dept }}</option>
                @endforeach
            </select>

            <select name="contract_type" onchange="this.form.submit();">
                <option value="">契約形態</option>
                @foreach($contractTypes as $type)
                    <option value="{{ $type }}" @if($selectedContract == $type) selected @endif>{{ $type }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit();">
                <option value="">ステータス</option>
                <option value="承認" @if($selectedStatus=="承認") selected @endif>承認</option>
                <option value="差戻" @if($selectedStatus=="差戻") selected @endif>差戻</option>
                <option value="未処理" @if($selectedStatus=="未処理") selected @endif>未処理</option>
            </select>

            <a href="{{ route('admin.leave.list', ['year'=>$year, 'month'=>$month]) }}" class="clear-btn">クリア</a>
        </div>
    </form>

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


    <!-- ▼ テーブル -->
    <div class="attendance-table-wrapper">
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
                        <td class="remaining-days" data-user="{{ $row['user_id'] }}">
                            {{ $row['remaining_days'] ?? 0 }}日
                        </td>
                        <td class="current-year-taken" data-user="{{ $row['user_id'] }}">
                            {{ $row['current_year_taken'] ?? 0 }}日
                        </td>

                        <td>
                            <input type="checkbox" class="rejection-checkbox" data-id="{{ $row['id'] }}" 
                                @if($row['is_rejection']) checked @endif
                                @if($row['is_approved_by_admins']) disabled @endif>
                        </td>

                        <td>
                            <input type="checkbox" class="approve-checkbox" data-id="{{ $row['id'] }}"
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

    <!-- ▼ ページネーション -->
    <div class="pagination-wrapper">
        {{ $leaves->appends(request()->query())->links() }}
    </div>

</div>
@endsection


@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let selectedLeaveId = null;

    /** 承認チェックボックス **/
    document.querySelectorAll('.approve-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const leaveId = this.dataset.id;
            const rejectionCheckbox = document.querySelector(`.rejection-checkbox[data-id="${leaveId}"]`);

            // 差戻済は承認不可
            if (rejectionCheckbox.checked) {
                alert('差戻済の申請は承認できません');
                this.checked = false;
                return;
            }

            // 承認チェックON → 差戻チェックOFF + disabled
            if (this.checked) {
                rejectionCheckbox.checked = false;
                rejectionCheckbox.disabled = true;
            } else {
                // 承認チェックOFF → 差戻チェック再び有効化
                rejectionCheckbox.disabled = false;
            }

            const approveState = this.checked ? 1 : 0;

            fetch("{{ route('admin.leave.approve') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ leave_id: leaveId, approve: approveState })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message ?? '承認に失敗しました');
                    checkbox.checked = !checkbox.checked;
                    rejectionCheckbox.disabled = checkbox.checked;
                    return;
                }

                // ★ user_id単位で全部更新
                document
                .querySelectorAll(`.remaining-days[data-user="${data.user_id}"]`)
                .forEach(el => el.innerText = `${data.remaining_days}日`);

                document
                .querySelectorAll(`.current-year-taken[data-user="${data.user_id}"]`)
                .forEach(el => el.innerText = `${data.current_year_taken}日`);
            })


            .catch(() => {
                alert('通信エラー');
                checkbox.checked = !checkbox.checked;
                rejectionCheckbox.disabled = checkbox.checked ? true : false;
            });
        });
    });

    /** 差戻チェックボックス **/
    document.querySelectorAll('.rejection-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const leaveId = this.dataset.id;
            const approveCheckbox = document.querySelector(`.approve-checkbox[data-id="${leaveId}"]`);
            const isChecked = this.checked;

            // 承認済みなら差戻できない
            if (approveCheckbox.checked) {
                alert('承認済の申請は差戻できません');
                this.checked = false;
                return;
            }

            // チェックONならモーダル表示
            if (isChecked) {
                selectedLeaveId = leaveId;
                document.getElementById('reject-comment').value = '';
                document.getElementById('rejection-modal').style.display = 'block';
            } else {
                // チェックOFFならコメントなしでサーバーに差戻解除を送信
                fetch("{{ route('admin.leave.reject') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ leave_id: leaveId, is_rejection: false, rejection_comment: '' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        approveCheckbox.disabled = false; // 差戻解除で承認可能に
                    } else {
                        alert('差戻解除に失敗しました');
                        checkbox.checked = true; // 元に戻す
                    }
                })
                .catch(() => {
                    alert('通信エラー');
                    checkbox.checked = true;
                });
            }
        });
    });

    /** 差戻モーダル OK **/
    document.getElementById('reject-ok').addEventListener('click', function () {
        const comment = document.getElementById('reject-comment').value.trim();
        if (!comment) { alert('コメントを入力してください'); return; }

        fetch("{{ route('admin.leave.reject') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ leave_id: selectedLeaveId, is_rejection: true, rejection_comment: comment })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const checkbox = document.querySelector(`.rejection-checkbox[data-id="${selectedLeaveId}"]`);
                const approveCheckbox = document.querySelector(`.approve-checkbox[data-id="${selectedLeaveId}"]`);

                checkbox.checked = true;
                approveCheckbox.disabled = true; // 差戻中は承認不可
                document.getElementById('rejection-modal').style.display = 'none';
            } else {
                alert('差戻に失敗しました');
                document.querySelector(`.rejection-checkbox[data-id="${selectedLeaveId}"]`).checked = false;
            }
            selectedLeaveId = null;
        })
        .catch(() => {
            alert('通信エラー');
            selectedLeaveId = null;
        });
    });

    /** 差戻モーダル キャンセル **/
    document.getElementById('reject-cancel').addEventListener('click', function () {
        document.getElementById('rejection-modal').style.display = 'none';
        if (selectedLeaveId) {
            document.querySelector(`.rejection-checkbox[data-id="${selectedLeaveId}"]`).checked = false;
            selectedLeaveId = null;
        }
    });
});
</script>
@endsection
