@extends('layouts.app')

@section('title', 'クライアント登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_client_edit.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">クライアント新規登録</div>

            <div class="header-right">
                <button form="clientCreateForm" class="update-btn">登録</button>
                <a href="{{ route('admin.client.list') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <div class="edit-body">

        <!-- ▼ クライアント新規作成フォーム -->
        <form id="clientCreateForm"
              action="{{ route('admin.client.store') }}"
              method="POST">
            @csrf

            <div class="info-flex">

                <!-- ▼ 基本情報 -->
                <div class="info-block">
                    <h3>■ 基本情報</h3>

                    <div class="info-grid">

                        <div class="info-row">
                            <label>ID</label>
                            <div class="value-with-input">{{ $nextId }}</div>
                        </div>

                        <div class="info-row">
                            <label>会社名</label>
                            <div class="value-with-input">
                                <input type="text" name="company_name" value="{{ old('company_name') }}">
                                @error('company_name')
                                    <p class="error-msg">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="info-row">
                            <label>部署名</label>
                            <div class="value-with-input">
                                <input type="text" name="department_name" value="{{ old('department_name') }}">
                                @error('department_name')
                                    <p class="error-msg">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="info-row">
                            <label>連絡先</label>
                            <div class="value-with-input">
                                <input type="text" name="contact" value="{{ old('contact') }}">
                                @error('contact')
                                    <p class="error-msg">{{ $message }}</p>
                                @enderror
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
                                <input type="text" name="approver1_name" value="{{ old('approver1_name') }}">
                                @error('approver1_name')
                                    <p class="error-msg">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="info-row">
                            <label>メールアドレス➊</label>
                            <div class="value-with-unit">
                                <input type="email" name="approver1_email" value="{{ old('approver1_email') }}">
                                @error('approver1_email')
                                    <p class="error-msg">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="info-row">
                            <label>担当者➋</label>
                            <div class="value-with-unit">
                                <input type="text" name="approver2_name" value="{{ old('approver2_name') }}">
                                @error('approver2_name')
                                    <p class="error-msg">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="info-row">
                            <label>メールアドレス➋</label>
                            <div class="value-with-unit">
                                <input type="email" name="approver2_email" value="{{ old('approver2_email') }}">
                                @error('approver2_email')
                                    <p class="error-msg">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="info-row">
                            <label>担当者➌</label>
                            <div class="value-with-unit">
                                <input type="text" name="approver3_name" value="{{ old('approver3_name') }}">
                                @error('approver3_name')
                                    <p class="error-msg">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="info-row">
                            <label>メールアドレス➌</label>
                            <div class="value-with-unit">
                                <input type="email" name="approver3_email" value="{{ old('approver3_email') }}">
                                @error('approver3_email')
                                    <p class="error-msg">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

            </div><!-- /info-flex -->

        </form>

    </div>
</div>
@endsection
