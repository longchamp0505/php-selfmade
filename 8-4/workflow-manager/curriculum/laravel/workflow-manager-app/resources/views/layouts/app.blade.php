<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'アプリ')</title>

    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- 共通CSS -->
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">

    <!-- 画面ごとのCSS -->
    @yield('css')
</head>
<body style="font-family: 'Inter', sans-serif;">
    @yield('content')

    @yield('js')
</body>
</html>
