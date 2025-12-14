@extends('layouts.app')

@section('title', 'パスワード変更')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login-wrapper">
    <div class="login-box">
        <h2>パスワード変更</h2>

        @if($errors->any())
            <div style="color: red; margin-bottom: 10px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('password.update', ['type' => $type]) }}" method="POST">
            @csrf

            <input type="hidden" name="type" value="{{ $type }}">

            <div class="form-group">
                <input type="text" name="id" placeholder="ID" required>
            </div>

            <div class="form-group">
                <input type="password" name="old_password" placeholder="古いパスワード" required>
            </div>

            <div class="form-group">
                <input type="password" name="password" placeholder="新しいパスワード" required>
            </div>

            <div class="form-group">
                <input type="password" name="password_confirmation" placeholder="パスワードの確認入力" required>
            </div>

            <button type="submit" class="login-button">パスワード変更</button>

            <a href="{{ route('login', ['type' => $type])  }}" class="password-change" style="margin-top: 15px;">
                ログイン画面へ戻る
            </a>
        </form>
    </div>
</div>
@endsection
