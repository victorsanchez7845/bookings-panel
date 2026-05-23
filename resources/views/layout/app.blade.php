<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CRM Caribbean Transfers') }} - @yield('title')</title>
	<meta name="description" content="Caribbean Transfers | Bookings">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="/assets/img/icons/favicon.ico">

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="{{ mix('/assets/css/core/core.min.css') }}" rel="preload" as="style" >
    <link href="{{ mix('/assets/css/core/core.min.css') }}" rel="stylesheet" >
    <link href="{{ mix('/assets/css/panel/panel2.min.css') }}" rel="preload" as="style" >
    <link href="{{ mix('/assets/css/panel/panel2.min.css') }}" rel="stylesheet" >
    <link href="/assets/css/custom-dashboard.css" rel="stylesheet">
    @stack('Css')
</head>
<body class="">
    @once
        @include('layout.partials.loader')
    @endonce

    @once
        @include('layout.partials.header')
    @endonce

    <!--  BEGIN MAIN CONTAINER  -->
    <div class="main-container" id="container">

        <div class="overlay"></div>
        <div class="search-overlay"></div>

        @once
            @include('layout.partials.sidebar')
        @endonce

        <!--  BEGIN CONTENT AREA  -->
        <div id="content" class="main-content">
            <div class="layout-px-spacing">
                <div class="middle-content p-0">

                    @once
                        @include('layout.partials.breadcrumbs')
                    @endonce
                    
                    {{-- <div class="row layout-top-spacing"> --}}

                        @yield('content')

                    {{-- </div> --}}

                </div>
            </div>

            @once
                @include('layout.partials.footer')
            @endonce

        </div>
        <!--  END CONTENT AREA  -->

    </div>
    <!-- END MAIN CONTAINER -->

    <script src="{{ mix('/assets/js/core/core.min.js') }}"></script>
    <script src="{{ mix('/assets/js/panel/panel.min.js') }}"></script>

    @stack('Js')
</body>
</html>
