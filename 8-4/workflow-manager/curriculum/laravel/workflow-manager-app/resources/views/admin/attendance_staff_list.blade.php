@extends('layouts.app')

@section('title', '勤怠承認一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">勤怠承認一覧</div>
            <div class="header-right">
                <a href="{{ route('admin.export.csv', ['year'=>$year, 'month'=>$month]) }}" class="csv-btn">CSV出力</a>
                <a href="{{ route('admin.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- ▼ 年月セレクト -->
    <div class="month-picker">
        <form id="month-form" action="{{ route('admin.staff.list') }}" method="GET">
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
    <form method="GET" action="{{ route('admin.staff.list') }}" class="search-box">

        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">

        <div class="search-box">
            <div class="search-input-wrapper">
                <input type="text" name="keyword" value="{{ $keyword ?? '' }}" placeholder="ID or 氏名">
                <button type="submit" class="search-btn">🔍</button>
            </div>

            <select name="department" onchange="this.form.submit();">
                <option value="" @if($selectedDepartment === '') selected @endif>所属</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept }}" @if($selectedDepartment === $dept) selected @endif>{{ $dept }}</option>
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
                <option value="完了" @if($selectedStatus=="完了") selected @endif>完了</option>
                <option value="未完了" @if($selectedStatus=="未完了") selected @endif>未完了</option>
            </select>

            <a href="{{ route('admin.staff.list', ['year'=>$year, 'month'=>$month]) }}" class="clear-btn">クリア</a>
        </div>
    </form>

    

    <!-- ▼ テーブル -->
    <div class="attendance-table-wrapper">
        <form id="pdf-form" action="{{ route('admin.approval.pdf') }}" method="POST">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">

            <table>
                <colgroup>
                    <col style="width:5%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:12%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:8%">
                    <col style="width:5%">
                </colgroup>
                <thead>
                    <tr>
                        <th>ID</th><th>氏名</th><th>所属</th><th>勤務先</th>
                        <th>契約形態</th><th>出勤日数</th><th>有給日数</th>
                        <th>実働時間</th><th>残業時間</th><th>ステータス</th><th>PDF選択</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffData as $staff)
                    <tr>
                        <td>{{ $staff['id'] }}</td>

                        <td>
                            <a href="{{ route('admin.attendance.approval', ['staff' => $staff['id']]) }}">
                                {{ $staff['name'] }}
                            </a>


                        </td>

                        <td>{{ $staff['department'] ?? '' }}</td>
                        <td>{{ $staff['workplace'] ?? '' }}</td>
                        <td>{{ $staff['contract_type'] ?? '' }}</td>

                        <td>{{ $staff['workDays'] }}日</td>
                        <td>{{ $staff['paidLeaveDays'] ?? 0 }}日</td>

                        <td>{{ floor($staff['workMinutes']/60) }}:{{ sprintf('%02d', $staff['workMinutes']%60) }}</td>
                        <td>{{ floor($staff['overtimeMinutes']/60) }}:{{ sprintf('%02d', $staff['overtimeMinutes']%60) }}</td>

                        <td>
                            @if($staff['status'] === '完了')
                                <span class="status-done">完了</span>
                            @else
                                <span class="status-pending">未完了</span>
                            @endif
                        </td>

                        <td><input type="checkbox" name="staff_select[]" value="{{ $staff['id'] }}"></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" style="text-align:center;">データがありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    </div>

    <!-- ▼ PDFボタン -->
    <div class="pdf-button-wrapper">
        <button type="submit" form="pdf-form" class="pdf-btn" id="pdf-submit-btn">
            PDF出力
        </button>

    </div>

    <!-- ▼ ページネーション -->
    <div class="pagination-wrapper">
        {{ $staffs->appends(request()->query())->links() }}
    </div>

</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pdfButton = document.getElementById('pdf-submit-btn');
    const form = document.getElementById('pdf-form');

    pdfButton.addEventListener('click', function (e) {
        const checked = form.querySelectorAll('input[name="staff_select[]"]:checked');

        if (checked.length === 0) {
            e.preventDefault();
            alert('PDF出力するスタッフを選択してください。');
        }
    });
});
</script>
@endsection
