<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cupón operativo del proveedor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background:#eef3f8; font-family: Arial, Helvetica, sans-serif; color:#24364b;">

@php
    $services = $items ?? collect();

    $customerName = trim(
        ($reservation->client_first_name ?? '') . ' ' .
        ($reservation->client_last_name ?? '')
    );

    if ($customerName === '') {
        $customerName = 'No especificado';
    }

    $currency = $currency ?? ($reservation->currency ?? 'USD');

    $payAtArrival = (float) ($provider_balance ?? 0);
@endphp

<div style="width:100%; padding:24px 12px;">
    <div style="max-width:920px; margin:0 auto; background:#ffffff; border-radius:22px; overflow:hidden; box-shadow:0 8px 30px rgba(20,40,70,.08);">

        {{-- Header --}}
        <div style="background:#274f78; padding:28px 40px;">
            <h1 style="margin:0; font-size:26px; line-height:1.2; color:#ffffff; font-weight:700;">
                Cupón operativo del proveedor
            </h1>
        </div>

        <div style="padding:34px 40px 20px 40px;">

            {{-- Pasajero --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
                <tr>
                    <td style="font-size:16px; color:#5f7691; padding:0 0 8px 0; width:42%;">
                        Pasajero
                    </td>
                    <td style="font-size:18px; color:#24364b; font-weight:700; padding:0 0 8px 0;">
                        {{ $customerName }}
                    </td>
                </tr>
            </table>

            @foreach($services as $service)
                @php
                    $code = $service->code ?? 'No especificado';

                    $vehicle = $service->vehicle_name
                        ?? 'No especificado';

                    $passengers = $service->passengers
                        ?? 'No especificado';

                    $fromName = $service->from_name
                        ?: ($service->zone_from_name ?? 'No especificado');

                    $toName = $service->to_name
                        ?: ($service->zone_to_name ?? 'No especificado');

                    $flightInfo = trim(
                        (string) ($service->flight_number ?? '')
                    );

                    if ($flightInfo === '') {
                        $flightInfo = 'No especificado';
                    }

                    $arrivalComment = trim(
                        (string) ($service->op_one_comments ?? '')
                    );

                    $returnComment = trim(
                        (string) ($service->op_two_comments ?? '')
                    );

                    $pickupOne = $service->op_one_pickup ?? null;
                    $pickupTwo = $service->op_two_pickup ?? null;

                    $isRoundTrip = (int) ($service->is_round_trip ?? 0) === 1;

                    $pickupOneDate = $pickupOne
                        ? \Carbon\Carbon::parse($pickupOne)->format('d/m/Y')
                        : null;

                    $pickupOneTime = $pickupOne
                        ? \Carbon\Carbon::parse($pickupOne)->format('H:i')
                        : null;

                    $pickupTwoDate = $pickupTwo
                        ? \Carbon\Carbon::parse($pickupTwo)->format('d/m/Y')
                        : null;

                    $pickupTwoTime = $pickupTwo
                        ? \Carbon\Carbon::parse($pickupTwo)->format('H:i')
                        : null;
                @endphp

                <div style="border:1px solid #d7e1eb; border-radius:18px; overflow:hidden; margin-bottom:28px;">
                    <div style="background:#f2f7fc; padding:18px 24px; border-bottom:1px solid #d7e1eb;">
                        <div style="font-size:16px; color:#5f7691; font-weight:600;">
                            Reservación No.: {{ $code }}
                        </div>
                    </div>

                    <div style="padding:26px 24px 24px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:18px;">
                            <tr>
                                <td style="width:40%; padding:0 0 18px 0; font-size:16px; color:#5f7691; vertical-align:top;">Vehículo</td>
                                <td style="padding:0 0 18px 0; font-size:18px; color:#24364b; font-weight:700; vertical-align:top;">{{ $vehicle }}</td>
                            </tr>
                            <tr>
                                <td style="width:40%; padding:0 0 18px 0; font-size:16px; color:#5f7691; vertical-align:top;">Pasajeros</td>
                                <td style="padding:0 0 18px 0; font-size:18px; color:#24364b; font-weight:700; vertical-align:top;">{{ $passengers }}</td>
                            </tr>
                            <tr>
                                <td style="width:40%; padding:0 0 18px 0; font-size:16px; color:#5f7691; vertical-align:top;">Desde</td>
                                <td style="padding:0 0 18px 0; font-size:18px; color:#24364b; font-weight:700; vertical-align:top;">{{ $fromName }}</td>
                            </tr>
                            <tr>
                                <td style="width:40%; padding:0 0 18px 0; font-size:16px; color:#5f7691; vertical-align:top;">Hacia</td>
                                <td style="padding:0 0 18px 0; font-size:18px; color:#24364b; font-weight:700; vertical-align:top;">{{ $toName }}</td>
                            </tr>
                            <tr>
                                <td style="width:40%; padding:0; font-size:16px; color:#5f7691; vertical-align:top;">Aerolínea y vuelo</td>
                                <td style="padding:0; font-size:18px; color:#24364b; font-weight:700; vertical-align:top;">{{ $flightInfo }}</td>
                            </tr>
                        </table>

                        @if($arrivalComment !== '' || ($isRoundTrip && $returnComment !== ''))
                            <div style="background:#fff8e8; border:1px solid #f1d59b; border-left:5px solid #f0b43c; border-radius:14px; padding:16px 18px; margin:0 0 18px 0;">
                                <div style="font-size:15px; font-weight:700; color:#65470b; margin-bottom:10px;">
                                    Comentarios operativos
                                </div>

                                @if($arrivalComment !== '')
                                    <div style="font-size:16px; line-height:1.55; color:#4e3c17; margin-bottom:{{ $isRoundTrip && $returnComment !== '' ? '12px' : '0' }};">
                                        <strong>Servicio de ida:</strong><br>
                                        {!! nl2br(e($arrivalComment)) !!}
                                    </div>
                                @endif

                                @if($isRoundTrip && $returnComment !== '')
                                    <div style="font-size:16px; line-height:1.55; color:#4e3c17;">
                                        <strong>Servicio de regreso:</strong><br>
                                        {!! nl2br(e($returnComment)) !!}
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($pickupOne)
                            <div style="background:#edf4fb; border-left:5px solid #4591df; border-radius:14px; padding:18px 22px; margin-bottom:14px;">
                                <div style="font-size:16px; font-weight:700; color:#304d6a; margin-bottom:10px;">
                                    Llegada / servicio de ida
                                </div>
                                <div style="font-size:16px; color:#536c86; line-height:1.6;">
                                    <strong style="color:#304d6a;">Fecha:</strong> {{ $pickupOneDate ?? 'N/D' }}<br>
                                    <strong style="color:#304d6a;">Hora:</strong> {{ $pickupOneTime ?? 'N/D' }}
                                </div>
                            </div>
                        @endif

                        @if($isRoundTrip && $pickupTwo)
                            <div style="background:#edf4fb; border-left:5px solid #4591df; border-radius:14px; padding:18px 22px;">
                                <div style="font-size:16px; font-weight:700; color:#304d6a; margin-bottom:10px;">
                                    Regreso / servicio de salida
                                </div>
                                <div style="font-size:16px; color:#536c86; line-height:1.6;">
                                    <strong style="color:#304d6a;">Fecha:</strong> {{ $pickupTwoDate ?? 'N/D' }}<br>
                                    <strong style="color:#304d6a;">Hora:</strong> {{ $pickupTwoTime ?? 'N/D' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Saldo --}}
            <div style="background:#eef8ef; border:3px solid #4caf50; border-radius:18px; padding:28px 24px; text-align:center; margin:8px 0 28px 0;">
                <div style="font-size:18px; line-height:1.4; font-weight:700; color:#3b7840; text-transform:uppercase; letter-spacing:.4px;">
                    Saldo a cobrar al cliente al llegar
                </div>
                <div style="margin-top:12px; font-size:28px; line-height:1.2; font-weight:800; color:#165d2a;">
                    {{ number_format((float)$payAtArrival, 2) }} {{ $currency }}
                </div>
            </div>

            {{-- Aviso --}}
            <div style="background:#fff3f2; border-left:5px solid #df5b57; border-radius:14px; padding:18px 20px; margin-bottom:24px;">
                <div style="font-size:16px; line-height:1.6; color:#8b403e;">
                    <strong>Importante:</strong> este cupón contiene únicamente información operativa.
                    El contacto y seguimiento con el pasajero será gestionado por Taxi Dominicana.
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding:22px 30px 28px 30px; text-align:center; color:#7e93a8; font-size:15px;">
            Taxi Dominicana — Documento operativo interno
        </div>
    </div>
</div>

</body>
</html>
