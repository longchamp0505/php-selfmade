@extends('layouts.app')

@section('title', '経費精算一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">経費精算一覧</div>
            <div class="header-right">
                <a href="{{ route('admin.expense.export.csv', ['year'=>$year, 'month'=>$month]) }}" class="csv-btn">CSV出力</a>
                <a href="{{ route('admin.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- ▼ 年月セレクト -->
    <div class="month-picker">
        <form id="month-form" action="{{ route('admin.expense.list') }}" method="GET">
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
    <form method="GET" action="{{ route('admin.expense.list') }}" class="search-box">
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">

        <div class="search-box">
            <!-- ID / 氏名 -->
            <div class="search-input-wrapper">
                <input type="text" name="keyword" value="{{ $keyword ?? '' }}" placeholder="ID or 氏名">
                <button type="submit" class="search-btn">🔍</button>
            </div>

            <!-- 所属 -->
            <select name="department" onchange="this.form.submit();">
                <option value="">所属</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept }}" @if($selectedDepartment == $dept) selected @endif>
                        {{ $dept }}
                    </option>
                @endforeach
            </select>

            <!-- 契約形態 -->
            <select name="contract_type" onchange="this.form.submit();">
                <option value="">契約形態</option>
                @foreach($contractTypes as $type)
                    <option value="{{ $type }}" @if($selectedContract == $type) selected @endif>
                        {{ $type }}
                    </option>
                @endforeach
            </select>

            <!-- ステータス（承認完了 / 未承認） -->
            <select name="status" onchange="this.form.submit();">
                <option value="">ステータス</option>
                <option value="approved" @if($selectedStatus=="approved") selected @endif>承認完了</option>
                <option value="pending" @if($selectedStatus=="pending") selected @endif>未承認</option>
            </select>

            <a href="{{ route('admin.expense.list', ['year'=>$year, 'month'=>$month]) }}" class="clear-btn">
                クリア
            </a>
        </div>
    </form>

    <!-- ▼ 一覧テーブル -->
    <div class="attendance-table-wrapper">
        <table>
            <colgroup>
                <col style="width:6%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:8%">
                <col style="width:8%">
            </colgroup>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>氏名</th>
                    <th>所属</th>
                    <th>勤務先</th>
                    <th>契約形態</th>
                    <th>合計金額</th>
                    <th>申請件数</th>
                    <th>承認済</th>
                    <th>未承認</th>
                </tr>
            </thead>

            <tbody>
                @forelse($expenseSummary as $row)
                <tr>
                    <td>{{ $row['user_id'] }}</td>

                    <td>
                        <a href="{{ route('admin.expense.user.list', ['user_id'=>$row['user_id'], 'year'=>$year, 'month'=>$month]) }}"
                           class="name-link">
                            {{ $row['name'] }}
                        </a>
                    </td>

                    <td>{{ $row['department'] ?? '' }}</td>
                    <td>{{ $row['workplace'] ?? '' }}</td>
                    <td>{{ $row['contract_type'] ?? '' }}</td>

                    <td>¥{{ number_format($row['total_amount']) }}</td>
                    <td>{{ $row['total_count'] }}</td>
                    <td>{{ $row['approved_count'] }}</td>
                    <td>{{ $row['pending_count'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;">データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ▼ ページネーション -->
    <div class="pagination-wrapper">
        {{ $expensePaginator->appends(request()->query())->links() }}
    </div>

</div>
@endsection
