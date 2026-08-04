<?php

namespace App\Repositories\Settings;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Models\Enterprise;
use App\Models\ZonesEnterprise;
use App\Models\RatesEnterprise;
use App\Models\Destination;
use App\Models\DestinationService;

class RatesEnterpriseRepository
{
    public function index($request, $id = 0)
    {
        $enterprise = Enterprise::select([
                'id',
                'names',
                'is_rates_iva',
                'currency',
            ])
            ->with([
                'zones_enterprises' => function ($query) {
                    $query->select([
                        'id',
                        'enterprise_id',
                        'destination_id',
                        'name',
                        'is_primary',
                        'status',
                        'iata_code',
                        'cut_off',
                        'cut_off_operation',
                        'distance',
                        'time',
                    ]);
                },
            ])
            ->with([
                'rates_enterprises' => function ($query) use ($request) {
                    $query->select()
                        ->when(
                            $request->filled('enterprise_id'),
                            function ($q) use ($request) {
                                $q->where(
                                    'enterprise_id',
                                    $request->enterprise_id
                                );
                            }
                        )
                        ->when(
                            $request->filled('destination_id'),
                            function ($q) use ($request) {
                                $q->where(
                                    'destination_id',
                                    $request->destination_id
                                );
                            }
                        )
                        ->when(
                            $request->filled('destination_service_id'),
                            function ($q) use ($request) {
                                $q->where(
                                    'destination_service_id',
                                    $request->destination_service_id
                                );
                            }
                        )
                        ->when(
                            $request->filled('zone_one'),
                            function ($q) use ($request) {
                                $q->where(
                                    'zone_one',
                                    $request->zone_one
                                );
                            }
                        )
                        ->when(
                            $request->filled('zone_two'),
                            function ($q) use ($request) {
                                $q->where(
                                    'zone_two',
                                    $request->zone_two
                                );
                            }
                        )
                        ->with([
                            'enterprise' => function ($q) {
                                $q->select([
                                    'id',
                                    'names',
                                ]);
                            },
                        ])
                        ->with([
                            'destination' => function ($q) {
                                $q->select([
                                    'id',
                                    'name',
                                ]);
                            },
                        ])
                        ->with([
                            'destination_service' => function ($q) {
                                $q->select([
                                    'id',
                                    'name',
                                    'price_type',
                                ]);
                            },
                        ])
                        ->with([
                            'zoneOne' => function ($q) {
                                $q->select([
                                    'id',
                                    'name',
                                ]);
                            },
                        ])
                        ->with([
                            'zoneTwo' => function ($q) {
                                $q->select([
                                    'id',
                                    'name',
                                ]);
                            },
                        ]);
                },
            ])
            ->find($id);

        try {
            return view('settings.rates_enterprise.index', [
                'breadcrumbs' => [
                    [
                        'route' => route('enterprises.index'),
                        'name' => 'Listado de empresas',
                        'active' => false,
                    ],
                    [
                        'route' => '',
                        'name' => 'Tarifas de la empresa: '
                            . (
                                isset($enterprise->names)
                                    ? $enterprise->names
                                    : 'NO DEFINIDO'
                            ),
                        'active' => true,
                    ],
                ],
                'enterprise' => $enterprise,
                'destinations' => Destination::all(),
                'enterprises' => Enterprise::where(
                        'type_enterprise',
                        'CUSTOMER'
                    )
                    ->whereNull('deleted_at')
                    ->get(),
            ]);
        } catch (Exception $e) {
            return back()->with(
                'danger',
                'Error al obtener las tarifas: ' . $e->getMessage()
            );
        }
    }

    public function create($request, $id = 0)
    {
        $enterprise = Enterprise::select([
                'id',
                'names',
            ])
            ->find($id);

        try {
            return view('settings.rates_enterprise.new', [
                'breadcrumbs' => [
                    [
                        'route' => route('enterprises.index'),
                        'name' => 'Listado de empresas',
                        'active' => false,
                    ],
                    [
                        'route' => route(
                            'enterprises.rates.index',
                            [
                                isset($enterprise->id)
                                    ? $enterprise->id
                                    : 0,
                            ]
                        ),
                        'name' => 'Tarifa de la empresa: '
                            . (
                                isset($enterprise->names)
                                    ? $enterprise->names
                                    : 'NO DEFINIDO'
                            ),
                        'active' => false,
                    ],
                    [
                        'route' => '',
                        'name' => 'Crear nueva tarifa',
                        'active' => true,
                    ],
                ],
                'enterprise' => $enterprise,
                'destinations' => Destination::select([
                        'id',
                        'name',
                    ])
                    ->get(),
            ]);
        } catch (Exception $e) {
            return back()->with(
                'danger',
                'Error al abrir el formulario: ' . $e->getMessage()
            );
        }
    }

    public function store($request, $id = 0)
    {
        try {
            DB::beginTransaction();

            $rate = new RatesEnterprise();

            $rate->enterprise_id = $request->enterprise_id;
            $rate->destination_service_id =
                $request->destination_service_id;
            $rate->destination_id = $request->destination_id;
            $rate->zone_one = $request->zone_one;
            $rate->zone_two = $request->zone_two;

            $rate->one_way = isset($request->one_way)
                ? $request->one_way
                : '0.00';

            $rate->ow_12 = isset($request->ow_12)
                ? $request->ow_12
                : '0.00';

            $rate->ow_37 = isset($request->ow_37)
                ? $request->ow_37
                : '0.00';

            $rate->up_8_ow = isset($request->up_8_ow)
                ? $request->up_8_ow
                : '0.00';

            /*
            |--------------------------------------------------------------------------
            | Costos operativos por rango de pasajeros
            |--------------------------------------------------------------------------
            */

            $rate->operating_cost_1_6 =
                isset($request->operating_cost_1_6)
                && $request->operating_cost_1_6 !== ''
                    ? $request->operating_cost_1_6
                    : '0.00';

            $rate->operating_cost_7_10 =
                isset($request->operating_cost_7_10)
                && $request->operating_cost_7_10 !== ''
                    ? $request->operating_cost_7_10
                    : '0.00';

            $rate->operating_cost_11_15 =
                isset($request->operating_cost_11_15)
                && $request->operating_cost_11_15 !== ''
                    ? $request->operating_cost_11_15
                    : '0.00';

            $rate->operating_cost_16_22 =
                isset($request->operating_cost_16_22)
                && $request->operating_cost_16_22 !== ''
                    ? $request->operating_cost_16_22
                    : '0.00';

            /*
            |--------------------------------------------------------------------------
            | Campo anterior conservado temporalmente
            |--------------------------------------------------------------------------
            */

            $rate->operating_cost = isset($request->operating_cost)
                && $request->operating_cost !== ''
                    ? $request->operating_cost
                    : '0.00';

            $rate->save();

            DB::commit();

            return redirect()
                ->route(
                    'enterprises.rates.index',
                    [$rate->enterprise_id]
                )
                ->with(
                    'success',
                    'Tarifa creada correctamente.'
                );
        } catch (Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'danger',
                    'Error al crear la tarifa: '
                        . $e->getMessage()
                );
        }
    }

    public function edit($request, $id = 0)
    {
        $rate = RatesEnterprise::select()
            ->with([
                'enterprise' => function ($query) {
                    $query->select([
                        'id',
                        'names',
                    ]);
                },
            ])
            ->find($id);

        try {
            return view('settings.rates_enterprise.new', [
                'breadcrumbs' => [
                    [
                        'route' => route('enterprises.index'),
                        'name' => 'Listado de empresas',
                        'active' => false,
                    ],
                    [
                        'route' => route(
                            'enterprises.rates.index',
                            [
                                isset($rate->enterprise->id)
                                    ? $rate->enterprise->id
                                    : 0,
                            ]
                        ),
                        'name' => 'Sitios de la empresa: '
                            . (
                                isset($rate->enterprise->names)
                                    ? $rate->enterprise->names
                                    : 'NO DEFINIDO'
                            ),
                        'active' => false,
                    ],
                    [
                        'route' => '',
                        'name' => 'Actualizar tarifa',
                        'active' => true,
                    ],
                ],
                'rate' => $rate,
                'destinations' => Destination::all(),
            ]);
        } catch (Exception $e) {
            return back()->with(
                'danger',
                'Error al abrir la tarifa: '
                    . $e->getMessage()
            );
        }
    }

    public function update($request, $id = 0)
    {
        try {
            DB::beginTransaction();

            $rate = RatesEnterprise::find($id);

            if (!$rate) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->with(
                        'danger',
                        'La tarifa no fue encontrada.'
                    );
            }

            $rate->destination_service_id =
                $request->destination_service_id;
            $rate->destination_id = $request->destination_id;
            $rate->zone_one = $request->zone_one;
            $rate->zone_two = $request->zone_two;

            $rate->one_way = isset($request->one_way)
                ? $request->one_way
                : '0.00';

            $rate->ow_12 = isset($request->ow_12)
                ? $request->ow_12
                : '0.00';

            $rate->ow_37 = isset($request->ow_37)
                ? $request->ow_37
                : '0.00';

            $rate->up_8_ow = isset($request->up_8_ow)
                ? $request->up_8_ow
                : '0.00';

            /*
            |--------------------------------------------------------------------------
            | Costos operativos por rango de pasajeros
            |--------------------------------------------------------------------------
            */

            $rate->operating_cost_1_6 =
                isset($request->operating_cost_1_6)
                && $request->operating_cost_1_6 !== ''
                    ? $request->operating_cost_1_6
                    : '0.00';

            $rate->operating_cost_7_10 =
                isset($request->operating_cost_7_10)
                && $request->operating_cost_7_10 !== ''
                    ? $request->operating_cost_7_10
                    : '0.00';

            $rate->operating_cost_11_15 =
                isset($request->operating_cost_11_15)
                && $request->operating_cost_11_15 !== ''
                    ? $request->operating_cost_11_15
                    : '0.00';

            $rate->operating_cost_16_22 =
                isset($request->operating_cost_16_22)
                && $request->operating_cost_16_22 !== ''
                    ? $request->operating_cost_16_22
                    : '0.00';

            /*
            |--------------------------------------------------------------------------
            | Campo anterior conservado temporalmente
            |--------------------------------------------------------------------------
            */

            $rate->operating_cost = isset($request->operating_cost)
                && $request->operating_cost !== ''
                    ? $request->operating_cost
                    : '0.00';

            $rate->save();

            DB::commit();

            return redirect()
                ->route(
                    'enterprises.rates.index',
                    [$rate->enterprise_id]
                )
                ->with(
                    'success',
                    'Tarifa actualizada correctamente.'
                );
        } catch (Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'danger',
                    'Error al actualizar la tarifa: '
                        . $e->getMessage()
                );
        }
    }

    public function items($request)
    {
        $data = [
            'zones' => [],
            'services' => [],
        ];

        if (!$request->id) {
            return response()->json([
                'error' => [
                    'code' => 'required_params',
                    'message' => 'id is required',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $data['zones'] = ZonesEnterprise::where(
                'enterprise_id',
                $request->enterprise
            )
            ->where(
                'destination_id',
                $request->id
            )
            ->get();

        $data['services'] = DestinationService::select([
                'id',
                'name',
                'price_type',
            ])
            ->where(
                'destination_id',
                $request->id
            )
            ->get();

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function getRatesEnterprise($request)
    {
        $query = '';

        if ($request->service_id != 0) {
            $query = '
                AND rt.destination_service_id =
                    :destination_service_id
            ';
        }

        $params = [
            'destination_id' => $request->destination_id,
            'zone_one' => $request->from_id,
            'zone_two' => $request->to_id,
            'zone_three' => $request->to_id,
            'zone_four' => $request->from_id,
            'enterprise_id' => $request->enterprise_id,
        ];

        if ($request->service_id != 0) {
            $params['destination_service_id'] =
                $request->service_id;
        }

        $rates = DB::select("
            SELECT
                ds.name AS service_name,
                ds.price_type,
                rt.*,
                zoneOne.name AS from_name,
                zoneTwo.name AS to_name
            FROM rates_enterprises AS rt

            LEFT JOIN destination_services AS ds
                ON ds.id = rt.destination_service_id

            LEFT JOIN enterprises AS e
                ON e.id = rt.enterprise_id

            LEFT JOIN zones_enterprises AS zoneOne
                ON zoneOne.id = rt.zone_one

            LEFT JOIN zones_enterprises AS zoneTwo
                ON zoneTwo.id = rt.zone_two

            WHERE rt.destination_id = :destination_id

              AND (
                    (
                        rt.zone_one = :zone_one
                        AND rt.zone_two = :zone_two
                    )
                    OR
                    (
                        rt.zone_one = :zone_three
                        AND rt.zone_two = :zone_four
                    )
              )

              AND e.id = :enterprise_id

              {$query}
        ", $params);

        $data = [
            'destination_data' => $request->destination_id,
            'from_data' => [],
            'to_data' => [],
            'enterprise_data' => [],
        ];

        if (
            sizeof($rates) <= 0
            && $request->service_id != 0
        ) {
            $fromZone = ZonesEnterprise::find(
                $request->from_id
            );

            $toZone = ZonesEnterprise::find(
                $request->to_id
            );

            $enterprise = Enterprise::find(
                $request->enterprise_id
            );

            $service = DestinationService::find(
                $request->service_id
            );

            $data['from_data'] = $fromZone
                ? $fromZone->toArray()
                : [];

            $data['to_data'] = $toZone
                ? $toZone->toArray()
                : [];

            $data['enterprise_data'] = $enterprise
                ? $enterprise->toArray()
                : [];

            $data['service_data'] = $service
                ? $service->toArray()
                : [];
        }

        return view(
            'settings.rates_enterprise.list',
            compact('rates', 'data')
        );
    }

    public function newRates($request)
    {
        try {
            DB::beginTransaction();

            $rate = new RatesEnterprise();

            $rate->enterprise_id = $request->enterprise_id;
            $rate->destination_service_id =
                $request->destination_service_id;
            $rate->destination_id = $request->destination_id;
            $rate->zone_one = $request->zone_one;
            $rate->zone_two = $request->zone_two;

            $rate->one_way = isset($request->one_way)
                ? $request->one_way
                : '0.00';

            $rate->ow_12 = isset($request->ow_12)
                ? $request->ow_12
                : '0.00';

            $rate->ow_37 = isset($request->ow_37)
                ? $request->ow_37
                : '0.00';

            $rate->up_8_ow = isset($request->up_8_ow)
                ? $request->up_8_ow
                : '0.00';

            /*
            |--------------------------------------------------------------------------
            | Costos operativos por rango de pasajeros
            |--------------------------------------------------------------------------
            */

            $rate->operating_cost_1_6 =
                isset($request->operating_cost_1_6)
                && $request->operating_cost_1_6 !== ''
                    ? $request->operating_cost_1_6
                    : '0.00';

            $rate->operating_cost_7_10 =
                isset($request->operating_cost_7_10)
                && $request->operating_cost_7_10 !== ''
                    ? $request->operating_cost_7_10
                    : '0.00';

            $rate->operating_cost_11_15 =
                isset($request->operating_cost_11_15)
                && $request->operating_cost_11_15 !== ''
                    ? $request->operating_cost_11_15
                    : '0.00';

            $rate->operating_cost_16_22 =
                isset($request->operating_cost_16_22)
                && $request->operating_cost_16_22 !== ''
                    ? $request->operating_cost_16_22
                    : '0.00';

            /*
            |--------------------------------------------------------------------------
            | Campo anterior conservado temporalmente
            |--------------------------------------------------------------------------
            */

            $rate->operating_cost = isset($request->operating_cost)
                && $request->operating_cost !== ''
                    ? $request->operating_cost
                    : '0.00';

            $rate->save();

            DB::commit();

            return response()->json([
                'message' => 'Tarifa agregada con éxito',
                'success' => true,
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteRates($request)
    {
        try {
            DB::beginTransaction();

            $item = RatesEnterprise::find(
                $request->id
            );

            if ($item) {
                $item->delete();
            }

            DB::commit();

            return response()->json([
                'message' => 'Tarifa eliminada con éxito',
                'success' => true,
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateRates($request)
    {
        try {
            DB::beginTransaction();

            foreach ($request->price as $key => $value) {
                $item = RatesEnterprise::find(
                    $value['id']
                );

                if (!$item) {
                    continue;
                }

                $item->one_way =
                    isset($value['one_way'])
                    && $value['one_way'] !== ''
                        ? $value['one_way']
                        : '0.00';

                $item->ow_12 =
                    isset($value['ow_12'])
                    && $value['ow_12'] !== ''
                        ? $value['ow_12']
                        : '0.00';

                $item->ow_37 =
                    isset($value['ow_37'])
                    && $value['ow_37'] !== ''
                        ? $value['ow_37']
                        : '0.00';

                $item->up_8_ow =
                    isset($value['up_8_ow'])
                    && $value['up_8_ow'] !== ''
                        ? $value['up_8_ow']
                        : '0.00';

                /*
                |--------------------------------------------------------------------------
                | Costos operativos por rango de pasajeros
                |--------------------------------------------------------------------------
                */

                $item->operating_cost_1_6 =
                    isset($value['operating_cost_1_6'])
                    && $value['operating_cost_1_6'] !== ''
                        ? $value['operating_cost_1_6']
                        : '0.00';

                $item->operating_cost_7_10 =
                    isset($value['operating_cost_7_10'])
                    && $value['operating_cost_7_10'] !== ''
                        ? $value['operating_cost_7_10']
                        : '0.00';

                $item->operating_cost_11_15 =
                    isset($value['operating_cost_11_15'])
                    && $value['operating_cost_11_15'] !== ''
                        ? $value['operating_cost_11_15']
                        : '0.00';

                $item->operating_cost_16_22 =
                    isset($value['operating_cost_16_22'])
                    && $value['operating_cost_16_22'] !== ''
                        ? $value['operating_cost_16_22']
                        : '0.00';

                /*
                |--------------------------------------------------------------------------
                | Campo anterior conservado temporalmente
                |--------------------------------------------------------------------------
                */

                $item->operating_cost =
                    isset($value['operating_cost'])
                    && $value['operating_cost'] !== ''
                        ? $value['operating_cost']
                        : $item->operating_cost;

                $item->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Tarifas actualizadas con éxito',
                'success' => true,
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
