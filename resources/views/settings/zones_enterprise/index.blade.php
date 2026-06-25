@extends('layout.app')
@section('title') Zonas De Agencia @endsection

@push('Css')
    <link href="{{ mix('/assets/css/sections/settings/zones.min.css') }}" rel="preload" as="style">
    <link href="{{ mix('/assets/css/sections/settings/zones.min.css') }}" rel="stylesheet">
@endpush

@push('Js')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.gmaps.key') }}&v=3.64&libraries=drawing,places" async defer></script>
    <script src="{{ mix('/assets/js/sections/settings/zones_enterprise.min.js') }}"></script>
@endpush

@section('content')
    @php
        $buttons = array();
    @endphp
    <div class="row layout-top-spacing">        
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
            <div id="filters" class="accordion">
                <div class="card">
                <div class="card-header" id="headingOne1">
                    <section class="mb-0 mt-0">
                        <div role="menu" class="" data-bs-toggle="collapse" data-bs-target="#defaultAccordionOne" aria-expanded="true" aria-controls="defaultAccordionOne">
                            Filtro de zonas <div class="icons"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                        </div>
                    </section>
                </div>
                <div id="defaultAccordionOne" class="collapse show" aria-labelledby="headingOne1" data-bs-parent="#filters">
                    <div class="card-body">
                        <form action="" class="row" method="POST" id="zoneForm">
                            @csrf                            
                            <div class="col-12 col-sm-4 mb-3 mb-lg-0">
                                <label class="form-label" for="destinationID">Selecciona un destino</label>
                                <select name="destination_id" class="form-control" id="destinationID">
                                    <option value="">Selecciona una opción</option>
                                    @if (sizeof($destinations) >= 1)
                                        @foreach ($destinations as $destination)
                                            <option {{ isset($_REQUEST['destination_id']) && $_REQUEST['destination_id'] == $destination->id ? 'selected' : '' }} value="{{ $destination->id }}">{{ $destination->name }}</option>
                                        @endforeach                                        
                                    @endif                                
                                </select>
                            </div>
                            <div class="col-12 col-sm-4 align-self-end">
                                <button type="submit" class="btn btn-primary w-100 py-2" id="btnSendZone">Buscar</button>
                            </div>
                            <div class="col-12 col-sm-4 align-self-end">
                                <a href="{{ route('enterprises.zones.create', [( isset($zones->id) ? $zones->id : 0 )]) }}" class="btn btn-primary w-100 py-2"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Agregar una zona</a>
                            </div>
                        </form>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="alert alert-icon-left alert-light-warning alert-dismissible fade show mb-4" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <strong>Información:</strong> Tomar en cuenta que una zona, es <strong>primario</strong> solo cuando es aereopuerto.
            </div>

            <div class="alert alert-icon-left alert-light-danger alert-dismissible fade show mb-4" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <strong>Importante:</strong> cuando se crea una zona es <strong>necesario dar click</strong> al icono de mapa, para poder trazar las geocerca, si no causara problemas en las tarifas.
            </div>

            <div class="widget-content widget-content-area br-8">
                @if ($errors->any())
                    <div class="alert alert-light-primary alert-dismissible fade show border-0 mb-4" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x close" data-bs-dismiss="alert"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-light-success alert-dismissible fade show border-0 mb-4" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('danger'))
                    <div class="alert alert-light-danger alert-dismissible fade show border-0 mb-4" role="alert">
                        {{ session('danger') }}
                    </div>
                @endif
                
                <table id="dataZones" class="table table-rendering dt-table-hover" style="width:100%" data-button='<?=json_encode($buttons)?>'>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Destino</th>
                            <th>Nombre de zona</th>
                            <th class="text-center">Primario</th>
                            <th class="text-center">Estatus</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(sizeof($zones->zones_enterprises) >= 1)
                            @foreach($zones->zones_enterprises as $key => $value)
                                <tr>
                                    <td>{{ $value->id }}</td>
                                    <td>{{ $value->destination->name }}</td>
                                    <td>{{ $value->name }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light-{{ ( $value->is_primary == 1 ) ? 'success' : 'danger' }}">{{ ( $value->is_primary == 1 ) ? 'Sí' : 'No' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light-{{ ( $value->status == 1 ) ? 'success' : 'danger' }}">{{ ( $value->status == 1 ) ? 'Activo' : 'Inactivo' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a class="btn btn-primary" href="{{ route('enterprises.zones.edit', [$value->id]) }}" style="font-size: 13px;">Editar</a>
                                        <button class="btn btn-primary" onclick="getPoints(event, {{ $value->destination_id }}, {{ $value->id }}, {{ $value->enterprise_id }} )"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg></button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-zones.map/>
@endsection
