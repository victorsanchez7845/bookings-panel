<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Bookings</title>
	<meta name="description" content="Caribbean Transfers | Login">
    <link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="/assets/img/icons/icon-48x48.png">
	<meta name='robots' content='noindex,follow' />

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="{{ mix('/assets/css/core/core.min.css') }}" rel="preload" as="style" >
    <link href="{{ mix('/assets/css/core/core.min.css') }}" rel="stylesheet" >
    <link href="{{ mix('/assets/css/panel/panel2.min.css') }}" rel="preload" as="style" >
    <link href="{{ mix('/assets/css/panel/panel2.min.css') }}" rel="stylesheet" >
    <link href="{{ mix('/assets/css/panel/panel.min.css') }}"rel="preload" as="style" >
    <link href="{{ mix('/assets/css/panel/panel.min.css') }}"rel="stylesheet" >	
        	<style>
        
        body{
            margin:0;
            font-family:'Poppins',sans-serif;
            background:#fff;
            overflow:hidden;
        }
        
        .auth-container{
            min-height:100vh;
            display:grid;
            grid-template-columns:1fr 1fr;
        }
        
        /* LEFT SIDE */
        
        .login-left{
            display:flex;
            flex-direction:column;
            justify-content:center;
            padding:60px 90px;
            background:#ffffff;
        }
        
        .login-logo{
            margin-bottom:60px;
        }
        
        .login-logo img{
            width:220px;
        }
        
        .login-box{
            max-width:460px;
            width:100%;
        }
        
        .login-subtitle{
            color:#777;
            font-size:15px;
            margin-bottom:10px;
        }
        
        .login-title{
            font-size:42px;
            font-weight:700;
            color:#16161d;
            margin-bottom:40px;
        }
        
        .form-label{
            color:#4f7c91 !important;
            font-size:14px;
            margin-bottom:10px;
        }
        
        .form-control{
            height:60px;
            border:1px solid #d6dce2;
            border-radius:6px;
            padding:0 18px;
            font-size:15px;
            box-shadow:none !important;
        }
        
        .form-control:focus{
            border-color:#4a90e2;
        }
        
        .btn{
            height:60px;
            border:none !important;
            border-radius:6px !important;
            background:#fb5607 !important;
            font-size:16px !important;
            font-weight:600;
            transition:.3s;
        }
        
        .btn:hover{
            background:#e24d05 !important;
        }
        
        .form-check-label{
            color:#666 !important;
        }
        
        /* RIGHT SIDE */
        
        .login-right{
            background-image:url('/assets/img/login-bg.jpg');
            background-size:cover;
            background-position:center;
            background-repeat:no-repeat;
        }
        
        /* MOBILE */
        
        @media(max-width:991px){
        
            .auth-container{
                grid-template-columns:1fr;
            }
        
            .login-right{
                display:none;
            }
        
            .login-left{
                padding:40px 25px;
            }
        
            .login-title{
                font-size:34px;
            }
        }
        
        </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">

    <div class="auth-container">

    <!-- LEFT -->
    <div class="login-left">

        <div class="login-logo">
            <img src="https://caribbean-transfers.com/assets/img/logo.svg"
                 alt="Caribbean Transfers">
        </div>

        <div class="login-box">

            <div class="login-subtitle">
                Welcome back
            </div>

            <div class="login-title">
                Iniciar sesión
            </div>

            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="log-in-form" method="POST" action="/login">
                @csrf

                <div class="mb-4">
                    <label class="form-label">Email</label>

                    <input
                        class="form-control"
                        type="email"
                        name="email"
                        placeholder="contacto@caribbean-transfers.com"
                        value="{{ old('email') }}"
                        autocomplete="username">
                </div>

                <div class="mb-4">
                    <label class="form-label">Contraseña</label>

                    <input
                        type="password"
                        class="form-control"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password">
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="remember-me"
                            checked>

                        <label class="form-check-label">
                            Remember me
                        </label>
                    </div>
                </div>

                <button
                    class="g-recaptcha btn btn-secondary w-100"
                    data-sitekey="{{ config('services.gcaptcha.key')}}"
                    data-callback="onSubmit"
                    data-action='submit'>

                    Iniciar Sesión

                </button>

            </form>

        </div>

    </div>

    <!-- RIGHT -->
    <div class="login-right"></div>

</div>
    <script src="{{ mix('/assets/js/core/core.min.js') }}"></script>
    <script src="{{ mix('/assets/js/panel/panel_custom.min.js') }}"></script>	
	{{-- <script src="https://www.google.com/recaptcha/api.js" async defer></script> --}}
    <script>
        function onSubmit(token) {
            document.getElementById("log-in-form").submit();
        }
    </script>
</body>
</html>
