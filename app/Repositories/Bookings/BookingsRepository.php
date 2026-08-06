<?php

namespace App\Repositories\Bookings;

// TRAITS
use App\Traits\ApiTrait2;

class BookingsRepository
{
    use ApiTrait2;

    public function ReservationDetail($request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validar sesión de reservación
        |--------------------------------------------------------------------------
        */

        if (!session()->has('reservation')) {
            return redirect()->route('dashboard');
        }

        if (
            session()->has('reservation')
            && session()->get('reservation_time') < now()
        ) {
            session()->forget('reservation');
            session()->forget('reservation_time');

            return redirect()->route('dashboard');
        }

        $rez = session()->get('reservation');

        /*
        |--------------------------------------------------------------------------
        | Enlaces de pago
        |--------------------------------------------------------------------------
        */

        $paymentLinks = [
            'PAYPAL' => null,
            'STRIPE' => null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Pendiente en línea
        |--------------------------------------------------------------------------
        |
        | La API ya debe devolver payments.pending calculado contra
        | pay_now_amount, no contra el total completo de la reservación.
        |
        | Ejemplo:
        |
        | Total de la reserva: 32 USD
        | Paga ahora:           1 USD
        | Pagado:               1 USD
        | Pendiente en línea:   0 USD
        |
        | En ese caso ya no se generan enlaces de pago.
        |
        */

        $onlinePending = isset($rez['payments']['pending'])
            ? round((float) $rez['payments']['pending'], 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad con respuestas antiguas de la API
        |--------------------------------------------------------------------------
        |
        | Si payments.pending no existe, calculamos el pendiente utilizando
        | pay_now_amount. Para reservaciones antiguas sin pay_now_amount,
        | se conserva el total completo como pago requerido.
        |
        */

        if (!isset($rez['payments']['pending'])) {
            $totalSales = isset($rez['sales']['total'])
                ? round((float) $rez['sales']['total'], 2)
                : 0;

            $totalPayments = isset($rez['payments']['total'])
                ? round((float) $rez['payments']['total'], 2)
                : 0;

            $payNowAmount = isset($rez['config']['pay_now_amount'])
                && $rez['config']['pay_now_amount'] !== null
                    ? round((float) $rez['config']['pay_now_amount'], 2)
                    : $totalSales;

            $onlinePending = round(
                max(
                    0,
                    $payNowAmount - $totalPayments
                ),
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Generar enlaces solamente si queda pago en línea pendiente
        |--------------------------------------------------------------------------
        */

        if ($onlinePending > 0) {
            /*
            |--------------------------------------------------------------------------
            | PayPal
            |--------------------------------------------------------------------------
            */

            $paymentData = [
                'type' => 'PAYPAL',
                'id' => $rez['config']['id'],
                'language' => app()->getLocale(),

                'success_url' => config('app.locale') === 'es'
                    ? route(
                        'process.success.es',
                        ['locale' => config('app.locale')]
                    )
                    : route('process.success'),

                'cancel_url' => config('app.locale') === 'es'
                    ? route(
                        'process.cancel.es',
                        ['locale' => config('app.locale')]
                    )
                    : route('process.cancel'),
            ];

            $paypal = ApiTrait2::paymentLink($paymentData);

            if (!isset($paypal['error'])) {
                $paymentLinks['PAYPAL'] = $paypal['url'];
            }

            /*
            |--------------------------------------------------------------------------
            | Stripe
            |--------------------------------------------------------------------------
            */

            $paymentData = [
                'type' => 'STRIPE',
                'id' => $rez['config']['id'],
                'language' => app()->getLocale(),

                'success_url' => config('app.locale') === 'es'
                    ? route(
                        'process.success.es',
                        ['locale' => config('app.locale')]
                    )
                    : route('process.success'),

                'cancel_url' => config('app.locale') === 'es'
                    ? route(
                        'process.cancel.es',
                        ['locale' => config('app.locale')]
                    )
                    : route('process.cancel'),
            ];

            $stripe = ApiTrait2::paymentLink($paymentData);

            if (!isset($stripe['error'])) {
                $paymentLinks['STRIPE'] = $stripe['url'];
            }
        }

        return view(
            'process.my-reservation',
            [
                'rez' => $rez,
                'payment_link' => $paymentLinks,
                'online_pending' => $onlinePending,
            ]
        );
    }
}
