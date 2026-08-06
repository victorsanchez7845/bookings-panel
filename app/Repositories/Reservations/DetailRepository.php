<?php

namespace App\Repositories\Reservations;

use Exception;
use Illuminate\Support\Facades\DB;

// MODELS
use App\Models\Reservation;
use App\Models\ReservationsMedia;
use App\Models\ReservationsItem;
use App\Models\PaymentLink;

// TRAITS
use App\Traits\ApiTrait;
use App\Traits\FiltersTrait;
use App\Traits\QueryTrait;

class DetailRepository
{
    use ApiTrait, FiltersTrait, QueryTrait;

    /*
    |--------------------------------------------------------------------------
    | Estados de reservación
    |--------------------------------------------------------------------------
    */

    private const STATUS_PENDING = 'PENDING';
    private const STATUS_CANCELLED = 'CANCELLED';
    private const STATUS_DUPLICATED = 'DUPLICATED';
    private const STATUS_OPEN_CREDIT = 'OPENCREDIT';
    private const STATUS_QUOTATION = 'QUOTATION';
    private const STATUS_PAY_AT_ARRIVAL = 'PAY_AT_ARRIVAL';
    private const STATUS_CREDIT = 'CREDIT';
    private const STATUS_CONFIRMED = 'CONFIRMED';

    /**
     * Obtiene el detalle de una reservación.
     *
     * @param mixed $request
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function detail($request, $id)
    {
        /*
        |--------------------------------------------------------------------------
        | Datos financieros y estado inicial
        |--------------------------------------------------------------------------
        */

        $reservationData = [
            'status' => self::STATUS_PENDING,
            'total_sales' => 0,
            'total_payments' => 0,

            /*
             * Pago inicial requerido en línea.
             */
            'pay_now_amount' => 0,

            /*
             * Saldo que el cliente debe pagar cuando llegue.
             */
            'pay_at_arrival_amount' => 0,

            /*
             * Cantidad que todavía falta pagar en línea.
             */
            'online_pending' => 0,
        ];

        try {
            /*
            |--------------------------------------------------------------------------
            | Obtener reservación y relaciones
            |--------------------------------------------------------------------------
            */

            $reservation = $this->getReservationWithRelations($id);

            if (!$reservation) {
                throw new Exception('Reservation not found');
            }

            /*
            |--------------------------------------------------------------------------
            | Calcular ventas, pagos y saldos
            |--------------------------------------------------------------------------
            */

            $this->calculateTotals(
                $reservation,
                $reservationData
            );

            /*
            |--------------------------------------------------------------------------
            | Detectar tipos de traslado
            |--------------------------------------------------------------------------
            */

            $reservationData['transfer_types'] =
                $this->detectArrivalDeparture($reservation);

            /*
            |--------------------------------------------------------------------------
            | Determinar estado final
            |--------------------------------------------------------------------------
            */

            $reservationData['status'] =
                $this->determineReservationStatus(
                    $reservation,
                    $reservationData
                );

            return $this->buildReservationDetailView(
                $request,
                $id,
                $reservation,
                $reservationData
            );
        } catch (Exception $e) {
            return $this->buildErrorView(
                $request,
                $id,
                $reservationData
            );
        }
    }

    /**
     * Obtiene la reservación con todas sus relaciones.
     */
    protected function getReservationWithRelations(
        int $id
    ): ?Reservation {
        return Reservation::with([
            'destination.destination_services',

            'items' => $this->getItemsQuery(),

            'sales',
            'callCenterAgent',
            'payments',
            'refunds.user',
            'followUps',
            'site',
            'cancellationType',
            'originSale',
        ])->find($id);
    }

    /**
     * Construye la consulta para los items de reservación.
     */
    protected function getItemsQuery(): \Closure
    {
        return function ($query) {
            $query
                ->join(
                    'zones as zone_one',
                    'zone_one.id',
                    '=',
                    'reservations_items.from_zone'
                )
                ->join(
                    'zones as zone_two',
                    'zone_two.id',
                    '=',
                    'reservations_items.to_zone'
                )
                ->select(
                    'reservations_items.*',

                    'reservations_items.id as reservations_item_id',

                    'zone_one.name as from_zone_name',
                    'zone_one.is_primary as is_primary_from',

                    'zone_two.name as to_zone_name',
                    'zone_two.is_primary as is_primary_to',

                    DB::raw(
                        $this->getServiceTypeCase(
                            'zone_one',
                            'zone_two',
                            'final_service_type_one'
                        )
                    ),

                    DB::raw(
                        $this->getServiceTypeCase(
                            'zone_two',
                            'zone_one',
                            'final_service_type_two'
                        )
                    )
                );
        };
    }

    /**
     * Genera la expresión CASE para determinar el tipo de servicio.
     */
    protected function getServiceTypeCase(
        string $mainZone,
        string $otherZone,
        string $alias
    ): string {
        return "
            CASE
                WHEN {$mainZone}.is_primary = 1
                    THEN 'ARRIVAL'

                WHEN {$mainZone}.is_primary = 0
                    AND {$otherZone}.is_primary = 1
                    THEN 'DEPARTURE'

                WHEN {$mainZone}.is_primary = 0
                    AND {$otherZone}.is_primary = 0
                    THEN 'TRANSFER'

                ELSE 'ARRIVAL'
            END AS {$alias}
        ";
    }

    /**
     * Calcula ventas, pagos, anticipo y saldo al llegar.
     */
    protected function calculateTotals(
        Reservation $reservation,
        array &$data
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Total completo de la reservación
        |--------------------------------------------------------------------------
        */

        $data['total_sales'] = round(
            (float) $reservation->sales->sum('total'),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Total pagado en la moneda de la reservación
        |--------------------------------------------------------------------------
        */

        $data['total_payments'] = round(
            (float) $reservation->payments->reduce(
                function ($carry, $payment) {
                    $paymentTotal = (float) $payment->total;
                    $exchangeRate = (float) $payment->exchange_rate;

                    if (
                        $payment->operation === 'multiplication'
                    ) {
                        /*
                         * Un pago USD con TC 1 queda:
                         * total × 1.
                         */
                        return $carry
                            + (
                                $paymentTotal
                                * (
                                    $exchangeRate > 0
                                        ? $exchangeRate
                                        : 1
                                )
                            );
                    }

                    if (
                        $payment->operation === 'division'
                        && $exchangeRate > 0
                    ) {
                        return $carry
                            + (
                                $paymentTotal
                                / $exchangeRate
                            );
                    }

                    /*
                     * Compatibilidad para pagos antiguos sin operación.
                     */
                    return $carry + $paymentTotal;
                },
                0
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Pago inicial requerido
        |--------------------------------------------------------------------------
        |
        | Reservas nuevas:
        | Se utiliza reservations.pay_now_amount.
        |
        | Reservas antiguas:
        | Si pay_now_amount es NULL, se utiliza el total completo.
        |
        */

        if ($reservation->pay_now_amount !== null) {
            $data['pay_now_amount'] = round(
                max(
                    0,
                    min(
                        (float) $reservation->pay_now_amount,
                        $data['total_sales']
                    )
                ),
                2
            );
        } else {
            $data['pay_now_amount'] =
                $data['total_sales'];
        }

        /*
        |--------------------------------------------------------------------------
        | Pago al llegar
        |--------------------------------------------------------------------------
        */

        $data['pay_at_arrival_amount'] = round(
            max(
                0,
                $data['total_sales']
                - $data['pay_now_amount']
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Pendiente en línea
        |--------------------------------------------------------------------------
        |
        | Solo compara los pagos recibidos contra el anticipo requerido.
        |
        */

        $data['online_pending'] = round(
            max(
                0,
                $data['pay_now_amount']
                - $data['total_payments']
            ),
            2
        );
    }

    /**
     * Detecta ARRIVAL, DEPARTURE o TRANSFER.
     */
    protected function detectArrivalDeparture(
        Reservation $reservation
    ): array {
        $hasArrival = false;
        $hasDeparture = false;
        $hasTransfer = false;

        foreach ($reservation->items as $item) {
            if (
                (
                    isset($item->final_service_type_one)
                    && $item->final_service_type_one === 'ARRIVAL'
                )
                ||
                (
                    isset($item->final_service_type_two)
                    && $item->final_service_type_two === 'ARRIVAL'
                    && (int) $item->is_round_trip === 1
                )
            ) {
                $hasArrival = true;
            }

            if (
                (
                    isset($item->final_service_type_one)
                    && $item->final_service_type_one === 'DEPARTURE'
                )
                ||
                (
                    isset($item->final_service_type_two)
                    && $item->final_service_type_two === 'DEPARTURE'
                    && (int) $item->is_round_trip === 1
                )
            ) {
                $hasDeparture = true;
            }

            if (
                (
                    isset($item->final_service_type_one)
                    && $item->final_service_type_one === 'TRANSFER'
                )
                ||
                (
                    isset($item->final_service_type_two)
                    && $item->final_service_type_two === 'TRANSFER'
                )
            ) {
                $hasTransfer = true;
            }

            if (
                $hasArrival
                && $hasDeparture
                && $hasTransfer
            ) {
                break;
            }
        }

        return [
            'has_arrival' => $hasArrival,
            'has_departure' => $hasDeparture,
            'has_transfer' => $hasTransfer,
        ];
    }

    /**
     * Determina el estado de la reservación.
     */
    protected function determineReservationStatus(
        Reservation $reservation,
        array $data
    ): string {
        $totalSales = round(
            (float) $data['total_sales'],
            2
        );

        $totalPayments = round(
            (float) $data['total_payments'],
            2
        );

        $onlinePending = round(
            (float) $data['online_pending'],
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Estados especiales con prioridad
        |--------------------------------------------------------------------------
        */

        if (
            $reservation->is_cancelled
            && (int) $reservation->was_is_quotation === 1
        ) {
            return self::STATUS_CANCELLED;
        }

        if ($reservation->is_cancelled) {
            return self::STATUS_CANCELLED;
        }

        if ($reservation->is_duplicated) {
            return self::STATUS_DUPLICATED;
        }

        if ($reservation->open_credit) {
            return self::STATUS_OPEN_CREDIT;
        }

        if ($reservation->is_quotation) {
            return self::STATUS_QUOTATION;
        }

        /*
        |--------------------------------------------------------------------------
        | Campo antiguo de pago total al llegar
        |--------------------------------------------------------------------------
        |
        | Este estado se conserva solamente para reservas marcadas
        | explícitamente como pay_at_arrival.
        |
        */

        if (
            $reservation->pay_at_arrival
            && $totalPayments <= 0
        ) {
            return self::STATUS_PAY_AT_ARRIVAL;
        }

        /*
        |--------------------------------------------------------------------------
        | Reservaciones de crédito
        |--------------------------------------------------------------------------
        */

        if (
            $reservation->site
            && $reservation->site->is_cxc
            && (
                $totalPayments <= 0
                || $totalPayments < $totalSales
            )
        ) {
            return self::STATUS_CREDIT;
        }

        /*
        |--------------------------------------------------------------------------
        | Nueva lógica de anticipo
        |--------------------------------------------------------------------------
        |
        | La reserva se confirma cuando el cliente cubre pay_now_amount.
        | No es necesario que cubra el saldo que se pagará al llegar.
        |
        */

        return $onlinePending > 0
            ? self::STATUS_PENDING
            : self::STATUS_CONFIRMED;
    }

    /**
     * Construye la vista de detalle de la reservación.
     */
    protected function buildReservationDetailView(
        $request,
        int $id,
        Reservation $reservation,
        array $data
    ) {
        $queryOne = "
            AND rez.id = {$id}
            AND it.op_one_pickup
                BETWEEN :init_date_one AND :init_date_two
        ";

        $queryTwo = "
            AND rez.id = {$id}
            AND it.op_two_pickup
                BETWEEN :init_date_three AND :init_date_four
            AND it.is_round_trip = 1
        ";

        $operations = $this->queryOperations(
            $queryOne,
            $queryTwo,
            '',
            [
                'init' => '1900-01-01 00:00:00',
                'end' => '2099-12-31 23:59:59',
            ]
        );

        return view(
            'reservations.detail',
            [
                'breadcrumbs' => [
                    [
                        'route' => '',
                        'name' =>
                            'Detalle de reservación: '
                            . $id,
                        'active' => true,
                    ],
                ],

                'reservation' => $reservation,
                'data' => $data,
                'request' => $request->input(),
                'operations' => $operations,
            ]
        );
    }

    /**
     * Construye la vista cuando ocurre un error.
     */
    protected function buildErrorView(
        $request,
        int $id,
        array $data
    ) {
        return view(
            'reservations.detail',
            [
                'breadcrumbs' => [
                    [
                        'route' => '',
                        'name' =>
                            'Detalle de reservación: '
                            . $id,
                        'active' => true,
                    ],
                ],

                'reservation' => [],
                'data' => $data,
                'types_cancellations' => [],
                'request' => $request->input(),
            ]
        );
    }

    /**
     * Obtiene los medios asociados a una reservación.
     */
    public function getMedia($request)
    {
        $query = ReservationsMedia::where(
            'reservation_id',
            $request->id
        )->orderByRaw(
            'CASE WHEN `order` IS NULL THEN 1 ELSE 0 END, '
            . '`order` ASC, id DESC'
        );

        if (isset($request->type)) {
            $query->where(
                'type_media',
                'OPERATION'
            );
        }

        $media = $query->get();

        return view(
            'reservations.media',
            compact('media')
        );
    }

    /**
     * Genera un enlace de pago.
     */
    public function paymentLink($request)
    {
        $request->validate([
            'code' => [
                'required',
                'string',
            ],

            'email' => [
                'required',
                'string',
            ],

            'language' => [
                'required',
                'in:en,es',
            ],

            'type' =>
                'required|string|in:STRIPE,PAYPAL-V3,OPENPAY',

            'currency' => [
                'nullable',
                'in:MXN,USD',
            ],

            'amount' => [
                'nullable',
                'numeric',
            ],
        ]);

        $linkCode = $this->generateLinkCode();

        $link = env(
            'MAIN_CT_SITE_URL',
            'https://caribbean-transfers.com'
        );

        if ($request->language === 'es') {
            $link .= '/es';
        }

        $link .= '/payment-link/' . $linkCode;

        $reservationItem =
            ReservationsItem::where(
                'code',
                $request->code
            )->first();

        if (!$reservationItem) {
            throw new Exception(
                'No se encontró la reservación'
            );
        }

        $paymentLink = new PaymentLink();

        $paymentLink->reservation_id =
            $reservationItem->reservation_id;

        $paymentLink->link_code = $linkCode;
        $paymentLink->code = $request->code;
        $paymentLink->email = $request->email;
        $paymentLink->language = $request->language;
        $paymentLink->type = $request->type;
        $paymentLink->link = $link;

        if (
            $request->currency
            && $request->amount
        ) {
            $paymentLink->currency =
                $request->currency;

            $paymentLink->amount =
                $request->amount;
        }

        $paymentLink->save();

        return $paymentLink;
    }

    /**
     * Reordena los medios.
     */
    public function reorderMedia($request)
    {
        $request->validate([
            'order' => [
                'required',
                'array',
            ],

            'order.*.id' => [
                'required',
                'integer',
            ],

            'order.*.order' => [
                'required',
                'integer',
            ],
        ]);

        foreach ($request->order as $item) {
            ReservationsMedia::where(
                'id',
                $item['id']
            )->update([
                'order' => $item['order'],
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Genera un código único para el enlace de pago.
     */
    protected function generateLinkCode(
        int $length = 9
    ): string {
        $characters =
            '0123456789'
            . 'abcdefghijklmnopqrstuvwxyz'
            . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $charactersLength = strlen($characters);

        do {
            $bytes = random_bytes($length);
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[
                    ord($bytes[$i])
                    % $charactersLength
                ];
            }
        } while (
            PaymentLink::where(
                'link_code',
                $code
            )->exists()
        );

        return $code;
    }
}
