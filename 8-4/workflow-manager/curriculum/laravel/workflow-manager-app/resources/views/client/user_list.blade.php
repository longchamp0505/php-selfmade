@extends('layouts.app')

@section('title', '対象スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff-list.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">対象スタッフ一覧</div>
            <div class="header-right">
                <a href="{{ route('client.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <!-- 年月セレクト -->
    <div class="month-picker">
        <form id="month-form" action="{{ route('client.staff.list') }}" method="GET">
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

    <!-- スタッフ一覧テーブル -->
    <div class="attendance-table-wrapper">
        <form id="pdf-form" action="{{ route('client.staff.export.pdf') }}" method="POST">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <table>
                <colgroup>
                    <col style="width:15%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:5%">
                </colgroup>
                <thead>
                    <tr>
                        <th>スタッフ氏名</th>
                        <th>契約形態</th>
                        <th>稼働時間</th>
                        <th>契約内時間</th>
                        <th>契約外時間</th>
                        <th>稼働日数</th>
                        <th>ステータス</th>
                        <th>PDF選択</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffData as $staff)
                    <tr>
                        <td>
                            <a href="{{ route('client.staff.approval', ['id' => $staff['id']]) }}">
                                {{ $staff['name'] }}
                            </a>
                        </td>

                        <td>{{ $staff['contract_type'] ?? 'N/A' }}</td>

                        <td>{{ floor($staff['workMinutes']/60) }}:{{ sprintf('%02d', $staff['workMinutes']%60) }}</td>
                        <td>{{ floor($staff['contractMinutes']/60) }}:{{ sprintf('%02d', $staff['contractMinutes']%60) }}</td>
                        <td>{{ floor($staff['overtimeMinutes']/60) }}:{{ sprintf('%02d', $staff['overtimeMinutes']%60) }}</td>

                        <td>{{ $staff['workDays'] }}日</td>

                        <td>
                            @if($staff['status'] === '完了')
                                <span class="status-done">完了</span>
                            @else
                                <span class="status-pending">未完了</span>
                            @endif
                        </td>

                        <td><input type="checkbox" name="staff_select[]" value="{{ $staff['id'] }}"></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center;">データがありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    </div>

    <!-- PDFボタン -->
    <div class="pdf-button-wrapper">
        <button type="submit" form="pdf-form" class="pdf-btn" id="pdf-submit-btn">
            PDF出力
        </button>
    </div>



</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pdfButton = document.getElementById('pdf-submit-btn');
    const form = document.getElementById('pdf-form');

    pdfButton.addEventListener('click', function (e) {
        const checked = form.querySelectorAll('input[name="staff_select[]"]:checked');

        if (checked.length === 0) {
            e.preventDefault(); // ← 送信を止める
            alert('PDF出力するスタッフを選択してください。');
        }
    });
});
</script>
@endsection

