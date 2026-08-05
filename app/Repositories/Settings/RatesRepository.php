<?php

namespace App\Repositories\Settings;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Models\Enterprise;
use App\Models\Zones;
use App\Models\RatesTransfer;
use App\Models\Destination;
use App\Models\DestinationService;
use App\Models\RatesGroup;

class RatesRepository
{
    public function index($request, $id = 0)
    {
        $enterprise = Enterprise::select([
            'id',
            'names',
        ])->find($id);

        $query = RatesTransfer::select()
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

        if ($request->filled('destination_id')) {
            $query->where(
                'destination_id',
                $request->destination_id
            );
        }

        if ($request->filled('destination_service_id')) {
            $query->where(
                'destination_service_id',
                $request->destination_service_id
            );
        }

        if ($request->filled('zone_one')) {
            $query->where(
                'zone_one',
                $request->zone_one
            );
        }

        if ($request->filled('zone_two')) {
            $query->where(
                'zone_two',
                $request->zone_two
            );
        }

        if ($request->filled('rate_group_id')) {
            $query->where(
                'rate_group_id',
                $request->rate_group_id
            );
        }

        $rates = $query->get();

        try {
            return view('settings.rates.index', [
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
                'rates' => $rates,
                'destinations' => Destination::all(),
                'rate_groups' => RatesGroup::all(),
            ]);
        } catch (Exception $e) {
            return back()->with(
                'danger',
                'Error al cargar las tarifas: '
                    . $e->getMessage()
            );
        }
    }

    public function create($request, $id = 0)
    {
        $enterprise = Enterprise::select([
            'id',
            'names',
        ])->find($id);

        try {
            return view('settings.rates.new', [
                'breadcrumbs' => [
                    [
                        'route' => route('enterprises.index'),
                        'name' => 'Listado de empresas',
                        'active' => false,
                    ],
                    [
                        'route' => route(
                            'enterprises.rates.web.index',
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
                ])->get(),
                'rate_groups' => RatesGroup::all(),
            ]);
        } catch (Exception $e) {
            return back()->with(
                'danger',
                'Error al abrir el formulario: '
                    . $e->getMessage()
            );
        }
    }

    public function store($request, $id = 0)
    {
        try {
            DB::beginTransaction();

            $rate = new RatesTransfer();

            $rate->rate_group_id = $request->rate_group_id;
            $rate->destination_service_id =
                $request->destination_service_id;
            $rate->destination_id = $request->destination_id;
            $rate->zone_one = $request->zone_one;
            $rate->zone_two = $request->zone_two;

            $this->assignPublicPricesFromRequest(
                $rate,
                $request
            );

            $this->assignOperatingCostsFromRequest(
                $rate,
                $request
            );

            $rate->save();

            DB::commit();

            return redirect()
                ->route(
                    'enterprises.rates.web.index',
                    [2]
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
        $rate = RatesTransfer::select()->find($id);

        $enterprise = Enterprise::select([
            'id',
            'names',
        ])->find(2);

        try {
            return view('settings.rates.new', [
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
                        'name' => 'Sitios de la empresa: '
                            . (
                                isset($enterprise->names)
                                    ? $enterprise->names
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
                'destinations' => Destination::select([
                    'id',
                    'name',
                ])->get(),
                'rate_groups' => RatesGroup::all(),
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

            $rate = RatesTransfer::find($id);

            if (!$rate) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->with(
                        'danger',
                        'La tarifa no fue encontrada.'
                    );
            }

            $rate->rate_group_id = $request->rate_group_id;
            $rate->destination_service_id =
                $request->destination_service_id;
            $rate->destination_id = $request->destination_id;
            $rate->zone_one = $request->zone_one;
            $rate->zone_two = $request->zone_two;

            $this->assignPublicPricesFromRequest(
                $rate,
                $request
            );

            $this->assignOperatingCostsFromRequest(
                $rate,
                $request
            );

            $rate->save();

            DB::commit();

            return redirect()
                ->route(
                    'enterprises.rates.web.index',
                    [2]
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

        $data['zones'] = Zones::where(
            'destination_id',
            $request->id
        )->get();

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

    public function getRates($request)
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
            'rate_group' => $request->rate_group,
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

            FROM rates_transfers AS rt

            LEFT JOIN destination_services AS ds
                ON ds.id = rt.destination_service_id

            LEFT JOIN rates_groups AS rg
                ON rg.id = rt.rate_group_id

            LEFT JOIN zones AS zoneOne
                ON zoneOne.id = rt.zone_one

            LEFT JOIN zones AS zoneTwo
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

              AND rg.id = :rate_group

              {$query}
        ", $params);

        $data = [
            'destination_data' => $request->destination_id,
            'from_data' => [],
            'to_data' => [],
            'rate_group_data' => [],
        ];

        if (
            sizeof($rates) <= 0
            && $request->service_id != 0
        ) {
            $fromZone = Zones::find($request->from_id);
            $toZone = Zones::find($request->to_id);
            $rateGroup = RatesGroup::find(
                $request->rate_group
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

            $data['rate_group_data'] = $rateGroup
                ? $rateGroup->toArray()
                : [];

            $data['service_data'] = $service
                ? $service->toArray()
                : [];
        }

        return view(
            'settings.rates.list',
            compact('rates', 'data')
        );
    }

    public function newRates($request)
    {
        try {
            DB::beginTransaction();

            $rate = new RatesTransfer();

            $rate->rate_group_id = $request->rate_group_id;
            $rate->destination_service_id =
                $request->destination_service_id;
            $rate->destination_id = $request->destination_id;
            $rate->zone_one = $request->zone_one;
            $rate->zone_two = $request->zone_two;

            $this->assignPublicPricesFromRequest(
                $rate,
                $request
            );

            $this->assignOperatingCostsFromRequest(
                $rate,
                $request
            );

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

            $item = RatesTransfer::find(
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
                $item = RatesTransfer::find(
                    $value['id']
                );

                if (!$item) {
                    continue;
                }

                $this->assignPublicPricesFromArray(
                    $item,
                    $value
                );

                $this->assignOperatingCostsFromArray(
                    $item,
                    $value
                );

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

    private function assignPublicPricesFromRequest(
        RatesTransfer $rate,
        $request
    ): void {
        $rate->one_way = $this->requestValue(
            $request,
            'one_way'
        );

        $rate->round_trip = $this->requestValue(
            $request,
            'round_trip'
        );

        $rate->ow_12 = $this->requestValue(
            $request,
            'ow_12'
        );

        $rate->rt_12 = $this->requestValue(
            $request,
            'rt_12'
        );

        $rate->ow_37 = $this->requestValue(
            $request,
            'ow_37'
        );

        $rate->rt_37 = $this->requestValue(
            $request,
            'rt_37'
        );

        $rate->up_8_ow = $this->requestValue(
            $request,
            'up_8_ow'
        );

        $rate->up_8_rt = $this->requestValue(
            $request,
            'up_8_rt'
        );
    }

    private function assignOperatingCostsFromRequest(
        RatesTransfer $rate,
        $request
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Campo anterior conservado temporalmente
        |--------------------------------------------------------------------------
        */

        $rate->operating_cost = $this->requestValue(
            $request,
            'operating_cost'
        );

        /*
        |--------------------------------------------------------------------------
        | Costos operativos por rango de pasajeros
        |--------------------------------------------------------------------------
        */

        $rate->operating_cost_1_6 = $this->requestValue(
            $request,
            'operating_cost_1_6'
        );

        $rate->operating_cost_7_10 = $this->requestValue(
            $request,
            'operating_cost_7_10'
        );

        $rate->operating_cost_11_15 = $this->requestValue(
            $request,
            'operating_cost_11_15'
        );

        $rate->operating_cost_16_22 = $this->requestValue(
            $request,
            'operating_cost_16_22'
        );
    }

    private function assignPublicPricesFromArray(
        RatesTransfer $rate,
        array $value
    ): void {
        $rate->one_way = $this->arrayValue(
            $value,
            'one_way'
        );

        $rate->round_trip = $this->arrayValue(
            $value,
            'round_trip'
        );

        $rate->ow_12 = $this->arrayValue(
            $value,
            'ow_12'
        );

        $rate->rt_12 = $this->arrayValue(
            $value,
            'rt_12'
        );

        $rate->ow_37 = $this->arrayValue(
            $value,
            'ow_37'
        );

        $rate->rt_37 = $this->arrayValue(
            $value,
            'rt_37'
        );

        $rate->up_8_ow = $this->arrayValue(
            $value,
            'up_8_ow'
        );

        $rate->up_8_rt = $this->arrayValue(
            $value,
            'up_8_rt'
        );
    }

    private function assignOperatingCostsFromArray(
        RatesTransfer $rate,
        array $value
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Campo anterior: conservar el valor existente si no viene en el formulario
        |--------------------------------------------------------------------------
        */

        if (
            isset($value['operating_cost'])
            && $value['operating_cost'] !== ''
        ) {
            $rate->operating_cost =
                $value['operating_cost'];
        }

        /*
        |--------------------------------------------------------------------------
        | Costos operativos por rango de pasajeros
        |--------------------------------------------------------------------------
        */

        $rate->operating_cost_1_6 = $this->arrayValue(
            $value,
            'operating_cost_1_6'
        );

        $rate->operating_cost_7_10 = $this->arrayValue(
            $value,
            'operating_cost_7_10'
        );

        $rate->operating_cost_11_15 = $this->arrayValue(
            $value,
            'operating_cost_11_15'
        );

        $rate->operating_cost_16_22 = $this->arrayValue(
            $value,
            'operating_cost_16_22'
        );
    }

    private function requestValue(
        $request,
        string $field
    ): string {
        if (
            isset($request->{$field})
            && $request->{$field} !== ''
        ) {
            return (string) $request->{$field};
        }

        return '0.00';
    }

    private function arrayValue(
        array $value,
        string $field
    ): string {
        if (
            isset($value[$field])
            && $value[$field] !== ''
        ) {
            return (string) $value[$field];
        }

        return '0.00';
    }
}
