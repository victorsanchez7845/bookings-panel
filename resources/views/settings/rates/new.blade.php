@extends('layout.app')

@section('title')
    Editar zona
@endsection

@push('Css')
    <link
        href="{{ mix('/assets/css/sections/enterprise_forms.min.css') }}"
        rel="preload"
        as="style"
    >
    <link
        href="{{ mix('/assets/css/sections/enterprise_forms.min.css') }}"
        rel="stylesheet"
    >
    <link
        href="{{ mix('/assets/css/sections/settings/rates.min.css') }}"
        rel="preload"
        as="style"
    >
    <link
        href="{{ mix('/assets/css/sections/settings/rates.min.css') }}"
        rel="stylesheet"
    >
@endpush

@push('Js')
    <script src="{{ mix('/assets/js/sections/settings/rates.min.js') }}"></script>
@endpush

@section('content')
    <div class="account-settings-container layout-top-spacing">
        <div class="account-content">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 layout-spacing">
                    <div class="section general-info">
                        <div class="info">

                            @if ($errors->any())
                                <div
                                    class="alert alert-light-danger alert-dismissible fade show border-0 mb-4"
                                    role="alert"
                                >
                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="alert"
                                        aria-label="Close"
                                    ></button>

                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (session('success'))
                                <div
                                    class="alert alert-light-success alert-dismissible fade show border-0 mb-4"
                                    role="alert"
                                >
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session('danger'))
                                <div
                                    class="alert alert-light-danger alert-dismissible fade show border-0 mb-4"
                                    role="alert"
                                >
                                    {{ session('danger') }}
                                </div>
                            @endif

                            <form
                                action="{{ !isset($rate)
                                    ? route(
                                        'enterprises.rates.web.store',
                                        [isset($enterprise->id) ? $enterprise->id : 0]
                                    )
                                    : route(
                                        'enterprises.rates.web.update',
                                        [isset($rate->id) ? $rate->id : 0]
                                    )
                                }}"
                                method="POST"
                                enctype="multipart/form-data"
                            >
                                @csrf

                                @if (isset($rate))
                                    @method('PUT')
                                @endif

                                <input
                                    type="hidden"
                                    name="destination_service_type"
                                    class="form-control"
                                    id="destinationServiceType"
                                    value=""
                                >

                                <div class="row">

                                    <div class="col-md-3">
                                        <div class="form-group mb-2">
                                            <label
                                                class="form-label"
                                                for="rateGroupID"
                                            >
                                                Selecciona Grupo de tarifa
                                            </label>

                                            <select
                                                name="rate_group_id"
                                                class="form-control"
                                                id="rateGroupID"
                                            >
                                                <option value="">
                                                    [Grupo de tarifa]
                                                </option>

                                                @if(sizeof($rate_groups) >= 1)
                                                    @foreach ($rate_groups as $value)
                                                        <option
                                                            value="{{ $value->id }}"
                                                            {{
                                                                old(
                                                                    'rate_group_id',
                                                                    isset($rate->rate_group_id)
                                                                        ? $rate->rate_group_id
                                                                        : null
                                                                ) == $value->id
                                                                    ? 'selected'
                                                                    : ''
                                                            }}
                                                        >
                                                            ({{ $value->code }})
                                                            {{ $value->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group mb-2">
                                            <label
                                                class="form-label"
                                                for="destinationID"
                                            >
                                                Selecciona destino
                                            </label>

                                            <select
                                                name="destination_id"
                                                class="form-control"
                                                id="destinationID"
                                            >
                                                <option value="0">
                                                    Selecciona el destino
                                                </option>

                                                @if (sizeof($destinations) >= 1)
                                                    @foreach ($destinations as $destination)
                                                        <option
                                                            value="{{ $destination->id }}"
                                                            {{
                                                                old(
                                                                    'destination_id',
                                                                    isset($rate->destination_id)
                                                                        ? $rate->destination_id
                                                                        : null
                                                                ) == $destination->id
                                                                    ? 'selected'
                                                                    : ''
                                                            }}
                                                        >
                                                            {{ $destination->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group mb-2">
                                            <label
                                                class="form-label"
                                                for="rateZoneOneId"
                                            >
                                                Zona de origen
                                            </label>

                                            <select
                                                name="zone_one"
                                                class="form-control"
                                                id="rateZoneOneId"
                                                data-code="{{ old(
                                                    'zone_one',
                                                    isset($rate->zone_one)
                                                        ? $rate->zone_one
                                                        : ''
                                                ) }}"
                                            >
                                                <option value="0">
                                                    Zona de origen
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group mb-2">
                                            <label
                                                class="form-label"
                                                for="rateZoneTwoId"
                                            >
                                                Zona de destino
                                            </label>

                                            <select
                                                name="zone_two"
                                                class="form-control"
                                                id="rateZoneTwoId"
                                                data-code="{{ old(
                                                    'zone_two',
                                                    isset($rate->zone_two)
                                                        ? $rate->zone_two
                                                        : ''
                                                ) }}"
                                            >
                                                <option value="0">
                                                    Zona de destino
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label
                                                class="form-label"
                                                for="rateServicesID"
                                            >
                                                Vehículo
                                            </label>

                                            <select
                                                name="destination_service_id"
                                                class="form-control"
                                                id="rateServicesID"
                                                data-code="{{ old(
                                                    'destination_service_id',
                                                    isset($rate->destination_service_id)
                                                        ? $rate->destination_service_id
                                                        : ''
                                                ) }}"
                                            >
                                                <option value="0">
                                                    Selecciona el servicio
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div
                                        id="rates-container"
                                        class="mt-3 mb-3"
                                    >
                                        <div class="item">
                                            <div class="bottom_">

                                                <div class="single_ d-none">

                                                    <div>
                                                        <p>One way</p>

                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            class="form-control"
                                                            name="one_way"
                                                            value="{{ old(
                                                                'one_way',
                                                                isset($rate->one_way)
                                                                    ? $rate->one_way
                                                                    : 0.00
                                                            ) }}"
                                                        >
                                                    </div>

                                                    <div>
                                                        <p>Round Trip</p>

                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            class="form-control"
                                                            name="round_trip"
                                                            value="{{ old(
                                                                'round_trip',
                                                                isset($rate->round_trip)
                                                                    ? $rate->round_trip
                                                                    : 0.00
                                                            ) }}"
                                                        >
                                                    </div>

                                                </div>

                                                <div class="multiple_ d-none">

                                                    <div>
                                                        <p>One Way (1-2)</p>

                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            class="form-control"
                                                            name="ow_12"
                                                            value="{{ old(
                                                                'ow_12',
                                                                isset($rate->ow_12)
                                                                    ? $rate->ow_12
                                                                    : 0.00
                                                            ) }}"
                                                        >
                                                    </div>

                                                    <div>
                                                        <p>Round Trip (1-2)</p>

                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            class="form-control"
                                                            name="rt_12"
                                                            value="{{ old(
                                                                'rt_12',
                                                                isset($rate->rt_12)
                                                                    ? $rate->rt_12
                                                                    : 0.00
                                                            ) }}"
                                                        >
                                                    </div>

                                                    <div>
                                                        <p>One Way (3-7)</p>

                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            class="form-control"
                                                            name="ow_37"
                                                            value="{{ old(
                                                                'ow_37',
                                                                isset($rate->ow_37)
                                                                    ? $rate->ow_37
                                                                    : 0.00
                                                            ) }}"
                                                        >
                                                    </div>

                                                    <div>
                                                        <p>Round Trip (3-7)</p>

                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            class="form-control"
                                                            name="rt_37"
                                                            value="{{ old(
                                                                'rt_37',
                                                                isset($rate->rt_37)
                                                                    ? $rate->rt_37
                                                                    : 0.00
                                                            ) }}"
                                                        >
                                                    </div>

                                                    <div>
                                                        <p>One Way (&gt; 8)</p>

                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            class="form-control"
                                                            name="up_8_ow"
                                                            value="{{ old(
                                                                'up_8_ow',
                                                                isset($rate->up_8_ow)
                                                                    ? $rate->up_8_ow
                                                                    : 0.00
                                                            ) }}"
                                                        >
                                                    </div>

                                                    <div>
                                                        <p>Round Trip (&gt; 8)</p>

                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            class="form-control"
                                                            name="up_8_rt"
                                                            value="{{ old(
                                                                'up_8_rt',
                                                                isset($rate->up_8_rt)
                                                                    ? $rate->up_8_rt
                                                                    : 0.00
                                                            ) }}"
                                                        >
                                                    </div>

                                                </div>

                                                <div class="costOperative d-none">

                                                    <div class="row">

                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label
                                                                    class="form-label"
                                                                    for="operatingCost16"
                                                                >
                                                                    Costo operativo (1-6 pasajeros)
                                                                </label>

                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    class="form-control"
                                                                    id="operatingCost16"
                                                                    name="operating_cost_1_6"
                                                                    value="{{ old(
                                                                        'operating_cost_1_6',
                                                                        isset($rate->operating_cost_1_6)
                                                                            ? $rate->operating_cost_1_6
                                                                            : 0.00
                                                                    ) }}"
                                                                >
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label
                                                                    class="form-label"
                                                                    for="operatingCost710"
                                                                >
                                                                    Costo operativo (7-10 pasajeros)
                                                                </label>

                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    class="form-control"
                                                                    id="operatingCost710"
                                                                    name="operating_cost_7_10"
                                                                    value="{{ old(
                                                                        'operating_cost_7_10',
                                                                        isset($rate->operating_cost_7_10)
                                                                            ? $rate->operating_cost_7_10
                                                                            : 0.00
                                                                    ) }}"
                                                                >
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label
                                                                    class="form-label"
                                                                    for="operatingCost1115"
                                                                >
                                                                    Costo operativo (11-15 pasajeros)
                                                                </label>

                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    class="form-control"
                                                                    id="operatingCost1115"
                                                                    name="operating_cost_11_15"
                                                                    value="{{ old(
                                                                        'operating_cost_11_15',
                                                                        isset($rate->operating_cost_11_15)
                                                                            ? $rate->operating_cost_11_15
                                                                            : 0.00
                                                                    ) }}"
                                                                >
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label
                                                                    class="form-label"
                                                                    for="operatingCost1622"
                                                                >
                                                                    Costo operativo (16-22 pasajeros)
                                                                </label>

                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    class="form-control"
                                                                    id="operatingCost1622"
                                                                    name="operating_cost_16_22"
                                                                    value="{{ old(
                                                                        'operating_cost_16_22',
                                                                        isset($rate->operating_cost_16_22)
                                                                            ? $rate->operating_cost_16_22
                                                                            : 0.00
                                                                    ) }}"
                                                                >
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <input
                                                        type="hidden"
                                                        name="operating_cost"
                                                        value="{{ old(
                                                            'operating_cost',
                                                            isset($rate->operating_cost)
                                                                ? $rate->operating_cost
                                                                : 0.00
                                                        ) }}"
                                                    >

                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 d-flex justify-content-between">

                                        <a
                                            class="btn btn-danger"
                                            href="{{ route(
                                                'enterprises.rates.web.index',
                                                [
                                                    isset($enterprise->id)
                                                        ? $enterprise->id
                                                        : 2
                                                ]
                                            ) }}"
                                        >
                                            Cancelar
                                        </a>

                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                        >
                                            {{ !isset($rate) ? 'Guardar' : 'Actualizar' }}
                                        </button>

                                    </div>

                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
