<?php

namespace App\Repositories\Reservations;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Carbon\Carbon;

use App\Models\Reservation;
use App\Models\DestinationService;
use App\Models\ReservationFollowUp;
use App\Models\ReservationsItem;
use App\Models\ReservationsService;
use App\Models\Payment;
use App\Models\OriginSale;
use App\Models\ContactPoints;
use App\Models\Zones;
use App\Models\Site;
use App\Models\Sale;
use App\Models\User;
use App\Traits\ApiTrait;
//TRAITS
use App\Traits\MailjetTrait;
use App\Traits\FiltersTrait;
use App\Traits\QueryTrait;
use App\Traits\FollowUpTrait;
use App\Traits\LoggerTrait;
use Illuminate\Support\Facades\Validator;

class ReservationsRepository
{
    use MailjetTrait, FiltersTrait, QueryTrait, FollowUpTrait, ApiTrait, LoggerTrait;

    public function update($request,$reservation)
    {
        $validatedData = $this->validateBookingRequest($request); //Validar y preparar los datos de la solicitud
        
        $allow_edit = $this->checkIfCanBeEdited($reservation->id);
        if( !$allow_edit ) {
            return response()->json([
                'message' => 'No se puede editar la reservación porque la operación ya ha sido cerrada', 
                'success' => false,
                'status' => 'error',
            ], Response::HTTP_FORBIDDEN);
        }

        try{
            DB::beginTransaction();

            $this->logBookingChanges($reservation, $request);
            $this->updateBookingAttributes($reservation, $validatedData); //Update item attributes with validated data

            DB::commit();
            return response()->json([
                'message' => 'Reserva actualizada exitosamente', 
                'success' => true,
                'status' => 'success',
            ], Response::HTTP_OK);                 
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al editar la reserva: ' . $e->getMessage(), 
                'success' => false,
                'status' => 'error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //METODO QUE NOS AYUDA A CANCELAR LA RESERVACIÓN
    public function destroy($request, $reservation){
        try {
            // Verificar si existe algún ítem relacionado con status COMPLETED
            $hasCompletedItems = $reservation->items()->where(function ($query) {
                $query->where('op_one_status', 'COMPLETED')
                ->orWhere('op_two_status', 'COMPLETED');
            })->exists();

            // No se puede cancelar la reserva porque tiene artículos con estado COMPLETADO
            if ($hasCompletedItems) {                
                return response()->json(['status' => 'warning', 'message' => 'No se puede cancelar la reserva porque tiene servicios con estado COMPLETADO'], Response::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $reservation->is_cancelled = 1;
            ( isset($request->type) ? $reservation->cancellation_type_id = $request->type : '' );
            $reservation->save();

            // Actualizar los estados de los ítems y el ID por el tipo de cancelación
            $reservation->items()->update([
                'vehicle_id_one' => NULL,
                'driver_id_one' => NULL,
                'op_one_status' => 'CANCELLED',
                'op_one_status_operation' => 'PENDING',
                'op_one_time_operation' => NULL,
                'op_one_preassignment' => NULL,
                'op_one_operating_cost' => 0,
                'op_one_cancellation_type_id' => $request->type,
                'vehicle_id_two' => NULL,
                'driver_id_two' => NULL,
                'op_two_status' => 'CANCELLED',
                'op_two_status_operation' => 'PENDING',
                'op_two_time_operation' => NULL,
                'op_two_preassignment' => NULL,
                'op_two_operating_cost' => 0,
                'op_two_cancellation_type_id' => $request->type,
            ]);

            $check = $this->create_followUps($reservation->id, 'El usuario: '.auth()->user()->name.", cancelo la reservación: ".$reservation->id, 'HISTORY', 'CANCELLATION_BOOKING');
            
            DB::commit();
            // Reservation cancelled successfully
            return response()->json([
                'status' => 'success',
                'message' => 'Reserva cancelada correctamente'
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            // Error cancelling reservation
            return response()->json([
                'status' => 'danger',
                'message' => 'Error al cancelar la reserva',
                // 'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteReservation($request){
        try {
            $reservation = Reservation::find($request->id);

            $hasCompletedItems = $reservation->items()->where(function ($query) {
                $query->where('op_one_status', 'COMPLETED')
                ->orWhere('op_two_status', 'COMPLETED');
            })->exists();
            if ($hasCompletedItems) {                
                return response()->json(['status' => 'warning', 'message' => 'No se puede eliminar la reserva porque tiene servicios con estado COMPLETADO'], Response::HTTP_BAD_REQUEST);
            }
            
            $hasPayments = $reservation->payments()->exists();
            if($hasPayments) {
                return response()->json(['status' => 'warning', 'message' => 'La reservación tiene pagos'], Response::HTTP_BAD_REQUEST);
            }

            DB::transaction(function () use ($reservation) {
                $reservation->followUps()->delete();
                $reservation->items()->delete();
                $reservation->photos()->delete();
                $reservation->sales()->withTrashed()->forceDelete();
                $reservation->delete();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Reserva eliminada correctamente'
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'danger',
                'message' => 'Error al eliminar la reserva',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteReservations($request){
        $ids     = $request->ids ?? [];
        $deleted = [];
        $failed  = [];

        foreach ($ids as $id) {
            try {
                $reservation = Reservation::find($id);

                if (!$reservation) {
                    $failed[] = ['id' => $id, 'reason' => 'Reserva no encontrada'];
                    continue;
                }

                $hasCompletedItems = $reservation->items()->where(function ($query) {
                    $query->where('op_one_status', 'COMPLETED')
                          ->orWhere('op_two_status', 'COMPLETED');
                })->exists();

                if ($hasCompletedItems) {
                    $failed[] = ['id' => $id, 'reason' => 'Tiene servicios con estado COMPLETADO'];
                    continue;
                }

                $hasPayments = $reservation->payments()->exists();
                if ($hasPayments) {
                    $failed[] = ['id' => $id, 'reason' => 'La reservación tiene pagos'];
                    continue;
                }

                DB::transaction(function () use ($reservation) {
                    $reservation->followUps()->delete();
                    $reservation->items()->delete();
                    $reservation->photos()->delete();
                    $reservation->sales()->withTrashed()->forceDelete();
                    $reservation->delete();
                });

                $deleted[] = $id;

            } catch (Exception $e) {
                $failed[] = ['id' => $id, 'reason' => 'Error interno al procesar'];
            }
        }

        $status = count($deleted) > 0 ? 'success' : 'warning';

        return response()->json([
            'status'  => $status,
            'deleted' => $deleted,
            'failed'  => $failed,
        ], Response::HTTP_OK);
    }

    public function get_exchange($request, $reservation){
        $currency = $request->currency;
        $to_currency = $reservation->currency;
        $exchange = DB::table('payments_exchange_rate')->where('origin',$currency)->where('destination',$to_currency)->first();
        return $exchange;
    }

    public function editreservitem($request, $item)
    {
        $validatedData = $this->validateServiceRequest($request); //Validar y preparar los datos de la solicitud

        $allow_edit = $this->checkIfCanBeEdited($item->reservation_id);
        if( !$allow_edit ) {
            return response()->json([
                'message' => 'No se puede editar el servicio porque la operación ya ha sido cerrada', 
                'success' => false,
                'status' => 'error',
            ], Response::HTTP_FORBIDDEN);
        }
        
        try {
            DB::beginTransaction();
            
            $this->logBookingServiceChanges($item, $request);
            $this->updateItemAttributes($item, $validatedData); //Update item attributes with validated data
            $this->updateReservationExpiration($item->reservation_id, $validatedData);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Item updated successfully', 
                'success' => true,
                'status' => 'success',
            ], Response::HTTP_OK);            
        } catch (Exception $e) {
            DB::rollBack();            
            return response()->json([
                'message' => 'Error updating item: ' . $e->getMessage(), 
                'success' => false,
                'status' => 'error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function editReservationItemComment($request)
    {
        $validator = Validator::make($request->all(), [            
            'item_id' => 'required|',
            'type' => 'required|in:one,two',
            'comment' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'REQUIRED_PARAMS', 
                    'message' =>  $validator->errors()->all() 
                ]
            ], 422);
        }
            
        $reservation_item = ReservationsItem::find($request->item_id);
        if(!$reservation_item) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND', 
                    'message' =>  'No se encontró el servicio' 
                ]
            ], 404);
        }

        $comment = $request->comment ? $request->comment : null;
        $message = $comment ? $comment : ' --- ';
        if($request->type === 'one') {
            $reservation_item->op_one_comments = $comment;
        }
        else {
            $reservation_item->op_two_comments = $comment;
        }

        $this->create_followUps(
            $reservation_item->reservation_id, 
            "El usuario: " . auth()->user()->name . ", actualizó el comentario de la operación ". ($request->type === 'one' ? 'uno' : 'dos') ." a: " . $message, 
            'HISTORY',
            'UPDATE_SERVICE'
        );

        $reservation_item->save();

        return response()->json([
            'message' => 'Comentario actualizado exitosamente', 
            'success' => true,
            'status' => 'success',
        ], Response::HTTP_OK);     
    }

    /**
     * Validar y preparar los datos de la solicitud
     */
    protected function validateBookingRequest($request): array
    {
        $validatedData = [
            'client_first_name' => $request->client_first_name,
            'client_last_name' => $request->client_last_name,
            'client_email' => $request->client_email,
            'client_phone' => $request->client_phone,
            'site_id' => $request->site_id,
            'reference' => $request->reference,
            'origin_sale_id' => isset($request->origin_sale_id) && ( $request->origin_sale_id !== '' || $request->origin_sale_id !== 0 ) ? $request->origin_sale_id : NULL,
            'currency' => $request->currency,
            'special_request' => $request->special_request ?: NULL,
        ];

        if(isset($request->call_center_agent_id) && auth()->user()->hasRole(14)) {
            $call_center = User::find($request->call_center_agent_id);
            if($call_center) {
                $validatedData['call_center_agent_id'] = $call_center->id;
            }
        }

        return $validatedData;
    }

    /**
     * Validar y preparar los datos de la solicitud
     */
    protected function validateServiceRequest($request): array
    {
        return [
            'serviceTypeForm' => $request->serviceTypeForm ?: '',
            'destination_service_id' => $request->destination_service_id,
            'passengers' => $request->passengers,
            'flight_number' => $request->flight_number,
            'from_zone_id' => $request->from_zone_id,
            'from_name' => $request->from_name,
            'from_lat' => $request->from_lat,
            'from_lng' => $request->from_lng,
            'to_zone_id' => $request->to_zone_id,
            'to_name' => $request->to_name,
            'to_lat' => $request->to_lat,
            'to_lng' => $request->to_lng,
            'op_one_pickup' => $request->op_one_pickup,
            'op_two_pickup' => $request->op_two_pickup ?? null,
        ];
    }

    protected function logBookingChanges($reservation, $request)
    {
        $changes = [
            'client_first_name' => [
                'condition' => $request->client_first_name != $reservation->client_first_name,
                'message' => "actualizó el nombre del cliente de: ({$reservation->client_first_name}) a ({$request->client_first_name})"
            ],
            'client_last_name' => [
                'condition' => $request->client_last_name != $reservation->client_last_name,
                'message' => "actualizó los apellidos del cliente de: ({$reservation->client_last_name}) a ({$request->client_last_name})"
            ],
            'client_email' => [
                'condition' => $request->client_email != $reservation->client_email,
                'message' => "actualizó el correo del cliente de: ({$reservation->client_email}) a ({$request->client_email})"
            ],
            'client_phone' => [
                'condition' => $request->client_phone != $reservation->client_phone,
                'message' => "actualizó el teléfono del cliente de: ({$reservation->client_phone}) a ({$request->client_phone})"
            ],
            'site_id' => [
                'condition' => $request->site_id != $reservation->site_id,
                'action' => function() use ($request, $reservation) {
                    $old = Site::find($reservation->site_id);
                    $new = Site::find($request->site_id);
                    return "actualizo el sitio de: ({$old->name}) a ({$new->name})";
                }
            ],
            'reference' => [
                'condition' => $request->reference != $reservation->reference,
                'message' => "actualizo la referencia de: ({$reservation->reference}) a ({$request->reference})"
            ],
            'origin_sale_id' => [
                'condition' => isset($request->origin_sale_id) && ( $request->origin_sale_id !== '' || $request->origin_sale_id !== 0 ) && ( $request->origin_sale_id != $reservation->origin_sale_id ),
                'action' => function() use ($request, $reservation) {
                    $old = OriginSale::find($reservation->origin_sale_id);
                    $new = OriginSale::find($request->origin_sale_id);
                    $name_old = ( isset($old->code) ? $old->code : "PAGINA WEB" );
                    $name_new = ( isset($new->code) ? $new->code : "PAGINA WEB" );
                    return "actualizo el origen de venta de: ({$name_old}) a ({$name_new})";
                }
            ],
            'currency' => [
                'condition' => $request->currency != $reservation->currency,
                'message' => "actualizo la moneda de: ({$reservation->currency}) a ({$request->currency})"
            ],
            'special_request' => [
                'condition' => '',
                'message' => ""
            ],            
        ];
    
        foreach ($changes as $change) {
            if ($change['condition']) {
                $message = $change['message'] ?? null;
                if (isset($change['action'])) {
                    $result = $change['action']();
                    if (is_array($result)) {
                        foreach ($result as $msg) {
                            $this->createLogEntry($reservation->id, $msg, 'UPDATE_RESERVATION');
                        }
                        continue;
                    }
                    $message = $result;
                }                
                $this->createLogEntry($reservation->id, $message, 'UPDATE_RESERVATION');
            }
        }
    }    
    
    protected function logBookingServiceChanges($item, $request)
    {
        $changes = [
            'service_type' => [
                'condition' => isset($request->serviceTypeForm) && $request->serviceTypeForm == 1 && $request->serviceTypeForm != $item->is_round_trip,
                'message' => 'actualizó el servicio: {$item->id}({$item->code}) de: (One Way) a (Round Trip)'
            ],
            'vehicle_type' => [
                'condition' => $request->destination_service_id != $item->destination_service_id,
                'action' => function() use ($request, $item) {
                    $old = DestinationService::find($item->destination_service_id);
                    $new = DestinationService::find($request->destination_service_id);
                    return "actualizó el tipo de Vehículo de: ({$old->name}) a ({$new->name})";
                }
            ],
            'passengers' => [
                'condition' => $request->passengers != $item->passengers,
                'message' => "actualizó el número de pasajeros de: ({$item->passengers}) a ({$request->passengers})"
            ],
            'flight_number' => [
                'condition' => $request->flight_number != $item->flight_number,
                'message' => "actualizó el número de vuelo de: ({$item->flight_number}) a ({$request->flight_number})"
            ],
            'from_zone' => [
                'condition' => $request->from_zone_id != $item->from_zone,
                'action' => function() use ($request, $item) {
                    $old = Zones::find($item->from_zone);
                    $new = Zones::find($request->from_zone_id);
                    return "actualizó la zona Desde de: ({$old->name}) a ({$new->name})";
                }
            ],
            'from_name' => [
                'condition' => $request->from_name != $item->from_name,
                'message' => "actualizó Desde de: ({$item->from_name}) a ({$request->from_name})"
            ],
            'from_coords' => [
                'condition' => $request->from_lat != $item->from_lat,
                'message' => "actualizó las coordenadas Desde lat: ({$item->from_lat}) a ({$request->from_lat}), lng: ({$item->from_lng}) a ({$request->from_lng})"
            ],
            'to_zone' => [
                'condition' => $request->to_zone_id != $item->to_zone,
                'action' => function() use ($request, $item) {
                    $old = Zones::find($item->to_zone);
                    $new = Zones::find($request->to_zone_id);
                    return "actualizó la zona Hacia de: ({$old->name}) a ({$new->name})";
                }
            ],
            'to_name' => [
                'condition' => $request->to_name != $item->to_name,
                'message' => "actualizó Hacia de: ({$item->to_name}) a ({$request->to_name})"
            ],
            'to_coords' => [
                'condition' => $request->to_lat != $item->to_lat,
                'message' => "actualizó las coordenadas Hacia lat: ({$item->to_lat}) a ({$request->to_lat}), lng: ({$item->to_lng}) a ({$request->to_lng})"
            ],
            'pickup_time' => [
                'condition' => $request->op_one_pickup != $item->op_one_pickup,
                'action' => function() use ($request, $item) {
                    $requestDate = date("Y-m-d", strtotime($request->op_one_pickup));
                    $requestTime = date("H:i", strtotime($request->op_one_pickup));
                    $itemDate = date("Y-m-d", strtotime($item->op_one_pickup));
                    $itemTime = date("H:i", strtotime($item->op_one_pickup));
                    
                    $messages = [];
                    if ($requestDate != $itemDate) {
                        $messages[] = "actualizó la fecha de recogida de: ({$itemDate}) a ({$requestDate})";
                    }
                    if ($requestTime != $itemTime) {
                        $messages[] = "actualizó la hora de recogida de: ({$itemTime}) a ({$requestTime})";
                    }
                    return $messages;
                }
            ],
            'return_time' => [
                'condition' => $item->is_round_trip == 1 && $request->op_two_pickup != $item->op_two_pickup,
                'action' => function() use ($request, $item) {
                    $requestDate = date("Y-m-d", strtotime($request->op_two_pickup));
                    $requestTime = date("H:i", strtotime($request->op_two_pickup));
                    $itemDate = date("Y-m-d", strtotime($item->op_two_pickup));
                    $itemTime = date("H:i", strtotime($item->op_two_pickup));
                    
                    $messages = [];
                    if ($requestDate != $itemDate) {
                        $messages[] = "actualizó la fecha de regreso de: ({$itemDate}) a ({$requestDate})";
                    }
                    if ($requestTime != $itemTime) {
                        $messages[] = "actualizó la hora de regreso de: ({$itemTime}) a ({$requestTime})";
                    }
                    return $messages;
                }
            ]
        ];
    
        foreach ($changes as $change) {
            if ($change['condition']) {
                $message = $change['message'] ?? null;                
                if (isset($change['action'])) {
                    $result = $change['action']();
                    if (is_array($result)) {
                        foreach ($result as $msg) {
                            $this->createLogEntry($item->reservation_id, $msg, 'UPDATE_SERVICE');
                        }
                        continue;
                    }
                    $message = $result;
                }                
                $this->createLogEntry($item->reservation_id, $message, 'UPDATE_SERVICE');
            }
        }
    }

    protected function createLogEntry($reservationId, $message, $action = "")
    {
        $this->create_followUps(
            $reservationId, 
            "El usuario: " . auth()->user()->name . ", " . $message, 
            'HISTORY', 
            // 'EDICIÓN RESERVACIÓN'
            // 'EDICIÓN SERVICIO',
            $action
        );
    }  
    
    /**
     * Update item attributes with validated data
     */    
    protected function updateBookingAttributes($reservation, array $data): void
    {
        $oldSiteId = $reservation->site_id;

        // Actualización de campos básicos
        $reservation->fill($data);
        if(isset($data['call_center_agent_id'])) $reservation->call_center_agent_id = $data['call_center_agent_id'];

        // Manejo de solicitud especial
        if (!empty($data['special_request'])) {
            $this->create_followUps(
                $reservation->id, 
                $data['special_request'], 
                'CLIENT', 
                auth()->user()->name
            );
        }

        // Manejo de cambio de sitio
        if ($oldSiteId != $data['site_id']) {
            $this->handleSiteChange($reservation, $data['site_id']);
        }

        $reservation->save();
    }    

    /**
     * Update item attributes with validated data
     */
    protected function updateItemAttributes($item, array $data): void
    {
        ( isset($data['serviceTypeForm']) && $data['serviceTypeForm'] == 1 ? $item->is_round_trip = $data['serviceTypeForm'] : '' );
        $item->destination_service_id = $data['destination_service_id'];
        $item->passengers = $data['passengers'];
        $item->flight_number = $data['flight_number'];
        $item->from_name = $data['from_name'];
        $item->to_name = $data['to_name'];
        $item->from_zone = $data['from_zone_id'];
        $item->to_zone = $data['to_zone_id'];
        $item->op_one_pickup = $data['op_one_pickup'];
        $item->op_two_pickup = $data['op_two_pickup'];

        // Conditional location updates
        if (!empty($data['from_lat'])) {
            $item->from_lat = $data['from_lat'];
            $item->from_lng = $data['from_lng'];
        }

        if (!empty($data['to_lat'])) {
            $item->to_lat = $data['to_lat'];
            $item->to_lng = $data['to_lng'];
        }

        $item->save();
    }

    protected function updateReservationExpiration(int $reservation_id, array $data): void
    {
        $booking = Reservation::find($reservation_id);
        if ($booking && $booking->is_quotation == 1) {
            $newDate = $this->calculateNewExpirationDate(date('Y-m-d H:i:s'), $data['op_one_pickup']);
            if ($newDate) {
                $booking->expires_at = $newDate;
                $booking->save();
            }
        }
    }

    protected function calculateNewExpirationDate($currentDate, $pickupDate)
    {
        $current = Carbon::parse($currentDate);
        $pickup = Carbon::parse($pickupDate);
        $daysDifference = $current->diffInDays($pickup);
    
        return match(true) {
            $daysDifference === 0 || $daysDifference === 1 => $pickup->copy()->subHours(3),
            $daysDifference >= 2 && $daysDifference <= 4 => $pickup->copy()->subDay(),
            $daysDifference >= 5 => $pickup->copy()->subDays(2),
            default => null
        };
    }
    
    protected function handleSiteChange($reservation, $newSiteId)
    {
        $site = Site::where('id', $newSiteId)->where('is_cxc', 1)->first();
        
        if ($site) {
            $reservation->is_quotation = 0;
            $reservation->expires_at = null;
            return;
        }
        
        // if ($reservation->was_is_quotation == 1) {
        //     $item = $reservation->items->first();
        //     if ($item) {
        //         $newDate = $this->calculateNewExpirationDate(now(), $item['op_one_pickup']);
        //         if ($newDate) {
        //             $reservation->is_quotation = 1;
        //             $reservation->expires_at = $newDate;
        //         }
        //     }
        // }      
    }

    public function sendArrivalConfirmation($request){
        $lang = $request['lang'];
        $point_id = $request['terminal_id'];

        if($request['terminal_id'] == 0):
            return response()->json(['message' => 'Es necesario seleccionar un punto', 'success' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
        endif;

        $item = DB::select("SELECT 
                                    it.code, 
                                    it.from_name, 
                                    it.to_name, 
                                    it.flight_number, 
                                    it.passengers, 
                                    it.op_one_pickup, 
                                    it.op_two_pickup, 
                                    rez.client_first_name, 
                                    rez.client_email, 
                                    sit.transactional_phone,
                                    sit.transactional_phone_arrival,
                                    rez.id as reservation_id
                                FROM reservations_items as it
                                INNER JOIN reservations as rez ON rez.id = it.reservation_id
                                INNER JOIN sites as sit ON sit.id = rez.site_id
                                    WHERE it.id = :id", ['id' => $request['item_id'] ]);

        $point = DB::table('contact_points as cp')
                        ->select(DB::raw('cp.name as point_name, IFNULL(cp_translate.translation, cp.point_description) AS point_description'))
                        ->leftJoin('contact_points_translate as cp_translate', function ($join) use($lang) {
                            $join->on('cp_translate.contact_point_id', '=', 'cp.id')
                                ->where('cp_translate.lang', '=', $lang );
                        })->where('cp.id', '=', $point_id)->get();
                        
        $message = $this->arrivalMessage($lang, $item[0], $point[0]);
        
        //Data to send in confirmation..
        $email_data = array(
            "Messages" => array(
                array(
                    "From" => array(
                        "Email" => 'bookings@caribbean-transfers.com',
                        "Name" => "Bookings"
                    ),
                    "To" => array(
                        array(
                            "Email" => $item[0]->client_email,
                            "Name" => $item[0]->client_first_name,
                        )
                    ),
                    "Bcc" => array(
                        array(
                            "Email" => 'bookings@caribbean-transfers.com',
                            "Name" => "Bookings"
                        )
                    ),
                    "Subject" => (($lang == "en")?'Service confirmation message':'Mensaje de confirmación de servicio'),
                    "TextPart" => (($lang == "en")?'Dear client':'Estimado cliente'),
                    "HTMLPart" => $message
                )
            )
        );

        $email_response = $this->sendMailjet($email_data);

        if(isset($email_response['Messages'][0]['Status']) && $email_response['Messages'][0]['Status'] == "success"):
            $check = $this->create_followUps($item[0]->reservation_id, 'El usuario: '.auth()->user()->name.", a enviado E-mail (confirmación de llegada) para la reservación: ".$item[0]->reservation_id, 'INTERN', 'SISTEMA');
            // E-mail enviado (confirmación de llegada) por '.auth()->user()->name
            return response()->json(['status' => "success", "message" => $message], 200);
        else:
            $check = $this->create_followUps($item[0]->reservation_id, 'No fue posible enviar el e-mail de confirmación de llegada, por favor contactar a Desarrollo', 'INTERN', 'SISTEMA');
            // No fue posible enviar el e-mail de confirmación de llegada, por favor contactar a Desarrollo
            return response()->json([
                'error' => [
                    'code' => 'mailing_system',
                    'message' => 'The mailing platform has a problem, please report to development'
                ]
            ], 404);
        endif;
    }

    public function arrivalMessage($lang = "en", $item = [], $point = []){
        $arrival_date = date("Y-m-d H:i", strtotime($item->op_one_pickup));
        if($lang == "en"):
            return <<<EOF
                    <p><strong>DEAR CUSTOMER,  FOR SECURITY REASONS REMEMBER TO PRESENT AN ID WHAT IT MATCH WITH THE NAME OF THE CARDHOLDER AND SIGN THE PAYMENT AUTHORIZATION FORM IN ORDER TO ABOARD THE VEHICLE OTHERWISE YOU WILL BE ASKED TO PAY IN CASH AND THEN THE ONLINE PAYMENT WILL BE REINBURSED IN FULL BACK TO THE CARDHOLDER'S ACCOUNT. THIS INFORMATION IS REQUIRED BY THE  BANKS INSTITUTION TO PROOF THE PAYMENT HAS BEEN GENUINELY AUTHORIZED.</strong></p>
                    <p>Arrival confirmation</p>
                    <p>Before boarding, you will be asked to show photo identification of the cardholder of the card with which the payment was made.</p>
                    <p>This is your reservation voucher, please verify that the following information is correct.</p>
                    <p>Dear $item->client_first_name | Reservation No: <strong>$item->code</strong>.</p>
                    <p>Thank you for choosing Caribbean Transfers, we appreciate your confidence, the information below will facilitate your contact with our staff at the airport, flight $item->flight_number lands at $point->point_name on $arrival_date hrs therefore our representative will be waiting for you with a Caribbean Transfers identifier.</p>
                    <p>To facilitate contact, please turn on your cell phone as soon as you land, you can use the free WIFI network at the airport to contact us. Let us know when you are ready to board your unit (after clearing customs and collecting your bags), a representative will be ready to meet you and take you to your assigned unit.</p>
                    <p>Please confirm receipt</p>
                    <p>Thank you for your confidence, have a great trip.</p>
                    <p>*In case you require additional assistance, please send a message to the number: <strong>$item->transactional_phone_arrival</strong></p>
                    <p>Tips not included</p>
                    <p>All company personnel are identified with badges and uniforms, please do not pay attention to scam attempts as these payments will not be reimbursed</p>
                    <p>When you are ready, meet our uniformed Caribbean Transfers staff at the Airport. </p>
                    <img src="https://ik.imagekit.io/zqiqdytbq/transportation-api/mailing/terminals/rep.jpg?updatedAt=1725124715647" width="250">
            EOF;
        else:
            return <<<EOF
                <p><strong>ESTIMADO CLIENTE, POR MOTIVOS DE SEGURIDAD, RECUERDE PRESENTAR UNA IDENTIFICACIÓN QUE COINCIDA CON EL NOMBRE DEL TITULAR DE LA TARJETA Y FIRMAR EL FORMULARIO DE AUTORIZACIÓN DE PAGO PARA PODER SUBIR AL VEHÍCULO. DE LO CONTRARIO, SE LE SOLICITARÁ QUE PAGUE EN EFECTIVO Y LUEGO EL PAGO EN LÍNEA SE REEMBOLSARÁ ÍNTEGRAMENTE A LA CUENTA DEL TITULAR DE LA TARJETA. ESTA INFORMACIÓN ES REQUERIDA POR LA INSTITUCIÓN BANCARIA PARA COMPROBAR QUE EL PAGO HA SIDO AUTORIZADO.</strong></p>
                <p>Confirmación de llegada</p>
                <p>Antes de abordar se le solicitará la identificación con fotografía del titular de la tarjeta con la que se realizó el pago</p>
                <p>Este es su comprobante de reserva, verifique que la información detallada a continuación sea correcta.</p>
                <p>Estimado/a $item->client_first_name | Reservación No: <strong>$item->code</strong></p>                
                <p>Gracias por elegir a Caribbean Transfers, agradecemos su confianza, la información escrita a continuación facilitará su contacto con nuestro staff en el Aeropuerto, el vuelo $item->flight_number aterriza en $point->point_name el día $arrival_date hrs por lo tanto nuestro representante lo estará esperando en $point->point_description con un identificador de Caribbean Transfers</p>
                <p>Para facilitar el contacto encienda su celular tan pronto como aterrice, puede usar la red gratuita del WIFI en el aeropuerto para poder contactarnos. Avísenos cuando esté listo para abordar su unidad (después de pasar aduana y recolectar sus maletas), un representante estará listo para recibirle y acercarlo a la unidad asignada.</p>
                <p>Por favor confirme de recibido</p>
                <p>Gracias por su confianza, que tenga un excelente viaje</p>
                <p>*En caso de requerir ayuda adicional, envíe un mensaje al número: <strong>$item->transactional_phone_arrival</strong></p>
                <p>Propinas no incluidas</p>
                <p>Todo el personal de la empresa está identificado con gafete y uniforme por favor no haga caso de intentos de estafa ya que estos pagos no serán reembolsados.</p>
                <p>Cuando esté listo, localice a nuestro personal uniformado de Caribbean Transfers en el Aeropuerto. </p>
                <img src="https://ik.imagekit.io/zqiqdytbq/transportation-api/mailing/terminals/rep.jpg?updatedAt=1725124715647" width="250">
            EOF;            
        endif;
    }

    function sendDepartureConfirmation($request){
        $lang = $request['lang'];
        $item = DB::select("SELECT 
                                    it.code, 
                                    it.from_name, 
                                    it.to_name, 
                                    it.flight_number, 
                                    it.passengers, 
                                    it.op_one_pickup, 
                                    it.op_two_pickup, 
                                    rez.client_first_name, 
                                    rez.client_email, 
                                    sit.transactional_phone, 
                                    sit.transactional_phone_departure, 
                                    rez.id as reservation_id
                                FROM reservations_items as it
                                INNER JOIN reservations as rez ON rez.id = it.reservation_id
                                INNER JOIN sites as sit ON sit.id = rez.site_id
                                    WHERE it.id = :id", ['id' => $request['item_id'] ]);
        
        $message = $this->departureMessage($lang, $item[0], $request['destination_id'], $request['type'], $request['is_round_trip']);

        //Data to send in confirmation..
        $email_data = array(
            "Messages" => array(
                array(
                    "From" => array(
                        "Email" => 'bookings@caribbean-transfers.com',
                        "Name" => "Bookings"
                    ),
                    "To" => array(
                        array(
                            "Email" => $item[0]->client_email,
                            "Name" => $item[0]->client_first_name,
                        )
                    ),
                    "Bcc" => array(
                        array(
                            "Email" => 'bookings@caribbean-transfers.com',
                            "Name" => "Bookings"
                        )
                    ),
                    "Subject" => (($lang == "en")?'Service departure confirmation message':'Mensaje de confirmación de servicio de regreso'),
                    "TextPart" => (($lang == "en")?'Dear client':'Estimado cliente'),
                    "HTMLPart" => $message
                )
            )
        );

        $email_response = $this->sendMailjet($email_data);

        if(isset($email_response['Messages'][0]['Status']) && $email_response['Messages'][0]['Status'] == "success"):
            $check = $this->create_followUps($item[0]->reservation_id, 'El usuario: '.auth()->user()->name.", a enviado E-mail (confirmación de regreso) para la reservación: ".$item[0]->reservation_id, 'INTERN', 'SISTEMA');
            // 'E-mail enviado (confirmación de regreso) por '.auth()->user()->name

            return response()->json(['status' => "success", "message" => $message], 200);
        else:
            $check = $this->create_followUps($item[0]->reservation_id, 'No fue posible enviar el e-mail de confirmación de regreso, por favor contactar a Desarrollo', 'INTERN', 'SISTEMA');
            // No fue posible enviar el e-mail de confirmación de regreso, por favor contactar a Desarrollo
            
            return response()->json([
                'error' => [
                    'code' => 'mailing_system',
                    'message' => 'The mailing platform has a problem, please report to development'
                ]
            ], 404);
        endif;
    }

    public function departureMessage($lang = "en", $item = [], $destination_id = 0, $type = "departure", $is_round_trip = "0"){
        $departure_date = NULL;
        $departure_date_new = NULL;
        $departure_time = NULL;
        $destination = NULL;     
        
        if($type == "transfer-pickup"):
            $departure_date = date("Y-m-d H:i", strtotime($item->op_one_pickup));
            $departure_date_new = date("Y-m-d", strtotime($item->op_one_pickup));
            $departure_time = date("H:i", strtotime($item->op_one_pickup));
            $destination = $item->from_name;
        endif;

        if($type == "transfer-return"):
            $departure_date = date("Y-m-d H:i", strtotime($item->op_two_pickup));
            $departure_date_new = date("Y-m-d", strtotime($item->op_two_pickup));
            $departure_time = date("H:i", strtotime($item->op_two_pickup));
            $destination = $item->to_name;
        endif;

        if($type == "departure" && $is_round_trip == 0):
            $destination = $item->from_name;
            $departure_date = date("Y-m-d H:i", strtotime($item->op_one_pickup));
            $departure_date_new = date("Y-m-d", strtotime($item->op_one_pickup));
            $departure_time = date("H:i", strtotime($item->op_one_pickup));
        endif;

        if($type == "departure" && $is_round_trip == 1):
            $destination = $item->to_name;
            $departure_date = date("Y-m-d H:i", strtotime($item->op_two_pickup));
            $departure_date_new = date("Y-m-d", strtotime($item->op_two_pickup));
            $departure_time = date("H:i", strtotime($item->op_two_pickup));
        endif;        

        // $destination = $item->to_name;
        // $departure_date = date("Y-m-d H:i", strtotime($item->op_one_pickup));
        // $departure_date_new = date("Y-m-d", strtotime($item->op_one_pickup));
        // $departure_time = date("H:i", strtotime($item->op_one_pickup));

        $message = '';
        $message_departure = '';
        if($destination_id == 1 && $lang == "en" && ( $type == "departure" )):
            $message = '<p><strong>The Cancun airport recommends users to arrive three hours in advance for international flights and two hours in advance for domestic flights.</strong></p>';
        endif;
        if($destination_id == 1 && $lang == "es" && ( $type == "departure" )):
            $message = '<p><strong>El aeropuerto de Cancún recomienda a sus usuarios llegar con tres horas de anticipación en vuelos internacionales y dos horas en vuelos nacionales.</strong></p>';
        endif;

        if($lang == "en" && ( $type == "departure" )):
            $message_departure = '<p>Remember we cannot wait more than 15 minutes after the assigned time. If you you exceed it extras fees could apply subject under availability.</p>';            
        endif;
        if($lang == "es" && ( $type == "departure" )):
            $message_departure = '<p>Recuerde que no podemos esperar más de 15 minutos después de la hora asignada. Si se excede, se aplicarán cargos adicionales, según disponibilidad.</p>';            
        endif;        

        if($lang == "en"):
            // return <<<EOF
            //         <p>Departure confirmation</p>
            //         <p>Dear $item->client_first_name | Reservation Number: <strong>$item->code</strong></p>
            //         <p>Thank you for choosing Caribbean Transfers the reason for this email is to confirm your pick up time. The date indicated on your reservation is $departure_date hrs. We will be waiting for you in $destination at that time.</p>
            //         $message
            //         <p>You can also confirm by phone: <strong>$item->transactional_phone_departure</strong></p>
            //         <p>Tips not included</p>
            //         $message_departure
            //     EOF;

            return <<<EOF
                        <p><strong>DEAR CUSTOMER,  FOR SECURITY REASONS REMEMBER TO PRESENT AN ID WHAT IT MATCH WITH THE NAME OF THE CARDHOLDER AND SIGN THE PAYMENT AUTHORIZATION FORM IN ORDER TO ABOARD THE VEHICLE OTHERWISE YOU WILL BE ASKED TO PAY IN CASH AND THEN THE ONLINE PAYMENT WILL BE REINBURSED IN FULL BACK TO THE CARDHOLDER'S ACCOUNT. THIS INFORMATION IS REQUIRED BY THE  BANKS INSTITUTION TO PROOF THE PAYMENT HAS BEEN GENUINELY AUTHORIZED.</strong></p>

                        <p>Departure confirmation</p>
                        <p>Dear $item->client_first_name | Booking Number: <strong>$item->code</strong></p>
                        <p>Thank you for choosing Caribbean Transfers. This is to confirm your departure transfer scheduled for <strong>$departure_date_new</strong>, at <strong>$departure_time</strong> hrs. Our driver will be waiting for you at the <strong>$destination</strong>.</p>
                        $message
                        <p><strong>Please note: we allow a 15-minute waiting period. After that, No Show or Waiting Time penalty may apply.</strong></p>
                        <p>To confirm or make changes, call us at: <strong>$item->transactional_phone_departure</strong></p>
                        <p>Important: Same-day changes may incur penalty fees. Please double-check that your transfer date and time are correct.</p>
                    EOF;            
        else:
            return <<<EOF
                    <p><strong>ESTIMADO CLIENTE, POR MOTIVOS DE SEGURIDAD, RECUERDE PRESENTAR UNA IDENTIFICACIÓN QUE COINCIDA CON EL NOMBRE DEL TITULAR DE LA TARJETA Y FIRMAR EL FORMULARIO DE AUTORIZACIÓN DE PAGO PARA PODER SUBIR AL VEHÍCULO. DE LO CONTRARIO, SE LE SOLICITARÁ QUE PAGUE EN EFECTIVO Y LUEGO EL PAGO EN LÍNEA SE REEMBOLSARÁ ÍNTEGRAMENTE A LA CUENTA DEL TITULAR DE LA TARJETA. ESTA INFORMACIÓN ES REQUERIDA POR LA INSTITUCIÓN BANCARIA PARA COMPROBAR QUE EL PAGO HA SIDO AUTORIZADO.</strong></p>

                    <p>Confirmación de salida</p>
                    <p>Estimado/a $item->client_first_name | Reservación No: <strong>$item->code</strong></p>
                    <p>Gracias por elegir a Caribbean Transfers el motivo de este correo es confirmar su hora de recolección. La fecha indicada en su reserva es $departure_date hrs. Le estaremos esperando en $destination a esa hora.</p>
                    $message
                    <p>También puedes confirmar por teléfono: <strong>$item->transactional_phone_departure</strong></p>
                    <p>Propinas no incluidas</p>
                    $message_departure
                EOF;           
        endif;
    }

    public function sendPaymentRequest($request){
        $item = DB::select("SELECT sit.payment_domain, sit.transactional_phone, rez.client_email, rez.client_first_name, rez.id as reservation_id, sit.success_payment_url, sit.cancel_payment_url
                            FROM reservations as rez 
                                INNER JOIN sites as sit ON sit.id = rez.site_id
                            WHERE rez.id = :id", ['id' => $request['item_id'] ]);
        $item = $item[0];
        $lang = $request['lang'];

        try {
            $response = $this->sendPaymentRequestApi($request['item_id'], $request['lang']);

            if($response['status'] == false):
                throw new Exception('No se pudo enviar el correo correctamente');
            endif;

            $email_data = array(
                "Messages" => array(
                    array(
                        "From" => array(
                            "Email" => 'bookings@caribbean-transfers.com',
                            "Name" => "Bookings"
                        ),
                        "To" => array(
                            array(
                                "Email" => $item->client_email,
                                "Name" => $item->client_first_name,
                            )
                        ),
                        "Bcc" => array(
                            array(
                                "Email" => 'bookings@caribbean-transfers.com',
                                "Name" => "Bookings"
                            )
                        ),
                        "Subject" => (($lang == "en")?'Payment request':'Solicitúd de pago'),
                        "TextPart" => (($lang == "en")?'Dear client':'Estimado cliente'),
                        "HTMLPart" => $response['data']
                    )
                )
            );

            $email_response = $this->sendMailjet($email_data);

            try {
                $this->createLog([
                    'type' => 'info',
                    'category' => 'mailjet_debug',
                    'message' => json_encode($email_response),
                ]);
            } catch(Exception $e) {
                $this->createLog([
                    'type' => 'error',
                    'category' => 'mailjet_debug',
                    'exception' => $e,
                ]);
            }

            if(isset($email_response['Messages'][0]['Status']) && $email_response['Messages'][0]['Status'] == "success") {
                $this->create_followUps($item->reservation_id, 'El usuario: '.auth()->user()->name.", a enviado E-mail (solicitúd de pago) para la reservación: ".$item->reservation_id, 'INTERN', 'SISTEMA');
            }
            else {
                throw new Exception('No se pudo enviar el correo correctamente');
            }

            return response()->json(['status' => "success"], 200);
        } catch(Exception $e) {
            $this->create_followUps($item->reservation_id, 'No fue posible enviar el e-mail de solicitúd de pago, por favor contactar a Desarrollo', 'INTERN', 'SISTEMA');

            return response()->json([
                'error' => [
                    'code' => 'mailing_system',
                    'message' => 'No se pudo enviar el correo correctamente'
                ]
            ], 500);
        }
    }

    private function orderByDateTime($a, $b) {
        return strtotime($b->created_at) - strtotime($a->created_at);
    }

    private function makePaymentURL($request, $item, $type = "STRIPE"){

        $data = [
            "type" => $type,
            "id" => $request->item_id,
            "language" => $request->lang,
            "success_url" => $item->success_payment_url,
            "cancel_url" => $item->cancel_payment_url,
            "redirect" => 1
        ];

        return 'https://api.caribbean-transfers.com/api/v1/reservation/payment/handler?'.http_build_query($data);        
    }

    public function follow_ups($request){
        $check = $this->create_followUps($request->reservation_id, "El usuario: ".auth()->user()->name.", agrego el siguiente comentario: ".$request->text, $request->type, Str::slug(strtoupper($request->name)));
        if($check){
            return response()->json(['message' => 'Follow up created successfully','success' => true], Response::HTTP_OK);
        }else{
            return response()->json(['message' => 'Error creating follow up','success' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function checkIfCanBeEdited($reservation_id) {
        $allow_edit = true;
        $reservation_item = ReservationsItem::where('reservation_id', $reservation_id)->first();

        if($reservation_item) {
            if($reservation_item->op_one_pickup && $reservation_item->op_two_pickup) {
                if($reservation_item->op_one_operation_close && $reservation_item->op_two_operation_close) {
                    $allow_edit = false;
                }
            }
            else if($reservation_item->op_one_pickup) {
                if($reservation_item->op_one_operation_close) $allow_edit = false;
            }
            else if($reservation_item->op_two_pickup) {
                if($reservation_item->op_two_operation_close) $allow_edit = false;
            }
        }

        return $allow_edit;
    }
}