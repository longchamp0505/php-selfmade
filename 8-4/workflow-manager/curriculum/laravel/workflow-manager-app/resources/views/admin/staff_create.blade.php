@extends('layouts.app')

@section('title', 'スタッフ登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_staff_edit.css') }}">
@endsection

@section('content')
<div class="edit-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">アカウント発行（スタッフ）</div>

            <div class="header-right">
                <button form="userCreateForm" class="update-btn">登録</button>
                <a href="{{ route('admin.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <div class="edit-body">

        <!-- ▼ フォーム開始（新規用） -->
        <form id="userCreateForm" action="{{ route('admin.user.store') }}" method="POST">
        @csrf

        <div class="info-flex">

            <!-- ▼ 基本情報 -->
            <div class="info-block">
                <h3>■ 基本情報</h3>
                <div class="info-grid">

                    <div class="info-row">
                        <label>ID（自動採番）</label>
                        <div class="value-with-input" id="userIdDisplay">{{ $nextId }}</div>
                    </div>

                    <div class="info-row">
                        <label>氏名</label>
                        <div class="value-with-input">
                            <input type="text" name="name" value="{{ old('name') }}">
                            @error('name')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>フリガナ</label>
                        <div class="value-with-input">
                            <input type="text" name="name_kana" value="{{ old('name_kana') }}">
                            @error('name_kana')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <!-- 入社日 -->
                    <div class="info-row">
                        <label>入社日</label>
                        <div class="value-with-input">
                            <input type="date" name="hire_date" id="hire_date" value="{{ old('hire_date') }}">
                            @error('hire_date')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>所属</label>
                        <div class="value-with-input">
                            <input type="text" name="department" value="{{ old('department') }}">
                            @error('department')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>所定労働日数</label>
                        <div class="value-with-input">
                            <select name="scheduled_days">
                                @foreach([5,4,3,2,1] as $day)
                                    <option value="{{ $day }}" @selected(old('scheduled_days')==$day)>
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
                                    <option value="{{ $type }}" @selected(old('employment_type')==$type)>
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
                            <input type="text" name="workplace" value="{{ old('workplace') }}">
                            @error('workplace')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>契約形態</label>
                        <div class="value-with-input">
                            <select name="contract_type">
                                @foreach($contractTypes as $type)
                                    <option value="{{ $type }}" @selected(old('contract_type')==$type)>
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
                                    <option value="{{ $client->id }}" @selected(old('client_id')==$client->id)>
                                        {{ $client->company_name }}（{{ $client->id }}）
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                </div>
            </div>

            <!-- ▼ 有給情報 -->
            <div class="paidleave-block">
                <h3>■ 有給情報</h3>
                <div class="info-grid">

                    <div class="info-row">
                        <label>有給残日数（➋+➌-➊）</label>
                        <div class="value-with-unit">
                            <input type="number" name="remaining_days" value="{{ old('remaining_days', 0) }}">
                            <span>日</span>
                            @error('remaining_days')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>有給付与日</label>
                        <div class="value-with-unit">
                            <input type="date" name="granted_date" id="granted_date" value="{{ old('granted_date') }}">
                            @error('granted_date')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➊ 当年度取得日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="current_year_taken" value="{{ old('current_year_taken', 0) }}">
                            <span>日</span>
                            @error('current_year_taken')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➋ 前年度取得日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="last_year_taken" value="{{ old('last_year_taken', 0) }}">
                            <span>日</span>
                            @error('last_year_taken')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➌ 前年度繰越日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="last_year_carried" value="{{ old('last_year_carried', 0) }}">
                            <span>日</span>
                            @error('last_year_carried')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➍ 一昨年度取得日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="before_last_taken" value="{{ old('before_last_taken', 0) }}">
                            <span>日</span>
                            @error('before_last_taken')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➎ 一昨年度付与日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="two_years_ago_granted" value="{{ old('two_years_ago_granted', 0) }}">
                            <span>日</span>
                            @error('two_years_ago_granted')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>➏ 一昨年度繰越日数</label>
                        <div class="value-with-unit">
                            <input type="number" name="two_years_ago_carried" value="{{ old('two_years_ago_carried', 0) }}">
                            <span>日</span>
                            @error('two_years_ago_carried')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>次回失効予定日</label>
                        <div class="value-with-unit">
                            <input type="date" name="next_expiration_date" value="{{ old('next_expiration_date') }}">
                            @error('next_expiration_date')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="info-row">
                        <label>失効予定日数（➌-➊）</label>
                        <div class="value-with-unit">
                            <input type="number" name="expiration_days" value="{{ old('expiration_days', 0) }}">
                            <span>日</span>
                            @error('expiration_days')<span class="error-message">{{ $message }}</span>@enderror
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /info-flex -->

        </form>

    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const hireDateInput = document.getElementById('hire_date');
    const grantDateInput = document.getElementById('granted_date');

    function updateGrantDate() {
        if (hireDateInput.value) {
            const hireDate = new Date(hireDateInput.value);
            hireDate.setMonth(hireDate.getMonth() + 6);
            const yyyy = hireDate.getFullYear();
            const mm = String(hireDate.getMonth() + 1).padStart(2, '0');
            const dd = String(hireDate.getDate()).padStart(2, '0');
            grantDateInput.value = `${yyyy}-${mm}-${dd}`;
        } else {
            grantDateInput.value = '';
        }
    }

    hireDateInput.addEventListener('change', updateGrantDate);
    updateGrantDate();
});
</script>
@endsection
