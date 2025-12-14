@extends('layouts.app')

@section('title', '経費精算詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">経費精算詳細</div>
            <div class="header-right">
                <a href="{{ route('admin.expense.list', ['year'=>$year, 'month'=>$month]) }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- ▼ 年月セレクト -->
    <div class="month-picker">
        <form id="month-form" action="{{ route('admin.user.list', ['user_id'=>$user->id]) }}" method="GET">
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

    <!-- ▼ 差戻モーダル -->
    <div id="rejection-modal" class="modal">
        <div class="modal-content">
            <h2>差戻確認</h2>
            <p>コメント</p>

            <textarea id="reject-comment" rows="4"
                placeholder="差戻理由を入力"
                class="reject-textarea"></textarea>

            <div class="modal-footer">
                <button id="reject-ok" class="modal-btn ok-btn" type="button">OK</button>
                <button id="reject-cancel" class="modal-btn cancel-btn" type="button">キャンセル</button>
            </div>
        </div>
    </div>

    <!-- ▼ テーブル -->
    <div class="attendance-table-wrapper">
        <table>
            <colgroup>
                <col style="width:7%"> <!-- 日付：短め -->
                <col style="width:7%"> <!-- 曜日：短め -->
                <col style="width:12%"> <!-- 種別：やや広め -->
                <col style="width:12%"> <!-- 金額：数字なのでやや広め -->
                <col style="width:15%"> <!-- 支払先：店名が長くなることがあるので広め -->
                <col style="width:21%"> <!-- 用途：文章なので一番広く -->
                <col style="width:10%"> <!-- 画像：リンクのみなので狭め -->
                <col style="width:8%"> <!-- 差戻：チェックボックス -->
                <col style="width:8%"> <!-- 承認：チェックボックス -->
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
                @foreach($expenseList as $exp)
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
                            class="reject-checkbox"
                            data-id="{{ $exp->id }}"
                            @if($exp->is_rejection == 1) checked @endif
                            @if($exp->is_approved_by_admins == 1) disabled @endif>
                    </td>
                    <td>
                        <input type="checkbox"
                            class="approve-checkbox"
                            data-id="{{ $exp->id }}"
                            @if($exp->is_approved_by_admins == 1) checked @endif>
                    </td>
                </tr>
                @endforeach
            </tbody>

        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $expenseList->appends(request()->query())->links() }}
    </div>

</div>
@endsection

@section('js')
<script>
document.addEventListener("DOMContentLoaded", () => {

    let targetId = null;
    const modal = document.getElementById("rejection-modal");
    const commentBox = document.getElementById("reject-comment");

    // ▼ 差戻チェックボックス
    document.querySelectorAll(".reject-checkbox").forEach(cb => {
        cb.addEventListener("change", function() {
            const id = this.dataset.id;
            const approveCB = document.querySelector(`.approve-checkbox[data-id="${id}"]`);
            
            // 承認済は差戻不可
            if (approveCB.checked) {
                alert("承認済みの申請は差戻できません");
                this.checked = false;
                return;
            }

            if (this.checked) {
                // チェックON → モーダル表示
                targetId = id;
                commentBox.value = "";
                modal.style.display = "block";
            } else {
                // チェックOFF → 即サーバー反映（差戻解除）
                fetch("{{ route('admin.reject') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        expense_id: id,
                        is_rejection: false,
                        rejection_comment: ""
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert("差戻解除に失敗しました");
                        this.checked = true;
                    } else {
                        // 承認可能にする
                        approveCB.disabled = false;
                        const row = this.closest("tr");
                        row.classList.remove("rejected");
                    }
                })
                .catch(() => {
                    alert("通信エラー");
                    this.checked = true;
                });
            }
        });
    });

    // ▼ 差戻キャンセル
    document.getElementById("reject-cancel").addEventListener("click", () => {
        modal.style.display = "none";
        if (targetId) {
            document.querySelector(`.reject-checkbox[data-id="${targetId}"]`).checked = false;
            targetId = null;
        }
    });

    // ▼ 差戻OK（コメント入力後）
    document.getElementById("reject-ok").addEventListener("click", () => {
        const comment = commentBox.value.trim();
        if (!comment) return alert("コメントを入力してください");

        fetch("{{ route('admin.reject') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                expense_id: targetId,
                is_rejection: true,
                rejection_comment: comment
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert("差戻に失敗しました");
                document.querySelector(`.reject-checkbox[data-id="${targetId}"]`).checked = false;
            } else {
                const row = document.querySelector(`.reject-checkbox[data-id="${targetId}"]`).closest("tr");
                const approveCB = row.querySelector(".approve-checkbox");
                approveCB.disabled = true;  // 差戻中は承認不可
                row.classList.add("rejected");
            }
            modal.style.display = "none";
            targetId = null;
        })
        .catch(() => {
            alert("通信エラー");
            targetId = null;
        });
    });

    // ▼ 承認チェックボックス
    document.querySelectorAll(".approve-checkbox").forEach(cb => {
        cb.addEventListener("change", function() {
            const id = this.dataset.id;
            const approve = this.checked ? 1 : 0;
            const row = this.closest("tr");
            const rejectCB = row.querySelector(".reject-checkbox");

            // 差戻中は承認不可
            if (rejectCB.checked) {
                alert("差戻中の申請は承認できません");
                this.checked = false;
                return;
            }

            fetch("{{ route('admin.approve') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    expense_id: id,
                    approve: approve
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert("承認更新に失敗");
                    this.checked = !this.checked;
                } else {
                    if (approve) {
                        // 承認ON → 差戻解除 + 差戻不可
                        rejectCB.checked = false;
                        rejectCB.disabled = true;
                        row.classList.remove("rejected");
                        row.classList.add("approved");
                    } else {
                        // 承認OFF → 差戻再び可能
                        rejectCB.disabled = false;
                        row.classList.remove("approved");
                    }
                }
            })
            .catch(() => {
                alert("通信エラー");
                this.checked = !this.checked;
            });
        });
    });

});

</script>
@endsection
