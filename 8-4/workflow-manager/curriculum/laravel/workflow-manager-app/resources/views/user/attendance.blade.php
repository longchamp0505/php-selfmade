@extends('layouts.app')

@section('title', '勤怠打刻')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">勤怠打刻</div>
            <div class="header-right">
                <a href="{{ route('user.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- メイン -->
    <main class="attendance-main">

        <!-- メニュー -->
        <div class="menu">
            <a href="{{ route('user.attendance.input') }}">勤怠入力</a>
        </div>

        <!-- 日付・時刻表示 -->
        <div class="display-area">
            <p class="date">{{ \Carbon\Carbon::now()->format('n月j日') }}（{{ ['日','月','火','水','木','金','土'][\Carbon\Carbon::now()->dayOfWeek] }}）</p>
            <p class="time" id="current-time"></p>
        </div>

        <!-- ✅ 成功メッセージ表示 -->
        @if(session('success'))
            <div class="attendance-message">
                {{ session('success') }}
            </div>
        @endif

        <!-- 出退勤ボタン -->
        <div class="button-area">
            <form action="{{ route('user.attendance.start') }}" method="POST">
                @csrf
                <button type="submit" class="btn start-btn"
                    @if($attendance && $attendance->clock_in) disabled @endif>
                    出勤
                </button>
            </form>

            <form action="{{ route('user.attendance.end') }}" method="POST" onsubmit="return confirmEnd()">
                @csrf
                <button type="submit" class="btn end-btn"
                    @if(!$attendance || ($attendance && $attendance->clock_out)) disabled @endif>
                    退勤
                </button>
            </form>
        </div>

    </main>

</div>

<script>
    // 時間を1秒ごとに更新
    function updateTime() {
        const now = new Date();
        const formatted = now.toLocaleTimeString("ja-JP", { hour: "2-digit", minute: "2-digit" });
        document.getElementById('current-time').textContent = formatted;
    }
    setInterval(updateTime, 1000);
    updateTime();

    // 退勤確認用
    function confirmEnd() {
        @if(session('confirm'))
            return confirm("{{ session('confirm') }}"); // OKでtrue、キャンセルでfalse
        @endif
        return true;
    }
</script>

@endsection
