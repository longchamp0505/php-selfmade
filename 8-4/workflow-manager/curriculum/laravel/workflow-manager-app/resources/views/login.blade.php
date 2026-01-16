@extends('layouts.app')

@section('title', 'ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login-wrapper">
    <div class="login-box">
        <h2>Login</h2>

        <!-- エラーメッセージ  -->
        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <p class="error">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit', ['type' => $type]) }}">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <div class="form-group">
                <input type="text"
                    name="id"
                    placeholder="ログインID"
                    required
                    value="{{ old('id') }}"
                    autocomplete="username">
            </div>

            <div class="form-group">
                <input type="password"
                    name="password"
                    placeholder="パスワード"
                    required
                    autocomplete="current-password">
            </div>

            <button type="submit" class="login-btn">ログイン</button>
        </form>

        <a href="{{ route('password.change', ['type' => $type]) }}" class="password-change">
            パスワード変更
        </a>

    </div>
</div>
@endsection
