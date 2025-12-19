@extends('layouts.app')

@section('title', 'お知らせ管理')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_notice.css') }}">
@endsection

@section('content')

<div class="edit-wrapper">

    <!-- ▼ ヘッダー -->
    <header class="home-header">
        <div class="header-inner">
            <div class="header-left">お知らせ管理</div>
            <div class="header-right">
                <a href="{{ route('admin.home') }}" class="back-btn">戻る</a>
            </div>
        </div>
    </header>

    <form action="{{ route('admin.notice.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- ▼ スタッフ向け -->
    <div class="info-block">
        <h3>■ スタッフ画面</h3>

        <div class="notice-board">
            <div class="notice-header">お知らせ</div>
            <div class="notice-content">
                <textarea name="content_staff" rows="15" style="width:100%;">{{ old('content_staff', $notice->content_staff ?? '') }}</textarea>
                @error('content_staff')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>


    <!-- ▼ クライアント向け -->
    <div class="info-block">
        <h3>■ クライアント画面</h3>

        <div class="notice-board">
            <div class="notice-header">お知らせ</div>
            <div class="notice-content">
                <textarea name="content_client" rows="15" style="width:100%;">{{ old('content_client', $notice->content_client ?? '') }}</textarea>
                @error('content_client')
                    <div class="error-msg">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>


    <div class="pdf-button-wrapper" style="margin-top:10px;">
        <button type="submit" class="pdf-btn">登録・更新</button>
    </div>

    </form>
</div>
@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            alert("{{ session('success') }}");
        });
    </script>
@endif


@endsection
