@php
    use App\Traits\RoleTrait;
    use App\Traits\BookingTrait;
    use App\Traits\OperationTrait;

    $today = new DateTime();
    $dates = [];

    for ($i = 0; $i < 30; $i++) {
        $dates[] = $today->format('Y-m-d');
        $today->modify('-1 day');
    }
@endphp
@extends('layout.app')
@section('title') Dashboard Agente de Call Center @endsection

@push('Css')    
    <link href="{{ mix('/assets/css/sections/dashboard/callcenteragent.min.css') }}" rel="preload" as="style" >
    <link href="{{ mix('/assets/css/sections/dashboard/callcenteragent.min.css') }}" rel="stylesheet" >
    <style>
        img.qr{
            width: 100%;
            max-width: 300px;
        }        
    </style>
@endpush

@push('Js')
    <script src="https://cdn.jsdelivr.net/npm/@easepick/datetime@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/core@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/base-plugin@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/lock-plugin@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/range-plugin@1.2.1/dist/index.umd.min.js"></script>
    <script src="{{ mix('/assets/js/sections/dashboard/callcenteragent.min.js') }}"></script>
@endpush

@section('content')
    <div class="row layout-top-spacing callcenter-container">
        {{-- <div class="col-12 layout-spacing">
            <div class="alert alert-arrow-left alert-icon-left alert-light-primary alert-dismissible fade show mb-2" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" data-bs-dismiss="alert" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <strong>Total de ventas</strong> Solo se toman reservas con los siguientes estatus CONFIRMADO, CREDITO o CREDITO ABIERTO.
            </div>
            <div class="alert alert-arrow-left alert-icon-left alert-light-success alert-dismissible fade show mb-2" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" data-bs-dismiss="alert" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <strong>Total de servicios operados</strong> Solo se toman reservas con los siguientes estatus CONFIRMADO, CREDITO o CREDITO ABIERTO, y que el estatus del servicio sea COMPLETADO.
            </div>
            <div class="alert alert-arrow-left alert-icon-left alert-light-warning alert-dismissible fade show mb-2" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" data-bs-dismiss="alert" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <strong>Total de servicios pendientes</strong> Solo se toman reservas con los siguientes estatus CONFIRMADO, CREDITO o CREDITO ABIERTO, y que el estatus del servicio sea PENDIENTE.
            </div>
            <div class="alert alert-arrow-left alert-icon-left alert-light-info alert-dismissible fade show mb-2" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" data-bs-dismiss="alert" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <strong>comisiones</strong> Tomar en cuenta que al total de los servicios operados se le aplica un 20% de descuento y sobre el total se genera la comision, ver mas detalles en la seccion de total de comisión.
            </div>            
        </div> --}}
        <div class="col-12 layout-spacing">
            <div class="items-button">
                <button class="btn btn-primary btn-sm __btn_create" data-title="Filtros de callcenter" data-bs-toggle="modal" data-bs-target="#filterModal"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24" name="filter" class=""><path fill="" fill-rule="evenodd" d="M5 7a1 1 0 000 2h14a1 1 0 100-2H5zm2 5a1 1 0 011-1h8a1 1 0 110 2H8a1 1 0 01-1-1zm3 4a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg> Filtros</button>
                <button class="btn btn-success btn-sm">Fecha: <strong id="dateInfo"></strong></button>
                <button class="btn btn-warning btn-sm">Tipo de cambio comisiones: <strong id="exchangeInfo"></strong></button>
                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#callcenterQRModal">Ver QR</button>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
            <div class="widget widget-card-four">
                <div class="widget-content">
                    <div class="w-header">
                        <div class="w-info">
                            <h6 class="value">Meta diaría</h6>
                        </div>
                        {{-- <div class="task-action">
                            <div class="dropdown">
                                <a class="dropdown-toggle" href="#" role="button" id="expenses" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                </a>
                                <div class="dropdown-menu left" aria-labelledby="expenses" style="will-change: transform;">
                                    <a class="dropdown-item" href="javascript:void(0);">Este día</a>
                                    <a class="dropdown-item" href="javascript:void(0);">Este mes</a>
                                </div>
                            </div>
                        </div> --}}
                    </div>

                    <div class="w-content">
                        <div class="w-info">
                            <p class="value">
                                <strong id="dailyGoal"></strong> 
                                <span>Este día</span> 
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                            </p>
                        </div>                        
                    </div>

                    <div class="w-progress-stats" id="progressDailyGoal">
                        <div class="progress">
                            <div class="progress-bar bg-gradient-secondary" role="progressbar" style="width: 57%" aria-valuenow="57" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="">
                            <div class="w-icon">
                                <p>57%</p>
                            </div>
                        </div>                        
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
            <div class="widget widget-card-five">
                <div class="widget-content">
                    <div class="account-box">
                        <div class="info-box">
                            <div class="icon">
                                <span>
                                    <img src="https://designreset.com/equation/html/src/assets/img/money-bag.png" alt="money-bag">
                                </span>
                            </div>
                            <div class="balance-info">
                                <h6>Total de ventas</h6>
                                <p id="totalSales"></p>
                            </div>
                        </div>
                        <div class="card-bottom-section">
                            <div><span class="badge badge-light-success">+ 13.6% <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                            <a href="javascript:void(0);" class="getData" data-type="sales">Ver Reporte</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
            <div class="widget widget-card-five">
                <div class="widget-content">
                    <div class="account-box">
                        <div class="info-box">
                            <div class="icon">
                                <span>
                                    <img src="https://designreset.com/equation/html/src/assets/img/money-bag.png" alt="money-bag">
                                </span>
                            </div>
                            <div class="balance-info">
                                <h6>Total de servicios operados</h6>
                                <p id="totalServicesOperated"></p>
                            </div>
                        </div>

                        <div class="card-bottom-section">
                            <div><span class="badge badge-light-success">+ 13.6% <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                            <a href="javascript:void(0);" class="getData" data-type="completed">Ver reporte</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 col-12 layout-spacing">
            <div class="widget widget-card-five">
                <div class="widget-content">
                    <div class="account-box">
                        <div class="info-box">
                            <div class="icon">
                                <span>
                                    <img src="https://designreset.com/equation/html/src/assets/img/money-bag.png" alt="money-bag">
                                </span>
                            </div>
                            <div class="balance-info">
                                <h6>Total de servicios pendientes</h6>
                                <p id="totalPendingServices"></p>
                            </div>
                        </div>

                        <div class="card-bottom-section">
                            <div><span class="badge badge-light-success">+ 13.6% <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span></div>
                            <a href="javascript:void(0);" class="getData" data-type="pending">Ver reporte</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12 col-12 layout-spacing">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h5 class="" id="titleCharts">Total</h5>
                    <div class="task-action">
                        <div class="dropdown">
                            <a class="dropdown-toggle" href="#" role="button" id="renvenue" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                            </a>
                            <div class="dropdown-menu left" aria-labelledby="renvenue" style="will-change: transform;">
                                <a class="dropdown-item" data-type="sales" href="javascript:void(0);" onclick="callcenter.reloadChartsSales()">Ventas</a>
                                <a class="dropdown-item" data-type="operation" href="javascript:void(0);" onclick="callcenter.reloadChartsOperations()">Operación</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="widget-content">
                    <div class="text-center" id="revenueMonthly" style="max-height: 502px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-12 layout-spacing">
            <div class="widget widget-wallet-one" style="max-height:512px; overflow-y: scroll;">
                <div class="wallet-info text-center mb-3">
                    <p class="wallet-title mb-3">Total de comisión</p>                    
                    <p class="total-amount mb-3" id="totalCommissionOperated"></p>
                    <a href="javascript:void(0);" class="wallet-text"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up me-2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg> Ver más detalles de comisión</a>
                </div>
                {{-- <div class="wallet-action text-center d-flex justify-content-around">
                    <button class="btn btn-success btn-sm bs-tooltip" title="Total de comisión generada por los servicios operados">
                        <span class="btn-text-inner" id="totalCommissionOperated2"></span>
                    </button>
                    <button class="btn btn-warning btn-sm bs-tooltip" title="Total de comisión pendiente de generar por los servicios pendientes de operar">
                        <span class="btn-text-inner" id="totalCommissionPending"></span>
                    </button>
                </div> --}}
                <div class="w-100 d-none" id="containerBreakdownCommissions">
                    <hr>                
                    <ul class="list-group list-group-media" id="listBreakdownCommissions"></ul>    
                </div>
                <hr>                
                <ul class="list-group list-group-media" id="listTargets"></ul>
            </div>
        </div>

        {{-- SECION DE POST VENTA --}}
        @if ( auth()->user()->is_external == 0 )        
            <div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Cotizaciones del: <strong id="dateQuotationInfo"></strong></h5>
                    </div>
                    <div class="card-body card-data p-3">
                        <div id="quotation-general-container">
                            <div class="loaderItem"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pendientes de pago</h5>
                    </div>
                    <div class="card-body card-data p-3">
                        <div id="pending-general-container">
                            <div class="loaderItem"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                <div class="card">
                    <div class="card-header spam-date-container">
                        <h5 class="card-title mb-0">Gestión de SPAM</h5>
                        <select class="form-select" id="spam-selec-date" onchange="getSpamByDate(event)">
                            @foreach($dates as $key => $value) 
                                <option value="{{ $value }}">{{ date("Y/m/d", strtotime($value)) }}</option>
                            @endforeach                            
                        </select>                        
                    </div>                
                    <div class="card-body card-data p-3">
                        <div class="row" id="spam-general-container">
                            <div class="loaderItem"></div>
                        </div>
                    </div>                
                </div>
            </div>
        @endif
    </div>

    <x-modals.filters.bookings />
    <x-modals.dashboard.callcenteragent />
    <x-modals.dashboard.QR />
    <x-modals.management.aftersales />
@endsection
