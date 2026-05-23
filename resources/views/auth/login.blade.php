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
            overflow:hidden;
            background:#fff;
        }

        .auth-container{
            min-height:100vh;
            display:grid;
            grid-template-columns:1fr 1fr;
        }

        /* LEFT */

        .login-left{
            background:#ffffff;
            display:flex;
            justify-content:center;
            align-items:center;
            padding:60px 80px;
            position:relative;
            z-index:5;
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

        /* RIGHT SIDE */

        .login-right{
            position:relative;
            overflow:hidden;
            background:
                radial-gradient(circle at 50% 50%, #111827 0%, #050816 55%, #02030a 100%);
            isolation:isolate;
        }

        .login-right::before{
            content:"";
            position:absolute;
            width:120%;
            height:120%;
            inset:-10%;
            background:
                conic-gradient(
                    from 180deg,
                    #fb5607,
                    #ff006e,
                    #8338ec,
                    #3a86ff,
                    #00f5d4,
                    #fb5607
                );

            filter:blur(80px);
            opacity:.75;
            animation:cosmicSpin 18s linear infinite;
        }

        .login-right::after{
            content:"";
            position:absolute;
            inset:0;
            background:
                radial-gradient(circle at 30% 25%, rgba(255,255,255,.35), transparent 7%),
                radial-gradient(circle at 70% 65%, rgba(255,255,255,.22), transparent 9%),
                radial-gradient(circle at 50% 50%, transparent 0%, rgba(2,3,10,.55) 75%);
            backdrop-filter:blur(40px) saturate(140%);
            animation:liquidPulse 9s ease-in-out infinite alternate;
        }

        .orb{
            position:absolute;
            border-radius:50%;
            mix-blend-mode:screen;
            filter:blur(30px);
            z-index:2;
        }

        .orb.one{
            width:500px;
            height:500px;
            background:radial-gradient(circle, rgba(255,255,255,.25), transparent 65%);
            top:5%;
            left:10%;
            animation:floatOne 16s ease-in-out infinite;
        }

        .orb.two{
            width:380px;
            height:380px;
            background:radial-gradient(circle, rgba(0,245,212,.25), transparent 65%);
            bottom:10%;
            right:5%;
            animation:floatTwo 18s ease-in-out infinite;
        }

        .orb.three{
            width:300px;
            height:300px;
            background:radial-gradient(circle, rgba(255,0,110,.25), transparent 65%);
            top:45%;
            left:40%;
            animation:floatThree 20s ease-in-out infinite;
        }

        .glass-lines{
            position:absolute;
            inset:0;
            background-image:
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size:70px 70px;
            z-index:1;
        }

        @keyframes cosmicSpin{
            from{
                transform:rotate(0deg) scale(1.15);
            }
            to{
                transform:rotate(360deg) scale(1.15);
            }
        }

        @keyframes liquidPulse{
            0%{
                transform:scale(1);
            }
            100%{
                transform:scale(1.08);
            }
        }

        @keyframes floatOne{
            0%,100%{
                transform:translate(0,0) scale(1);
            }
            50%{
                transform:translate(60px,-40px) scale(1.15);
            }
        }

        @keyframes floatTwo{
            0%,100%{
                transform:translate(0,0) scale(1);
            }
            50%{
                transform:translate(-50px,50px) scale(1.12);
            }
        }

        @keyframes floatThree{
            0%,100%{
                transform:translate(0,0) scale(1);
            }
            50%{
                transform:translate(40px,-60px) scale(1.2);
            }
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

        <!-- RIGHT -->

        <div class="login-right">

            <div class="glass-lines"></div>

            <span class="orb one"></span>
            <span class="orb two"></span>
            <span class="orb three"></span>

        </div>

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
