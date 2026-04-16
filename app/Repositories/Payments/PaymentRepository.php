<?php

namespace App\Repositories\Payments;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

//MODELS
use App\Models\Reservation;
use App\Models\Payment;

//REPOSITORY
use App\Repositories\Accounting\ConciliationRepository;

//TRAIS
use App\Traits\FollowUpTrait;
use App\Traits\PayPalTrait;

//MODELS
use App\Models\ReservationsRefund;

class PaymentRepository
{
    use PayPalTrait, FollowUpTrait;

    public function store($request)
    {
        $reference = trim($request->reference);

        $payment_with_same_reference_exists = Payment::where('payment_method', $request->payment_method)
        ->whereIn('payment_method', ['PAYPAL', 'STRIPE'])
        ->where('reference', $reference)
        ->exists();

        if($payment_with_same_reference_exists) {
            return response()->json([
                'status' => 'error',
                'message' => "Ya existe un pago con la referencia: $reference. Favor de poner una referencia diferente",
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();
            
            $payment = new Payment();
            $payment->description = 'Panel';
            $payment->total = $request->total;
            $payment->exchange_rate = $request->exchange_rate;
            $payment->status = 1;
            $payment->operation = $request->operation;
            $payment->payment_method = $request->payment_method;
            $payment->currency = $request->currency;
            $payment->reservation_id = $request->reservation_id;
            $payment->reference = $request->reference;
            $payment->user_id = auth()->user()->id;

            if( isset($request->reservation_refund_id) ){
                $payment->reservation_refund_id = $request->reservation_refund_id;
                $payment->category = $request->category;
            }

            if( isset($request->is_conciliated) && $request->is_conciliated == 1 ){
                if( $request->payment_method != "PAYPAL" && $request->payment_method != "STRIPE" && $request->payment_method != "CARD" ){
                    $payment->is_conciliated = $request->is_conciliated;
                    $payment->total_net = $request->total;
                    $payment->conciliation_comment = $request->conciliation_comment;
                }
            }

            if( $payment->save() ){
                $this->create_followUps($request->reservation_id, 'El usuario: '.auth()->user()->name.', agrego un pago tipo: '.$request->payment_method.', por un monto de: '.$request->total.' '.$request->currency, 'HISTORY', 'CREATE_PAYMENT');
                $booking = Reservation::find($request->reservation_id);
    
                //ACTUALIZAMOS EL ESTATUS DE LA RESERVA, CUANDO SE AGREGA UN PAGO Y ESTA ES COTIZACIÓN        
                if( $booking && $booking->is_quotation == 1 ){
                    $booking->is_quotation = 0;
                    $booking->expires_at = NULL;
                    $booking->save();
                }
    
                //AQUI REGISTRAMOS EL PAGO, PARA SABER SI UN AGENTE O SUPERVISOR DE CALLCENTER LE ESTA DANDO SEGUIMIENTO, LO HACEMOS MEDIANTE EL ROL
                // 3 Gerente - Call Center
                // 4 Agente - Call Center
                $roles = session()->get('roles');
                if( isset($request->type_site) && !empty($request->type_site) && ( in_array(3, $roles['roles']) || in_array(4, $roles['roles']) ) ){
                    if( $booking->type_site == "CALLCENTER" ){
                        $booking->agent_id_after_sales = auth()->user()->id;
                    }else{
                        $booking->agent_id_pull_sales = auth()->user()->id;
                    }
                    $booking->type_after_sales = ( $request->platform == "Bookign" ? "PENDING" : "SPAM" );
                    $booking->save();
                }

                if( isset($request->reservation_refund_id) ){                    
                    $refund = ReservationsRefund::find($request->reservation_refund_id);
                    $refund->status = "REFUND_COMPLETED";
                    $refund->end_at = date('Y-m-d H:m:s');
                    $refund->link_refund = $request->link_refund;
                    $refund->save();
                }
            }

            DB::commit();

            // Payment created successfully
            return response()->json([
                'status' => 'success',
                'message' => 'El pago se creo correctamente',
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                // 'message' => 'Error al crear el pago, contacte a soporte',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($request,$payment)
    {
        $reference = trim($request->reference);

        $payment_with_same_reference_exists = Payment::where('id', '!=', $payment->id)
        ->where('reservation_id', $request->reservation_id)
        ->where('payment_method', $request->payment_method)
        ->where('reference', $reference)
        ->exists();

        if($payment_with_same_reference_exists) {
            return response()->json([
                'status' => 'error',
                'message' => "Ya existe un pago con la referencia: $reference. Favor de poner una referencia diferente",
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            //VALIDAMOS SI NO ESTA CONCILIADNO EL PAGO, PERO ALGUN DATO ES DIFERENTE DEL ORIGINAL GUARDA UN LOG
            if( !isset($request->is_conciliated) && ( ( $payment->payment_method != $request->payment_method ) || ( $payment->total != $request->total ) || ( $payment->currency != $request->currency ) ) ){
                $this->create_followUps($request->reservation_id, 'El usuario: '.auth()->user()->name.', actualizo el pago con ID: '.$payment->id.' de ( tipo: '.$payment->payment_method.', por un monto de: '.$payment->total.' '.$payment->currency.' ) a ( tipo: '.$request->payment_method.', por un monto de: '.$request->total.' '.$request->currency.' )', 'HISTORY', 'UPDATE_PAYMENT');
            }

            //
            if( isset($request->is_conciliated) && ( ( $payment->payment_method != $request->payment_method ) || ( $payment->total != $request->total ) || ( $payment->currency != $request->currency ) ) ){
                $this->create_followUps($request->reservation_id, 'El usuario: '.auth()->user()->name.', '.( $request->is_conciliated == 0 ? "desconcilio" : "concilio" ).' el pago con ID: '.$payment->id.' de ( tipo: '.$payment->payment_method.', por un monto de: '.$payment->total.' '.$payment->currency.' ) a ( tipo: '.$request->payment_method.', por un monto de: '.$request->total.' '.$request->currency.' )', 'HISTORY', 'PAYMENT_CONCILIATION');
            }

            //
            if( isset($request->is_conciliated) && $request->is_conciliated == 1 ){
                $this->create_followUps($request->reservation_id, 'El usuario: '.auth()->user()->name.', '.( $request->is_conciliated == 0 ? "desconcilio" : "concilio" ).' el pago con ID: '.$payment->id.' de ( tipo: '.$payment->payment_method.', por un monto de: '.$payment->total.' '.$payment->currency.' )', 'HISTORY', 'PAYMENT_CONCILIATION');
            }

            $payment->description = 'Panel';
            $payment->total = $request->total;
            $payment->exchange_rate = $request->exchange_rate;
            $payment->status = 1;
            $payment->operation = $request->operation;            
            $payment->payment_method = $request->payment_method;
            $payment->currency = $request->currency;
            $payment->reservation_id = $request->reservation_id;
            $payment->reference = $request->reference;            
            if( isset($request->is_conciliated) && $request->is_conciliated == 1 ){
                $conciliation = new ConciliationRepository();
                if( $request->payment_method == "PAYPAL" ){
                    $conciliation->conciliationPayPalPayment($request, $payment);
                }

                if( $request->payment_method == "STRIPE" || $request->payment_method == "CARD" ){
                    $conciliation->conciliationStripePayment($request, $payment);
                }

                if( $request->payment_method != "PAYPAL" && $request->payment_method != "STRIPE" && $request->payment_method != "CARD" ){
                    $payment->is_conciliated = $request->is_conciliated;
                    $payment->total_net = $request->total;
                    $payment->conciliation_comment = $request->conciliation_comment;
                }
            };
            $payment->save();

            DB::commit();

            // Payment updated successfully
            return response()->json([
                'status' => 'success',
                'message' => 'El pago se actualizo correctamente',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el pago, contacte a soporte',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($request,$payment)
    {
        try {
            DB::beginTransaction();
            
            $reservation = Reservation::find($payment->reservation_id);
            $payment->delete();

            $this->create_followUps($payment->reservation_id, 'El usuario: '.auth()->user()->name.', elimino el pago con ID: '.$payment->id.', por un monto de: '.$payment->total.' '.$payment->currency, 'HISTORY', 'DELETE_PAYMENT');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'El pago se elimino correctamente'
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar el pago, contacte a soporte'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function conciliation($request){
        try {
            DB::beginTransaction();

            if( isset($request->ids) && !empty($request->ids) ){
                foreach ($request->ids as $key => $id) {
                    $reservation = Reservation::with('sales')->where('id', $id)->first();
                    $total = 0;
                    foreach ($reservation->sales as $key => $sale) {
                        $total += $sale->total;
                    }

                    $payment = new Payment();
                    $payment->description = 'Panel';
                    $payment->total = $total;
                    $payment->exchange_rate = 1.00;
                    $payment->status = 1;
                    $payment->operation = ( $reservation->currency == "USD" ? "multiplication" : "division" );
                    $payment->payment_method = "TRANSFER";
                    $payment->currency = $reservation->currency;
                    $payment->reservation_id = $id;
                    $payment->reference = "CONCILIACION-".$id;
                    $payment->user_id = auth()->user()->id;
                    $payment->is_conciliated = 1;
                    $payment->total_net = $total;
                    $payment->conciliation_comment = "";
                    $payment->save();

                    $this->create_followUps($id, 'El usuario: '.auth()->user()->name.', agrego un pago tipo: TRANSFER, por un monto de: '.$total.' '.$reservation->currency, 'HISTORY', 'CREATE_PAYMENT');
                }

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'El pago se creo correctamente',
                ], Response::HTTP_CREATED);                
            }

            return response()->json([
                'status' => 'error',
                'message' => "No se encontron reservas para procesar.",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                // 'message' => 'Error al crear el pago, contacte a soporte',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}