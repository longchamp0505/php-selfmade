@extends('layouts.app')

@section('title', 'クライアント詳細・編集')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_client_edit.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">クライアント詳細・編集</div>

            <div class="header-right">
                <button form="clientUpdateForm" class="update-btn">変更</button>
                <a href="{{ route('admin.client.list') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <div class="edit-body">

        <!-- ▼ クライアント基本情報フォーム -->
        <form id="clientUpdateForm"
              action="{{ route('admin.client.update', ['client_id' => $client->id]) }}"
              method="POST">
            @csrf
            @method('PUT')

            <div class="info-flex">

                <!-- ▼ 基本情報 -->
                <div class="info-block">
                    <h3>■ 基本情報</h3>

                    <div class="info-grid">

                        <div class="info-row">
                            <label>ID</label>
                            <div class="value-with-input">{{ $client->id }}</div>
                        </div>

                        <div class="info-row">
                            <label>会社名</label>
                            <div class="value-with-input">
                                <input type="text" name="company_name"
                                       value="{{ old('company_name', $client->company_name) }}">
                            </div>
                        </div>

                        <div class="info-row">
                            <label>部署名</label>
                            <div class="value-with-input">
                                <input type="text" name="department_name"
                                       value="{{ old('department_name', $client->department_name) }}">
                            </div>
                        </div>

                        <div class="info-row">
                            <label>連絡先</label>
                            <div class="value-with-input">
                                <input type="text" name="contact"
                                       value="{{ old('contact', $client->contact) }}">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ▼ 担当者情報 -->
                <div class="paidleave-block">
                    <h3>■ 担当者情報</h3>

                    <div class="info-grid">

                        <div class="info-row">
                            <label>担当者➊</label>
                            <div class="value-with-unit">
                                <input type="text" name="approver1_name"
                                       value="{{ old('approver1_name', $client->approver1_name) }}">
                            </div>
                        </div>

                        <div class="info-row">
                            <label>メールアドレス➊</label>
                            <div class="value-with-unit">
                                <input type="email" name="approver1_email"
                                       value="{{ old('approver1_email', $client->approver1_email) }}">
                            </div>
                        </div>

                        <div class="info-row">
                            <label>担当者➋</label>
                            <div class="value-with-unit">
                                <input type="text" name="approver2_name"
                                       value="{{ old('approver2_name', $client->approver2_name) }}">
                            </div>
                        </div>

                        <div class="info-row">
                            <label>メールアドレス➋</label>
                            <div class="value-with-unit">
                                <input type="email" name="approver2_email"
                                       value="{{ old('approver2_email', $client->approver2_email) }}">
                            </div>
                        </div>

                        <div class="info-row">
                            <label>担当者➌</label>
                            <div class="value-with-unit">
                                <input type="text" name="approver3_name"
                                       value="{{ old('approver3_name', $client->approver3_name) }}">
                            </div>
                        </div>

                        <div class="info-row">
                            <label>メールアドレス➌</label>
                            <div class="value-with-unit">
                                <input type="email" name="approver3_email"
                                       value="{{ old('approver3_email', $client->approver3_email) }}">
                            </div>
                        </div>

                    </div>
                </div>

            </div><!-- /info-flex -->

        </form>

        <!-- ▼ パスワード初期化 -->
        <form action="{{ route('admin.client.password.reset', ['client_id' => $client->id]) }}"
              method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn-reset pink-btn">パスワード初期化</button>
        </form>

        <!-- ▼ 稼働スタッフ -->
        <h3>■ 稼働スタッフ情報</h3>

        <div class="month-picker">
            <form id="month-form" action="{{ route('admin.client.edit', ['client_id'=>$client->id]) }}" method="GET">
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

        <div class="attendance-table-wrapper">
            <table>
                <colgroup>
                    <col style="width:15%">
                    <col style="width:12%">
                    <col style="width:12%">
                    <col style="width:12%">
                    <col style="width:12%">
                    <col style="width:12%">
                    <col style="width:10%">
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
                    </tr>
                </thead>

                <tbody>
                    @forelse($staffData as $staff)
                    <tr>
                        <td>{{ $staff['name'] }}</td>

                        <td>{{ $staff['contract_type'] ?? 'ー' }}</td>

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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;">データがありません</td>
                    </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>
</div>
@endsection
