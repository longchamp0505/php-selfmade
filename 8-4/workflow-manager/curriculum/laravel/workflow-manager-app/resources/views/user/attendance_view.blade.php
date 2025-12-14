@extends('layouts.app')

@section('title', '勤怠閲覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-view.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">勤怠閲覧</div>
            <div class="header-right">
                <a href="{{ route('user.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- メイン -->
    <main class="attendance-main">

        <!-- 年セレクト -->
        <div class="month-picker">
            <form id="year-form" action="{{ route('user.attendance.view') }}" method="GET">
                <select name="year" id="year-select" onchange="this.form.submit();">
                    @for ($y = now()->year - 1; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" @if($y == $year) selected @endif>{{ $y }}年</option>
                    @endfor
                </select>
            </form>
        </div>

        <!-- 勤怠一覧テーブル -->
        <div class="attendance-table-wrapper">
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

    </main>
</div>
@endsection
