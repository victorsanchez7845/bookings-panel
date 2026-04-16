@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $bookingsStatus = [
        "type" => "BOOKING",
        "total" => 0,
        "gran_total" => 0,
        "USD" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "MXN" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "counter" => 0,
        "data" => []
    ];

    $dataMethodPayments = [
        "total" => 0,
        "gran_total" => 0,
        "USD" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "MXN" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "counter" => 0,
        "data" => []
    ];
    
    $dataCurrency = [
        "total" => 0,
        "gran_total" => 0,
        "counter" => 0,
        "data" => []
    ];

    $dataVehicles = [
        "total" => 0,
        "gran_total" => 0,
        "USD" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "MXN" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "counter" => 0,
        "data" => []
    ];

    $dataServiceType = [
        "total" => 0,
        "gran_total" => 0,
        "USD" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "MXN" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "counter" => 0,
        "data" => []
    ];

    $dataSites = [
        "total" => 0,
        "gran_total" => 0,
        "USD" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "MXN" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "counter" => 0,
        "data" => []
    ];

    $dataDestinations = [
        "total" => 0,
        "gran_total" => 0,
        "USD" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "MXN" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "counter" => 0,
        "data" => []
    ];

    $dataOriginSale = [
        "total" => 0,
        "gran_total" => 0,
        "USD" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "MXN" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "counter" => 0,
        "data" => []
    ];

    $dataServiceTypeOperation = [
        "total" => 0,
        "gran_total" => 0,
        "USD" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "MXN" => [
            "total" => 0,
            "gran_total" => 0,
            "counter" => 0,
        ],
        "counter" => 0,
        "data" => []
    ];
@endphp
@extends('layout.app')
@section('title') Reporte De Ventas @endsection

@push('Css')
    <link href="{{ mix('/assets/css/sections/reports/sales.min.css') }}" rel="preload" as="style" >
    <link href="{{ mix('/assets/css/sections/reports/sales.min.css') }}" rel="stylesheet" >
@endpush

@push('Js')
    <script src="https://cdn.jsdelivr.net/npm/@easepick/datetime@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/core@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/base-plugin@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/lock-plugin@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/range-plugin@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script src="{{ mix('assets/js/sections/reports/sales.min.js') }}"></script>
@endpush

@section('content')
    @php
        $buttons = array(
            array(
                'text' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24" name="filter" class=""><path fill="" fill-rule="evenodd" d="M5 7a1 1 0 000 2h14a1 1 0 100-2H5zm2 5a1 1 0 011-1h8a1 1 0 110 2H8a1 1 0 01-1-1zm3 4a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg> Filtros',
                'className' => 'btn btn-primary __btn_create',
                'attr' => array(
                    'data-title' =>  "Filtros de ventas",
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#filterModal',
                )
            ),
            array(
                'text' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24" name="layout-columns" class=""><path fill="" fill-rule="evenodd" d="M7 5a2 2 0 00-2 2v10a2 2 0 002 2h1V5H7zm3 0v14h4V5h-4zm6 0v14h1a2 2 0 002-2V7a2 2 0 00-2-2h-1zM3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7z" clip-rule="evenodd"></path></svg> Administrar columnas',
                'titleAttr' => 'Administrar columnas',
                'className' => 'btn btn-primary __btn_columns',
                'attr' => array(
                    'data-title' =>  "Administrar columnas",
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#columnsModal',
                    'data-table' => 'bookings',// EL ID DE LA TABLA QUE VAMOS A OBTENER SUS HEADERS
                    'data-container' => 'columns', //EL ID DEL DIV DONDE IMPRIMIREMOS LOS CHECKBOX DE LOS HEADERS                    
                )                
            ),
            array(
                'text' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24" name="cloud-download" class=""><path fill="" fill-rule="evenodd" d="M12 4a7 7 0 00-6.965 6.299c-.918.436-1.701 1.177-2.21 1.95A5 5 0 007 20a1 1 0 100-2 3 3 0 01-2.505-4.65c.43-.653 1.122-1.206 1.772-1.386A1 1 0 007 11a5 5 0 0110 0 1 1 0 00.737.965c.646.176 1.322.716 1.76 1.37a3 3 0 01-.508 3.911 3.08 3.08 0 01-1.997.754 1 1 0 00.016 2 5.08 5.08 0 003.306-1.256 5 5 0 00.846-6.517c-.51-.765-1.28-1.5-2.195-1.931A7 7 0 0012 4zm1 7a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L13 16.586V11z" clip-rule="evenodd"></path></svg> Ver graficas',
                'titleAttr' => 'Ver graficas de ventas',
                'className' => 'btn btn-primary __btn_chart',
                'attr' => array(
                    'data-title' =>  "Grafica de ventas",
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#chartsModal2',
                )
            ),        
            array(
                'text' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24" name="cloud-download" class=""><path fill="" fill-rule="evenodd" d="M12 4a7 7 0 00-6.965 6.299c-.918.436-1.701 1.177-2.21 1.95A5 5 0 007 20a1 1 0 100-2 3 3 0 01-2.505-4.65c.43-.653 1.122-1.206 1.772-1.386A1 1 0 007 11a5 5 0 0110 0 1 1 0 00.737.965c.646.176 1.322.716 1.76 1.37a3 3 0 01-.508 3.911 3.08 3.08 0 01-1.997.754 1 1 0 00.016 2 5.08 5.08 0 003.306-1.256 5 5 0 00.846-6.517c-.51-.765-1.28-1.5-2.195-1.931A7 7 0 0012 4zm1 7a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L13 16.586V11z" clip-rule="evenodd"></path></svg> Exportar Excel',
                'extend' => 'excelHtml5',
                'titleAttr' => 'Exportar Excel',
                'className' => 'btn btn-primary',
                'exportOptions' => [
                    'columns' => ':visible'  // Solo exporta las columnas visibles   
                ]
            ),
            array(
                'text' => 'Tipo de cambio: '.$exchange,
                'titleAttr' => 'Tipo de cambio',
                'className' => 'btn btn-warning',
            ),
        );
    @endphp
    
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                @if ($errors->any())
                    <div class="alert alert-light-danger alert-dismissible fade show border-0 mb-4" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-bs-dismiss="alert"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <table id="dataSales" class="table table-rendering dt-table-hover" style="width:100%" data-button='<?=json_encode($buttons)?>'>
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th class="text-center">TIPO DE SERVICIO</th>
                            <th class="text-center">CÓDIGO</th>
                            <th class="text-center">REFERENCIA</th>
                            <th class="text-center">VENDEDOR</th>
                            <th class="text-center">FECHA DE RESERVACIÓN</th>
                            <th class="text-center">HORA DE RESERVACIÓN</th>
                            <th class="text-center">SITIO</th>
                            <th class="text-center">ORIGEN DE VENTA</th>
                            <th class="text-center">CAMPAÑA</th>
                            <th class="text-center">ESTATUS DE RESERVACIÓN</th>
                            <th class="text-center">NOMBRE DEL CLIENTE</th>
                            <th class="text-center">TELÉFONO DEL CLIENTE</th>
                            <th class="text-center">CORREO DEL CLIENTE</th>
                            <th class="text-center">VEHÍCULO</th>
                            <th class="text-center">PAX</th>
                            <th class="text-center">ORIGEN</th>                            
                            <th class="text-center">DESDE</th>
                            <th class="text-center">DESTINO</th>
                            <th class="text-center">HACIA</th>
                            <th class="text-center">FECHA DE SERVICIO</th>
                            <th class="text-center">HORA DE SERVICIO</th>
                            <th class="text-center">ESTATUS DE SERVICIO(S)</th>
                            <th class="text-center">ESTATUS DE PAGO</th>
                            <th class="text-center">TOTAL DE RESERVACIÓN</th>
                            <th class="text-center">BALANCE</th>
                            <th class="text-center">PRECIO POR SERVICIO</th>
                            <th class="text-center">MONEDA</th>
                            <th class="text-center">MÉTODO DE PAGO</th> 
                            <th class="text-center">INFORMACIÓN DE MÉTODO DE PAGO</th>
                            <th class="text-center">FUE COTIZACIÓN</th>
                            <th class="text-center">CALIFICACIÓN</th>
                            <th class="text-center">COMISIÓNABLE</th>
                            <th class="text-center">TIPO DE CANCELACIÓN</th>
                            <th class="text-center">MOTIVO DE CANCELACIÓN</th>
                            <th class="text-center">TIENE REEMBOLSO</th>
                            <th class="text-center">CUANTAS SOLICITUDES</th>
                            <th class="text-center">ESTATUS DE REEMBOLSO</th>
                            <th class="text-center">PAGADO DESPUÉS DE LA VENTA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(sizeof($bookings) >= 1)
                            @foreach ($bookings as $item)
                                @php
                                    //ESTATUS
                                    if (!isset( $bookingsStatus['data'][$item->reservation_status] )){
                                        $bookingsStatus['data'][$item->reservation_status] = [
                                            "name" => auth()->user()->statusBooking($item->reservation_status),
                                            "color" => auth()->user()->colorStatusBooking($item->reservation_status),
                                            "total" => 0,
                                            "gran_total" => 0,
                                            "USD" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "MXN" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "counter" => 0,
                                        ];
                                    }
                                    $bookingsStatus['total'] += $item->total_sales;
                                    $bookingsStatus['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $bookingsStatus[$item->currency]['total'] += $item->total_sales;
                                    $bookingsStatus[$item->currency]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $bookingsStatus[$item->currency]['counter']++;
                                    $bookingsStatus['counter']++;
                                    $bookingsStatus['data'][$item->reservation_status]['total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $bookingsStatus['data'][$item->reservation_status]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $bookingsStatus['data'][$item->reservation_status][$item->currency]['total'] += $item->total_sales;
                                    $bookingsStatus['data'][$item->reservation_status][$item->currency]['counter']++;
                                    $bookingsStatus['data'][$item->reservation_status]['counter']++;

                                    if (!isset( $dataServiceTypeOperation['data'][strtoupper(Str::slug($item->service_types))] ) ){
                                        $dataServiceTypeOperation['data'][strtoupper(Str::slug($item->service_types))] = [
                                            "name" => $item->service_types,
                                            "total" => 0,
                                            "gran_total" => 0,
                                            "USD" => [
                                                "total" => 0,
                                                "gran_total" => 0,
                                                "counter" => 0,
                                            ],
                                            "MXN" => [
                                                "total" => 0,
                                                "gran_total" => 0,
                                                "counter" => 0,
                                            ],
                                            "counter" => 0,                                            
                                        ];
                                    }
                                    $dataServiceTypeOperation['total'] += $item->total_sales;
                                    $dataServiceTypeOperation['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataServiceTypeOperation[$item->currency]['total'] += $item->total_sales;
                                    $dataServiceTypeOperation[$item->currency]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataServiceTypeOperation[$item->currency]['counter']++;
                                    $dataServiceTypeOperation['data'][strtoupper(Str::slug($item->service_types))]['total'] += $item->total_sales;
                                    $dataServiceTypeOperation['data'][strtoupper(Str::slug($item->service_types))]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataServiceTypeOperation['data'][strtoupper(Str::slug($item->service_types))][$item->currency]['total'] += $item->total_sales;
                                    $dataServiceTypeOperation['data'][strtoupper(Str::slug($item->service_types))][$item->currency]['counter']++;
                                    $dataServiceTypeOperation['data'][strtoupper(Str::slug($item->service_types))]['counter']++;
                                    $dataServiceTypeOperation['counter']++;

                                    //METODOS DE PAGO
                                    if (!isset( $dataMethodPayments['data'][strtoupper(Str::slug($item->payment_type_name))] ) ){
                                        $dataMethodPayments['data'][strtoupper(Str::slug($item->payment_type_name))] = [
                                            "name" => $item->payment_type_name,
                                            "color" => auth()->user()->colorPaymentMethods($item->payment_type_name),
                                            "total" => 0,
                                            "gran_total" => 0,
                                            "USD" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "MXN" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "counter" => 0,                                            
                                        ];
                                    }
                                    $dataMethodPayments['total'] += $item->total_sales;
                                    $dataMethodPayments['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataMethodPayments[$item->currency]['total'] += $item->total_sales;
                                    $dataMethodPayments[$item->currency]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataMethodPayments[$item->currency]['counter']++;
                                    $dataMethodPayments['data'][strtoupper(Str::slug($item->payment_type_name))]['total'] += $item->total_sales;
                                    $dataMethodPayments['data'][strtoupper(Str::slug($item->payment_type_name))]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataMethodPayments['data'][strtoupper(Str::slug($item->payment_type_name))][$item->currency]['total'] += $item->total_sales;
                                    $dataMethodPayments['data'][strtoupper(Str::slug($item->payment_type_name))][$item->currency]['counter']++;
                                    $dataMethodPayments['data'][strtoupper(Str::slug($item->payment_type_name))]['counter']++;
                                    $dataMethodPayments['counter']++;

                                    //MONEDAS
                                    if (!isset( $dataCurrency['data'][$item->currency] ) ){
                                        $dataCurrency['data'][$item->currency] = [
                                            "name" => $item->currency,
                                            "color" => ( $item->currency == "USD" ? "#27ae60" : "#f39c12" ),
                                            "total" => 0,
                                            "gran_total" => 0,
                                            "counter" => 0,
                                        ];
                                    }
                                    $dataCurrency['total'] += $item->total_sales;
                                    $dataCurrency['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataCurrency['data'][$item->currency]['total'] += $item->total_sales;
                                    $dataCurrency['data'][$item->currency]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataCurrency['data'][$item->currency]['counter']++;
                                    $dataCurrency['counter']++;

                                    //VEHICULOS                                    
                                    if (!isset( $dataVehicles['data'][strtoupper(Str::slug($item->service_type_name))] ) ){
                                        $dataVehicles['data'][strtoupper(Str::slug($item->service_type_name))] = [
                                            "name" => strtoupper($item->service_type_name),
                                            "total" => 0,
                                            "gran_total" => 0,
                                            "USD" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "MXN" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "counter" => 0,                                            
                                        ];
                                    }
                                    $dataVehicles['total'] += $item->total_sales;
                                    $dataVehicles['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataVehicles[$item->currency]['total'] += $item->total_sales;
                                    $dataVehicles[$item->currency]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataVehicles[$item->currency]['counter']++;
                                    $dataVehicles['data'][strtoupper(Str::slug($item->service_type_name))]['total'] += $item->total_sales;
                                    $dataVehicles['data'][strtoupper(Str::slug($item->service_type_name))]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataVehicles['data'][strtoupper(Str::slug($item->service_type_name))][$item->currency]['total'] += $item->total_sales;
                                    $dataVehicles['data'][strtoupper(Str::slug($item->service_type_name))][$item->currency]['counter']++;
                                    $dataVehicles['data'][strtoupper(Str::slug($item->service_type_name))]['counter']++;
                                    $dataVehicles['counter']++;

                                    //TIPO DE SERVICIO                                    
                                    if (!isset( $dataServiceType['data'][strtoupper(Str::slug($item->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP'))] ) ){
                                        $dataServiceType['data'][strtoupper(Str::slug($item->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP'))] = [
                                            "name" => strtoupper($item->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP'),
                                            "color" => strtoupper($item->is_round_trip == 0 ? '#00ab55' : '#e7515a'),
                                            "total" => 0,
                                            "gran_total" => 0,
                                            "USD" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "MXN" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "counter" => 0,                                            
                                        ];
                                    }
                                    $dataServiceType['total'] += $item->total_sales;
                                    $dataServiceType['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataServiceType[$item->currency]['total'] += $item->total_sales;
                                    $dataServiceType[$item->currency]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataServiceType[$item->currency]['counter']++;
                                    $dataServiceType['data'][strtoupper(Str::slug($item->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP'))]['total'] += $item->total_sales;
                                    $dataServiceType['data'][strtoupper(Str::slug($item->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP'))]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataServiceType['data'][strtoupper(Str::slug($item->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP'))][$item->currency]['total'] += $item->total_sales;
                                    $dataServiceType['data'][strtoupper(Str::slug($item->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP'))][$item->currency]['counter']++;
                                    $dataServiceType['data'][strtoupper(Str::slug($item->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP'))]['counter']++;
                                    $dataServiceType['counter']++;

                                    //SITIOS                                    
                                    if (!isset( $dataSites['data'][strtoupper(Str::slug($item->site_name))] ) ){
                                        $dataSites['data'][strtoupper(Str::slug($item->site_name))] = [
                                            "name" => strtoupper($item->site_name),
                                            "total" => 0,
                                            "gran_total" => 0,
                                            "USD" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "MXN" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "counter" => 0,                                            
                                        ];
                                    }
                                    $dataSites['total'] += $item->total_sales;
                                    $dataSites['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataSites[$item->currency]['total'] += $item->total_sales;
                                    $dataSites[$item->currency]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataSites[$item->currency]['counter']++;
                                    $dataSites['data'][strtoupper(Str::slug($item->site_name))]['total'] += $item->total_sales;
                                    $dataSites['data'][strtoupper(Str::slug($item->site_name))]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataSites['data'][strtoupper(Str::slug($item->site_name))][$item->currency]['total'] += $item->total_sales;
                                    $dataSites['data'][strtoupper(Str::slug($item->site_name))][$item->currency]['counter']++;
                                    $dataSites['data'][strtoupper(Str::slug($item->site_name))]['counter']++;
                                    $dataSites['counter']++;

                                    //DESTINOS                                    
                                    if (!isset( $dataDestinations['data'][strtoupper(Str::slug($item->destination_name_to))] ) ){
                                        $dataDestinations['data'][strtoupper(Str::slug($item->destination_name_to))] = [
                                            "name" => strtoupper($item->destination_name_to),
                                            "total" => 0,
                                            "gran_total" => 0,
                                            "USD" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "MXN" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "counter" => 0,                                            
                                        ];
                                    }
                                    $dataDestinations['total'] += $item->total_sales;
                                    $dataDestinations['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataDestinations[$item->currency]['total'] += $item->total_sales;
                                    $dataDestinations[$item->currency]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataDestinations[$item->currency]['counter']++;
                                    $dataDestinations['data'][strtoupper(Str::slug($item->destination_name_to))]['total'] += $item->total_sales;
                                    $dataDestinations['data'][strtoupper(Str::slug($item->destination_name_to))]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataDestinations['data'][strtoupper(Str::slug($item->destination_name_to))][$item->currency]['total'] += $item->total_sales;
                                    $dataDestinations['data'][strtoupper(Str::slug($item->destination_name_to))][$item->currency]['counter']++;
                                    $dataDestinations['data'][strtoupper(Str::slug($item->destination_name_to))]['counter']++;
                                    $dataDestinations['counter']++;

                                    //ORIGEN DE VENTA
                                    if (!isset( $dataOriginSale['data'][strtoupper(Str::slug(( !empty($item->origin_code) ? $item->origin_code : 'PAGINA WEB' )))] ) ){
                                        $dataOriginSale['data'][strtoupper(Str::slug(( !empty($item->origin_code) ? $item->origin_code : 'PAGINA WEB' )))] = [
                                            "name" => ( !empty($item->origin_code) ? $item->origin_code : 'PAGINA WEB' ),
                                            "total" => 0,
                                            "gran_total" => 0,
                                            "USD" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "MXN" => [
                                                "total" => 0,
                                                "counter" => 0,
                                            ],
                                            "counter" => 0,                                            
                                        ];
                                    }
                                    $dataOriginSale['total'] += $item->total_sales;
                                    $dataOriginSale['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataOriginSale[$item->currency]['total'] += $item->total_sales;
                                    $dataOriginSale[$item->currency]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataOriginSale[$item->currency]['counter']++;
                                    $dataOriginSale['data'][strtoupper(Str::slug(( !empty($item->origin_code) ? $item->origin_code : 'PAGINA WEB' )))]['total'] += $item->total_sales;
                                    $dataOriginSale['data'][strtoupper(Str::slug(( !empty($item->origin_code) ? $item->origin_code : 'PAGINA WEB' )))]['gran_total'] += ( $item->currency == "USD" ? ($item->total_sales * $exchange) : $item->total_sales );
                                    $dataOriginSale['data'][strtoupper(Str::slug(( !empty($item->origin_code) ? $item->origin_code : 'PAGINA WEB' )))][$item->currency]['total'] += $item->total_sales;
                                    $dataOriginSale['data'][strtoupper(Str::slug(( !empty($item->origin_code) ? $item->origin_code : 'PAGINA WEB' )))][$item->currency]['counter']++;
                                    $dataOriginSale['data'][strtoupper(Str::slug(( !empty($item->origin_code) ? $item->origin_code : 'PAGINA WEB' )))]['counter']++;
                                    $dataOriginSale['counter']++;

                                    $background_identifier = "";
                                    if( $item->is_today != 0 ){
                                        $background_identifier = "background-color: #fb5607b8;";
                                    }

                                    if( $item->is_tomorrow != 0 ){
                                        $background_identifier = "background-color: #009688;";
                                    }
                                    // dump($item);
                                @endphp
                                <tr class="{{ ( $item->is_today != 0 ? 'bs-tooltip' : '' ) }}" title="{{ ( $item->is_today != 0 ? 'Es una reserva que se opera el mismo día en que se creo #: '.$item->reservation_id : '' ) }}" style="{{ $background_identifier }}" data-reservation="{{ $item->reservation_id }}" data-is_round_trip="{{ $item->is_round_trip }}">
                                    <td class="text-center">{{ $item->reservation_id }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $item->is_round_trip == 0 ? 'success' : 'danger' }} text-lowercase">{{ $item->is_round_trip == 0 ? 'ONE WAY' : 'ROUND TRIP' }}</span>
                                        [{{ $item->service_types }}]
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $codes_string = "";
                                            $codes = explode(",",$item->reservation_codes);
                                            foreach ($codes as $key => $code) {
                                                $codes_string .= '<p class="mb-1">'.$code.'</p>';
                                            }
                                        @endphp
                                        @if (auth()->user()->hasPermission(61))
                                            <a href="/reservations/detail/{{ $item->reservation_id }}"><?=$codes_string?></a>
                                        @else
                                            <?=$codes_string?>
                                        @endif
                                    </td>
                                    <td class="text-center"><?=( !empty($item->reference) ? '<p class="mb-1">'.$item->reference.'</p>' : '' )?></td>
                                    <td class="text-center">{{ $item->employee ? $item->employee : 'System' }}</td>
                                    <td class="text-center">{{ date("Y-m-d", strtotime($item->created_at)) }}</td>
                                    <td class="text-center">{{ date("H:i", strtotime($item->created_at)) }}</td>
                                    <td class="text-center">{{ $item->site_name }}</td>
                                    <td class="text-center">{{ !empty($item->origin_code) ? $item->origin_code : 'PAGINA WEB' }}</td>
                                    <td class="text-center">{{ $item->campaign }}</td>
                                    <td class="text-center"><button type="button" class="btn btn-{{ auth()->user()->classStatusBooking($item->reservation_status) }}">{{ auth()->user()->statusBooking($item->reservation_status) }}</button></td>
                                    <td class="text-center">{{ $item->full_name }}</td>
                                    <td class="text-center">{{ $item->client_phone }}</td>
                                    <td class="text-center">{{ $item->client_email }}</td>
                                    <td class="text-center">{{ $item->service_type_name }}</td>
                                    <td class="text-center">{{ $item->passengers }}</td>
                                    <td class="text-center normal">{{ $item->destination_name_from }}</td>
                                    <td class="text-center normal">{{ $item->from_name }}</td>
                                    <td class="text-center normal">{{ $item->destination_name_to }}</td>
                                    <td class="text-center normal">{{ $item->to_name }}</td>
                                    <td class="text-center">
                                        @php
                                            $pickupFrom = $item->pickup_from ?? ''; // Asegurar que la variable exista
                                            $pickupTo = $item->pickup_to ?? ''; // Asegurar que la variable exista

                                            // Verificar si hay una coma para dividir, de lo contrario, ponerlo en un array con un único valor
                                            $pickupDatesFrom = strpos($pickupFrom, ',') !== false 
                                                ? array_map('trim', explode(',', $pickupFrom)) 
                                                : [$pickupFrom];

                                            // Verificar si hay una coma para dividir, de lo contrario, ponerlo en un array con un único valor
                                            $pickupDatesTo = strpos($pickupTo, ',') !== false 
                                                ? array_map('trim', explode(',', $pickupTo)) 
                                                : [$pickupTo];
                                        @endphp
                                        [{{ date("Y-m-d", strtotime($pickupDatesFrom[0])) }}] <br>
                                        @if ( $item->is_round_trip != 0 )
                                            [{{ date("Y-m-d", strtotime($pickupDatesTo[0])) }}]
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        [{{ date("H:i", strtotime($pickupDatesFrom[0])) }}] <br>
                                        @if ( $item->is_round_trip != 0 )
                                            [{{ date("H:i", strtotime($pickupDatesTo[0])) }}]
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <?=auth()->user()->renderServiceStatus($item->one_service_status)?><br>
                                        @if ( $item->is_round_trip != 0 )
                                            <?=auth()->user()->renderServiceStatus($item->two_service_status)?>
                                        @endif
                                    </td>
                                    <td class="text-center" <?=auth()->user()->classStatusPayment($item)?>>{{ auth()->user()->statusPayment($item->payment_status) }}</td>
                                    <td class="text-center" <?=auth()->user()->classStatusPayment($item)?>>{{ number_format(($item->total_sales),2) }}</td>
                                    <td class="text-center" {{ (($item->total_balance > 0)? "style=background-color:green;color:white;font-weight:bold;":"") }}>{{ number_format($item->total_balance,2) }}</td>
                                    <td class="text-center">{{ number_format(($item->is_round_trip != 0 ? ( $item->total_sales / 2 ) : $item->total_sales),2) }}</td>
                                    <td class="text-center">{{ $item->currency }}</td>
                                    <td class="text-center">{{ $item->payment_type_name }}</td>
                                    <td class="text-center">
                                        @if ( !empty($item->payment_details) )
                                            [{{ $item->payment_details }}]
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ( $item->is_quotation == 0 && $item->was_is_quotation == 1 )
                                            <button class="btn btn-success" type="button">Sí</button>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ( $item->reserve_rating )
                                            <button class="btn btn-{{ $item->reserve_rating == 1 ? 'success' : 'danger' }}" type="button"><?=$item->reserve_rating == 1 ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-thumbs-up"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-thumbs-down"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path></svg>'?></button>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ( $item->is_commissionable == 1 )
                                            <button class="btn btn-success" type="button">Sí</button>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <p>{{ $item->one_service_status == "CANCELLED" ? ( isset($item->one_cancellation_level) ? $item->one_cancellation_level : "SIN TIPO" ) : "" }}</p>
                                        
                                        @if ( $item->is_round_trip != 0 )
                                            <p>{{ $item->two_service_status == "CANCELLED" ? ( isset($item->two_cancellation_level) ? $item->two_cancellation_level : "SIN TIPO" ) : "" }}</p>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ( $item->reservation_status == "CANCELLED" )
                                            @if ( !empty($item->cancellation_reason) )
                                                {{ $item->cancellation_reason }}
                                            @else
                                                {{ "NO SHOW" }}
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ( $item->has_refund_request )
                                            <button class="btn btn-success" type="button">Sí</button>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ( $item->has_refund_request )
                                            {{ $item->refund_request_count }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ( $item->has_refund_request )
                                            <button class="btn btn-{{ auth()->user()->classStatusRefund($item->refund_status) }} btn-sm">{{ auth()->user()->statusRefund($item->refund_status) }}</button>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($item->transportation_payment_status == "PAID_AFTER_SALE")
                                            <button class="btn btn-success btn-sm">Sí</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-modals.filters.bookings :data="$data" :isSearch="1" :services="$services" :istoday="1" :isagency="1" :currencies="$currencies" :users="$users" :websites="$websites" :origins="$origins" :reservationstatus="$reservation_status" :vehicles="$vehicles" :zones="$zones" :paymentstatus="$payment_status" :isbalance="1" :methods="$methods" :wasIsQuotation="1" :rating="1" :iscommissionable="1" :cancellations="$cancellations" :ispayarrival="1" :refundRequestCount="1" :isPaidAfterSale="1" />
    <x-modals.reports.columns />
    <x-modals.charts.sales2 :bookingsStatus="$bookingsStatus" :dataMethodPayments="$dataMethodPayments" :dataCurrency="$dataCurrency" :dataVehicles="$dataVehicles" :dataServiceType="$dataServiceType" :dataSites="$dataSites" :dataDestinations="$dataDestinations" :dataOriginSale="$dataOriginSale" :dataServiceTypeOperation="$dataServiceTypeOperation" />
@endsection