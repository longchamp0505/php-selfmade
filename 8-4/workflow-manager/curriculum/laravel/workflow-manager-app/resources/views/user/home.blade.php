@extends('layouts.app')

@section('title', 'ホーム画面')

@section('css')
<link rel="stylesheet" href="{{ asset('css/home.css') }}">
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
                    <input type="hidden" name="type" value="user">
                    <button type="submit">ログアウト</button>
                </form>
            </div>
        </div>
    </header>

    <!-- メイン -->
    <main class="home-main">
        <!-- メニューボタン -->
        <div class="menu-buttons">
            <a href="{{ route('user.attendance') }}">
                <button>出勤打刻</button>
            </a>
            <a href="{{ route('user.attendance.input') }}">
                <button>勤怠入力</button>
            </a>
            <a href="{{ route('user.expense.index') }}">
                <button>経費申請</button>
            </a>
            <a href="{{ route('user.leave.index') }}">
                <button type="button">休暇申請</button>
            </a>
            <a href="{{ route('user.attendance.view') }}">
                <button type="button">勤怠閲覧</button>
            </a>
        </div>

        <!-- お知らせ（縦幅増量） -->
        <div class="notice-board">
            <div class="notice-header">お知らせ</div>
            <div class="notice-content">
                {!! nl2br(preg_replace(
                    '/(https?:\/\/[^\s]+)/',
                    '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                    $notice->content_staff ?? 'お知らせはありません'
                )) !!}
            </div>
        </div>

    </main>
</div>
@endsection
