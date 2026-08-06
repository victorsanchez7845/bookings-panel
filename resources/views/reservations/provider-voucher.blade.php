@php
    $reservationId = $reservation->id ?? null;

    $clientName = trim(
        ($reservation->client_first_name ?? '') . ' ' .
        ($reservation->client_last_name ?? '')
    );

    $currency = $currency ?? ($reservation->currency ?? 'USD');

    $providerBalance = (float) ($provider_balance ?? 0);
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Cupón de proveedor - Reservación #{{ $reservationId }}
    </title>
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f3f6f9;
        font-family:Arial, Helvetica, sans-serif;
        color:#26384a;
    "
>
    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="
            width:100%;
            background:#f3f6f9;
            border-collapse:collapse;
        "
    >
        <tr>
            <td
                align="center"
                style="padding:24px 12px;"
            >
                <table
                    role="presentation"
                    width="680"
                    cellspacing="0"
                    cellpadding="0"
                    border="0"
                    style="
                        width:100%;
                        max-width:680px;
                        background:#ffffff;
                        border-collapse:separate;
                        border-spacing:0;
                        border-radius:14px;
                        overflow:hidden;
                        box-shadow:0 8px 24px rgba(34, 58, 82, 0.10);
                    "
                >
                    <tr>
                        <td
                            style="
                                padding:26px 28px;
                                background:#2f4a67;
                                color:#ffffff;
                            "
                        >
                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                                border="0"
                            >
                                <tr>
                                    <td>
                                        <h1
                                            style="
                                                margin:0;
                                                font-size:24px;
                                                line-height:1.25;
                                                font-weight:700;
                                            "
                                        >
                                            Cupón operativo del proveedor
                                        </h1>

                                        <p
                                            style="
                                                margin:8px 0 0;
                                                font-size:14px;
                                                line-height:1.4;
                                                color:#dbe8f4;
                                            "
                                        >
                                            Reservación #{{ $reservationId }}
                                        </p>
                                    </td>

                                    <td
                                        align="right"
                                        valign="middle"
                                        style="
                                            width:52px;
                                            font-size:34px;
                                        "
                                    >
                                        ✓
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:26px 28px;">
                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                                border="0"
                                style="
                                    width:100%;
                                    border-collapse:collapse;
                                    margin-bottom:22px;
                                "
                            >
                                <tr>
                                    <td
                                        style="
                                            width:42%;
                                            padding:7px 0;
                                            color:#6a7f93;
                                            font-size:14px;
                                        "
                                    >
                                        Pasajero
                                    </td>

                                    <td
                                        style="
                                            padding:7px 0;
                                            color:#26384a;
                                            font-size:14px;
                                            font-weight:700;
                                        "
                                    >
                                        {{ $clientName ?: 'No especificado' }}
                                    </td>
                                </tr>
                            </table>

                            @foreach($items as $index => $item)

                                @php
                                    $arrivalDate = $item->op_one_pickup
                                        ? \Carbon\Carbon::parse($item->op_one_pickup)
                                        : null;

                                    $returnDate = $item->op_two_pickup
                                        ? \Carbon\Carbon::parse($item->op_two_pickup)
                                        : null;

                                    $flightNumber = trim(
                                        (string) ($item->flight_number ?? '')
                                    );

                                    $fromName = $item->from_name
                                        ?: ($item->zone_from_name ?? 'No especificado');

                                    $toName = $item->to_name
                                        ?: ($item->zone_to_name ?? 'No especificado');

                                    $vehicleName = $item->vehicle_name
                                        ?? 'No especificado';
                                @endphp

                                <table
                                    role="presentation"
                                    width="100%"
                                    cellspacing="0"
                                    cellpadding="0"
                                    border="0"
                                    style="
                                        width:100%;
                                        margin-bottom:20px;
                                        border:1px solid #dfe6ed;
                                        border-radius:10px;
                                        border-collapse:separate;
                                        border-spacing:0;
                                        overflow:hidden;
                                    "
                                >
                                    <tr>
                                        <td
                                            style="
                                                padding:14px 18px;
                                                background:#f4f8fb;
                                                border-bottom:1px solid #dfe6ed;
                                            "
                                        >
                                            <strong
                                                style="
                                                    color:#2f4a67;
                                                    font-size:16px;
                                                "
                                            >
                                                Servicio {{ $index + 1 }}
                                            </strong>

                                            @if(!empty($item->code))
                                                <span
                                                    style="
                                                        display:block;
                                                        margin-top:4px;
                                                        color:#75899d;
                                                        font-size:12px;
                                                    "
                                                >
                                                    Código: {{ $item->code }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style="padding:18px;">
                                            <table
                                                role="presentation"
                                                width="100%"
                                                cellspacing="0"
                                                cellpadding="0"
                                                border="0"
                                                style="
                                                    width:100%;
                                                    border-collapse:collapse;
                                                "
                                            >
                                                <tr>
                                                    <td
                                                        style="
                                                            width:42%;
                                                            padding:6px 0;
                                                            color:#6a7f93;
                                                            font-size:14px;
                                                        "
                                                    >
                                                        Vehículo
                                                    </td>

                                                    <td
                                                        style="
                                                            padding:6px 0;
                                                            color:#26384a;
                                                            font-size:14px;
                                                            font-weight:700;
                                                        "
                                                    >
                                                        {{ $vehicleName }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td
                                                        style="
                                                            padding:6px 0;
                                                            color:#6a7f93;
                                                            font-size:14px;
                                                        "
                                                    >
                                                        Pasajeros
                                                    </td>

                                                    <td
                                                        style="
                                                            padding:6px 0;
                                                            color:#26384a;
                                                            font-size:14px;
                                                            font-weight:700;
                                                        "
                                                    >
                                                        {{ $item->passengers ?? 0 }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td
                                                        style="
                                                            padding:6px 0;
                                                            color:#6a7f93;
                                                            font-size:14px;
                                                        "
                                                    >
                                                        Desde
                                                    </td>

                                                    <td
                                                        style="
                                                            padding:6px 0;
                                                            color:#26384a;
                                                            font-size:14px;
                                                            font-weight:700;
                                                        "
                                                    >
                                                        {{ $fromName }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td
                                                        style="
                                                            padding:6px 0;
                                                            color:#6a7f93;
                                                            font-size:14px;
                                                        "
                                                    >
                                                        Hacia
                                                    </td>

                                                    <td
                                                        style="
                                                            padding:6px 0;
                                                            color:#26384a;
                                                            font-size:14px;
                                                            font-weight:700;
                                                        "
                                                    >
                                                        {{ $toName }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td
                                                        style="
                                                            padding:6px 0;
                                                            color:#6a7f93;
                                                            font-size:14px;
                                                        "
                                                    >
                                                        Aerolínea y vuelo
                                                    </td>

                                                    <td
                                                        style="
                                                            padding:6px 0;
                                                            color:#26384a;
                                                            font-size:14px;
                                                            font-weight:700;
                                                        "
                                                    >
                                                        {{ $flightNumber ?: 'No especificado' }}
                                                    </td>
                                                </tr>
                                            </table>

                                            <div
                                                style="
                                                    margin-top:16px;
                                                    padding:14px 16px;
                                                    background:#eef6ff;
                                                    border-left:4px solid #4d94d8;
                                                    border-radius:8px;
                                                "
                                            >
                                                <p
                                                    style="
                                                        margin:0 0 8px;
                                                        color:#2f4a67;
                                                        font-size:14px;
                                                        font-weight:700;
                                                    "
                                                >
                                                    Llegada / servicio de ida
                                                </p>

                                                <p
                                                    style="
                                                        margin:0;
                                                        color:#53697f;
                                                        font-size:14px;
                                                        line-height:1.5;
                                                    "
                                                >
                                                    @if($arrivalDate)
                                                        Fecha:
                                                        <strong>
                                                            {{ $arrivalDate->format('d/m/Y') }}
                                                        </strong>

                                                        <br>

                                                        Hora:
                                                        <strong>
                                                            {{ $arrivalDate->format('H:i') }}
                                                        </strong>
                                                    @else
                                                        Fecha y hora no especificadas
                                                    @endif
                                                </p>
                                            </div>

                                            @if(
                                                (int) ($item->is_round_trip ?? 0) === 1
                                                && $returnDate
                                            )
                                                <div
                                                    style="
                                                        margin-top:12px;
                                                        padding:14px 16px;
                                                        background:#fff8e8;
                                                        border-left:4px solid #d6a530;
                                                        border-radius:8px;
                                                    "
                                                >
                                                    <p
                                                        style="
                                                            margin:0 0 8px;
                                                            color:#5d4b1f;
                                                            font-size:14px;
                                                            font-weight:700;
                                                        "
                                                    >
                                                        Servicio de regreso
                                                    </p>

                                                    <p
                                                        style="
                                                            margin:0;
                                                            color:#6d603d;
                                                            font-size:14px;
                                                            line-height:1.5;
                                                        "
                                                    >
                                                        Fecha:
                                                        <strong>
                                                            {{ $returnDate->format('d/m/Y') }}
                                                        </strong>

                                                        <br>

                                                        Hora de pickup:
                                                        <strong>
                                                            {{ $returnDate->format('H:i') }}
                                                        </strong>
                                                    </p>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                            @endforeach

                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                                border="0"
                                style="
                                    width:100%;
                                    margin-top:8px;
                                    border:2px solid #55b84d;
                                    border-radius:12px;
                                    background:#effff3;
                                    border-collapse:separate;
                                    border-spacing:0;
                                "
                            >
                                <tr>
                                    <td
                                        align="center"
                                        style="padding:20px;"
                                    >
                                        <p
                                            style="
                                                margin:0 0 7px;
                                                color:#39763b;
                                                font-size:13px;
                                                font-weight:800;
                                                letter-spacing:0.5px;
                                                text-transform:uppercase;
                                            "
                                        >
                                            Saldo a cobrar al cliente al llegar
                                        </p>

                                        <p
                                            style="
                                                margin:0;
                                                color:#174e25;
                                                font-size:30px;
                                                line-height:1.2;
                                                font-weight:800;
                                            "
                                        >
                                            {{ number_format($providerBalance, 2) }}
                                            {{ $currency }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <div
                                style="
                                    margin-top:20px;
                                    padding:14px 16px;
                                    background:#fff5f5;
                                    border-left:4px solid #d95c5c;
                                    border-radius:8px;
                                "
                            >
                                <p
                                    style="
                                        margin:0;
                                        color:#874040;
                                        font-size:13px;
                                        line-height:1.5;
                                    "
                                >
                                    <strong>Importante:</strong>
                                    este cupón contiene únicamente información
                                    operativa. El contacto y seguimiento con el
                                    pasajero será gestionado por Taxi Dominicana.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td
                            align="center"
                            style="
                                padding:18px 24px;
                                background:#eef3f7;
                                color:#71869a;
                                font-size:12px;
                                line-height:1.5;
                            "
                        >
                            Taxi Dominicana — Documento operativo interno
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
