<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Shuttles Central | Bookings</title>
	<meta name="description" content="Shuttles Central | Bookings">
    <link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="/assets/img/icons/favicon.ico">
    @stack('Css')
</head>
<body class="layout-boxed">

    @yield('content')
   
    <script src="{{ mix('/assets/js/core/core.min.js') }}"></script>
    @stack('Js')
</body>
</html>
