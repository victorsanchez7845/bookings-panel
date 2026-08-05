@if(sizeof($rates) >= 1)

    <form id="editPriceForm">

        @if (auth()->user()->hasPermission(34))
            <button
                type="button"
                class="btn btn-success btnUpdateRates"
            >
                Actualizar Tarifas
            </button>
        @endif

        @foreach($rates as $key => $value)

            <div class="item">

                <input
                    type="hidden"
                    name="price[{{ $value->id }}][id]"
                    value="{{ $value->id }}"
                >

                <div class="top_">
                    <p>
                        <strong>Desde:</strong>
                        {{ $value->from_name }}
                    </p>

                    <p>
                        <strong>Hacia:</strong>
                        {{ $value->to_name }}
                    </p>

                    <p>
                        <strong>Servicio:</strong>
                        {{ $value->service_name }}
                    </p>
                </div>

                @if(
                    $value->price_type == "vehicle"
                    || $value->price_type == "shared"
                )

                    <div class="bottom_">

                        <div class="single_">

                            <div>
                                <p>One way</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][one_way]"
                                    value="{{ $value->one_way }}"
                                >
                            </div>

                            <div>
                                <p>Round Trip</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][round_trip]"
                                    value="{{ $value->round_trip }}"
                                >
                            </div>

                            <div>
                                <p>Costo operativo (1-6 pasajeros)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][operating_cost_1_6]"
                                    value="{{ $value->operating_cost_1_6 ?? 0.00 }}"
                                >
                            </div>

                            <div>
                                <p>Costo operativo (7-10 pasajeros)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][operating_cost_7_10]"
                                    value="{{ $value->operating_cost_7_10 ?? 0.00 }}"
                                >
                            </div>

                            <div>
                                <p>Costo operativo (11-15 pasajeros)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][operating_cost_11_15]"
                                    value="{{ $value->operating_cost_11_15 ?? 0.00 }}"
                                >
                            </div>

                            <div>
                                <p>Costo operativo (16-22 pasajeros)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][operating_cost_16_22]"
                                    value="{{ $value->operating_cost_16_22 ?? 0.00 }}"
                                >
                            </div>

                        </div>

                        @if (auth()->user()->hasPermission(35))
                            <button
                                class="btn btn-sm btn-danger"
                                type="button"
                                onclick="deleteItem({{ $value->id }})"
                                data-id="{{ $value->id }}"
                            >
                                Eliminar
                            </button>
                        @endif

                    </div>

                @endif

                @if($value->price_type == "passenger")

                    <div class="bottom_">

                        <div class="multiple_">

                            <div>
                                <p>One Way (1-2)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][ow_12]"
                                    value="{{ $value->ow_12 }}"
                                >
                            </div>

                            <div>
                                <p>Round Trip (1-2)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][rt_12]"
                                    value="{{ $value->rt_12 }}"
                                >
                            </div>

                            <div>
                                <p>One Way (3-7)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][ow_37]"
                                    value="{{ $value->ow_37 }}"
                                >
                            </div>

                            <div>
                                <p>Round Trip (3-7)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][rt_37]"
                                    value="{{ $value->rt_37 }}"
                                >
                            </div>

                            <div>
                                <p>Up OW (&gt; 8)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][up_8_ow]"
                                    value="{{ $value->up_8_ow }}"
                                >
                            </div>

                            <div>
                                <p>Up RT (&gt; 8)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][up_8_rt]"
                                    value="{{ $value->up_8_rt }}"
                                >
                            </div>

                            <div>
                                <p>Costo operativo (1-6 pasajeros)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][operating_cost_1_6]"
                                    value="{{ $value->operating_cost_1_6 ?? 0.00 }}"
                                >
                            </div>

                            <div>
                                <p>Costo operativo (7-10 pasajeros)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][operating_cost_7_10]"
                                    value="{{ $value->operating_cost_7_10 ?? 0.00 }}"
                                >
                            </div>

                            <div>
                                <p>Costo operativo (11-15 pasajeros)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][operating_cost_11_15]"
                                    value="{{ $value->operating_cost_11_15 ?? 0.00 }}"
                                >
                            </div>

                            <div>
                                <p>Costo operativo (16-22 pasajeros)</p>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    name="price[{{ $value->id }}][operating_cost_16_22]"
                                    value="{{ $value->operating_cost_16_22 ?? 0.00 }}"
                                >
                            </div>

                        </div>

                        @if (auth()->user()->hasPermission(35))
                            <button
                                class="btn btn-danger"
                                type="button"
                                onclick="deleteItem({{ $value->id }})"
                                data-id="{{ $value->id }}"
                            >
                                Eliminar
                            </button>
                        @endif

                    </div>

                @endif

            </div>

        @endforeach

        @if (auth()->user()->hasPermission(34))
            <button
                type="button"
                class="btn btn-success btnUpdateRates"
            >
                Actualizar Tarifas
            </button>
        @endif

    </form>

@else

    @if(
        isset($data['from_data'])
        && !empty($data['from_data'])
    )

        <form
            class="item"
            id="newPriceForm"
        >

            <input
                type="hidden"
                name="rate_group_id"
                value="{{ $data['rate_group_data']['id'] }}"
            >

            <input
                type="hidden"
                name="destination_service_id"
                value="{{ $data['service_data']['id'] }}"
            >

            <input
                type="hidden"
                name="destination_service_type"
                value="{{ $data['service_data']['price_type'] }}"
            >

            <input
                type="hidden"
                name="destination_id"
                value="{{ $data['destination_data'] }}"
            >

            <input
                type="hidden"
                name="zone_one"
                value="{{ $data['from_data']['id'] }}"
            >

            <input
                type="hidden"
                name="zone_two"
                value="{{ $data['to_data']['id'] }}"
            >

            <div class="top_">

                <p>
                    <strong>Desde:</strong>
                    {{ $data['from_data']['name'] }}
                </p>

                <p>
                    <strong>Hacia:</strong>
                    {{ $data['to_data']['name'] }}
                </p>

                <p>
                    <strong>Servicio:</strong>
                    {{ $data['service_data']['name'] }}
                </p>

                <p>
                    <strong>Grupo de tarifa:</strong>
                    ({{ $data['rate_group_data']['code'] }})
                    {{ $data['rate_group_data']['name'] }}
                </p>

            </div>

            @if(
                $data['service_data']['price_type'] == "vehicle"
                || $data['service_data']['price_type'] == "shared"
            )

                <div class="bottom_">

                    <div class="single_">

                        <div>
                            <p>One way</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="one_way"
                            >
                        </div>

                        <div>
                            <p>Round Trip</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="round_trip"
                            >
                        </div>

                        <div>
                            <p>Costo operativo (1-6 pasajeros)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="operating_cost_1_6"
                            >
                        </div>

                        <div>
                            <p>Costo operativo (7-10 pasajeros)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="operating_cost_7_10"
                            >
                        </div>

                        <div>
                            <p>Costo operativo (11-15 pasajeros)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="operating_cost_11_15"
                            >
                        </div>

                        <div>
                            <p>Costo operativo (16-22 pasajeros)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="operating_cost_16_22"
                            >
                        </div>

                    </div>

                    @if (auth()->user()->hasPermission(33))
                        <button
                            class="btn btn-sm btn-success"
                            type="button"
                            id="btn_add_rate"
                        >
                            Agregar Tarifa
                        </button>
                    @endif

                </div>

            @endif

            @if($data['service_data']['price_type'] == "passenger")

                <div class="bottom_">

                    <div class="multiple_">

                        <div>
                            <p>One Way (1-2)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="ow_12"
                            >
                        </div>

                        <div>
                            <p>Round Trip (1-2)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="rt_12"
                            >
                        </div>

                        <div>
                            <p>One Way (3-7)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="ow_37"
                            >
                        </div>

                        <div>
                            <p>Round Trip (3-7)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="rt_37"
                            >
                        </div>

                        <div>
                            <p>One Way (&gt; 8)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="up_8_ow"
                            >
                        </div>

                        <div>
                            <p>Round Trip (&gt; 8)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="up_8_rt"
                            >
                        </div>

                        <div>
                            <p>Costo operativo (1-6 pasajeros)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="operating_cost_1_6"
                            >
                        </div>

                        <div>
                            <p>Costo operativo (7-10 pasajeros)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="operating_cost_7_10"
                            >
                        </div>

                        <div>
                            <p>Costo operativo (11-15 pasajeros)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="operating_cost_11_15"
                            >
                        </div>

                        <div>
                            <p>Costo operativo (16-22 pasajeros)</p>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                value="0.00"
                                name="operating_cost_16_22"
                            >
                        </div>

                    </div>

                    @if (auth()->user()->hasPermission(33))
                        <button
                            class="btn btn-sm btn-success"
                            type="button"
                            id="btn_add_rate"
                        >
                            Agregar Tarifa
                        </button>
                    @endif

                </div>

            @endif

        </form>

    @else

        <div
            class="alert alert-primary alert-dismissible"
            role="alert"
            style="margin: 0;"
        >
            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

            <div class="alert-message">
                <strong>¡Lo sentimos!</strong>
                No hay tarifas que editar.
            </div>
        </div>

    @endif

@endif
