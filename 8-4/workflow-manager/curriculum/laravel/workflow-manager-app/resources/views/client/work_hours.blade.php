@extends('layouts.app')
@section('title','稼働時間一覧')
@section('css')
<link rel="stylesheet" href="{{ asset('css/work_hours.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">稼働時間一覧</div>
            <div class="header-right">
                <a href="{{ route('client.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <div class="month-picker">
        <form method="GET">
            <select name="year" onchange="this.form.submit()">
                @for($y = now()->year -1; $y <= now()->year +1; $y++)
                    <option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}年</option>
                @endfor
            </select>

            <select name="contract_type" onchange="this.form.submit()">
                <option value="">全て</option>
                @foreach(['SES','派遣'] as $type)
                    <option value="{{ $type }}" {{ $type==$contractType?'selected':'' }}>{{ $type }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="attendance-table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>該当月</th>
                    <th>稼働人数</th>
                    <th>稼働時間</th>
                    <th>契約内時間</th>
                    <th>契約外時間</th>
                    <th>稼働合計日数</th>
                </tr>
            </thead>
            <tbody>
                @foreach($months as $i => $month)
                <tr>
                    <td>{{ $month }}月</td>
                    <td>{{ $data[$i]['staffCount'] }}名</td>
                    <td>{{ sprintf('%02d:%02d', floor($data[$i]['workMinutes']/60), $data[$i]['workMinutes']%60) }}</td>
                    <td>{{ sprintf('%02d:%02d', floor($data[$i]['contractMinutes']/60), $data[$i]['contractMinutes']%60) }}</td>
                    <td>{{ sprintf('%02d:%02d', floor($data[$i]['overtimeMinutes']/60), $data[$i]['overtimeMinutes']%60) }}</td>
                    <td>{{ $data[$i]['workDays'] }}日</td>
                </tr>
                @endforeach
            
            
                <tr class="total-row">
                    <td>合計</td>
                    <td>{{ $totals['staffCount'] }}名</td>
                    <td>{{ sprintf('%02d:%02d', floor($totals['workMinutes']/60), $totals['workMinutes']%60) }}</td>
                    <td>{{ sprintf('%02d:%02d', floor($totals['contractMinutes']/60), $totals['contractMinutes']%60) }}</td>
                    <td>{{ sprintf('%02d:%02d', floor($totals['overtimeMinutes']/60), $totals['overtimeMinutes']%60) }}</td>
                    <td>{{ $totals['workDays'] }}日</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection
