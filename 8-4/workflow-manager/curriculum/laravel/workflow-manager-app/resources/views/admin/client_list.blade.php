@extends('layouts.app')

@section('title', 'クライアント一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_staff_list.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">クライアント一覧</div>
            <div class="header-right">
                <a href="{{ route('admin.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- ▼ 検索条件 -->
    <form method="GET" action="{{ route('admin.client.list') }}" class="search-box">
        <div class="search-box">
            <div class="search-input-wrapper">
                <input type="text" name="keyword" value="{{ $keyword ?? '' }}" placeholder="ID or 会社名">
                <button type="submit" class="search-btn">🔍</button>
            </div>

            <!-- ステータス（承認完了 / 未承認） -->
            <select name="status" onchange="this.form.submit();">
                <option value="">ステータス</option>
                <option value="approved" @if($selectedStatus=="approved") selected @endif>契約中</option>
                <option value="pending" @if($selectedStatus=="pending") selected @endif>非契約</option>
            </select>

            <a href="{{ route('admin.user.list') }}" class="clear-btn">クリア</a>
        </div>
    </form>

    <!-- ▼ テーブル -->
    <div class="attendance-table-wrapper">
        <table>
            <colgroup>
                <col style="width:8%">   <!-- ID -->
                <col style="width:18%">  <!-- 会社名 -->
                <col style="width:12%">  <!-- 部署 -->
                <col style="width:12%">  <!-- 担当者① -->
                <col style="width:12%">  <!-- 担当者② -->
                <col style="width:12%">  <!-- 担当者③ -->
                <col style="width:10%">   <!-- ステータス -->
                <col style="width:8%">   <!-- 稼働人数 -->
                <col style="width:8%">   <!-- 編集・詳細 -->
            </colgroup>


            <thead>
                <tr>
                    <th>ID</th>
                    <th>会社名</th>
                    <th>部署</th>
                    <th>担当者➊</th>
                    <th>担当者➋</th>
                    <th>担当者➌</th>
                    <th>ステータス</th>
                    <th>稼働人数</th>
                    <th>編集・詳細</th>
                </tr>
            </thead>

            <tbody>
            @forelse($clients as $client)
            <tr>
                <td>{{ $client->id }}</td>
                <td>{{ $client->company_name }}</td>
                <td>{{ $client->department_name ?? '' }}</td>
                <td>{{ $client->approver1_name ?: 'ー' }}</td>
                <td>{{ $client->approver2_name ?: 'ー' }}</td>
                <td>{{ $client->approver3_name ?: 'ー' }}</td>

                {{-- ステータス --}}
                <td>
                    @if($client->users_count > 0)
                        契約中
                    @else
                        非契約
                    @endif
                </td>

                {{-- 稼働人数 --}}
                <td>{{ $client->users_count }}名</td>

                <td>
                    <a href="{{ route('admin.client.edit', ['client_id'=>$client->id]) }}">
                        <i class="fa-solid fa-pen-to-square"></i>
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

    <!-- ▼ ページネーション -->
    <div class="pagination-wrapper">
        {{ $clients->appends(request()->query())->links() }}
    </div>

</div>
@endsection
