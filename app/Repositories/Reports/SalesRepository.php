<?php

namespace App\Repositories\Reports;

use Exception;
use Illuminate\Http\Response;

//FACADES
use Illuminate\Support\Facades\DB;

//TRAIT
use App\Traits\FiltersTrait;
use App\Traits\QueryTrait;

class SalesRepository
{
    use FiltersTrait, QueryTrait;

    private $months = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];    

    public function index($request, $id)
    {
        ini_set('memory_limit', '-1'); // Sin límite
        set_time_limit(120); // Aumenta el límite a 60 segundos

        $data = [
            "init" => ( isset( $request->date ) && !empty( $request->date) ? explode(" - ", $request->date)[0] : date("Y-m-d") ),
            "end" => ( isset( $request->date ) && !empty( $request->date) ? explode(" - ", $request->date)[1] : date("Y-m-d") ),
            "filter_text" => ( isset( $request->filter_text ) && !empty( $request->filter_text ) ? $request->filter_text : NULL ),

            "is_round_trip" => ( isset($request->is_round_trip) ? $request->is_round_trip : NULL ),
            "is_today" => ( isset($request->is_today) ? $request->is_today : 0 ),
            "is_duplicated" => ( isset($request->is_duplicated) ? $request->is_duplicated : 0 ),
            "is_agency" => ( isset($request->is_agency) ? $request->is_agency : 0 ),
            "currency" => ( isset($request->currency) ? $request->currency : 0 ),

            "users" => ( isset($request->user) ? $request->user : NULL ),
            
            "site" => ( isset($request->site) ? $request->site : 0 ),
            "origin" => ( isset($request->origin) ? $request->origin : NULL ),
            "reservation_status" => ( isset($request->reservation_status) ? $request->reservation_status : 0 ),
            "product_type" => ( isset($request->product_type) ? $request->product_type : 0 ),
            "zone_one_id" => ( isset($request->zone_one_id) ? $request->zone_one_id : 0 ),
            "zone_two_id" => ( isset($request->zone_two_id) ? $request->zone_two_id : 0 ),
            "payment_status" => ( isset( $request->payment_status ) && !empty( $request->payment_status ) ? $request->payment_status : 0 ),
            "is_balance" => ( isset($request->is_balance) ? $request->is_balance : NULL ),
            "payment_method" => ( isset( $request->payment_method ) && !empty( $request->payment_method ) ? $request->payment_method : 0 ),
            "was_is_quotation" => ( isset($request->was_is_quotation) ? $request->was_is_quotation : NULL ),
            "reserve_rating" => ( isset($request->reserve_rating) ? $request->reserve_rating : NULL ),
            "is_commissionable" => ( isset($request->is_commissionable) ? $request->is_commissionable : NULL ),
            "cancellation_status" => ( isset( $request->cancellation_status ) && !empty( $request->cancellation_status ) ? $request->cancellation_status : 0 ),
            "is_pay_at_arrival" => ( isset($request->is_pay_at_arrival) ? $request->is_pay_at_arrival : NULL ),
            "refund_request_count" => ( isset($request->refund_request_count) ? $request->refund_request_count : NULL ),
            "is_paidaftersale" => ( isset($request->is_paidaftersale) ? $request->is_paidaftersale : 0 ),
        ];
        
        $query = ' AND rez.site_id NOT IN(21,11) AND rez.created_at BETWEEN :init AND :end AND rez.destination_id = :destination ';
        $havingConditions = []; $queryHaving = '';
        $queryData = [
            'init'          => ( isset( $request->date ) && !empty( $request->date) ? explode(" - ", $request->date)[0] : date("Y-m-d") ) . " 00:00:00",
            'end'           => ( isset( $request->date ) && !empty( $request->date) ? explode(" - ", $request->date)[1] : date("Y-m-d") ) . " 23:59:59",
            'destination'   => $id
        ];       

        //TIPO DE SERVICIO is_round_trip
        if(isset( $request->is_round_trip )){
            $params = "";
            foreach( $request->is_round_trip as $key => $is_round_trip ){
                $queryData['is_round_trip' . $key] = $is_round_trip;
                $params .= "FIND_IN_SET(:is_round_trip".$key.", is_round_trip) > 0 OR ";
            }
            $params = rtrim($params, ' OR ');
            $query .= " AND (".$params.") ";            
        }

        //RESERVAS OPERADAS EL MISMO DIA DE SU CREACION
        if(isset( $request->is_today ) && !empty( $request->is_today )){
            // $havingConditions[] = ( $request->is_today == 1 ? ' is_today != 0 ' : ' is_today = 0 ' );
            $havingConditions[] = ' is_today != 0 ';
        }

        //VER AGENCIAS
        if(!isset( $request->is_agency )){
            $query .= " AND site.type_site != 'AGENCY' ";
        }

        //MONEDA DE LA RESERVA
        if(isset( $request->currency ) && !empty( $request->currency )){
            $params = $this->parseArrayQuery($request->currency,"single");
            $query .= " AND rez.currency IN ($params) ";
        }

        //USUARIO
        if(isset( $request->user ) && !empty( $request->user )){
            $queryweb = "";
            if( in_array("0", $request->user) ){
                $queryweb = " OR us.id IS NULL ";
            }
            $params = $this->parseArrayQuery($request->user);
            $query .= " AND ( us.id IN ($params) $queryweb ) ";
        }

        //SITIO
        if(isset( $request->site ) && !empty( $request->site )){
            $params = $this->parseArrayQuery($request->site);
            $query .= " AND site.id IN ($params) ";
        }
        
        //ORIGEN DE VENTA
        if(isset( $request->origin ) && !empty( $request->origin )){
            $queryweb = "";
            if( in_array("0", $request->origin) ){
                $queryweb = " OR origin.id IS NULL ";
            }
            $params = $this->parseArrayQuery($request->origin);
            $query .= " AND ( origin.id IN ($params) $queryweb ) ";
        }

        //ESTATUS DE RESERVACIÓN
        if(isset( $request->reservation_status ) && !empty( $request->reservation_status )){
            $params = $this->parseArrayQuery($request->reservation_status,"single");

            if( in_array('DUPLICATED', $request->reservation_status) ) {
                $query .= " AND rez.is_duplicated IN (1,0) ";
            }
            $havingConditions[] = " reservation_status IN (".$params.") ";
        }

        //TIPO DE VEHÍCULO
        if(isset( $request->product_type ) && !empty( $request->product_type )){
            $params = "";
            foreach( $request->product_type as $key => $product_type ){
                $queryData['product_type' . $key] = $product_type;
                $params .= "FIND_IN_SET(:product_type".$key.", service_type_id) > 0 OR ";
            }
            $params = rtrim($params, ' OR ');
            $query .= " AND (".$params.") ";
        }

        //ZONA DE ORIGEN
        if(isset( $request->zone_one_id ) && !empty( $request->zone_one_id )){
            $params = "";
            foreach( $request->zone_one_id as $key => $zone_one_id ){
                $queryData['zone_one_id' . $key] = $zone_one_id;
                $params .= "FIND_IN_SET(:zone_one_id".$key.", zone_one_id) > 0 OR ";
            }
            $params = rtrim($params, ' OR ');
            $query .= " AND (".$params.") ";
        }

        //ZONA DE DESTINO
        if(isset( $request->zone_two_id ) && !empty( $request->zone_two_id )){
            $params = "";
            foreach( $request->zone_two_id as $key => $zone_two_id ){
                $queryData['zone_two_id' . $key] = $zone_two_id;
                $params .= "FIND_IN_SET(:zone_two_id".$key.", zone_two_id) > 0 OR ";
            }
            $params = rtrim($params, ' OR ');
            $query .= " AND (".$params.") ";
        }

        //ESTATUS DE PAGO
        if(isset( $request->payment_status ) && !empty( $request->payment_status )){
            $params = $this->parseArrayQuery($request->payment_status,"single");
            $havingConditions[] = " payment_status IN (".$params.") ";
        }

        //RESERVAS CON UN BALANCE
        if(isset( $request->is_balance )){
            $havingConditions[] = ( $request->is_balance == 1 ? ' total_balance > 0 ' : ' total_balance <= 0 ' );
        }        

        //METODO DE PAGO
        if(isset( $request->payment_method ) && !empty( $request->payment_method )){
            $params = "";
            foreach( $request->payment_method as $key => $payment_method ){
                $queryData['payment_method' . $key] = $payment_method;
                $params .= "FIND_IN_SET(:payment_method".$key.", payment_type_name) > 0 OR ";
            }
            $params = rtrim($params, ' OR ');
            $havingConditions[] = " (".$params.") "; 
        }

        //FUE COTIZACIÓN
        if( $request->was_is_quotation !=  NULL ){
            $params = $request->was_is_quotation;
            $query .= " AND rez.is_quotation = 0 AND rez.was_is_quotation = $params ";
        }
        
        //TIPO DE LIKE
        if(isset( $request->reserve_rating )){
            $params = $request->reserve_rating;
            $query .= " AND rez.reserve_rating = $params ";      
        }

        //RESERVAS COMISIONABLES
        if(isset( $request->is_commissionable )){
            $params = $request->is_commissionable;
            $query .= " AND rez.is_commissionable = $params ";
        }

        //MOTIVOS DE CANCELACIÓN
        if(isset( $request->cancellation_status ) && !empty( $request->cancellation_status )){
            $params = $this->parseArrayQuery($request->cancellation_status);
            $query .= " AND tc.id IN ($params) ";
        }

        //PAGO A LA LLEGADA
        if( $request->is_pay_at_arrival !=  NULL ){
            $params = $request->is_pay_at_arrival;
            $query .= " AND rez.pay_at_arrival = $params ";
        }

        if(isset( $request->refund_request_count )){
            $havingConditions[] = ( $request->refund_request_count == 1 ? ' refund_request_count > 0 ' : ' refund_request_count <= 0 ' );
        }

        if(isset( $request->is_paidaftersale ) && !empty( $request->is_paidaftersale )){
            $havingConditions[] = " transportation_payment_status = 'PAID_AFTER_SALE' ";
        }
        
        if(isset( $request->filter_text ) && !empty( $request->filter_text )){
            $queryData = [];
            $query  = " AND (
                        ( CONCAT(rez.client_first_name,' ',rez.client_last_name) like '%".$data['filter_text']."%') OR
                        ( rez.client_phone like '%".$data['filter_text']."%') OR
                        ( rez.client_email like '%".$data['filter_text']."%') OR
                        ( rez.reference like '%".$data['filter_text']."%') OR
                        ( it.code like '%".$data['filter_text']."%' )
                    )";
        }

        if( !empty($havingConditions) ){
            $queryHaving = " HAVING " . implode(' AND ', $havingConditions);
        }

        // dd($query, $queryHaving, $queryData);
        $bookings = $this->queryBookings($query, $queryHaving, $queryData);
        
        return view('reports.sales.index', [
            'breadcrumbs' => [
                [
                    "route" => "",
                    "name" => "Reporte de ventas del <strong>" . date("d", strtotime($data['init'])) . " al ". date("d", strtotime($data['end'])) .  " de " . $this->months[str_replace("0","",date("m", strtotime($data['init'])))] ."</strong>",
                    "active" => true
                ]
            ],
            'bookings' => $bookings,
            'exchange' => $this->Exchange(date("Y-m-d", strtotime($data['init'])), date("Y-m-d", strtotime($data['end']))),
            'data' => $data,
        ]);
    }

    private function helpers()
    {
        
    }
}