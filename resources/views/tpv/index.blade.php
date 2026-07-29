
@extends('layout.app')
@section('title') TPV @endsection

@push('Css')
    <link href="{{ mix('/assets/css/sections/tpv.min.css') }}" rel="preload" as="style" >
    <link href="{{ mix('/assets/css/sections/tpv.min.css') }}" rel="stylesheet" >
@endpush

@push('Js')
    <script>
        const code = '{!! $config['code'] !!}';
    </script>
    <script src="{{ mix('/assets/js/sections/tpv/index.min.js') }}" defer></script>
@endpush

@section('content')
    <div class="row layout-top-spacing">
        <div id="one" class="active">
            <div class="top">
                <span id="clearBooking">Limpiar caja de reservas</span>
            </div>
            <div class="middle">
                <div class="bookingbox">
                    <form id="formQuotation" method="post">
                        @csrf
                        <input type="hidden" name="code" id="uuid" value="{{ $config['code'] }}">
                        <input type="hidden" name="from_lat">
                        <input type="hidden" name="from_lng">
                        <input type="hidden" name="to_lat">
                        <input type="hidden" name="to_lng">
                        <input type="hidden" id="flexSwitchCheckDefault" name="is_round_trip" value="0">
                        <input type="hidden" id="bookingCurrencyForm" name="currency" value="USD">
                        <input type="hidden" id="is_tpv" name="is_tpv" value="true">
                        <div class="box">
                            <div class="options">
                                <div class="one">
                                    <button class="aff-toggle-type active" type="button" data-type="OW">@lang('bookingbox.one_way')</button>
                                    <button class="aff-toggle-type" type="button" data-type="RT">@lang('bookingbox.round_trip')</button>
                                </div>
                                <div class="two">
                                    <button type="button" class="aff-toggle-currency active" data-currency="USD">USD</button>
                                    <button type="button" class="aff-toggle-currency" data-currency="MXN">MXN</button>
                                </div>
                            </div>
                            <div class="elements">
                                <div class="rate_group">
                                    <label class="form-label" for="bookingRategroupForm">Destino</label>
                                
                                    <select class="form-control mb-2" id="bookingRategroupForm" name="rate_group">
                                        <option value="xLjDl18" selected>Punta Cana</option>
                                    </select>
                                </div>

                                <div class="from">
                                    <label class="form-label" for="aff-input-from">Desde</label>
                                    <input class="form-control" type="text" name="from_name" id="aff-input-from">
                                    <div class="autocomplete-results" id="aff-input-from-elements"></div>
                                </div>
                                <div class="to">
                                    <label class="form-label" for="bookingToForm">Hacia</label>
                                    <input type="text" class="form-control" name="to_name" id="aff-input-to">
                                    <div class="autocomplete-results" id="aff-input-to-elements"></div>
                                </div>
                                
                                <div>
                                    <label class="form-label" for="bookingPickupForm">Fecha de recogida</label>
                                    <input type="text" class="form-control" id="bookingPickupForm" name="pickup" data-default-mode="single" value="{{ date("Y-m-d H:i") }}">
                                </div>
                                <div class="d-none" id="departureContainer">
                                    <label class="form-label" for="bookingDepartureForm">Fecha de regreso</label>
                                    <input type="text" class="form-control" id="bookingDepartureForm" name="pickup_departure" data-default-mode="single" value="{{ date("Y-m-d H:i") }}">
                                </div>
                                
                                <div class="passengers">
                                    <label class="form-label" for="bookingPassengersForm">Pasajeros</label>
                                    <select class="form-control mb-2" id="bookingPassengersForm" name="passengers">
                                        @for ($i = 1; $i < 151; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>                             
                                        @endfor                            
                                    </select>
                                </div>
                                <div class="language">
                                    <label class="form-label" for="bookingLanguageForm">Idioma</label>
                                    <select class="form-control mb-2" id="bookingLanguageForm" name="language">
                                        <option value="en">Español</option>
                                        <option value="es">Ingles</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="button">
                            <button class="btn" type="submit" id="btnQuote">Cotizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="loadContent"></div>
    </div>
@endsection
