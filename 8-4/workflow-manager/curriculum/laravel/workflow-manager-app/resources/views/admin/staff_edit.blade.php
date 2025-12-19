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
                            @error('name')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>フリガナ</label>
                        <div class="value-with-input">
                            <input type="text" name="name_kana" value="{{ old('name_kana', $user->name_kana) }}">
                            @error('name_kana')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>入社日</label>
                        <div class="value-with-input">
                            <input type="date" name="hire_date" value="{{ old('hire_date', $user->hire_date) }}">
                            @error('hire_date')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>所属</label>
                        <div class="value-with-input">
                            <input type="text" name="department" value="{{ old('department', $user->department) }}">
                            @error('department')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>所定労働日数</label>
                        <div class="value-with-input">
                            <select name="scheduled_days">
                                @foreach([5,4,3,2,1] as $day)
                                    <option value="{{ $day }}" @selected(old('scheduled_days', $user->scheduled_days)==$day)>
                                        週{{ $day }}日
                                    </option>
                                @endforeach
                            </select>
                            @error('scheduled_days')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>雇用形態</label>
                        <div class="value-with-input">
                            <select name="employment_type">
                                @foreach($employmentTypes as $type)
                                    <option value="{{ $type }}" @selected(old('employment_type', $user->employment_type)==$type)>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employment_type')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>勤務先</label>
                        <div class="value-with-input">
                            <input type="text" name="workplace" value="{{ old('workplace', $user->workplace) }}">
                            @error('workplace')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>契約形態</label>
                        <div class="value-with-input">
                            <select name="contract_type">
                                @foreach($contractTypes as $type)
                                    <option value="{{ $type }}" @selected(old('contract_type', $user->contract_type)==$type)>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contract_type')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>勤怠承認者①</label>
                        <div class="value-with-input">
                            <select name="client_id">
                                <option value="">未設定</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" @selected(old('client_id', $user->client_id)==$client->id)>
                                        {{ $client->company_name }}（{{ $client->id }}）
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>退職日</label>
                        <div class="value-with-input">
                            <input type="date" name="retire_date" value="{{ old('retire_date', $user->retire_date) }}">
                            @error('retire_date')<span class="error-message">{{ $message }}</span>@enderror
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
                                value="{{ old('remaining_days', $leave->remaining_days ?? 0) }}"
                                readonly id="remaining_days">
                            <span>日</span>
                            @error('remaining_days')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>次回有給付与日</label>
                        <div class="value-with-unit">
                            <input type="date" name="granted_date"
                                value="{{ old('granted_date', $leave->granted_date ?? '') }}" id="granted_date">
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➊ 当年度取得日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="current_year_taken"
                                value="{{ old('current_year_taken', $leave->current_year_taken ?? 0) }}"
                                min="0" step="1" id="current_year_taken">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➋ 前年度付与日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="last_year_granted"
                                value="{{ old('last_year_granted', $leave->last_year_granted ?? 0) }}"
                                min="0" step="1" id="last_year_granted">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➌ 前年度繰越日数（➎+➏-➍）</label>
                        <div class="value-with-unit">
                            <input type="number" name="last_year_carried"
                                value="{{ old('last_year_carried', $leave->last_year_carried ?? 0) }}"
                                readonly id="last_year_carried">
                            <span>日</span>
                            @error('last_year_carried')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➍ 前年度取得日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="last_year_taken"
                                value="{{ old('last_year_taken', $leave->last_year_taken ?? 0) }}"
                                min="0" step="1" id="last_year_taken">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➎ 一昨年度付与日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="two_years_ago_granted"
                                value="{{ old('two_years_ago_granted', $leave->two_years_ago_granted ?? 0) }}"
                                min="0" step="1" id="two_years_ago_granted">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➏ 一昨年度繰越日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="two_years_ago_carried"
                                value="{{ old('two_years_ago_carried', $leave->two_years_ago_carried ?? 0) }}"
                                min="0" step="1" id="two_years_ago_carried">
                            <span>日</span>
                        </div>
                    </div>

                    <div class="info-row">
                        <label>失効予定日数（➌-➊）</label>
                        <div class="value-with-unit">
                            <input type="number" name="expiration_days"
                                value="{{ old('expiration_days', $leave->expiration_days ?? 0) }}"
                                readonly id="expiration_days">
                            <span>日</span>
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /info-flex -->

        </form>

        <!-- ▼ パスワード初期化 -->
        <form action="{{ route('admin.user.password.reset', ['user_id' => $user->id]) }}"
            method="POST"
            onsubmit="return confirm('本当にパスワードを初期化しますか？');">
            @csrf
            <button type="submit" class="btn-reset pink-btn">
                パスワード初期化
            </button>
        </form>


    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currentYear = document.getElementsByName('current_year_taken')[0];
    const lastYearTaken = document.getElementsByName('last_year_taken')[0];       // ➍ 前年度取得日数
    const lastYearGranted = document.getElementsByName('last_year_granted')[0];   // ➋ 前年度付与日数
    const twoYearsAgoGranted = document.getElementsByName('two_years_ago_granted')[0];
    const twoYearsAgoCarried = document.getElementsByName('two_years_ago_carried')[0];

    const remainingDays = document.getElementsByName('remaining_days')[0];
    const lastYearCarried = document.getElementsByName('last_year_carried')[0];
    const expirationDays = document.getElementsByName('expiration_days')[0];

    function calcPaidLeave() {
        const valCurrent = parseInt(currentYear.value) || 0;
        const valLastTaken = parseInt(lastYearTaken.value) || 0;
        const valLastGranted = parseInt(lastYearGranted.value) || 0;
        const valTwoYearsAgoG = parseInt(twoYearsAgoGranted.value) || 0;
        const valTwoYearsAgoC = parseInt(twoYearsAgoCarried.value) || 0;

        // 前年度繰越日数（➎+➏-➍）
        const lastCarriedCalc = valTwoYearsAgoG + valTwoYearsAgoC - valLastTaken;
        lastYearCarried.value = lastCarriedCalc;

        // 有給残日数（➋+➌-➊）
        remainingDays.value = valLastGranted + lastCarriedCalc - valCurrent;

        // 失効予定日数（➌-➊）
        expirationDays.value = Math.max(0, lastCarriedCalc - valCurrent);
    }

    [currentYear, lastYearTaken, lastYearGranted, twoYearsAgoGranted, twoYearsAgoCarried].forEach(el => {
        el.addEventListener('input', calcPaidLeave);
    });

    calcPaidLeave();

    @if(session('success'))
        alert(@json(session('success')));
    @endif
});
</script>
@endsection