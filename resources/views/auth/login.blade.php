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

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        rel="stylesheet">

    <link href="{{ mix('/assets/css/core/core.min.css') }}" rel="stylesheet">
    <link href="{{ mix('/assets/css/panel/panel2.min.css') }}" rel="stylesheet">
    <link href="{{ mix('/assets/css/panel/panel.min.css') }}" rel="stylesheet">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
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
            background:#ffffff;
            display:flex;
            justify-content:center;
            align-items:center;
            padding:60px 80px;
        }

        .login-box{
            width:100%;
            max-width:460px;
        }

        .login-logo{
            margin-bottom:60px;
        }

        .login-logo img{
            width:220px;
        }

        .login-subtitle{
            color:#7a7a7a;
            font-size:15px;
            margin-bottom:10px;
        }

        .login-title{
            font-size:42px;
            line-height:1.1;
            font-weight:700;
            color:#101828;
            margin-bottom:45px;
        }

        .form-label{
            color:#667085 !important;
            font-size:14px;
            font-weight:500;
            margin-bottom:10px;
        }

        .form-control{
            height:60px;
            border-radius:10px;
            border:1px solid #d0d5dd;
            padding:0 20px;
            font-size:15px;
            box-shadow:none !important;
        }

        .form-control:focus{
            border-color:#3b82f6;
        }

        .btn-login{
            height:60px;
            width:100%;
            border:none;
            border-radius:10px;
            background:#fb5607;
            color:#fff;
            font-size:16px;
            font-weight:600;
            transition:.3s;
        }

        .btn-login:hover{
            background:#e34f05;
        }

        .form-check-label{
            color:#667085;
            font-size:14px;
        }

        /* RIGHT SIDE IMAGE */

        .login-right{
            background-image:url('/assets/img/login-bg.jpg');
            background-size:cover;
            background-position:center;
            background-repeat:no-repeat;
        }

        /* MOBILE */

        @media(max-width:991px){

            body{
                overflow:auto;
            }

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

<body>

    <div class="auth-container">

        <!-- LEFT -->

        <div class="login-left">

            <div class="login-box">

                <div class="login-logo">
                    <img src="https://caribbean-transfers.com/assets/img/logo.svg"
                        alt="Caribbean Transfers">
                </div>

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

                        <label class="form-label">
                            Email
                        </label>

                        <input
                            class="form-control"
                            type="email"
                            name="email"
                            placeholder="contacto@caribbean-transfers.com"
                            value="{{ old('email') }}"
                            autocomplete="username">

                    </div>

                    <div class="mb-4">

                        <label class="form-label">
                            Contraseña
                        </label>

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
                                checked>

                            <label class="form-check-label">
                                Remember me
                            </label>

                        </div>

                    </div>

                    <button
                        class="g-recaptcha btn-login"
                        data-sitekey="{{ config('services.gcaptcha.key')}}"
                        data-callback="onSubmit"
                        data-action='submit'>

                        Iniciar Sesión

                    </button>

                </form>

            </div>

        </div>

        <!-- RIGHT IMAGE -->

        <div class="login-right"></div>

    </div>

    <script src="{{ mix('/assets/js/core/core.min.js') }}"></script>
    <script src="{{ mix('/assets/js/panel/panel_custom.min.js') }}"></script>

    <script>

        function onSubmit(token) {
            document.getElementById("log-in-form").submit();
        }

    </script>

</body>
</html>
