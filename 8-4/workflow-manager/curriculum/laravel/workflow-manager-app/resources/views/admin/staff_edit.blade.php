@extends('layouts.app')

@section('title', 'スタッフ詳細・編集')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_staff_edit.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">スタッフ詳細・編集</div>

            <div class="header-right">
                <button form="userUpdateForm" class="update-btn">変更</button>
                <a href="{{ route('admin.user.list') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <div class="edit-body">

        <!-- ▼ フォーム開始 -->
        <form id="userUpdateForm" action="{{ route('admin.user.update', ['user_id'=>$user->id]) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- ▼ 横並びのラッパー（基本情報 + 有給情報） -->
        <div class="info-flex">

            <!-- ▼ 基本情報 -->
            <div class="info-block">
                <h3>■ 基本情報</h3>

                <div class="info-grid">

                    <div class="info-row">
                        <label>ID</label>
                        <div class="value-with-input">{{ $user->id }}</div>
                    </div>

                    <div class="info-row">
                        <label>氏名</label>
                        <div class="value-with-input">
                            <input type="text" name="name" value="{{ old('name', $user->name) }}">
                        </div>
                    </div>

                    <div class="info-row">
                        <label>フリガナ</label>
                        <div class="value-with-input">
                            <input type="text" name="name_kana" value="{{ old('name_kana', $user->name_kana) }}">
                        </div>
                    </div>

                    <div class="info-row">
                        <label>入社日</label>
                        <div class="value-with-input">
                            <input type="date" name="hire_date" value="{{ old('hire_date', $user->hire_date) }}">
                        </div>
                    </div>

                    <div class="info-row">
                        <label>所属</label>
                        <div class="value-with-input">
                            <input type="text" name="department" value="{{ old('department', $user->department) }}">
                        </div>
                    </div>

                    <div class="info-row">
                        <label>所定労働日数</label>
                        <div class="value-with-input">
                            <select name="scheduled_days">
                                @foreach([5,4,3,2,1] as $day)
                                    <option value="{{ $day }}" @if($user->scheduled_days==$day) selected @endif>
                                        週{{ $day }}日
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>雇用形態</label>
                        <div class="value-with-input">
                            <select name="employment_type">
                                @foreach($employmentTypes as $type)
                                    <option value="{{ $type }}" @if($user->employment_type==$type) selected @endif>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>勤務先</label>
                        <div class="value-with-input">
                            <input type="text" name="workplace" value="{{ old('workplace', $user->workplace) }}">
                        </div>
                    </div>

                    <div class="info-row">
                        <label>契約形態</label>
                        <div class="value-with-input">
                            <select name="contract_type">
                                @foreach($contractTypes as $type)
                                    <option value="{{ $type }}" @if($user->contract_type==$type) selected @endif>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>勤怠承認者①</label>
                        <div class="value-with-input">
                            <select name="client_id">
                                <option value="">未設定</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" @if($user->client_id==$client->id) selected @endif>
                                        {{ $client->company_name }}（{{ $client->id }}）
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>退職日</label>
                        <div class="value-with-input">
                            <input type="date" name="retire_date" value="{{ old('retire_date', $user->retire_date) }}">
                        </div>
                    </div>

                </div>
            </div>
            <!-- ▼ 有給情報 -->
            <div class="paidleave-block">
                <h3>■ 有給情報</h3>

                @php $leave = $user->paidLeave; @endphp

                <div class="info-grid">

                    <div class="info-row">
                        <label>有給残日数（➋+➌-➊）</label>
                        <div class="value-with-unit">
                            <input type="number" name="remaining_days"
                                value="{{ old('remaining_days', $leave->remaining_days ?? 0) }}">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>有給付与日</label>
                        <div class="value-with-unit">
                            <input type="date" name="granted_date"
                                value="{{ old('granted_date', $leave->granted_date ?? '') }}">
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➊ 当年度取得日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="current_year_taken"
                                value="{{ old('current_year_taken', $leave->current_year_taken ?? 0) }}">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➋ 前年度取得日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="last_year_taken"
                                value="{{ old('last_year_taken', $leave->last_year_taken ?? 0) }}">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➌ 前年度繰越日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="last_year_carried"
                                value="{{ old('last_year_carried', $leave->last_year_carried ?? 0) }}">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➍ 一昨年度取得日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="two_years_ago_taken"
                                value="{{ old('two_years_ago_taken', $leave->two_years_ago_taken ?? 0) }}">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➎ 一昨年度付与日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="two_years_ago_granted"
                                value="{{ old('two_years_ago_granted', $leave->two_years_ago_granted ?? 0) }}">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➏ 一昨年度繰越日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="two_years_ago_carried"
                                value="{{ old('two_years_ago_carried', $leave->two_years_ago_carried ?? 0) }}">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>次回失効予定日</label>
                        <div class="value-with-unit">
                            <input type="date" name="next_expiration_date"
                                value="{{ old('next_expiration_date', $leave->next_expiration_date ?? '') }}">
                        </div>
                    </div>

                    <div class="info-row">
                        <label>失効予定日数（➌-➊）</label>
                        <div class="value-with-unit">
                            <input type="number" name="expiration_days"
                                value="{{ old('expiration_days', $leave->expiration_days ?? 0) }}">
                            <span>日</span>
                        </div>
                    </div>

                </div>
            </div>


            
            </div>

        </div><!-- /info-flex -->

        </form>
    
        <div style="align-self: flex-start;">
            <form action="{{ route('admin.user.password.reset', ['user_id'=>$user->id]) }}" method="POST"  style="display:block; margin:0; padding:0;">
                @csrf
                <button type="submit" class="btn-reset pink-btn">パスワード初期化</button>
            </form>
        </div>

    </div>


    
</div>
@endsection
