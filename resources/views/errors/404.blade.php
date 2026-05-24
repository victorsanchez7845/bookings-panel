
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    
    <title>@yield('title') | Bookings</title>
	<meta name="description" content="Shuttles Central | Affiliates">
    <link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="/assets/img/icons/favicon-32x32.png">

    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="{{ mix('/assets/css/panel/error.min.css') }}" rel="preload" as="style" >
    <link href="{{ mix('/assets/css/panel/error.min.css') }}" rel="stylesheet" >
    <style>
        body.dark .theme-logo.dark-element {
            display: inline-block;
        }
        .theme-logo.dark-element {
            display: none;
        }
        body.dark .theme-logo.light-element {
            display: none;
        }
        .theme-logo.light-element {
            display: inline-block;
        }
    </style>    
</head>
<body class="error text-center">

    <!-- BEGIN LOADER -->
    {{-- <div id="load_screen"> <div class="loader"> <div class="loader-content">
        <div class="spinner-grow align-self-center"></div>
    </div></div></div> --}}
    <!--  END LOADER -->
    
    {{-- <div class="container-fluid">
        <div class="row">
            <div class="col-md-4 mr-auto mt-5 text-md-left text-center">
                <a href="index.html" class="ml-md-5">
                    <img alt="image-404" src="{{ asset("/assets/img/logos/brand.svg") }}" class="dark-element theme-logo">
                    <img alt="image-404" src="{{ asset("/assets/img/logos/brand.svg") }}" class="light-element theme-logo">
                </a>
            </div>
        </div>
    </div> --}}
    <div class="container-fluid error-content">
        <div class="">
            <h1 class="error-number">404</h1>
            <p class="mini-text">Ooops!</p>
            <p class="error-text mb-5 mt-1">Página no encontrada</p>
            <img src="{{ asset("/assets/img/logos/brand.svg") }}" alt="cork-admin-404" class="error-img">
            <a href="{{ route('login') }}" class="btn btn-dark mt-5">Regresar</a>
        </div>
    </div>    
    <!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
    <script src="{{ mix('/assets/js/core/core.min.js') }}"></script>
    <!-- END GLOBAL MANDATORY SCRIPTS -->
</body>
</html>
