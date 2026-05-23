<?php

namespace App\Repositories\Actions;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Twilio\Rest\Client;

//MODELS
use App\Models\Reservation;
use App\Models\ReservationsItem;
use App\Models\ReservationsRefund;

//TRAITS
use App\Traits\FollowUpTrait;

class ActionsRepository
{
    use FollowUpTrait;

    /**
     * NOS AYUDA A REMOVER LA COMISION DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */
    public function deleteCommission($request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all()
                ],
                'status' => 'error',
                "message" => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);  // 422
        }

        $booking = Reservation::find($request->reservation_id);

        if (!$booking) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Reservación no encontrada'
                ],
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }         

        try {
            DB::beginTransaction();
            
            $booking->is_commissionable = 0;
            $booking->save();

            // ESTATUS DE RESERVACIÓN            
            $this->create_followUps($request->reservation_id, "El usuario: ".auth()->user()->name.", elimino la comisión de la reservación de (COMISIONABLE) a (NO COMISIONABLE)", 'HISTORY', "UPDATE_BOOKING_DELETE_COMMISSION");

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se elimino correctamente la comisión de lareservación',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],
                'status' => 'error',                
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 
     * @param request :la información recibida en la solicitud
    */
    public function sendMessageWhatsApp($request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all()
                ],
                'status' => 'error',
                "message" => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);  // 422
        }

        $booking = Reservation::with('items')->where('id', $request->reservation_id)->first();
        // dd($booking->toArray());

        if (!$booking) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Reservación no encontrada'
                ],
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            // $mensaje = ("https://api.taxidominicana.com/api/v1/mailing/reservation/viewQR?code=".$booking->items[0]->code."&email=".$booking->client_email."&language=".$booking->language."");
            // $html = file_get_contents("https://api.taxidominicana.com/api/v1/mailing/reservation/viewQR?code=".$booking->items[0]->code."&email=".$booking->client_email."&language=".$booking->language."");

            // $dompdf = new Dompdf();
            // $dompdf->loadHtml($html);
            // $dompdf->setPaper('A4', 'portrait');
            // $dompdf->render();
            // $pdfContent = $dompdf->output();
            
            // $filename = 'reserva_' . $booking->id . '.pdf';
            // $path = storage_path('app/public/pdf');
            // if (!file_exists($path)) {
            //     mkdir($path, 0755, true);
            // }
            // $path = storage_path('app/public/pdf/' . $filename);
            // file_put_contents($path, $pdfContent);

            // // Link público
            // $pdfLink = asset('storage/' . $filename);

            // // Mensaje que se enviará por WhatsApp
            // $mensajeTexto = urlencode("¡Hola! Aquí tienes el resumen de tu reservación:\n$pdfLink");

            // // Enlace wa.me
            // $linkWhatsapp = "https://wa.me/{$booking->client_phone}?text={$mensajeTexto}";         

            $telefono = $booking->client_phone; // Código de país + número sin espacios
            $mensaje = urlencode("https://api.taxidominicana.com/api/v1/mailing/reservation/viewQR?code=".$booking->items[0]->code."&language=".$booking->language."&email=".$booking->client_email."");
            $linkWhatsapp = "https://wa.me/$telefono?text=$mensaje";

            return response()->json([
                'status' => 'success',
                'message' => 'Mensaje enviado por WhatsApp con Twilio.',
                'link' => $linkWhatsapp,
                // 'pdf_url' => $pdfLink
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],
                'status' => 'error',                
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * NOS AYUDA A MARCAR LA RESERVACIÓN COMO PAGO A LA LLEGADA
     * @param request :la información recibida en la solicitud
    */
    public function enablePayArrival($request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all()
                ],
                'status' => 'error',
                "message" => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);  // 422
        }

        $booking = Reservation::find($request->reservation_id);

        if (!$booking) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Reservación no encontrada'
                ],
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }         

        try {
            DB::beginTransaction();
            
            $booking->pay_at_arrival = 1;
            $booking->is_quotation = 0;
            $booking->expires_at = NULL;
            $booking->save();

            // ESTATUS DE RESERVACIÓN            
            $this->create_followUps($request->reservation_id, "El usuario: ".auth()->user()->name.", actualizo el estatus de la reservación de (QUOTATION) a (PAY_AT_ARRIVAL), porque el cliente pagara a la llegada", 'HISTORY', "UPDATE_BOOKING_PAY_AT_ARRIVAL");

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se marco correctamente la reservación como pago a la llegada',
            ], Response::HTTP_OK);            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],
                'status' => 'error',                
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * NOS AYUDA A ACTIVAR EL SERVICIO PLUS DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */
    public function enablePlusService($request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all()
                ],
                'status' => 'error',
                "message" => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);  // 422
        }

        $booking = Reservation::find($request->reservation_id);

        if (!$booking) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Reservación no encontrada'
                ],
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }         

        try {
            DB::beginTransaction();
            
            $booking->is_advanced = 1;
            $booking->save();

            // ESTATUS DE RESERVACIÓN            
            $this->create_followUps($request->reservation_id, "El usuario: ".auth()->user()->name.", activo el servicio plus de la reservación, por solicitud del cliente", 'HISTORY', "UPDATE_BOOKING_PLUS_SERVICE_ACTIVATION");

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se activo correctamente servicio plus de la reservación',
            ], Response::HTTP_OK);            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],
                'status' => 'error',                
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }    

    /**
     * NOS AYUDA A MARCAR COMO CREDITO ABIERTO, EN LOS DETALLES DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */    
    public function markReservationOpenCredit($request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer',
            'status' => 'required|string|in:CANCELLED,DUPLICATED,OPENCREDIT,QUOTATION,PAY_AT_ARRIVAL,CREDIT,PENDING,CONFIRMED',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all()
                ],
                'status' => 'error',
                "message" => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);  // 422
        }

        $booking = Reservation::find($request->reservation_id);

        if (!$booking) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Reservación no encontrada'
                ],
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }         

        try {
            DB::beginTransaction();
            
            $booking->open_credit = 1;
            $booking->save();

            // Actualizar los estados de los ítems y el ID por el tipo de cancelación
            $booking->items()->update([
                'vehicle_id_one' => NULL,
                'driver_id_one' => NULL,
                'op_one_status' => 'PENDING',
                'op_one_status_operation' => 'PENDING',
                'op_one_time_operation' => NULL,
                'op_one_preassignment' => NULL,
                'op_one_operating_cost' => 0,
                'op_one_cancellation_type_id' => NULL,
                'vehicle_id_two' => NULL,
                'driver_id_two' => NULL,
                'op_two_status' => 'PENDING',
                'op_two_status_operation' => 'PENDING',
                'op_two_time_operation' => NULL,
                'op_two_preassignment' => NULL,
                'op_two_operating_cost' => 0,
                'op_two_cancellation_type_id' => NULL,
            ]);            

            // ESTATUS DE RESERVACIÓN
            $this->create_followUps($request->reservation_id, "El usuario: ".auth()->user()->name.", actualizo el estatus de la reservación de (".$request->status.") a (OPENCREDIT), porque el cliente tomara el servicio en otras fechas", 'HISTORY', "UPDATE_BOOKING_OPENCREDIT");

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se marco correctamente la reservación como crédito abierto',
            ], Response::HTTP_OK);            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],
                'status' => 'error',                
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }        
    }
    
    /**
     * NOS AYUDA A REACTIVAR UNA RESERVA, EN LOS DETALLES DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */
    public function reactivateReservation($request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer',
            'status' => 'required|string|in:CANCELLED,DUPLICATED,OPENCREDIT,QUOTATION,PAY_AT_ARRIVAL,CREDIT,PENDING,CONFIRMED',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all()
                ],
                'status' => 'error',
                "message" => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);  // 422
        }

        $booking = Reservation::find($request->reservation_id);

        if (!$booking) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Reservación no encontrada'
                ],
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }         

        try {
            DB::beginTransaction();
            
            $booking->is_cancelled = 0;
            $booking->is_duplicated = 0;
            $booking->open_credit = 0;
            $booking->save();

            // Actualizar los estados de los ítems y el ID por el tipo de cancelación
            $booking->items()->update([
                'vehicle_id_one' => NULL,
                'driver_id_one' => NULL,
                'op_one_status' => 'PENDING',
                'op_one_status_operation' => 'PENDING',
                'op_one_time_operation' => NULL,
                'op_one_preassignment' => NULL,
                'op_one_operating_cost' => 0,
                // 'op_one_cancellation_type_id' => NULL,
                'vehicle_id_two' => NULL,
                'driver_id_two' => NULL,
                'op_two_status' => 'PENDING',
                'op_two_status_operation' => 'PENDING',
                'op_two_time_operation' => NULL,
                'op_two_preassignment' => NULL,
                'op_two_operating_cost' => 0,
                // 'op_two_cancellation_type_id' => NULL,
            ]);

            // ESTATUS DE RESERVACIÓN
            $this->create_followUps($request->reservation_id, "El usuario: ".auth()->user()->name.", actualizo el estatus de la reservación de (".$request->status.") a (PENDING)", 'HISTORY', "UPDATE_BOOKING_REACTIVATE");

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se reactivo la reserva correctamente.',
            ], Response::HTTP_OK);            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],
                'status' => 'error',                
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }        
    }    

    /**
     * NOS AYUDA A SOLICITAR UN REEMBOLSO, EN LOS DETALLES DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */
    public function refundRequest($request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all()
                ],
                'status' => 'error',
                "message" => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);  // 422
        }

        try {
            DB::beginTransaction();

            $booking = new ReservationsRefund();
            $booking->reservation_id = $request->reservation_id;
            $booking->user_id = auth()->user()->id;
            $booking->message_refund = $request->message;
            $booking->status = 'REFUND_REQUESTED';
            $booking->save();

            // ESTATUS DE RESERVACIÓN
            $this->create_followUps($request->reservation_id, "El usuario: ".auth()->user()->name.", indica que la cliente solicita reeembolso de la reservación", 'HISTORY', "UPDATE_BOOKING_REFUND_REQUESTED");

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se marco correctamente la reservación como solicitud de reeembolso',
            ], Response::HTTP_OK);            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * NOS AYUDA A MARCAR COMO DUPLICADA, EN LOS DETALLES DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */    
    public function markReservationDuplicate($request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|integer',
            'status' => 'required|string|in:CANCELLED,DUPLICATED,OPENCREDIT,QUOTATION,PAY_AT_ARRIVAL,CREDIT,PENDING,CONFIRMED',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all()
                ],
                'status' => 'error',
                "message" => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);  // 422
        }

        $booking = Reservation::find($request->reservation_id);

        if (!$booking) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Reservación no encontrada'
                ],
                'status' => 'error',
                'message' => 'Reservación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }         

        try {
            DB::beginTransaction();
            
            $booking->is_duplicated = 1;
            $booking->save();

            // Actualizar los estados de los ítems y el ID por el tipo de cancelación
            $booking->items()->update([
                'vehicle_id_one' => NULL,
                'driver_id_one' => NULL,
                'op_one_status' => 'DUPLICATE',
                'op_one_status_operation' => 'PENDING',
                'op_one_time_operation' => NULL,
                'op_one_preassignment' => NULL,
                'op_one_operating_cost' => 0,
                'op_one_cancellation_type_id' => NULL,
                'vehicle_id_two' => NULL,
                'driver_id_two' => NULL,
                'op_two_status' => 'DUPLICATE',
                'op_two_status_operation' => 'PENDING',
                'op_two_time_operation' => NULL,
                'op_two_preassignment' => NULL,
                'op_two_operating_cost' => 0,
                'op_two_cancellation_type_id' => NULL,
            ]);            

            // ESTATUS DE RESERVACIÓN
            $this->create_followUps($request->reservation_id, "El usuario: ".auth()->user()->name.", actualizo el estatus de la reservación de (".$request->status.") a (DUPLICATED), porque el cliente genero varias reservas", 'HISTORY', "UPDATE_BOOKING_DUPLICATED");

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se marco correctamente la reservación como duplicada',
            ], Response::HTTP_OK);            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],
                'status' => 'error',                
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }    

    /**
     * NOS AYUDA A PODER CALIFICAR EL ESTATUS DE RESERVACIÓN, EN LOS DETALLES DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */
    public function enabledLike($request)
    {
        // Reglas de validación
        $rules = [
            'reservation_id' => 'required|integer',
            'status' => 'required|integer|in:1,0',
        ];

        // Validación de datos
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all() 
                ],
                'status' => 'error',
                'message' => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        // Obtener el item de la reservación
        $booking = Reservation::where('id', $request->reservation_id)->first();
        
        if (!$booking) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND', 
                    'message' =>  "reservación no encontrada" 
                ],
                'status' => 'error',
                'message' => 'reservación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            DB::beginTransaction();

            $likeOld = $booking->reserve_rating;
            $booking->reserve_rating = $request->status;

            // Guardar el cambio y verificar que se guardó correctamente
            if (!$booking->save()) {
                DB::rollBack();
                return response()->json([
                    'errors' => [
                        'code' => 'UPDATE_ERROR',
                        'message' => 'Error al actualizar la calificación de la reservación.'
                    ],
                    'status' => 'error',
                    'message' => 'Error al actualizar la calificación de la reservación.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // ESTATUS DE RESERVACIÓN
            $this->create_followUps($booking->id, "El usuario: ".auth()->user()->name.", califico la reservación como: ". ( $likeOld == NULL ? ( $request->status == 1 ? '"POSITVO"' : '"NEGATIVO"' ) : ( $likeOld == 1 ? '"POSITIVO"' : '"NEGATIVO"' )." a ".( $request->status == 1 ? '"POSITIVO"' : '"NEGATIVO"' ) ), 'HISTORY', "UPDATE_BOOKING_LIKE");
    
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se actualizo correctamente la calificación de la reservación.',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],                
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * NOS AYUDA A PODER CALIFICAR EL ESTATUS DE RESERVACIÓN, EN LOS DETALLES DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */
    public function deleteItem($request)
    {
        // Reglas de validación
        $rules = [
            'item_id' => 'required|integer',
        ];

        // Validación de datos
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all() 
                ],
                'status' => 'error',
                'message' => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        // Obtener el item de la reservación
        $booking = ReservationsItem::where('id', $request->item_id)->first();
        
        if (!$booking) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND', 
                    'message' =>  "reservación no encontrada" 
                ],
                'status' => 'error',
                'message' => 'reservación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            DB::beginTransaction();

            $booking->delete();

            // ESTATUS DE RESERVACIÓN
            $this->create_followUps($booking->reservation_id, "El usuario: ".auth()->user()->name.", elimino el item de la reservación con código: ".$booking->code, 'HISTORY', "DELETE_ITEM_RESERVATION");
    
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se elimino correctamente el item de la reservación.',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],                
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }    

    /**
     * NOS AYUDA A PODER CAMBIAR EL ESTATUS DE CONFIRMACION DEL SERVICIO, EN LOS DETALLES DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */
    public function confirmService($request)
    {
        // Reglas de validación
        $rules = [
            'item_id' => 'required|integer',
            'service' => 'required|string|in:ARRIVAL,DEPARTURE,TRANSFER',
            'status' => 'required|integer|in:2,1,0',
            'type' => 'required|string|in:TYPE_ONE,TYPE_TWO',
        ];

        // Validación de datos
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS', 
                    'message' =>  $validator->errors()->all() 
                ],
                'status' => 'error',
                'message' => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        // Obtener el item de la reservación
        $item = ReservationsItem::with('reservations')->where('id', $request->item_id)->first();
        
        if (!$item) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND', 
                    'message' =>  "Ítem no encontrado" 
                ],
                'status' => 'error',
                'message' => 'Ítem no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            DB::beginTransaction();

            $confimrationOld = $request->type == "TYPE_ONE" ? $item->op_one_confirmation : $item->op_two_confirmation;
            if($request->type == "TYPE_ONE"):
                $item->op_one_confirmation = $request->status ?: 0;
            endif;

            if($request->type == "TYPE_TWO"):
                $item->op_two_confirmation = $request->status ?: 0;
            endif;

            // Guardar el cambio y verificar que se guardó correctamente
            if (!$item->save()) {
                DB::rollBack();
                return response()->json([
                    'errors' => [
                        'code' => 'UPDATE_ERROR',
                        'message' => 'Error al actualizar la confirmación del servicio.'
                    ],                    
                    'status' => 'error',
                    'message' => 'Error al actualizar la confirmación del servicio.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->create_followUps($item->reservation_id, "El usuario: ".auth()->user()->name.", actualizo la confirmación de: ".( $confimrationOld == 0 ? '(No enviado)' : '(Enviado)' )." a ".( $request->status == 0 ? '(No enviado)' : '(Enviado)' ).", de la (".$request->service."), con ID: ".$item->id, 'HISTORY', "UPDATE_SERVICE_CONFIRMATION");
    
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se actualizo correctamente la confirmación del servicio.',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],                
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * NOS AYUDA A PODER DESBLOQUEAR EL SERVICIO, EN LOS DETALLES DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */
    public function updateServiceUnlock($request)
    {
        // Reglas de validación
        $rules = [
            'item_id' => 'required|integer',
            'service' => 'required|string|in:ARRIVAL,DEPARTURE,TRANSFER',
            'type' => 'required|string|in:TYPE_ONE,TYPE_TWO',
        ];

        // Validación de datos
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS', 
                    'message' =>  $validator->errors()->all() 
                ],
                'status' => 'error',
                'message' => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        // Obtener el item de la reservación
        $item = ReservationsItem::with('reservations')->where('id', $request->item_id)->first();
        
        if (!$item) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND', 
                    'message' =>  "Ítem no encontrado" 
                ],
                'status' => 'error',
                'message' => 'Ítem no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            DB::beginTransaction();

            $operationCloseOld = $request->type == "TYPE_ONE" ? $item->op_one_operation_close : $item->op_two_operation_close;
            if($request->type == "TYPE_ONE"):
                $item->op_one_operation_close = 0;
            endif;

            if($request->type == "TYPE_TWO"):
                $item->op_two_operation_close = 0;
            endif;

            // Guardar el cambio y verificar que se guardó correctamente
            if (!$item->save()) {
                DB::rollBack();
                return response()->json([
                    'errors' => [
                        'code' => 'UPDATE_ERROR',
                        'message' =>  "Error al actualizar el desbloqueo del servicio."
                    ],                    
                    'status' => 'error',
                    'message' => 'Error al actualizar el desbloqueo del servicio.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // ESTATUS DE RESERVACIÓN
            $this->create_followUps($item->reservation_id, "El usuario: ".auth()->user()->name.", desbloqueo el servicio de: ". ( $operationCloseOld == 0 ? '(ABIERTO)' : '(CERRADO)' )." a (ABIERTO), de la (".$request->service."), con ID: ".$item->id, 'HISTORY', "UPDATE_SERVICE_UNLOCK");
    
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Se actualizo correctamente estatus del servicio',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * NOS AYUDA A PODER CAMBIAR EL ESTATUS DEL SERVICIO, EN LOS DETALLES DE LA RESERVACIÓN
     * @param request :la información recibida en la solicitud
    */
    public function updateServiceStatus($request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
            'service' => 'required|string|in:ARRIVAL,DEPARTURE,TRANSFER',
            'status' => 'required|string|in:PENDING,COMPLETED,NOSHOW,CANCELLED,DUPLICATE,NOTOPERATED,REFUND,DISPUTE',
            'type' => 'required|string|in:TYPE_ONE,TYPE_TWO',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => [
                    'code' => 'REQUIRED_PARAMS',
                    'message' =>  $validator->errors()->all()
                ],
                'status' => 'error',
                "message" => $validator->errors()->all(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);  // 422
        }

        // Obtener el item de la reservación
        $item = ReservationsItem::with('reservations')->where('id', $request->item_id)->first();
        
        if (!$item) {
            return response()->json([
                'errors' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Item no encontrado'
                ],                    
                'status' => 'error',
                'message' => 'Ítem no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } 

        try {
            DB::beginTransaction();

            $statusOld = $request->type == "TYPE_ONE" ? $item->op_one_status : $item->op_two_status;
            if($request->type == "TYPE_ONE"):
                $item->op_one_status = $request->status;
                $item->op_one_cancellation_type_id = ( is_numeric($request->type_cancel) ? $request->type_cancel : NULL );
            endif;

            if($request->type == "TYPE_TWO"):
                $item->op_two_status = $request->status;
                $item->op_two_cancellation_type_id = ( is_numeric($request->type_cancel) ? $request->type_cancel : NULL );
            endif;

            // $this->create_followUps($request->rez_id, "El usuario: ".auth()->user()->name.", actualizo el estatus del servicio de (".strtoupper($request->type).") de: ".( $request->type == "arrival" ? "(".$item->op_one_status.")" : "(".$item->op_two_status.")" )." a (".$request->status.")", 'HISTORY', "UPDATE_SERVICE_STATUS");
            $this->create_followUps($item->reservation_id, "El usuario: ".auth()->user()->name.", actualizo el estatus del servicio de: (".($statusOld).") a (".$request->status."), de la (".$request->service."), con ID: ".$item->id, 'HISTORY', "UPDATE_SERVICE_STATUS");
            //ESTE LOG ES EL QUE SE HACIA EN OPERATIONSCONTROLLER, QUE YA NO SE USARA
            // $this->create_followUps($service->reservation_id, "Actualización de estatus de reservación (".$request->operation.") por ".$request->status, 'HISTORY', auth()->user()->name);

            // Cancelando todos los estatus
            if($request->status == "CANCELLED") {
                if($request->type == "TYPE_ONE"):
                    $item->op_one_status_operation = 'CANCELLED';
                    $item->vehicle_id_one = null;
                    $item->driver_id_one = null;
                endif;
                
                if($request->type == "TYPE_TWO"):
                    $item->op_two_status_operation = 'CANCELLED';
                    $item->vehicle_id_two = null;
                    $item->driver_id_two = null;
                endif;
            }

            // Guardar el cambio y verificar que se guardó correctamente
            if (!$item->save()) {
                DB::rollBack();
                return response()->json([
                    'errors' => [
                        'code' => 'UPDATE_ERROR',
                        'message' => 'Error al guardar los cambios en el item'
                    ],                    
                    'status' => 'error',
                    'message' => 'Error al guardar los cambios en el ítem'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            //Declaramos variables
            $reservationId = $item->reservation_id;
            $updateReservationStatus = false; // Por defecto NO se cancela la reserva

            // Verificar si todos los items están cancelados (solo si es round trip)
            // Si es round trip, se valida que ambos trayectos estén cancelados
            if ($item->is_round_trip) {
                $allCancelled = ReservationsItem::where('reservation_id', $reservationId)
                    ->where(function ($query) {
                        $query->where('op_one_status', '!=', 'CANCELLED')
                            ->orWhere('op_two_status', '!=', 'CANCELLED');
                    })
                    ->doesntExist(); // Si no hay ninguno distinto a 'CANCELLED', entonces todos están cancelados.
    
                $updateReservationStatus = $allCancelled;
            } else {
                // Si es One Way, se valida que TODOS los ítems asociados estén cancelados
                $allCancelled = ReservationsItem::where('reservation_id', $reservationId)
                    ->where(function ($query) {
                        $query->where('op_one_status', '!=', 'CANCELLED');
                    })
                    ->doesntExist();

                $updateReservationStatus = $allCancelled;
            }

            // Actualizar la reserva solo si debe hacerse
            if ( $request->status == "CANCELLED" && $updateReservationStatus ) {
                $resultBooking = Reservation::where('id', $reservationId)->update([ 
                    'is_cancelled' => 1, 
                    'cancellation_type_id' => ( is_numeric($request->type_cancel) ? $request->type_cancel : NULL ) 
                ]);
                if ( $resultBooking ) {
                    // Enviar correo en ambos casos
                    $result = $this->sendEmail("", array(
                        "code" => $item->code,
                        "email" => $item->reservations->client_email, // Usar la reserva actualizada
                        "language" => $item->reservations->language, // Usar la reserva actualizada
                        "type" => 'cancel',
                    ));

                    if( !$result['status'] ):
                        return response()->json([
                            'errors' => [
                                'code' => 'SEND_ERROR',
                                'message' => 'Error al enviar el correo de cancelación.'
                            ],                            
                            'status' => 'error',
                            'message' => 'Error al enviar el correo de cancelación.',
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);                        
                    endif;
                }
            }
    
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Estatus actualizado con éxito',
                'data' => [
                    'item' => ( isset($request->key) ? $request->key : 0 ),
                    'value' => $request->status,
                    "message" => "Actualización de estatus de reservación (".$request->type.") por ".$request->status." al servicio: ".$item->id.", por ".auth()->user()->name
                ]
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => [
                    'code' => 'INTERNAL_SERVER',
                    'message' =>  $e->getMessage()
                ],                
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }    

    public function sendEmail($baseUrl = '', $request = []){
        $data = [
            "status" => false,
            "data" => NULL
        ];

        $url = "https://api.taxidominicana.com/api/v1/reservation/send";

        $params = array(
            'code' => $request['code'],
            'email' => $request['email'],
            'language' => $request['language'],
            'type' => $request['type'],
        );

        $ch = curl_init();
        $urlWithParams = $url . '?' . http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $urlWithParams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
    
        if (curl_errno($ch)) {
            $data['status'] = false;
            $data['data'] = [
                'error' => [
                    'code' => 'curl_error',
                    'message' => 'Error en la solicitud cURL: '.curl_error($ch)
                ]
            ];
            return $data;
        }
        curl_close($ch);
        
        $jsonData = json_decode($response);

        //Es un JSON por lo que algo salió mal...
        $data['status'] = true;
        $data['data'] = json_decode($response, true);
        return $data;
    }
}