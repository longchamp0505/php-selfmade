@extends('layouts.app')

@section('title', 'ホーム画面')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-home.css') }}">
@endsection

@section('content')
<div class="home-wrapper">
    <!-- ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">Dashboard</div>
            <div class="header-right">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <input type="hidden" name="type" value="admin">
                    <button type="submit">ログアウト</button>
                </form>
            </div>
        </div>
    </header>

    <!-- メイン -->
    <main class="home-main">
        <!-- メニューボタン -->
        <div class="menu-buttons">
            <a href="{{ route('admin.staff.list') }}">
                <button>勤怠承認</button>
            </a>
            <a href="{{ route('admin.leave.list') }}">
                <button>休暇申請承認</button>
            </a>
            <a href="{{ route('admin.expense.list') }}">
                <button>経費精算承認</button>
            </a>
            <a href="{{ route('admin.user.list') }}">
                <button>スタッフ管理</button>
            </a>
            <a href="{{ route('admin.client.list') }}">
                <button>クライアント管理</button>
            </a>
            <a href="{{ route('admin.user.create') }}">
                <button>アカウント発行（スタッフ）</button>
            </a>
            <a href="{{ route('admin.client.create') }}">
                <button>アカウント発行（クライアント）</button>
            </a>
            <a href="{{ route('admin.notice.edit') }}">
                <button>お知らせ管理</button>
            </a>
        </div>
    </main>
</div>
@endsection
