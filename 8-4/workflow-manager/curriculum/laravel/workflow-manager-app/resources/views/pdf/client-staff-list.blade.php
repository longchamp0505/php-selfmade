<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>稼働報告</title>
<style>
body {
    font-family: "Helvetica Neue", Arial, "ヒラギノ角ゴ ProN", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
    font-size: 10pt;
    margin: 0;
}
h1 {
    text-align: center;
    margin-bottom: 15px;
}
.employee-info { margin-bottom: 10px; }
.employee-info div { margin-bottom: 2px; }

table {
    width: 100%;
    border-collapse: collapse;
    page-break-inside: avoid;
    table-layout: fixed;
}
colgroup col:nth-child(1){width:5%;}
colgroup col:nth-child(2){width:4%;}
colgroup col:nth-child(3){width:7%;}
colgroup col:nth-child(4){width:6%;}
colgroup col:nth-child(5){width:6%;}
colgroup col:nth-child(6){width:6%;}
colgroup col:nth-child(7){width:6%;}
colgroup col:nth-child(8){width:6%;}
colgroup col:nth-child(9){width:6%;}
colgroup col:nth-child(10){width:6%;}
colgroup col:nth-child(11){width:22%;}
colgroup col:nth-child(12){width:5%;}
colgroup col:nth-child(13){width:5%;}

th, td {
    border: 1px solid #444;
    padding: 4px;
    text-align: center;
    vertical-align: middle;
    page-break-inside: avoid;
}
th { background:#f2f2f2; }

.summary-container {
    display: flex;
    flex-wrap: nowrap;
    margin-top: 10px;
    justify-content: flex-start; /* 左寄せ */
}
.summary-box {
    border: 1px solid #444;
    text-align: center;
    display: flex;
    flex-direction: column;
    width: 200px;       /* 固定幅 */
    margin-right: 14px;  /* 余白を少し */
}
.summary-box:last-child { margin-right: 0; }
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
$categoryMap = [
    '出勤' => '稼働',
    '公休' => '非稼働',
    '有給' => '非稼働',
    '午前休' => '稼働',
    '午後休' => '稼働',
    '振出' => '振替稼働',
    '振休' => '振替非稼働',
    '休出' => '稼働',
    '欠勤' => '欠員',
    '遅刻' => '遅刻',
    '早退' => '早期終業',
];
$minutesToTime = fn($min) => $min>0 ? sprintf('%02d:%02d', floor($min/60), $min%60) : '';
@endphp

@foreach($staffs as $staff)
<div class="staff-page">
<h1>{{ $year }}年{{ $month }}月 稼働報告</h1>

<div class="employee-info">
    <div>スタッフ氏名：{{ $staff->name }}</div>
</div>

<table>
    <colgroup>
        <col><col><col><col><col><col><col><col><col><col><col><col><col>
    </colgroup>
    <thead>
        <tr>
            <th>日付</th>
            <th>曜日</th>
            <th>区分</th>
            <th>始業打刻</th>
            <th>始業</th>
            <th>終業打刻</th>
            <th>終業</th>
            <th>休憩</th>
            <th>実働</th>
            <th>契約外</th>
            <th>備考</th>
            <th>差戻</th>
            <th>承認</th>
        </tr>
    </thead>
    <tbody>
        @foreach($staff->attendanceData as $data)
            @php $att = $data['attendance']; @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($data['date'])->format('n/j') }}</td>
                <td>{{ $data['weekday'] }}</td>
                <td>{{ $att ? ($categoryMap[$att->category ?? ''] ?? '') : '' }}</td>
                <td>{{ $att && $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '' }}</td>
                <td>{{ $att && $att->start_time ? \Carbon\Carbon::parse($att->start_time)->format('H:i') : '' }}</td>
                <td>{{ $att && $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '' }}</td>
                <td>{{ $att && $att->end_time ? \Carbon\Carbon::parse($att->end_time)->format('H:i') : '' }}</td>
                <td>{{ $att && $att->break_time ? \Carbon\Carbon::parse($att->break_time)->format('H:i') : '' }}</td>
                <td>{{ $att ? $minutesToTime($data['workMinutes']) : '' }}</td>
                <td>{{ $att ? $minutesToTime($data['overtimeMinutes']) : '' }}</td>
                <td>{{ $att->remarks ?? '' }}</td>
                <td>{{ $att && $att->rejection ? '✔' : '' }}</td>
                <td>{{ $att && $att->is_approved_by_clients ? '✔' : '' }}</td>
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
        <div class="summary-value">{{ sprintf('%02d:%02d', floor($staff->summary['totalMinutes']/60), $staff->summary['totalMinutes']%60) }}</div>
    </div>
    <div class="summary-box">
        <div class="summary-title">時間外合計</div>
        <div class="summary-value">{{ sprintf('%02d:%02d', floor(($staff->summary['overtimeDailyMinutes']+$staff->summary['weeklyOvertime'])/60), ($staff->summary['overtimeDailyMinutes']+$staff->summary['weeklyOvertime'])%60) }}</div>
    </div>
</div>
</div>

<div style="page-break-after: always;"></div>
@endforeach

</body>
</html>
