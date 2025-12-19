@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_staff_list.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">スタッフ一覧</div>
            <div class="header-right">
                <a href="{{ route('admin.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- ▼ 検索条件 -->
    <form method="GET" action="{{ route('admin.user.list') }}" class="search-box">
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

            <select name="paid_leave_5days" onchange="this.form.submit();">
                <option value="">有給5日取得</option>
                <option value="1" @if($paidLeave5days=="1") selected @endif>取得済</option>
                <option value="0" @if($paidLeave5days=="0") selected @endif>未取得</option>
            </select>

            <select name="retired" onchange="this.form.submit();">
                <option value="">在籍</option>
                <option value="1" @if($retired=="1") selected @endif>退職済み</option>
            </select>

            <a href="{{ route('admin.user.list') }}" class="clear-btn">クリア</a>
        </div>
    </form>

    <!-- ▼ テーブル -->
    <div class="attendance-table-wrapper">
        <table>
            <colgroup>
                <col style="width:5%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:12%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:8%">
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
                    <th>有給付与日</th>
                    <th>有給5日取得</th>
                    <th>編集・詳細</th>
                    <th>申請・勤怠</th>
                </tr>
            </thead>

            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->department ?? '' }}</td>
                    <td>{{ $user->workplace ?? '' }}</td>
                    <td>{{ $user->contract_type ?? '' }}</td>
                    <td>{{ optional($user->paidLeave)->granted_date ? \Carbon\Carbon::parse($user->paidLeave->granted_date)->format('Y/m/d') : '-' }}</td>
                    <td>
                        @php
                            $taken = optional($user->paidLeave)->current_year_taken ?? 0;
                        @endphp

                        @if($taken >= 5)
                            ○
                        @else
                            {{ $taken }}日
                        @endif
                    </td>

                    <td>
                        <a href="{{ route('admin.user.edit', ['user_id'=>$user->id]) }}" title="編集・詳細">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('admin.user.requests', ['user_id'=>$user->id]) }}" title="申請・勤怠">
                            <i class="fa-solid fa-file-lines"></i>
                        </a>
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

    <div class="pagination-wrapper flex justify-center space-x-1 mt-4">
        {{-- 前へ --}}
        @if ($users->onFirstPage())
            <span class="page-box disabled">&laquo;</span>
        @else
            <a href="{{ $users->previousPageUrl() }}" class="page-box">&laquo;</a>
        @endif

        {{-- ページ番号 --}}
        @for ($i = 1; $i <= $users->lastPage(); $i++)
            @if ($i == $users->currentPage())
                <span class="page-box active">{{ $i }}</span>
            @else
                <a href="{{ $users->url($i) }}" class="page-box">{{ $i }}</a>
            @endif
        @endfor

        {{-- 次へ --}}
        @if ($users->hasMorePages())
            <a href="{{ $users->nextPageUrl() }}" class="page-box">&raquo;</a>
        @else
            <span class="page-box disabled">&raquo;</span>
        @endif
    </div>


</div>
@endsection
