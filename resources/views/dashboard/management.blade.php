@extends('layout.app')
@section('title') Dashboard @endsection

@push('Css')
    <link href="{{ mix('/assets/css/sections/dashboard/management.min.css') }}" rel="preload" as="style">
    <link href="{{ mix('/assets/css/sections/dashboard/management.min.css') }}" rel="stylesheet">
    <style>
        .custom-tooltip{
            padding: 5px 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;            
        }
        .custom-tooltip .apexcharts-tooltip-y-group{
            padding: 0
        }
        .custom-tooltip .apexcharts-tooltip-y-group .apexcharts-tooltip-text-y-label{
            font-weight: 600;
        }
        .custom-tooltip .apexcharts-tooltip-y-group .apexcharts-tooltip-text-y-value{
            font-weight: 400;
        }
    </style>     
@endpush

@push('Js')
    <script src="https://cdn.jsdelivr.net/npm/@easepick/datetime@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/core@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/base-plugin@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/lock-plugin@1.2.1/dist/index.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@easepick/range-plugin@1.2.1/dist/index.umd.min.js"></script>
    <script>
        let dashboard = {
            dataMonth: @json(( isset($bookings_month) ? $bookings_month : [] )),
            dataSitesMonth: @json($bookings_sites_month),
            dataDestinationsMonth: @json($bookings_destinations_month),
            number_format: function(number, decimals, dec_point, thousands_sep) {
                number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
                var n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                    s = '',
                    toFixedFix = function (n, prec) {
                        var k = Math.pow(10, prec);
                        return '' + Math.round(n * k) / k;
                    };
                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) {
                    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                }
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            },
            getTranslation: function(item){
                return (translations[language][item]) ? translations[language][item] : 'Translate not found';
            },            
            actionTable: function(table){
                let buttons = [];
                const _settings = {},
                    _buttons = table.data('button');

                if( _buttons != undefined && _buttons.length > 0 ){
                    _buttons.forEach(_btn => {
                        buttons.push(_btn);
                    });
                }

                _settings.dom = `<'dt--top-section'<'row'<'col-12 col-sm-6 d-flex justify-content-sm-start justify-content-center'l<'dt-action-buttons align-self-center ms-3'B>><'col-12 col-sm-6 d-flex justify-content-sm-end justify-content-center mt-sm-0 mt-3'f>>>
                                <'table-responsive'tr>
                                <'dt--bottom-section d-sm-flex justify-content-sm-between text-center'<'dt--pages-count  mb-sm-0 mb-3'i><'dt--pagination'p>>`;                        
                _settings.deferRender = true;
                _settings.responsive = true;
                _settings.buttons = buttons;        
                _settings.order = [[ 0, "DESC" ]];
                _settings.lengthMenu = [10, 20, 50];
                _settings.pageLength = 10;                
                _settings.oLanguage = {
                    "oPaginate": { "sPrevious": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>', "sNext": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>' },
                    "sInfo": this.getTranslation("table.pagination") + " _PAGE_ " + this.getTranslation("table.of") + " _PAGES_",
                    "sSearch": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
                    "sSearchPlaceholder": this.getTranslation("table.search") + "...",
                    "sLengthMenu": this.getTranslation("table.results") + " :  _MENU_",
                };

                table.DataTable( _settings );
            },
            seriesStatusMonth: function() {
                let object = {
                    USD: 0,
                    MXN: 0,                            
                    series: [],
                    labels: [],
                    colors: []
                };
                const data = this.dataMonth;
                if( data.hasOwnProperty('status') ){
                    const status = Object.entries(data.status);                
                    status.forEach( ([key, data]) => {
                        object.series.push(data.counter);
                        object.labels.push(key);
                        object.colors.push(data.color);
                    });
                }
                return object;
            },
            seriesBookingsMonth: function() {
                let object = {
                    USD: 0,
                    MXN: 0,
                    series: [{
                        data: []
                    }],
                    labels: []
                };
                const data = this.dataMonth;
                if( data.hasOwnProperty('bookings_day') ){
                    const bookings_day = Object.entries(data.bookings_day);
                    bookings_day.forEach( ([date, dataDay]) => {
                        let dateOriginal = date.split('-');
                        let dateFormat = dateOriginal[2] + '/' + dateOriginal[1];
                        object.USD  = object.USD + dataDay.USD;
                        object.MXN  = object.MXN + dataDay.MXN;
                        object.series[0].data.push({                        
                            y: dataDay.counter,
                            x: date + 'T00:00:00',
                            details: dataDay
                        });
                        object.labels.push(date + 'T00:00:00');
                    });
                }
                return object;
            },
            seriesBookingsCurrencyMonth: function() {
                let object = {
                    USD: 0,
                    MXN: 0,
                    series: [
                        {
                            name: 'USD',
                            data: []
                        },
                        {
                            name: 'MXN',
                            data: []
                        }                                
                    ],
                    labels: []
                };

                const data = this.dataMonth;
                if( data.hasOwnProperty('bookings_day') ){
                    const bookings_day = Object.entries(data.bookings_day);
                    bookings_day.forEach( ([date, dataDay]) => {
                        let dateOriginal = date.split('-');
                        let dateFormat = dateOriginal[2] + '/' + dateOriginal[1];
                        object.USD  = object.USD + dataDay.USD;
                        object.MXN  = object.MXN + dataDay.MXN;
                        object.series[0].data.push(dataDay.USD);
                        object.series[1].data.push(dataDay.MXN);                            
                        object.labels.push(date + 'T00:00:00');
                    });
                }
                return object;
            },
            seriesSitesMonth: function() {
                let seriesMonth = {
                    USD: 0,
                    MXN: 0,
                    counter: 0,
                    series: [{
                        data: []
                    }],
                    labels: []
                };            
                const response = (this.dataSitesMonth);
                const sites = Object.entries(response.data);
                sites.forEach( ([key, data]) => {
                    // console.log(key, data);
                    seriesMonth.USD  = seriesMonth.USD + data.USD;
                    seriesMonth.MXN  = seriesMonth.MXN + data.MXN;
                    seriesMonth.counter  = seriesMonth.counter + data.counter;
                    seriesMonth.series[0].data.push({                        
                        y: data.counter,
                        x: data.name,
                        details: data
                    });
                    seriesMonth.labels.push(data.name);
                });
                return seriesMonth;
            },
            seriesDestinationMonth: function() {
                let seriesMonth = {
                    USD: 0,
                    MXN: 0,
                    counter: 0,
                    series: [{
                        data: []
                    }],
                    labels: [],
                    colors: [],
                };            
                const response = (this.dataDestinationsMonth);
                const destinations = Object.entries(response.data);
                destinations.forEach( ([key, data]) => {
                    seriesMonth.USD  = seriesMonth.USD + data.USD;
                    seriesMonth.MXN  = seriesMonth.MXN + data.MXN;
                    seriesMonth.counter  = seriesMonth.counter + data.counter;
                    seriesMonth.series[0].data.push({
                        y: data.counter,
                        x: data.name,
                        details: data
                    });
                    seriesMonth.labels.push(data.name);
                    seriesMonth.colors.push(data.color);
                });
                return seriesMonth;
            },
        }
    </script>
    <script src="{{ mix('/assets/js/sections/dashboard/management.min.js') }}"></script>
@endpush

@section('content')
    <div class="row layout-top-spacing">
        @if (!auth()->user()->hasPermission(42))
            <div class="col-sm-12 col-xl-12">
                <div class="alert alert-primary alert-dismissible" role="alert">                    
                    <div class="alert-message">
                        <h4 class="alert-heading"><strong>Taxi Dominicana System</strong></h4>
                        <p>Bienvenido al sistema de reservaciones de &copy;Taxi Dominicana, para soporte y aclaraciones no dude en contactarnos por correo:</p>
                        <pre class="h6 text-danger mb-0">contacto@taxidominicana.com</pre>
                    </div>
                </div>
            </div>
        @else            
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                <div class="widget widget-chart-one">
                    <div class="widget-heading">
                        <h5 class="">Historial de reservas y ganancias del més</h5>
                    </div>
                    <div class="widget-content">
                        <div id="bookingsAnalyticsMonth" style="width:100%;"></div>
                    </div>
                </div>
            </div>                            
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                <div class="widget widget-chart-three">
                    <div class="widget-heading">
                        <h5 class="">Historial de reservas por divisas del més</h5>
                    </div>        
                    <div class="widget-content">
                        <div id="bookingsAnalyticsCurrencyMonth" style="width:100%;"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                <div class="widget widget-chart-three">
                    <div class="widget-heading">
                        <h5 class="">Historial de reservas por sitio del més</h5>
                    </div>        
                    <div class="widget-content">
                        <div id="bookingsAnalyticsSitesMonth"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                <div class="widget widget-chart-three">
                    <div class="widget-heading">
                        <h5 class="">Historial de reservas por destino del més</h5>
                    </div>        
                    <div class="widget-content">
                        <div id="bookingsAnalyticsDestinationsMonth"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
