<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>{{ $year }}年{{ $month }}月 稼働報告</title>
<style>
body {
    font-family: "Helvetica Neue", Arial, "ヒラギノ角ゴ ProN", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
    font-size: 10pt;
    margin: 0;
}
h1 {
    text-align: center;
    margin-bottom: 13px;
}
.employee-info { margin-bottom: 10px; }
.employee-info div { margin-bottom: 2px; }

table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
th, td {
    border: 1px solid #444;
    padding: 3.8px 4px;
    text-align: center;
    vertical-align: middle;
}
th { background:#f2f2f2; }

.summary-container {
    display: flex;
    margin-top: 10px;
}
.summary-box {
    border: 1px solid #444;
    width: 200px;
    margin-right: 14px;
    text-align: center;
}
.summary-title {
    font-weight: bold;
    background:#f2f2f2;
    padding:4px 0;
    border-bottom:1px solid #444;
}
.summary-value { padding:4px 0; }
</style>
</head>
<body>

@php
$minutesToTime = fn($m) =>
    $m > 0 ? sprintf('%02d:%02d', floor($m/60), $m%60) : '';
@endphp

@foreach($staffs as $staff)

<h1>{{ $year }}年{{ $month }}月 稼働報告</h1>

<div class="employee-info">
    <div>スタッフ氏名：{{ $staff->name }}</div>
</div>

<table>
    <colgroup>
        <col style="width:5%">
        <col style="width:4%">
        <col style="width:7%">

        @if($staff->client_id)
            <col style="width:5%"> {{-- 他社 --}}
        @endif
        <col style="width:6%">
        <col style="width:6%">
        <col style="width:6%">
        <col style="width:6%">
        <col style="width:6%">
        <col style="width:6%">
        <col style="width:6%">
        <col style="width:22%">
        <col style="width:5%">

        @if($staff->client_id)
            <col style="width:5%"> {{-- 承認① --}}
        @endif

        <col style="width:5%"> {{-- 承認② --}}
    </colgroup>

    <thead>
        <tr>
            <th>日付</th>
            <th>曜日</th>
            <th>区分</th>

            @if($staff->client_id)
                <th>他社</th>
            @endif

            <th>勤怠打刻</th>
            <th>始業</th>
            <th>終業打刻</th>
            <th>終業</th>
            <th>休憩</th>
            <th>実働</th>
            <th>時間外</th>
            <th>備考</th>
            <th>申請</th>

            @if($staff->client_id)
                <th>承認①</th>
            @endif

            <th>承認②</th>
        </tr>
    </thead>

    <tbody>
    @foreach($staff->attendanceData as $data)
        @php $att = $data['attendance']; @endphp
        <tr>
            <td>{{ \Carbon\Carbon::parse($data['date'])->format('n/j') }}</td>
            <td>{{ $data['weekday'] }}</td>
            <td>{{ $att->category ?? '' }}</td>

            @if($staff->client_id)
                <td>{{ $att && $att->is_other_company_work ? '○' : '' }}</td>
            @endif

            <td>{{ $att && $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '' }}</td>
            <td>{{ $att && $att->start_time ? \Carbon\Carbon::parse($att->start_time)->format('H:i') : '' }}</td>
            <td>{{ $att && $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '' }}</td>
            <td>{{ $att && $att->end_time ? \Carbon\Carbon::parse($att->end_time)->format('H:i') : '' }}</td>
            <td>{{ !empty($att->break_time) ? \Carbon\Carbon::parse($att->break_time)->format('H:i') : '' }}</td>
            <td>{{ $minutesToTime($data['workMinutes']) }}</td>
            <td>{{ $minutesToTime($data['overtimeMinutes']) }}</td>
            <td>{{ $att->remarks ?? '' }}</td>
            <td>{{ $att && $att->is_submitted ? '✔' : '' }}</td>

            @if($staff->client_id)
                <td>{{ $att && $att->is_approved_by_clients ? '◎' : '' }}</td>
            @endif

            <td>{{ $att && $att->is_approved_by_admins ? '◎' : '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="summary-container">
    <div class="summary-box">
        <div class="summary-title">出勤日数</div>
        <div class="summary-value">{{ $staff->summary['workDays'] }}日</div>
    </div>
    <div class="summary-box">
        <div class="summary-title">総稼働時間</div>
        <div class="summary-value">
            {{ sprintf('%02d:%02d',
                floor($staff->summary['totalMinutes']/60),
                $staff->summary['totalMinutes']%60
            ) }}
        </div>
    </div>
    <div class="summary-box">
        <div class="summary-title">時間外合計（➊+➋）</div>
        <div class="summary-value">
            {{ sprintf('%02d:%02d',
                floor(($staff->summary['overtimeDailyMinutes']+$staff->summary['weeklyOvertime'])/60),
                ($staff->summary['overtimeDailyMinutes']+$staff->summary['weeklyOvertime'])%60
            ) }}
        </div>
    </div>
</div>

{{-- ★ スタッフごとに改ページ --}}
<div style="page-break-after: always;"></div>

@endforeach

</body>
</html>
