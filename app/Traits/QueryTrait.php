<?php

namespace App\Traits;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

trait QueryTrait
{
    /**
     * UTILIZADO EN SECCION DE FINANZAS
     */
    public function queryRefunds($query, $query2, $queryData){
        $bookings = DB::select("SELECT 
                                    refund.id,
                                    refund.message_refund,
                                    refund.response_message,
                                    refund.status,
                                    refund.end_at,
                                    refund.link_refund,

                                    rez.id AS reservation_id,
                                    rez.categories,
                                    CONCAT(rez.client_first_name,' ',rez.client_last_name) as full_name,
                                    rez.client_email,
                                    rez.client_phone,
                                    rez.currency,
                                    rez.is_cancelled,
                                    rez.is_commissionable,                                    
                                    rez.site_id,
                                    rez.pay_at_arrival,
                                    rez.reference,
                                    rez.affiliate_id,
                                    rez.terminal,
                                    rez.comments,
                                    rez.is_duplicated,
                                    rez.open_credit,
                                    rez.is_complete,
                                    rez.created_at,
                                    rez.is_quotation,
                                    rez.was_is_quotation,
                                    rez.campaign,
                                    rez.is_last_minute,
                                    
                                    us.id AS employee_code,
                                    us.status AS employee_status,
                                    us.name AS employee,
                                    
                                    site.id as site_code,
                                    site.type_site AS type_site,
                                    site.name AS site_name,
                                    origin.code AS origin_code,
                                    tc.name_es AS cancellation_reason,
                                    GROUP_CONCAT(DISTINCT it.code ORDER BY it.code ASC SEPARATOR ',') AS reservation_codes,
                                    GROUP_CONCAT(DISTINCT it.zone_one_name ORDER BY it.zone_one_name ASC SEPARATOR ',') AS destination_name_from,
                                    GROUP_CONCAT(DISTINCT it.zone_one_id ORDER BY it.zone_one_id ASC SEPARATOR ',') AS zone_one_id,
                                    GROUP_CONCAT(DISTINCT it.from_name SEPARATOR ',') AS from_name,
                                    GROUP_CONCAT(DISTINCT it.zone_two_name ORDER BY it.zone_two_name ASC SEPARATOR ',') AS destination_name_to,
                                    GROUP_CONCAT(DISTINCT it.zone_two_id ORDER BY it.zone_two_id ASC SEPARATOR ',') AS zone_two_id,
                                    GROUP_CONCAT(DISTINCT it.to_name SEPARATOR ',') AS to_name,
                                    GROUP_CONCAT(DISTINCT it.service_type_id ORDER BY it.service_type_id ASC SEPARATOR ',') AS service_type_id,
                                    GROUP_CONCAT(DISTINCT it.service_type_name ORDER BY it.service_type_name ASC SEPARATOR ',') AS service_type_name,
                                    GROUP_CONCAT(DISTINCT it.pickup_from ORDER BY it.pickup_from ASC SEPARATOR ',') AS pickup_from,
                                    GROUP_CONCAT(DISTINCT it.pickup_to ORDER BY it.pickup_to ASC SEPARATOR ',') AS pickup_to,
                                    GROUP_CONCAT(DISTINCT it.one_service_status ORDER BY it.one_service_status ASC SEPARATOR ',') AS one_service_status,
                                    GROUP_CONCAT(DISTINCT it.two_service_status ORDER BY it.two_service_status ASC SEPARATOR ',') AS two_service_status,
                                    SUM(it.passengers) as passengers,
                                    COALESCE(SUM(it.op_one_pickup_today) + SUM(it.op_two_pickup_today), 0) as is_today,
                                    SUM(it.is_round_trip) as is_round_trip,

                                    COALESCE(SUM(s.total_sales), 0) as total_sales,
                                    COALESCE(SUM(p.total_payments), 0) as total_payments,                                    
                                    COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) AS total_balance, -- si hay balance
                                    SUM(p.is_conciliated) as is_conciliated,
                                    CASE
                                        WHEN rez.is_cancelled = 1   AND rez.was_is_quotation = 1  THEN 'EXPIRED_QUOTATION'
                                        WHEN rez.is_cancelled = 1   THEN 'CANCELLED'
                                        WHEN rez.is_duplicated = 1  THEN 'DUPLICATED'
                                        WHEN rez.open_credit = 1    THEN 'OPENCREDIT'
                                        WHEN rez.is_quotation = 1   THEN 'QUOTATION'
                                        WHEN site.is_cxc = 1 AND ( COALESCE(SUM(p.total_payments), 0) = 0 OR ( COALESCE(SUM(p.total_payments), 0) < COALESCE(SUM(s.total_sales), 0) ) ) THEN 'CREDIT'
                                        WHEN rez.pay_at_arrival = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'PAY_AT_ARRIVAL'
                                        WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) > 0 THEN 'PENDING'
                                        WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'CONFIRMED'
                                        ELSE 'UNKNOWN'
                                    END AS reservation_status,
                                    CASE
                                        WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
                                        WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'PAID'
                                        ELSE 'PENDING'
                                    END AS payment_status,
                                    GROUP_CONCAT(
                                        DISTINCT 
                                        CASE
                                            WHEN site.is_cxc = 1 AND p.payment_type_name IS NULL THEN 'CREDIT'
                                            WHEN p.payment_type_name IS NOT NULL THEN p.payment_type_name
                                            WHEN rez.pay_at_arrival = 1 THEN 'CASH'  -- Asumiendo que pay_at_arrival=1 significa pago en efectivo
                                            ELSE 'NO DEFENIDO'
                                        END
                                    ORDER BY p.payment_type_name ASC SEPARATOR ', ') AS payment_type_name,
                                    GROUP_CONCAT(DISTINCT p.payment_details ORDER BY p.payment_details ASC SEPARATOR ', ') AS payment_details
                                FROM reservations_refunds as refund
                                    INNER JOIN reservations as rez ON rez.id = refund.reservation_id
                                    INNER JOIN sites as site ON site.id = rez.site_id
                                    LEFT OUTER JOIN users as us ON us.id = rez.call_center_agent_id
                                    LEFT OUTER JOIN origin_sales as origin ON origin.id = rez.origin_sale_id
                                    LEFT OUTER JOIN types_cancellations as tc ON tc.id = rez.cancellation_type_id
                                    LEFT JOIN (
                                        SELECT 
                                            reservation_id, 
                                            ROUND(COALESCE(SUM(total), 0), 2) as total_sales
                                        FROM sales
                                            WHERE deleted_at IS NULL 
                                        GROUP BY reservation_id
                                    ) as s ON s.reservation_id = rez.id
                                    LEFT JOIN (
                                        SELECT 
                                            reservation_id,
                                            is_conciliated,
                                            ROUND(SUM(CASE 
                                                WHEN operation = 'multiplication' THEN total * exchange_rate
                                                WHEN operation = 'division' THEN total / exchange_rate
                                                ELSE total END), 2) AS total_payments,
                                            GROUP_CONCAT(DISTINCT payment_method ORDER BY payment_method ASC SEPARATOR ',') AS payment_type_name,
                                            GROUP_CONCAT(
                                                DISTINCT CONCAT(
                                                    payment_method, ' | ', 
                                                    ROUND(CASE 
                                                        WHEN operation = 'multiplication' THEN total * exchange_rate
                                                        WHEN operation = 'division' THEN total / exchange_rate
                                                        ELSE total END, 2), ' | ', 
                                                    reference
                                            ) ORDER BY payment_method ASC SEPARATOR ', ') AS payment_details
                                        FROM payments
                                            WHERE deleted_at IS NULL
                                        GROUP BY reservation_id, is_conciliated
                                    ) as p ON p.reservation_id = rez.id
                                    LEFT JOIN (
                                        SELECT  
                                            it.reservation_id, 
                                            it.is_round_trip,
                                            SUM(it.passengers) as passengers,
                                            GROUP_CONCAT(DISTINCT it.code ORDER BY it.code ASC SEPARATOR ',') AS code,
                                            GROUP_CONCAT(DISTINCT zone_one.name ORDER BY zone_one.name ASC SEPARATOR ',') AS zone_one_name,
                                            GROUP_CONCAT(DISTINCT zone_one.id ORDER BY zone_one.id ASC SEPARATOR ',') AS zone_one_id,
                                            GROUP_CONCAT(DISTINCT it.from_name SEPARATOR ',') AS from_name,
                                            GROUP_CONCAT(DISTINCT zone_two.name ORDER BY zone_two.name ASC SEPARATOR ',') AS zone_two_name, 
                                            GROUP_CONCAT(DISTINCT zone_two.id ORDER BY zone_two.id ASC SEPARATOR ',') AS zone_two_id,
                                            GROUP_CONCAT(DISTINCT it.to_name SEPARATOR ',') AS to_name,
                                            GROUP_CONCAT(DISTINCT dest.id ORDER BY dest.id ASC SEPARATOR ',') AS service_type_id,
                                            GROUP_CONCAT(DISTINCT dest.name ORDER BY dest.name ASC SEPARATOR ',') AS service_type_name,
                                            GROUP_CONCAT(DISTINCT it.op_one_pickup ORDER BY it.op_one_pickup ASC SEPARATOR ',') AS pickup_from,
                                            GROUP_CONCAT(DISTINCT it.op_two_pickup ORDER BY it.op_two_pickup ASC SEPARATOR ',') AS pickup_to,
                                            GROUP_CONCAT(DISTINCT it.op_one_status ORDER BY it.op_one_status ASC SEPARATOR ',') AS one_service_status,
                                            GROUP_CONCAT(DISTINCT it.op_two_status ORDER BY it.op_two_status ASC SEPARATOR ',') AS two_service_status,
                                            MAX(CASE WHEN DATE(it.op_one_pickup) = DATE(rez.created_at) THEN 1 ELSE 0 END) AS op_one_pickup_today,
                                            MAX(CASE WHEN DATE(it.op_two_pickup) = DATE(rez.created_at) THEN 1 ELSE 0 END) AS op_two_pickup_today
                                        FROM reservations_items as it
                                            INNER JOIN zones as zone_one ON zone_one.id = it.from_zone
                                            INNER JOIN zones as zone_two ON zone_two.id = it.to_zone
                                            INNER JOIN destination_services as dest ON dest.id = it.destination_service_id
                                            INNER JOIN reservations as rez ON rez.id = it.reservation_id
                                        GROUP BY it.reservation_id, it.is_round_trip
                                    ) as it ON it.reservation_id = rez.id
                                    WHERE 1=1 {$query}
                                GROUP BY refund.id, refund.message_refund, refund.response_message, refund.status, 
                                        refund.end_at, refund.link_refund, rez.id, rez.categories, rez.client_first_name, rez.client_last_name,
                                        rez.client_email, rez.client_phone, rez.currency, rez.is_cancelled, rez.is_commissionable,
                                        rez.site_id, rez.pay_at_arrival, rez.reference, rez.affiliate_id, rez.terminal,
                                        rez.comments, rez.is_duplicated, rez.open_credit, rez.is_complete, rez.created_at,
                                        rez.is_quotation, rez.was_is_quotation, rez.campaign, rez.is_last_minute,
                                        us.id, us.status, us.name, site.id, site.type_site, site.name, site.is_cxc,
                                        origin.code, tc.name_es {$query2}",
                                    $queryData);

                                    //, site.is_cxc

        if(sizeof( $bookings ) > 0):
            usort($bookings, array($this, 'orderByDateTime'));
        endif;

        return $bookings;
    }    

    // pay_at_arrival: INDICA PAGO A LA LLEGADA PORQUE ELIGIO LA OPCION DE PAGO EN EFECTIVO
    public function queryBookings($query, $query2, $queryData){
        $bookings = DB::select("SELECT 
                                    rez.id AS reservation_id, 
                                    CONCAT(rez.client_first_name,' ',rez.client_last_name) as full_name,
                                    rez.client_email,
                                    rez.client_phone,
                                    rez.currency,
                                    rez.is_cancelled,
                                    rez.is_commissionable,                                    
                                    rez.site_id,
                                    rez.pay_at_arrival,
                                    rez.pay_now_amount,
                                    rez.reference,
                                    rez.affiliate_id,
                                    rez.terminal,
                                    rez.comments,
                                    rez.is_duplicated,
                                    rez.open_credit,
                                    rez.is_complete,
                                    rez.created_at,
                                    rez.is_quotation,
                                    rez.was_is_quotation,
                                    rez.campaign,
                                    rez.reserve_rating,
                                    rez.is_last_minute,
                                    
                                    us.id AS employee_code,
                                    us.status AS employee_status,
                                    us.name AS employee,

                                    site.id as site_code,
                                    site.type_site AS type_site,
                                    site.name AS site_name,
                                    origin.code AS origin_code,
                                    tc.name_es AS cancellation_reason,
                                    GROUP_CONCAT(DISTINCT it.code ORDER BY it.code ASC SEPARATOR ',') AS reservation_codes,
                                    GROUP_CONCAT(DISTINCT it.zone_one_name ORDER BY it.zone_one_name ASC SEPARATOR ',') AS destination_name_from,
                                    GROUP_CONCAT(DISTINCT it.zone_one_id ORDER BY it.zone_one_id ASC SEPARATOR ',') AS zone_one_id,
                                    GROUP_CONCAT(DISTINCT it.from_name SEPARATOR ',') AS from_name,
                                    GROUP_CONCAT(DISTINCT it.zone_two_name ORDER BY it.zone_two_name ASC SEPARATOR ',') AS destination_name_to,
                                    GROUP_CONCAT(DISTINCT it.zone_two_id ORDER BY it.zone_two_id ASC SEPARATOR ',') AS zone_two_id,
                                    GROUP_CONCAT(DISTINCT it.to_name SEPARATOR ',') AS to_name,
                                    GROUP_CONCAT(DISTINCT it.service_type_id ORDER BY it.service_type_id ASC SEPARATOR ',') AS service_type_id,
                                    GROUP_CONCAT(DISTINCT it.service_type_name ORDER BY it.service_type_name ASC SEPARATOR ',') AS service_type_name,
                                    GROUP_CONCAT(DISTINCT it.pickup_from ORDER BY it.pickup_from ASC SEPARATOR ',') AS pickup_from,
                                    GROUP_CONCAT(DISTINCT it.pickup_to ORDER BY it.pickup_to ASC SEPARATOR ',') AS pickup_to,
                                    GROUP_CONCAT(DISTINCT it.one_service_status ORDER BY it.one_service_status ASC SEPARATOR ',') AS one_service_status,
                                    GROUP_CONCAT(DISTINCT it.two_service_status ORDER BY it.two_service_status ASC SEPARATOR ',') AS two_service_status,

                                    -- NUEVO: Tipo de servicio para cada item (mejorado para round trips)
                                    GROUP_CONCAT(DISTINCT it.service_types ORDER BY it.service_types ASC SEPARATOR ',') AS service_types,

                                    GROUP_CONCAT(DISTINCT it.one_cancellation_level ORDER BY it.one_cancellation_level ASC SEPARATOR ',') AS one_cancellation_level,
                                    GROUP_CONCAT(DISTINCT it.two_cancellation_level ORDER BY it.two_cancellation_level ASC SEPARATOR ',') AS two_cancellation_level,                                    
                                    SUM(it.passengers) as passengers,
                                    COALESCE(SUM(it.op_one_pickup_today) + SUM(it.op_two_pickup_today), 0) as is_today,
                                    COALESCE(SUM(it.op_one_pickup_tomorrow) + SUM(it.op_two_pickup_tomorrow), 0) as is_tomorrow,
                                    SUM(it.is_round_trip) as is_round_trip,
                                    MAX(it.is_same_day_round_trip) AS is_same_day_round_trip,

                                    COALESCE(SUM(s.total_sales), 0) as total_sales,
                                    COALESCE(SUM(s.total_sales_credit), 0) as total_sales_credit,
                                    -- NUEVO: Monto de ventas de transportación
                                    COALESCE(s.transportation_sales, 0) as transportation_sales_amount,

                                    COALESCE(SUM(p.total_payments), 0) as total_payments,
                                    COALESCE(SUM(p.total_payments_credit), 0) as total_payments_credit,
                                    -- NUEVO: Campos de pago de transportación
                                    COALESCE(p.transportation_payment_amount, 0) as transportation_payment_amount,
                                    p.transportation_payment_date,
                                    CASE 
                                        WHEN p.transportation_paid_later = 1 THEN 'PAID_AFTER_SALE'
                                        WHEN s.transportation_sales > 0 AND p.transportation_payment_amount >= s.transportation_sales THEN 'PAID'
                                        WHEN s.transportation_sales > 0 AND p.transportation_payment_amount > 0 AND p.transportation_payment_amount < s.transportation_sales THEN 'PARTIALLY_PAID'
                                        WHEN s.transportation_sales > 0 THEN 'NOT_PAID'
                                        ELSE 'NO_TRANSPORTATION_SALE'
                                    END AS transportation_payment_status,
                                    
                                    p.credit_conciliation_status as credit_conciliation_status,
                                    COALESCE(p.credit_payment_ids) AS credit_payment_ids,

                                    GROUP_CONCAT(DISTINCT p.credit_references_agency ORDER BY p.credit_references_agency ASC SEPARATOR ', ') AS credit_references_agency,
                                    GROUP_CONCAT(DISTINCT p.credit_references_payment ORDER BY p.credit_references_payment ASC SEPARATOR ', ') AS credit_references_payment,
                                    GROUP_CONCAT(DISTINCT p.credit_comments ORDER BY p.credit_comments ASC SEPARATOR ', ') AS credit_comments,
                                    GROUP_CONCAT(DISTINCT p.credit_conciliation_dates ORDER BY p.credit_conciliation_dates ASC SEPARATOR ', ') AS credit_conciliation_dates,
                                    GROUP_CONCAT(DISTINCT p.credit_deposit_dates ORDER BY p.credit_deposit_dates ASC SEPARATOR ', ') AS credit_deposit_dates,

                                    COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) AS total_balance,

                                    -- Nuevo esquema de pago parcial
                                    COALESCE(rez.pay_now_amount, COALESCE(SUM(s.total_sales), 0)) AS pay_now_amount,
                                    GREATEST(
                                        COALESCE(SUM(s.total_sales), 0)
                                        - COALESCE(rez.pay_now_amount, COALESCE(SUM(s.total_sales), 0)),
                                        0
                                    ) AS pay_at_arrival_amount,
                                    GREATEST(
                                        COALESCE(rez.pay_now_amount, COALESCE(SUM(s.total_sales), 0))
                                        - COALESCE(SUM(p.total_payments), 0),
                                        0
                                    ) AS online_balance,

                                    -- Información de reembolsos
                                    CASE WHEN rr.reservation_id IS NOT NULL THEN 1 ELSE 0 END as has_refund_request,
                                    COALESCE(rr.refund_count, 0) as refund_request_count,
                                    CASE 
                                        WHEN rr.reservation_id IS NULL THEN 'NO_REFUND'
                                        WHEN rr.pending_refund_count > 0 THEN 'REFUND_REQUESTED'
                                        ELSE 'REFUND_COMPLETED'
                                    END as refund_status,
                                    CASE
                                        WHEN rez.is_cancelled = 1 AND rez.was_is_quotation = 1 THEN 'EXPIRED_QUOTATION'
                                        WHEN rez.is_cancelled = 1 THEN 'CANCELLED'
                                        WHEN rez.is_duplicated = 1 THEN 'DUPLICATED'
                                        WHEN rez.open_credit = 1 THEN 'OPENCREDIT'
                                        WHEN rez.is_quotation = 1 THEN 'QUOTATION'
                                        WHEN site.is_cxc = 1
                                             AND (
                                                 COALESCE(SUM(p.total_payments), 0) = 0
                                                 OR COALESCE(SUM(p.total_payments), 0) < COALESCE(SUM(s.total_sales), 0)
                                             )
                                            THEN 'CREDIT'
                                        WHEN rez.pay_at_arrival = 1
                                             AND COALESCE(SUM(p.total_payments), 0) = 0
                                            THEN 'PAY_AT_ARRIVAL'
                                        WHEN (
                                            COALESCE(rez.pay_now_amount, COALESCE(SUM(s.total_sales), 0))
                                            - COALESCE(SUM(p.total_payments), 0)
                                        ) > 0
                                            THEN 'PENDING'
                                        ELSE 'CONFIRMED'
                                    END AS reservation_status,
                                    CASE
                                        WHEN site.is_cxc = 1
                                             AND COALESCE(SUM(p.total_payments), 0) = 0
                                            THEN 'CREDIT'
                                        WHEN (
                                            COALESCE(rez.pay_now_amount, COALESCE(SUM(s.total_sales), 0))
                                            - COALESCE(SUM(p.total_payments), 0)
                                        ) <= 0
                                            THEN 'PAID'
                                        ELSE 'PENDING'
                                    END AS payment_status,
                                    GROUP_CONCAT(
                                        DISTINCT 
                                        CASE
                                            WHEN site.is_cxc = 1 AND p.payment_type_name IS NULL THEN 'CREDIT'
                                            WHEN p.payment_type_name IS NOT NULL THEN p.payment_type_name
                                            WHEN rez.pay_at_arrival = 1 THEN 'CASH'  -- Asumiendo que pay_at_arrival=1 significa pago en efectivo
                                            ELSE 'SIN METODO DE PAGO'
                                        END
                                    ORDER BY p.payment_type_name ASC SEPARATOR ', ') AS payment_type_name,
                                    GROUP_CONCAT(DISTINCT p.payment_details ORDER BY p.payment_details ASC SEPARATOR ', ') AS payment_details
                                FROM reservations as rez
                                    INNER JOIN sites as site ON site.id = rez.site_id
                                    LEFT JOIN users as us ON us.id = rez.call_center_agent_id
                                    LEFT JOIN origin_sales as origin ON origin.id = rez.origin_sale_id
                                    LEFT JOIN types_cancellations as tc ON tc.id = rez.cancellation_type_id

                                    -- Reembolsos optimizados
                                    LEFT JOIN (
                                        SELECT 
                                            reservation_id,
                                            COUNT(*) as refund_count,
                                            SUM(CASE WHEN status != 'REFUND_COMPLETED' THEN 1 ELSE 0 END) as pending_refund_count
                                        FROM reservations_refunds
                                        GROUP BY reservation_id
                                    ) as rr ON rr.reservation_id = rez.id
                                    
                                    -- Ventas optimizadas
                                    LEFT JOIN (
                                        SELECT 
                                            reservation_id, 
                                            ROUND(SUM(total), 2) as total_sales,
                                            ROUND(SUM(CASE WHEN sale_type_id = 1 THEN total ELSE 0 END), 2) AS total_sales_credit,
                                            ROUND(SUM(CASE WHEN sale_type_id = 1 THEN total ELSE 0 END), 2) AS transportation_sales,
                                            MAX(CASE WHEN sale_type_id = 1 THEN created_at END) AS transportation_sale_date
                                        FROM sales
                                        WHERE deleted_at IS NULL 
                                        GROUP BY reservation_id
                                    ) as s ON s.reservation_id = rez.id

                                    -- JOINs para tabla de pagos (payments)
                                    -- PAGOS OPTIMIZADOS (CRÍTICO)
                                    LEFT JOIN (
                                        SELECT 
                                            p.reservation_id,
                                            -- Totales
                                            ROUND(SUM(CASE
                                                WHEN p.category IN ('PAYOUT', 'PAYOUT_CREDIT_PAID') THEN 
                                                    CASE p.operation
                                                        WHEN 'multiplication' THEN p.total * p.exchange_rate
                                                        WHEN 'division' THEN p.total / p.exchange_rate
                                                        ELSE p.total 
                                                    END
                                                ELSE 0
                                            END), 2) AS total_payments,

                                            ROUND(SUM(CASE
                                                WHEN p.category IN ('PAYOUT_CREDIT_PENDING', 'PAYOUT_CREDIT_PAID') THEN 
                                                    CASE p.operation
                                                        WHEN 'multiplication' THEN p.total * p.exchange_rate
                                                        WHEN 'division' THEN p.total / p.exchange_rate
                                                        ELSE p.total 
                                                    END
                                                ELSE 0
                                            END), 2) AS total_payments_credit,                                            

                                            -- Transportación (SIN subconsultas correlacionadas)
                                            MAX(CASE 
                                                WHEN st.transportation_total > 0 
                                                    AND p.total >= st.transportation_total
                                                    AND DATE(p.created_at) > DATE(st.max_transportation_date)
                                                THEN 1 
                                                ELSE 0 
                                            END) AS transportation_paid_later,

                                            ROUND(SUM(CASE
                                                WHEN st.transportation_total > 0 
                                                    AND p.total >= st.transportation_total
                                                    AND DATE(p.created_at) > DATE(st.max_transportation_date)
                                                THEN 
                                                    CASE p.operation
                                                        WHEN 'multiplication' THEN p.total * p.exchange_rate
                                                        WHEN 'division' THEN p.total / p.exchange_rate
                                                        ELSE p.total 
                                                    END
                                                ELSE 0
                                            END), 2) AS transportation_payment_amount,

                                            MAX(CASE 
                                                WHEN st.transportation_total > 0 
                                                    AND p.total >= st.transportation_total
                                                    AND DATE(p.created_at) > DATE(st.max_transportation_date)
                                                THEN p.created_at 
                                                ELSE NULL 
                                            END) AS transportation_payment_date,

                                            -- Campos existentes de pagos
                                            CONCAT('[',
                                                GROUP_CONCAT(DISTINCT 
                                                    CASE WHEN p.payment_method = 'CREDIT' OR p.payment_method LIKE '%CREDIT%' THEN p.id END
                                                    ORDER BY p.id ASC SEPARATOR ','
                                                ), ']'
                                            ) AS credit_payment_ids,                                        
                                            
                                            CASE 
                                                WHEN MAX(CASE WHEN p.category IN ('PAYOUT_CREDIT', 'PAYOUT_CREDIT_PAID', 'PAYOUT_CREDIT_PENDING') THEN p.is_conciliated END) = 2 THEN 'pre-reconciled'
                                                WHEN MAX(CASE WHEN p.category IN ('PAYOUT_CREDIT', 'PAYOUT_CREDIT_PAID', 'PAYOUT_CREDIT_PENDING') THEN p.is_conciliated END) = 1 THEN 'reconciled'
                                                WHEN MAX(CASE WHEN p.category IN ('PAYOUT_CREDIT', 'PAYOUT_CREDIT_PAID', 'PAYOUT_CREDIT_PENDING') THEN p.is_conciliated END) = 3 THEN 'cxc'
                                                ELSE NULL
                                            END AS credit_conciliation_status,

                                            GROUP_CONCAT(DISTINCT CASE WHEN p.category IN ('PAYOUT_CREDIT_PAID', 'PAYOUT_CREDIT_PENDING') THEN p.reference_invoice END SEPARATOR ', ') AS credit_references_agency,
                                            GROUP_CONCAT(DISTINCT CASE WHEN p.category IN ('PAYOUT_CREDIT_PAID', 'PAYOUT_CREDIT_PENDING') THEN p.reference END SEPARATOR ', ') AS credit_references_payment,
                                            GROUP_CONCAT(DISTINCT CASE WHEN p.category IN ('PAYOUT_CREDIT_PAID', 'PAYOUT_CREDIT_PENDING') THEN p.conciliation_comment END SEPARATOR ', ') AS credit_comments,
                                            GROUP_CONCAT(DISTINCT CASE WHEN p.category IN ('PAYOUT_CREDIT_PAID', 'PAYOUT_CREDIT_PENDING') THEN DATE_FORMAT(p.date_conciliation, '%Y-%m-%d') END SEPARATOR ', ') AS credit_conciliation_dates,
                                            GROUP_CONCAT(DISTINCT CASE WHEN p.category IN ('PAYOUT_CREDIT_PAID', 'PAYOUT_CREDIT_PENDING') THEN DATE_FORMAT(p.deposit_date, '%Y-%m-%d') END SEPARATOR ', ') AS credit_deposit_dates,                                            
                                            GROUP_CONCAT(DISTINCT p.payment_method ORDER BY p.payment_method ASC SEPARATOR ',') AS payment_type_name,
                                            GROUP_CONCAT(
                                                DISTINCT CONCAT(
                                                    p.payment_method, ' | ', 
                                                    ROUND(CASE 
                                                        WHEN p.operation = 'multiplication' THEN p.total * p.exchange_rate
                                                        WHEN p.operation = 'division' THEN p.total / p.exchange_rate
                                                        ELSE p.total END, 2), ' | ', 
                                                    p.reference
                                            ) ORDER BY p.payment_method ASC SEPARATOR ', ') AS payment_details
                                        FROM payments p

                                        -- JOIN CRÍTICO: Precalculamos datos de transportación
                                        LEFT JOIN (
                                            SELECT reservation_id,
                                                SUM(CASE WHEN sale_type_id = 1 THEN total ELSE 0 END) AS transportation_total,
                                                MAX(CASE WHEN sale_type_id = 1 THEN created_at END) AS max_transportation_date
                                            FROM sales
                                            WHERE deleted_at IS NULL
                                            GROUP BY reservation_id
                                        ) st ON st.reservation_id = p.reservation_id
                                        
                                        WHERE p.deleted_at IS NULL
                                        GROUP BY p.reservation_id
                                    ) as p ON p.reservation_id = rez.id

                                    -- JOINs para tabla de items de reservacion (reservations_items)
                                    LEFT JOIN (
                                        SELECT  
                                            it.reservation_id, 
                                            it.is_round_trip,
                                            SUM(it.passengers) as passengers,
                                            GROUP_CONCAT(DISTINCT it.code ORDER BY it.code ASC SEPARATOR ',') AS code,
                                            
                                            GROUP_CONCAT(DISTINCT zone_one.name ORDER BY zone_one.name ASC SEPARATOR ',') AS zone_one_name,
                                            GROUP_CONCAT(DISTINCT zone_one.id ORDER BY zone_one.id ASC SEPARATOR ',') AS zone_one_id,
                                            GROUP_CONCAT(DISTINCT it.from_name SEPARATOR ',') AS from_name,

                                            GROUP_CONCAT(DISTINCT zone_two.name ORDER BY zone_two.name ASC SEPARATOR ',') AS zone_two_name, 
                                            GROUP_CONCAT(DISTINCT zone_two.id ORDER BY zone_two.id ASC SEPARATOR ',') AS zone_two_id,
                                            GROUP_CONCAT(DISTINCT it.to_name SEPARATOR ',') AS to_name,

                                            GROUP_CONCAT(DISTINCT dest.id ORDER BY dest.id ASC SEPARATOR ',') AS service_type_id,
                                            GROUP_CONCAT(DISTINCT dest.name ORDER BY dest.name ASC SEPARATOR ',') AS service_type_name,
                                            GROUP_CONCAT(DISTINCT it.op_one_pickup ORDER BY it.op_one_pickup ASC SEPARATOR ',') AS pickup_from,
                                            GROUP_CONCAT(DISTINCT it.op_two_pickup ORDER BY it.op_two_pickup ASC SEPARATOR ',') AS pickup_to,
                                            GROUP_CONCAT(DISTINCT it.op_one_status ORDER BY it.op_one_status ASC SEPARATOR ',') AS one_service_status,
                                            GROUP_CONCAT(DISTINCT it.op_two_status ORDER BY it.op_two_status ASC SEPARATOR ',') AS two_service_status,
                                        
                                            -- NUEVO: Determinar el tipo de servicio para ambos tramos del round trip
                                            CASE 
                                                WHEN it.is_round_trip = 1 THEN
                                                    CONCAT(
                                                        -- Primer tramo (ida)
                                                        CASE 
                                                            WHEN zone_one.is_primary = 1 THEN 'ARRIVAL'
                                                            WHEN zone_two.is_primary = 1 THEN 'DEPARTURE'
                                                            WHEN zone_one.is_primary = 0 AND zone_two.is_primary = 0 THEN 'TRANSFER'
                                                        END,
                                                        ',',
                                                        -- Segundo tramo (vuelta) - invertimos las zonas
                                                        CASE 
                                                            WHEN zone_two.is_primary = 1 THEN 'ARRIVAL'
                                                            WHEN zone_one.is_primary = 1 THEN 'DEPARTURE'
                                                            WHEN zone_two.is_primary = 0 AND zone_one.is_primary = 0 THEN 'TRANSFER'
                                                        END
                                                    )
                                                ELSE
                                                    -- Servicio one-way
                                                    CASE 
                                                        WHEN zone_one.is_primary = 1 THEN 'ARRIVAL'
                                                        WHEN zone_two.is_primary = 1 THEN 'DEPARTURE'
                                                        WHEN zone_one.is_primary = 0 AND zone_two.is_primary = 0 THEN 'TRANSFER'
                                                    END
                                            END AS service_types,

                                            GROUP_CONCAT(DISTINCT it.op_one_cancellation_level ORDER BY it.op_one_cancellation_level ASC SEPARATOR ',') AS one_cancellation_level,
                                            GROUP_CONCAT(DISTINCT it.op_two_cancellation_level ORDER BY it.op_two_cancellation_level ASC SEPARATOR ',') AS two_cancellation_level,                                            
                                            MAX(CASE WHEN DATE(it.op_one_pickup) = DATE(rez.created_at) THEN 1 ELSE 0 END) AS op_one_pickup_today,
                                            MAX(CASE WHEN DATE(it.op_two_pickup) = DATE(rez.created_at) THEN 1 ELSE 0 END) AS op_two_pickup_today,

                                            MAX(CASE WHEN DATE(it.op_one_pickup) = DATE(DATE_ADD(rez.created_at, INTERVAL 1 DAY)) THEN 1 ELSE 0 END) AS op_one_pickup_tomorrow,
                                            MAX(CASE WHEN DATE(it.op_two_pickup) = DATE(DATE_ADD(rez.created_at, INTERVAL 1 DAY)) THEN 1 ELSE 0 END) AS op_two_pickup_tomorrow,                                            

                                            -- Nueva condición para verificar si es round trip y las fechas son el mismo día
                                            MAX(CASE WHEN it.is_round_trip = 1 AND DATE(it.op_one_pickup) = DATE(it.op_two_pickup) THEN 1 ELSE 0 END) AS is_same_day_round_trip
                                        FROM reservations_items as it
                                            INNER JOIN zones as zone_one ON zone_one.id = it.from_zone
                                            INNER JOIN zones as zone_two ON zone_two.id = it.to_zone
                                            INNER JOIN destination_services as dest ON dest.id = it.destination_service_id
                                            INNER JOIN reservations as rez ON rez.id = it.reservation_id
                                        GROUP BY it.reservation_id, it.is_round_trip, zone_one.is_primary, zone_two.is_primary
                                    ) as it ON it.reservation_id = rez.id
                                    WHERE 1=1 {$query}
                                GROUP BY rez.id, 
                                        rez.client_first_name, 
                                        rez.client_last_name,
                                        rez.client_email,
                                        rez.client_phone,
                                        rez.currency,
                                        rez.is_cancelled,
                                        rez.is_commissionable,
                                        rez.site_id,
                                        rez.pay_at_arrival,
                                        rez.pay_now_amount,
                                        rez.reference,
                                        rez.affiliate_id,
                                        rez.terminal,
                                        rez.comments,
                                        rez.is_duplicated,
                                        rez.open_credit,
                                        rez.is_complete,
                                        rez.created_at,
                                        rez.is_quotation,
                                        rez.was_is_quotation,
                                        rez.campaign,
                                        rez.reserve_rating,
                                        rez.is_last_minute,
                                        us.id,
                                        us.status,
                                        us.name,
                                        site.id,
                                        site.type_site,
                                        site.name,
                                        site.is_cxc,
                                        origin.code,
                                        tc.name_es,
                                        p.credit_payment_ids,
                                        p.credit_conciliation_status,
                                        
                                        p.transportation_paid_later,
                                        p.transportation_payment_amount,
                                        p.transportation_payment_date,

                                        s.transportation_sales,

                                        rr.reservation_id,
                                        rr.refund_count,
                                        rr.pending_refund_count {$query2}",
                                    $queryData);

        if(sizeof( $bookings ) > 0):
            usort($bookings, array($this, 'orderByDateTime'));
        endif;

        return $bookings;
    }

    //ESTE FUNCIONA BIEN PARA SERVIDOR DIGITAL OCEAN
    // public function queryOperations($queryOne, $queryTwo, $queryHaving, $queryData){
    //     return  DB::select("SELECT 
    //                             rez.id as reservation_id,
    //                             rez.categories,
    //                             CONCAT(rez.client_first_name, ' ', rez.client_last_name) as full_name,
    //                             rez.client_email,
    //                             rez.client_phone,
    //                             rez.currency,
    //                             rez.language,
    //                             rez.is_cancelled,
    //                             rez.is_commissionable,
    //                             rez.site_id,
    //                             rez.pay_at_arrival,
    //                             rez.reference,
    //                             rez.affiliate_id,
    //                             rez.terminal,
    //                             rez.comments,
    //                             rez.is_duplicated,
    //                             rez.open_credit,
    //                             rez.is_complete,
    //                             rez.created_at,
    //                             rez.campaign,
    //                             rez.reserve_rating,
    //                             rez.is_last_minute,

    //                             us.id AS employee_code,
    //                             us.status AS employee_status,
    //                             us.name AS employee,

    //                             site.id as site_code,
    //                             site.type_site AS type_site,
    //                             site.color as site_color,
    //                             site.name as site_name,

    //                             origin.code AS origin_code,
    //                             tc.name_es AS cancellation_reason,
    //                             CASE WHEN upload.reservation_id IS NOT NULL THEN 1 ELSE 0 END as pictures,
    //                             CASE WHEN rfu.reservation_id IS NOT NULL THEN 1 ELSE 0 END as messages,

    //                             -- Información de reembolsos
    //                             CASE WHEN rr.reservation_id IS NOT NULL THEN 1 ELSE 0 END as has_refund_request,
    //                             COALESCE(rr.refund_count, 0) as refund_request_count,
    //                             CASE 
    //                                 WHEN rr.reservation_id IS NULL THEN 'NO_REFUND'
    //                                 WHEN rr.pending_refund_count > 0 THEN 'REFUND_REQUESTED'
    //                                 ELSE 'REFUND_COMPLETED'
    //                             END as refund_status,

    //                             CASE
    //                                 WHEN rez.is_cancelled = 1   AND rez.was_is_quotation = 1  THEN 'EXPIRED_QUOTATION'
    //                                 WHEN rez.is_cancelled = 1   THEN 'CANCELLED'                                    
    //                                 WHEN rez.is_duplicated = 1  THEN 'DUPLICATED'
    //                                 WHEN rez.open_credit = 1    THEN 'OPENCREDIT'
    //                                 WHEN rez.is_quotation = 1   THEN 'QUOTATION'
    //                                 -- WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
    //                                 WHEN site.is_cxc = 1 AND ( COALESCE(SUM(p.total_payments), 0) = 0 OR ( COALESCE(SUM(p.total_payments), 0) < COALESCE(SUM(s.total_sales), 0) ) ) THEN 'CREDIT'
    //                                 WHEN rez.pay_at_arrival = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'PAY_AT_ARRIVAL'
    //                                 WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) > 0 THEN 'PENDING'
    //                                 WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'CONFIRMED'
    //                                 ELSE 'UNKNOWN'
    //                             END AS reservation_status,

    //                             'arrival' as operation_type,
    //                             'TYPE_ONE' as op_type,
    //                             it.id,
    //                             it.code,
    //                             it.flight_number,
    //                             it.is_round_trip,
    //                             it.passengers,
    //                             it.spam,
    //                             it.spam_count,
    //                             it.op_one_pickup as filtered_date,
    //                             zone_one.id as zone_one_id,
    //                             zone_one.name as destination_name_from,
    //                             it.from_name as from_name,
    //                             zone_one.is_primary as zone_one_is_primary,
    //                             zone_one.cut_off_operation as zone_one_cut_off,
    //                             it.op_one_status as one_service_status,
    //                             it.op_one_status_operation as one_service_operation_status,
    //                             it.op_one_time_operation,
    //                             it.op_one_preassignment,
    //                             it.op_one_operating_cost,
    //                             it.op_one_pickup as pickup_from,
    //                             it.op_one_confirmation,
    //                             it.op_one_operation_close,
    //                             it.op_one_comments,
    //                             it.op_one_cancelled_at,
    //                             it.op_one_cancellation_level,
    //                             it.vehicle_id_one,
    //                             it.driver_id_one,
    //                             zone_two.id as zone_two_id,
    //                             zone_two.name as destination_name_to,
    //                             it.to_name as to_name,
    //                             zone_two.is_primary as zone_two_is_primary,
    //                             zone_two.cut_off_operation as zone_two_cut_off,
    //                             it.op_two_status as two_service_status,
    //                             it.op_two_status_operation as two_service_operation_status,
    //                             it.op_two_time_operation,
    //                             it.op_two_preassignment,
    //                             it.op_two_operating_cost,
    //                             it.op_two_pickup as pickup_to,
    //                             it.op_two_confirmation,
    //                             it.op_two_operation_close,
    //                             it.op_two_comments,
    //                             it.op_two_cancelled_at,
    //                             it.op_two_cancellation_level,
    //                             it.vehicle_id_two,
    //                             it.driver_id_two,
    //                             it.is_open,
    //                             it.open_service_time,
    //                             tc_one.name_es AS cancellation_reason_one,
    //                             tc_two.name_es AS cancellation_reason_two,

    //                             CASE 
    //                                 WHEN zone_one.is_primary = 1 THEN 'ARRIVAL'
    //                                 WHEN zone_one.is_primary = 0 AND zone_two.is_primary = 1 THEN 'DEPARTURE'
    //                                 WHEN zone_one.is_primary = 0 AND zone_two.is_primary = 0 THEN 'TRANSFER'
    //                             END AS final_service_type,

    //                             serv.id as service_type_id,
    //                             serv.name as service_type_name,
    //                             vehicle_one.name as vehicle_one_name,
    //                             d_one.name as vehicle_name_one,
    //                             vehicle_two.name as vehicle_two_name,
    //                             d_two.name as vehicle_name_two,
    //                             CONCAT(driver_one.names,' ',driver_one.surnames) as driver_one_name,
    //                             CONCAT(driver_two.names,' ',driver_two.surnames) as driver_two_name,

    //                             it_counter.quantity,
    //                             -- GROUP_CONCAT(DISTINCT p.codes_payment ORDER BY p.codes_payment ASC SEPARATOR ',') AS codes_payment,
    //                             GROUP_CONCAT(
    //                                 DISTINCT 
    //                                 CASE 
    //                                     WHEN site.is_cxc = 1 AND p.payment_type_name IS NULL THEN 'CREDIT'
    //                                     WHEN p.payment_type_name IS NOT NULL THEN p.payment_type_name
    //                                     WHEN rez.pay_at_arrival = 1 THEN 'CASH'  -- Asumiendo que pay_at_arrival=1 significa pago en efectivo
    //                                     ELSE 'NO DEFENIDO'
    //                                 END
    //                             ORDER BY p.payment_type_name ASC SEPARATOR ', ') AS payment_type_name,

    //                             -- Nuevos campos para pagos en efectivo
    //                             COALESCE(p.cash_amount, 0) AS cash_amount,
    //                             COALESCE(p.cash_references) AS cash_references,
    //                             COALESCE(p.cash_comments) AS cash_comments,
    //                             COALESCE(p.cash_payment_ids) AS cash_payment_ids,
    //                             COALESCE(p.cash_is_conciliated) AS cash_is_conciliated,

    //                             COALESCE(SUM(s.total_sales), 0) as total_sales,
    //                             COALESCE(SUM(p.total_payments), 0) as total_payments,
    //                             COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) AS total_balance,
    //                             CASE
    //                                 WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
    //                                 WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'PAID'
    //                                 ELSE 'PENDING'
    //                             END AS payment_status,
    //                             CASE 
    //                                 WHEN it.is_round_trip = 1 THEN COALESCE(SUM(s.total_sales), 0) / 2
    //                                 ELSE COALESCE(SUM(s.total_sales), 0)
    //                             END AS service_cost,
    //                             CASE 
    //                                 WHEN it_counter.quantity > 0 THEN COALESCE(SUM(s.total_sales), 0) / it_counter.quantity
    //                                 ELSE 0
    //                             END AS cost
    //                         FROM reservations_items as it
    //                             INNER JOIN reservations as rez ON rez.id = it.reservation_id
    //                             INNER JOIN sites as site ON site.id = rez.site_id
    //                             INNER JOIN zones as zone_one ON zone_one.id = it.from_zone
    //                             INNER JOIN zones as zone_two ON zone_two.id = it.to_zone
    //                             INNER JOIN destination_services as serv ON serv.id = it.destination_service_id
    //                             LEFT OUTER JOIN users as us ON us.id = rez.call_center_agent_id
    //                             LEFT OUTER JOIN origin_sales as origin ON origin.id = rez.origin_sale_id
    //                             LEFT OUTER JOIN types_cancellations as tc ON tc.id = rez.cancellation_type_id
    //                             LEFT OUTER JOIN vehicles as vehicle_one ON vehicle_one.id = it.vehicle_id_one
    //                             LEFT OUTER JOIN destination_services as d_one ON d_one.id = vehicle_one.destination_service_id
    //                             LEFT OUTER JOIN vehicles as vehicle_two ON vehicle_two.id = it.vehicle_id_two
    //                             LEFT OUTER JOIN destination_services as d_two ON d_two.id = vehicle_two.destination_service_id
    //                             LEFT OUTER JOIN drivers as driver_one ON driver_one.id = it.driver_id_one
    //                             LEFT OUTER JOIN drivers as driver_two ON driver_two.id = it.driver_id_two

    //                             LEFT OUTER JOIN types_cancellations as tc_one ON tc_one.id = it.op_one_cancellation_type_id
    //                             LEFT OUTER JOIN types_cancellations as tc_two ON tc_two.id = it.op_two_cancellation_type_id

    //                             LEFT OUTER JOIN (
    //                                 SELECT DISTINCT reservation_id
    //                                 FROM reservations_media
    //                             ) as upload ON upload.reservation_id = rez.id
    //                             LEFT OUTER JOIN (
    //                                 SELECT DISTINCT reservation_id
    //                                 FROM reservations_follow_up
    //                                 WHERE type IN ('CLIENT', 'OPERATION')
    //                             ) as rfu ON rfu.reservation_id = rez.id

    //                             -- Nuevos JOINs para la tabla de reembolsos
    //                             LEFT OUTER JOIN (
    //                                 SELECT 
    //                                     reservation_id,
    //                                     COUNT(*) as refund_count,
    //                                     SUM(CASE WHEN status != 'REFUND_COMPLETED' THEN 1 ELSE 0 END) as pending_refund_count
    //                                 FROM reservations_refunds
    //                                 GROUP BY reservation_id
    //                             ) as rr ON rr.reservation_id = rez.id

    //                             LEFT JOIN (
    //                                     SELECT
    //                                         it.reservation_id,
    //                                         SUM(CASE WHEN it.is_round_trip = 1 THEN 2 ELSE 1 END) as quantity
    //                                     FROM reservations_items as it
    //                                     GROUP BY it.reservation_id
    //                             ) as it_counter ON it_counter.reservation_id = it.reservation_id
    //                             LEFT JOIN (
    //                                 SELECT 
    //                                     reservation_id,  
    //                                     ROUND( COALESCE(SUM(total), 0), 2) as total_sales
    //                                 FROM sales
    //                                     WHERE deleted_at IS NULL
    //                                 GROUP BY reservation_id
    //                             ) as s ON s.reservation_id = rez.id
    //                             LEFT JOIN (
    //                                 SELECT 
    //                                     reservation_id,
    //                                     -- ROUND(SUM(CASE 
    //                                     --     WHEN operation = 'multiplication' THEN total * exchange_rate
    //                                     --     WHEN operation = 'division' THEN total / exchange_rate
    //                                     --     ELSE total END), 2) AS total_payments,
    //                                     ROUND(SUM(CASE
    //                                         WHEN category IN ('PAYOUT', 'PAYOUT_CREDIT_PAID') THEN 
    //                                             CASE 
    //                                                 WHEN operation = 'multiplication' THEN total * exchange_rate
    //                                                 WHEN operation = 'division' THEN total / exchange_rate
    //                                                 ELSE total 
    //                                             END
    //                                         ELSE 0
    //                                     END), 2) AS total_payments,

    //                                     GROUP_CONCAT(DISTINCT payment_method ORDER BY payment_method ASC SEPARATOR ',') AS payment_type_name,

    //                                     -- Monto en efectivo
    //                                     ROUND(SUM(CASE 
    //                                         WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
    //                                             CASE 
    //                                                 WHEN operation = 'multiplication' THEN total * exchange_rate
    //                                                 WHEN operation = 'division' THEN total / exchange_rate
    //                                                 ELSE total 
    //                                             END
    //                                         ELSE 0 
    //                                     END), 2) AS cash_amount,                                        
    //                                     -- Referencias de pagos en efectivo concatenadas
    //                                     CONCAT(
    //                                         '[',
    //                                         GROUP_CONCAT(
    //                                             DISTINCT 
    //                                             CASE 
    //                                                 WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
    //                                                     CONCAT(
    //                                                         'Referencia: ', IFNULL(reference, 'SIN REFERENCIA'), 
    //                                                         ' - Monto: ', 
    //                                                         ROUND(
    //                                                             CASE 
    //                                                                 WHEN operation = 'multiplication' THEN total * exchange_rate
    //                                                                 WHEN operation = 'division' THEN total / exchange_rate
    //                                                                 ELSE total 
    //                                                             END, 2
    //                                                         )
    //                                                     )
    //                                                 ELSE NULL
    //                                             END
    //                                             SEPARATOR ']\n['
    //                                         ),
    //                                         ']'
    //                                     ) AS cash_references,
    //                                     -- Comentario de pagos en efectivo concatenadas
    //                                     GROUP_CONCAT(DISTINCT CASE WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN conciliation_comment END SEPARATOR ', ') AS cash_comments,
    //                                     -- Nuevo campo: solo IDs de pagos en efectivo concatenados
    //                                     CONCAT(
    //                                         '[',
    //                                         GROUP_CONCAT(
    //                                             DISTINCT 
    //                                             CASE 
    //                                                 WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN id
    //                                                 ELSE NULL
    //                                             END
    //                                             ORDER BY id ASC
    //                                             SEPARATOR ','
    //                                         ),
    //                                         ']'
    //                                     ) AS cash_payment_ids,
    //                                     -- Suma de estatus de conciliación (para determinar si todos están conciliados)
    //                                     SUM(CASE 
    //                                         WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
    //                                             CASE WHEN is_conciliated = 1 THEN 1 ELSE 0 END
    //                                         ELSE 0
    //                                     END) AS cash_is_conciliated,
    //                                     -- Contador de pagos en efectivo
    //                                     SUM(CASE 
    //                                         WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 1
    //                                         ELSE 0
    //                                     END) AS cash_payment_count                                                                       
    //                                 FROM payments
    //                                     WHERE deleted_at IS NULL
    //                                 GROUP BY reservation_id
    //                             ) as p ON p.reservation_id = rez.id
    //                         WHERE 1=1 {$queryOne}
    //                         GROUP BY it.id, 
    //                                 rez.id, 
    //                                 rez.categories,
    //                                 rez.client_first_name,
    //                                 rez.client_last_name,
    //                                 rez.client_email,
    //                                 rez.client_phone,
    //                                 rez.currency,
    //                                 rez.language,
    //                                 rez.is_cancelled,
    //                                 rez.is_commissionable,
    //                                 rez.site_id,
    //                                 rez.pay_at_arrival,
    //                                 rez.reference,
    //                                 rez.affiliate_id,
    //                                 rez.terminal,
    //                                 rez.comments,
    //                                 rez.is_duplicated,
    //                                 rez.open_credit,
    //                                 rez.is_complete,
    //                                 rez.created_at,
    //                                 rez.campaign,
    //                                 rez.reserve_rating,
    //                                 rez.is_last_minute,
    //                                 rez.was_is_quotation,
    //                                 rez.is_quotation,
    //                                 site.is_cxc,
    //                                 us.id,
    //                                 us.status,
    //                                 us.name,
    //                                 site.id,
    //                                 site.type_site,
    //                                 site.color,
    //                                 site.name,
    //                                 origin.code,
    //                                 tc.name_es,
    //                                 upload.reservation_id,
    //                                 rfu.reservation_id,
    //                                 rr.reservation_id,
    //                                 rr.refund_count,
    //                                 rr.pending_refund_count,
    //                                 serv.id,
    //                                 serv.name,
    //                                 zone_one.id,
    //                                 zone_one.name,
    //                                 zone_one.is_primary,
    //                                 zone_one.cut_off_operation,
    //                                 zone_two.id,
    //                                 zone_two.name,
    //                                 zone_two.is_primary,
    //                                 zone_two.cut_off_operation,
    //                                 vehicle_one.name,
    //                                 d_one.name,
    //                                 vehicle_two.name,
    //                                 d_two.name,
    //                                 driver_one.names,
    //                                 driver_one.surnames,
    //                                 driver_two.names,
    //                                 driver_two.surnames,
    //                                 tc_one.name_es,
    //                                 tc_two.name_es,
    //                                 it.code,
    //                                 it.flight_number,
    //                                 it.is_round_trip,
    //                                 it.passengers,
    //                                 it.spam,
    //                                 it.spam_count,
    //                                 it.op_one_pickup,
    //                                 it.from_name,
    //                                 it.op_one_status,
    //                                 it.op_one_status_operation,
    //                                 it.op_one_time_operation,
    //                                 it.op_one_preassignment,
    //                                 it.op_one_operating_cost,
    //                                 it.op_one_confirmation,
    //                                 it.op_one_operation_close,
    //                                 it.op_one_comments,
    //                                 it.op_one_cancelled_at,
    //                                 it.op_one_cancellation_level,
    //                                 it.vehicle_id_one,
    //                                 it.driver_id_one,
    //                                 it.op_two_pickup,
    //                                 it.to_name,
    //                                 it.op_two_status,
    //                                 it.op_two_status_operation,
    //                                 it.op_two_time_operation,
    //                                 it.op_two_preassignment,
    //                                 it.op_two_operating_cost,
    //                                 it.op_two_confirmation,
    //                                 it.op_two_operation_close,
    //                                 it.op_two_comments,
    //                                 it.op_two_cancelled_at,
    //                                 it.op_two_cancellation_level,
    //                                 it.vehicle_id_two,
    //                                 it.driver_id_two,
    //                                 it.is_open,
    //                                 it.open_service_time,
    //                                 it_counter.quantity,
    //                                 p.cash_amount,
    //                                 p.cash_references,
    //                                 p.cash_comments,
    //                                 p.cash_payment_ids,
    //                                 p.cash_is_conciliated                                   
    //                                 {$queryHaving}

    //                         UNION

    //                         SELECT 
    //                             rez.id as reservation_id,
    //                             rez.categories,
    //                             CONCAT(rez.client_first_name, ' ', rez.client_last_name) as full_name,
    //                             rez.client_email,
    //                             rez.client_phone,
    //                             rez.currency,
    //                             rez.language,
    //                             rez.is_cancelled,
    //                             rez.is_commissionable,
    //                             rez.site_id,
    //                             rez.pay_at_arrival,
    //                             rez.reference,
    //                             rez.affiliate_id,
    //                             rez.terminal,
    //                             rez.comments,
    //                             rez.is_duplicated,
    //                             rez.open_credit,
    //                             rez.is_complete,
    //                             rez.created_at,
    //                             rez.campaign,
    //                             rez.reserve_rating,
    //                             rez.is_last_minute,

    //                             us.id AS employee_code,
    //                             us.status AS employee_status,
    //                             us.name AS employee,

    //                             site.id as site_code,
    //                             site.type_site AS type_site,
    //                             site.color as site_color,
    //                             site.name as site_name,

    //                             origin.code AS origin_code,
    //                             tc.name_es AS cancellation_reason,
    //                             CASE WHEN upload.reservation_id IS NOT NULL THEN 1 ELSE 0 END as pictures,
    //                             CASE WHEN rfu.reservation_id IS NOT NULL THEN 1 ELSE 0 END as messages,

    //                             -- Información de reembolsos
    //                             CASE WHEN rr.reservation_id IS NOT NULL THEN 1 ELSE 0 END as has_refund_request,
    //                             COALESCE(rr.refund_count, 0) as refund_request_count,
    //                             CASE 
    //                                 WHEN rr.reservation_id IS NULL THEN 'NO_REFUND'
    //                                 WHEN rr.pending_refund_count > 0 THEN 'REFUND_REQUESTED'
    //                                 ELSE 'REFUND_COMPLETED'
    //                             END as refund_status,                                

    //                             CASE
    //                                 WHEN rez.is_cancelled = 1   AND rez.was_is_quotation = 1  THEN 'EXPIRED_QUOTATION'
    //                                 WHEN rez.is_cancelled = 1   THEN 'CANCELLED'                                    
    //                                 WHEN rez.is_duplicated = 1  THEN 'DUPLICATED'
    //                                 WHEN rez.open_credit = 1    THEN 'OPENCREDIT'
    //                                 WHEN rez.is_quotation = 1   THEN 'QUOTATION'
    //                                 -- WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
    //                                 WHEN site.is_cxc = 1 AND ( COALESCE(SUM(p.total_payments), 0) = 0 OR ( COALESCE(SUM(p.total_payments), 0) < COALESCE(SUM(s.total_sales), 0) ) ) THEN 'CREDIT'
    //                                 WHEN rez.pay_at_arrival = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'PAY_AT_ARRIVAL'
    //                                 WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) > 0 THEN 'PENDING'
    //                                 WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'CONFIRMED'
    //                                 ELSE 'UNKNOWN'
    //                             END AS reservation_status,

    //                             'departure' as operation_type,
    //                             'TYPE_TWO' as op_type,
    //                             it.id,
    //                             it.code,
    //                             it.flight_number,
    //                             it.is_round_trip,
    //                             it.passengers,
    //                             it.spam,
    //                             it.spam_count,
    //                             it.op_two_pickup as filtered_date,
    //                             zone_one.id as zone_one_id,
    //                             zone_one.name as destination_name_from,
    //                             it.from_name as from_name,
    //                             zone_one.is_primary as zone_one_is_primary, 
    //                             zone_one.cut_off_operation as zone_one_cut_off,
    //                             it.op_one_status as one_service_status,
    //                             it.op_one_status_operation as one_service_operation_status,
    //                             it.op_one_time_operation,
    //                             it.op_one_preassignment,
    //                             it.op_one_operating_cost,
    //                             it.op_one_pickup as pickup_from,
    //                             it.op_one_confirmation,
    //                             it.op_one_operation_close,
    //                             it.op_one_comments,
    //                             it.op_one_cancelled_at,
    //                             it.op_one_cancellation_level,
    //                             it.vehicle_id_one,
    //                             it.driver_id_one,                                
    //                             zone_two.id as zone_two_id,
    //                             zone_two.name as destination_name_to,                                
    //                             it.to_name as to_name,
    //                             zone_two.is_primary as zone_two_is_primary, 
    //                             zone_two.cut_off_operation as zone_two_cut_off,
    //                             it.op_two_status as two_service_status,
    //                             it.op_two_status_operation as two_service_operation_status,
    //                             it.op_two_time_operation,
    //                             it.op_two_preassignment,
    //                             it.op_two_operating_cost,
    //                             it.op_two_pickup as pickup_to,
    //                             it.op_two_confirmation,
    //                             it.op_two_operation_close,
    //                             it.op_two_comments,
    //                             it.op_two_cancelled_at,
    //                             it.op_two_cancellation_level,
    //                             it.vehicle_id_two,
    //                             it.driver_id_two,
    //                             it.is_open,
    //                             it.open_service_time,
    //                             tc_one.name_es AS cancellation_reason_one,
    //                             tc_two.name_es AS cancellation_reason_two,

    //                             CASE
    //                                 WHEN zone_two.is_primary = 0 AND zone_one.is_primary = 1  THEN 'DEPARTURE'
    //                                 WHEN zone_one.is_primary = 0 AND zone_two.is_primary = 0 THEN 'TRANSFER'
    //                                 ELSE 'ARRIVAL'
    //                             END AS final_service_type,

    //                             serv.id as service_type_id,
    //                             serv.name as service_type_name,
    //                             vehicle_one.name as vehicle_one_name,
    //                             d_one.name as vehicle_name_one,
    //                             vehicle_two.name as vehicle_two_name,
    //                             d_two.name as vehicle_name_two,
    //                             CONCAT(driver_one.names,' ',driver_one.surnames) as driver_one_name,
    //                             CONCAT(driver_two.names,' ',driver_two.surnames) as driver_two_name,

    //                             it_counter.quantity,
    //                             -- GROUP_CONCAT(DISTINCT p.codes_payment ORDER BY p.codes_payment ASC SEPARATOR ',') AS codes_payment,
    //                             GROUP_CONCAT(
    //                                 DISTINCT 
    //                                 CASE
    //                                     WHEN site.is_cxc = 1 AND p.payment_type_name IS NULL THEN 'CREDIT'
    //                                     WHEN p.payment_type_name IS NOT NULL THEN p.payment_type_name
    //                                     WHEN rez.pay_at_arrival = 1 THEN 'CASH'  -- Asumiendo que pay_at_arrival=1 significa pago en efectivo
    //                                     ELSE 'NO DEFENIDO'                                        
    //                                 END
    //                             ORDER BY p.payment_type_name ASC SEPARATOR ', ') AS payment_type_name,

    //                             -- Nuevos campos para pagos en efectivo
    //                             COALESCE(p.cash_amount, 0) AS cash_amount,
    //                             COALESCE(p.cash_references) AS cash_references,
    //                             COALESCE(p.cash_comments) AS cash_comments,
    //                             COALESCE(p.cash_payment_ids) AS cash_payment_ids,
    //                             COALESCE(p.cash_is_conciliated) AS cash_is_conciliated,

    //                             COALESCE(SUM(s.total_sales), 0) as total_sales,
    //                             COALESCE(SUM(p.total_payments), 0) as total_payments,
    //                             COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) AS total_balance,
    //                             CASE
    //                                 WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
    //                                 WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'PAID'
    //                                 ELSE 'PENDING'
    //                             END AS payment_status,
    //                             CASE 
    //                                 WHEN it.is_round_trip = 1 THEN COALESCE(SUM(s.total_sales), 0) / 2
    //                                 ELSE COALESCE(SUM(s.total_sales), 0)
    //                             END AS service_cost,
    //                             CASE 
    //                                 WHEN it_counter.quantity > 0 THEN COALESCE(SUM(s.total_sales), 0) / it_counter.quantity
    //                                 ELSE 0
    //                             END AS cost                                
    //                         FROM reservations_items as it
    //                             INNER JOIN reservations as rez ON rez.id = it.reservation_id
    //                             INNER JOIN sites as site ON site.id = rez.site_id
    //                             INNER JOIN zones as zone_one ON zone_one.id = it.from_zone
    //                             INNER JOIN zones as zone_two ON zone_two.id = it.to_zone
    //                             INNER JOIN destination_services as serv ON serv.id = it.destination_service_id
    //                             LEFT OUTER JOIN users as us ON us.id = rez.call_center_agent_id
    //                             LEFT OUTER JOIN origin_sales as origin ON origin.id = rez.origin_sale_id
    //                             LEFT OUTER JOIN types_cancellations as tc ON tc.id = rez.cancellation_type_id
    //                             LEFT OUTER JOIN vehicles as vehicle_one ON vehicle_one.id = it.vehicle_id_one
    //                             LEFT OUTER JOIN destination_services as d_one ON d_one.id = vehicle_one.destination_service_id
    //                             LEFT OUTER JOIN vehicles as vehicle_two ON vehicle_two.id = it.vehicle_id_two
    //                             LEFT OUTER JOIN destination_services as d_two ON d_two.id = vehicle_two.destination_service_id
    //                             LEFT OUTER JOIN drivers as driver_one ON driver_one.id = it.driver_id_one
    //                             LEFT OUTER JOIN drivers as driver_two ON driver_two.id = it.driver_id_two

    //                             LEFT OUTER JOIN types_cancellations as tc_one ON tc_one.id = it.op_one_cancellation_type_id
    //                             LEFT OUTER JOIN types_cancellations as tc_two ON tc_two.id = it.op_two_cancellation_type_id                                

    //                             LEFT OUTER JOIN (
    //                                 SELECT DISTINCT reservation_id
    //                                 FROM reservations_media
    //                             ) as upload ON upload.reservation_id = rez.id
    //                             LEFT OUTER JOIN (
    //                                 SELECT DISTINCT reservation_id
    //                                 FROM reservations_follow_up
    //                                 WHERE type IN ('CLIENT', 'OPERATION')
    //                             ) as rfu ON rfu.reservation_id = rez.id

    //                             -- Nuevos JOINs para la tabla de reembolsos
    //                             LEFT OUTER JOIN (
    //                                 SELECT 
    //                                     reservation_id,
    //                                     COUNT(*) as refund_count,
    //                                     SUM(CASE WHEN status != 'REFUND_COMPLETED' THEN 1 ELSE 0 END) as pending_refund_count
    //                                 FROM reservations_refunds
    //                                 GROUP BY reservation_id
    //                             ) as rr ON rr.reservation_id = rez.id

    //                             LEFT JOIN (
    //                                     SELECT
    //                                         it.reservation_id,
    //                                         SUM(CASE WHEN it.is_round_trip = 1 THEN 2 ELSE 1 END) as quantity
    //                                     FROM reservations_items as it
    //                                     GROUP BY it.reservation_id
    //                             ) as it_counter ON it_counter.reservation_id = it.reservation_id
    //                             LEFT JOIN (
    //                                 SELECT 
    //                                     reservation_id,  
    //                                     ROUND( COALESCE(SUM(total), 0), 2) as total_sales
    //                                 FROM sales
    //                                     WHERE deleted_at IS NULL
    //                                 GROUP BY reservation_id
    //                             ) as s ON s.reservation_id = rez.id
    //                             LEFT JOIN (
    //                                 SELECT 
    //                                     reservation_id,
    //                                     -- ROUND(SUM(CASE 
    //                                     --             WHEN operation = 'multiplication' THEN total * exchange_rate
    //                                     --             WHEN operation = 'division' THEN total / exchange_rate
    //                                     --             ELSE total 
    //                                     --         END), 2) AS total_payments,
    //                                     ROUND(SUM(CASE
    //                                         WHEN category IN ('PAYOUT', 'PAYOUT_CREDIT_PAID') THEN 
    //                                             CASE 
    //                                                 WHEN operation = 'multiplication' THEN total * exchange_rate
    //                                                 WHEN operation = 'division' THEN total / exchange_rate
    //                                                 ELSE total 
    //                                             END
    //                                         ELSE 0
    //                                     END), 2) AS total_payments,

    //                                     GROUP_CONCAT(DISTINCT payment_method ORDER BY payment_method ASC SEPARATOR ',') AS payment_type_name,

    //                                     -- Monto en efectivo
    //                                     ROUND(SUM(CASE 
    //                                                 WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
    //                                                 CASE 
    //                                                     WHEN operation = 'multiplication' THEN total * exchange_rate
    //                                                     WHEN operation = 'division' THEN total / exchange_rate
    //                                                     ELSE total 
    //                                                 END
    //                                             ELSE 0 
    //                                     END), 2) AS cash_amount,
    //                                     -- Referencias de pagos en efectivo concatenadas
    //                                     CONCAT(
    //                                         '[',
    //                                         GROUP_CONCAT(
    //                                             DISTINCT 
    //                                             CASE 
    //                                                 WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
    //                                                     CONCAT(
    //                                                         'Referencia: ', IFNULL(reference, 'SIN REFERENCIA'), 
    //                                                         ' - Monto: ', 
    //                                                         ROUND(
    //                                                             CASE 
    //                                                                 WHEN operation = 'multiplication' THEN total * exchange_rate
    //                                                                 WHEN operation = 'division' THEN total / exchange_rate
    //                                                                 ELSE total 
    //                                                             END, 2
    //                                                         )
    //                                                     )
    //                                                 ELSE NULL
    //                                             END
    //                                             SEPARATOR ']\n['
    //                                         ),
    //                                         ']'
    //                                     ) AS cash_references,
    //                                     -- Comentario de pagos en efectivo concatenadas
    //                                     GROUP_CONCAT(DISTINCT CASE WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN conciliation_comment END SEPARATOR ', ') AS cash_comments,                                        
    //                                     -- Nuevo campo: solo IDs de pagos en efectivo concatenados
    //                                     CONCAT(
    //                                         '[',
    //                                         GROUP_CONCAT(
    //                                             DISTINCT 
    //                                             CASE 
    //                                                 WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN id
    //                                                 ELSE NULL
    //                                             END
    //                                             ORDER BY id ASC
    //                                             SEPARATOR ','
    //                                         ),
    //                                         ']'
    //                                     ) AS cash_payment_ids,
    //                                     -- Suma de estatus de conciliación (para determinar si todos están conciliados)
    //                                     SUM(CASE 
    //                                         WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
    //                                             CASE WHEN is_conciliated = 1 THEN 1 ELSE 0 END
    //                                         ELSE 0
    //                                     END) AS cash_is_conciliated,
    //                                     -- Contador de pagos en efectivo
    //                                     SUM(CASE 
    //                                         WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 1
    //                                         ELSE 0
    //                                     END) AS cash_payment_count
    //                                 FROM payments
    //                                     WHERE deleted_at IS NULL
    //                                 GROUP BY reservation_id
    //                             ) as p ON p.reservation_id = rez.id
    //                         WHERE 1=1 {$queryTwo}
    //                         GROUP BY it.id, 
    //                                 rez.id, 
    //                                 rez.categories,
    //                                 rez.client_first_name,
    //                                 rez.client_last_name,
    //                                 rez.client_email,
    //                                 rez.client_phone,
    //                                 rez.currency,
    //                                 rez.language,
    //                                 rez.is_cancelled,
    //                                 rez.is_commissionable,
    //                                 rez.site_id,
    //                                 rez.pay_at_arrival,
    //                                 rez.reference,
    //                                 rez.affiliate_id,
    //                                 rez.terminal,
    //                                 rez.comments,
    //                                 rez.is_duplicated,
    //                                 rez.open_credit,
    //                                 rez.is_complete,
    //                                 rez.created_at,
    //                                 rez.campaign,
    //                                 rez.reserve_rating,
    //                                 rez.is_last_minute,
    //                                 rez.was_is_quotation,
    //                                 rez.is_quotation,
    //                                 site.is_cxc,
    //                                 us.id,
    //                                 us.status,
    //                                 us.name,
    //                                 site.id,
    //                                 site.type_site,
    //                                 site.color,
    //                                 site.name,
    //                                 origin.code,
    //                                 tc.name_es,
    //                                 upload.reservation_id,
    //                                 rfu.reservation_id,
    //                                 rr.reservation_id,
    //                                 rr.refund_count,
    //                                 rr.pending_refund_count,
    //                                 serv.id,
    //                                 serv.name,
    //                                 zone_one.id,
    //                                 zone_one.name,
    //                                 zone_one.is_primary,
    //                                 zone_one.cut_off_operation,
    //                                 zone_two.id,
    //                                 zone_two.name,
    //                                 zone_two.is_primary,
    //                                 zone_two.cut_off_operation,
    //                                 vehicle_one.name,
    //                                 d_one.name,
    //                                 vehicle_two.name,
    //                                 d_two.name,
    //                                 driver_one.names,
    //                                 driver_one.surnames,
    //                                 driver_two.names,
    //                                 driver_two.surnames,
    //                                 tc_one.name_es,
    //                                 tc_two.name_es,
    //                                 it.code,
    //                                 it.flight_number,
    //                                 it.is_round_trip,
    //                                 it.passengers,
    //                                 it.spam,
    //                                 it.spam_count,
    //                                 it.op_one_pickup,
    //                                 it.from_name,
    //                                 it.op_one_status,
    //                                 it.op_one_status_operation,
    //                                 it.op_one_time_operation,
    //                                 it.op_one_preassignment,
    //                                 it.op_one_operating_cost,                        
    //                                 it.op_one_confirmation,
    //                                 it.op_one_operation_close,
    //                                 it.op_one_comments,
    //                                 it.op_one_cancelled_at,
    //                                 it.op_one_cancellation_level,
    //                                 it.vehicle_id_one,
    //                                 it.driver_id_one,
    //                                 it.op_two_pickup,
    //                                 it.to_name,
    //                                 it.op_two_status,
    //                                 it.op_two_status_operation,
    //                                 it.op_two_time_operation,
    //                                 it.op_two_preassignment,
    //                                 it.op_two_operating_cost,
    //                                 it.op_two_confirmation,
    //                                 it.op_two_operation_close,
    //                                 it.op_two_comments,
    //                                 it.op_two_cancelled_at,
    //                                 it.op_two_cancellation_level,
    //                                 it.vehicle_id_two,
    //                                 it.driver_id_two,
    //                                 it.is_open,
    //                                 it.open_service_time,
    //                                 it_counter.quantity,
    //                                 p.cash_amount,
    //                                 p.cash_references,
    //                                 p.cash_comments,
    //                                 p.cash_payment_ids,
    //                                 p.cash_is_conciliated
    //                                 {$queryHaving}
    //                         -- ) AS combined_results
    //                         ORDER BY filtered_date ASC ",[
    //                             "init_date_one" => $queryData['init'],
    //                             "init_date_two" => $queryData['end'],
    //                             "init_date_three" => $queryData['init'],
    //                             "init_date_four" => $queryData['end'],
    //                         ]);
    // }

    public function queryOperations($queryOne, $queryTwo, $queryHaving, $queryData){
        return  DB::select("SELECT 
                                rez.id as reservation_id,
                                rez.categories,
                                CONCAT(rez.client_first_name, ' ', rez.client_last_name) as full_name,
                                rez.client_email,
                                rez.client_phone,
                                rez.currency,
                                rez.language,
                                rez.is_cancelled,
                                rez.is_commissionable,
                                rez.site_id,
                                rez.pay_at_arrival,
                                rez.reference,
                                rez.affiliate_id,
                                rez.terminal,
                                rez.comments,
                                rez.is_duplicated,
                                rez.open_credit,
                                rez.is_complete,
                                rez.created_at,
                                rez.campaign,
                                rez.reserve_rating,
                                rez.is_last_minute,

                                us.id AS employee_code,
                                us.status AS employee_status,
                                us.name AS employee,

                                site.id as site_code,
                                site.type_site AS type_site,
                                site.color as site_color,
                                site.name as site_name,

                                origin.code AS origin_code,
                                tc.name_es AS cancellation_reason,
                                CASE WHEN upload.reservation_id IS NOT NULL THEN 1 ELSE 0 END as pictures,
                                CASE WHEN rfu.reservation_id IS NOT NULL THEN 1 ELSE 0 END as messages,

                                -- Información de reembolsos
                                CASE WHEN rr.reservation_id IS NOT NULL THEN 1 ELSE 0 END as has_refund_request,
                                COALESCE(rr.refund_count, 0) as refund_request_count,
                                CASE 
                                    WHEN rr.reservation_id IS NULL THEN 'NO_REFUND'
                                    WHEN rr.pending_refund_count > 0 THEN 'REFUND_REQUESTED'
                                    ELSE 'REFUND_COMPLETED'
                                END as refund_status,

                                CASE
                                    WHEN rez.is_cancelled = 1   AND rez.was_is_quotation = 1  THEN 'EXPIRED_QUOTATION'
                                    WHEN rez.is_cancelled = 1   THEN 'CANCELLED'                                    
                                    WHEN rez.is_duplicated = 1  THEN 'DUPLICATED'
                                    WHEN rez.open_credit = 1    THEN 'OPENCREDIT'
                                    WHEN rez.is_quotation = 1   THEN 'QUOTATION'
                                    -- WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
                                    WHEN site.is_cxc = 1 AND ( COALESCE(SUM(p.total_payments), 0) = 0 OR ( COALESCE(SUM(p.total_payments), 0) < COALESCE(SUM(s.total_sales), 0) ) ) THEN 'CREDIT'
                                    WHEN rez.pay_at_arrival = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'PAY_AT_ARRIVAL'
                                    WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) > 0 THEN 'PENDING'
                                    WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'CONFIRMED'
                                    ELSE 'UNKNOWN'
                                END AS reservation_status,

                                'arrival' as operation_type,
                                'TYPE_ONE' as op_type,
                                it.id,
                                it.code,
                                it.flight_number,
                                it.is_round_trip,
                                it.passengers,
                                it.spam,
                                it.spam_count,
                                it.op_one_pickup as filtered_date,
                                zone_one.id as zone_one_id,
                                zone_one.name as destination_name_from,
                                it.from_name as from_name,
                                zone_one.is_primary as zone_one_is_primary,
                                zone_one.cut_off_operation as zone_one_cut_off,
                                it.op_one_status as one_service_status,
                                it.op_one_status_operation as one_service_operation_status,
                                it.op_one_time_operation,
                                it.op_one_preassignment,
                                it.op_one_operating_cost,
                                it.op_one_pickup as pickup_from,
                                it.op_one_confirmation,
                                it.op_one_operation_close,
                                it.op_one_comments,
                                it.op_one_cancelled_at,
                                it.op_one_cancellation_level,
                                it.vehicle_id_one,
                                it.driver_id_one,
                                zone_two.id as zone_two_id,
                                zone_two.name as destination_name_to,
                                it.to_name as to_name,
                                zone_two.is_primary as zone_two_is_primary,
                                zone_two.cut_off_operation as zone_two_cut_off,
                                it.op_two_status as two_service_status,
                                it.op_two_status_operation as two_service_operation_status,
                                it.op_two_time_operation,
                                it.op_two_preassignment,
                                it.op_two_operating_cost,
                                it.op_two_pickup as pickup_to,
                                it.op_two_confirmation,
                                it.op_two_operation_close,
                                it.op_two_comments,
                                it.op_two_cancelled_at,
                                it.op_two_cancellation_level,
                                it.vehicle_id_two,
                                it.driver_id_two,
                                it.is_open,
                                it.open_service_time,
                                tc_one.name_es AS cancellation_reason_one,
                                tc_two.name_es AS cancellation_reason_two,

                                CASE 
                                    WHEN zone_one.is_primary = 1 THEN 'ARRIVAL'
                                    WHEN zone_one.is_primary = 0 AND zone_two.is_primary = 1 THEN 'DEPARTURE'
                                    WHEN zone_one.is_primary = 0 AND zone_two.is_primary = 0 THEN 'TRANSFER'
                                END AS final_service_type,

                                serv.id as service_type_id,
                                serv.name as service_type_name,
                                vehicle_one.name as vehicle_one_name,
                                d_one.name as vehicle_name_one,
                                vehicle_two.name as vehicle_two_name,
                                d_two.name as vehicle_name_two,
                                CONCAT(driver_one.names,' ',driver_one.surnames) as driver_one_name,
                                CONCAT(driver_two.names,' ',driver_two.surnames) as driver_two_name,

                                it_counter.quantity,
                                -- GROUP_CONCAT(DISTINCT p.codes_payment ORDER BY p.codes_payment ASC SEPARATOR ',') AS codes_payment,
                                GROUP_CONCAT(
                                    DISTINCT 
                                    CASE 
                                        WHEN site.is_cxc = 1 AND p.payment_type_name IS NULL THEN 'CREDIT'
                                        WHEN p.payment_type_name IS NOT NULL THEN p.payment_type_name
                                        WHEN rez.pay_at_arrival = 1 THEN 'CASH'  -- Asumiendo que pay_at_arrival=1 significa pago en efectivo
                                        ELSE 'NO DEFENIDO'
                                    END
                                ORDER BY p.payment_type_name ASC SEPARATOR ', ') AS payment_type_name,

                                -- Nuevos campos para pagos en efectivo
                                COALESCE(p.cash_amount, 0) AS cash_amount,
                                COALESCE(p.cash_references) AS cash_references,
                                COALESCE(p.cash_comments) AS cash_comments,
                                COALESCE(p.cash_payment_ids) AS cash_payment_ids,
                                COALESCE(p.cash_is_conciliated) AS cash_is_conciliated,

                                COALESCE(SUM(s.total_sales), 0) as total_sales,
                                COALESCE(SUM(p.total_payments), 0) as total_payments,
                                COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) AS total_balance,
                                CASE
                                    WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
                                    WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'PAID'
                                    ELSE 'PENDING'
                                END AS payment_status,
                                CASE 
                                    WHEN it.is_round_trip = 1 THEN COALESCE(SUM(s.total_sales), 0) / 2
                                    ELSE COALESCE(SUM(s.total_sales), 0)
                                END AS service_cost,
                                CASE 
                                    WHEN it_counter.quantity > 0 THEN COALESCE(SUM(s.total_sales), 0) / it_counter.quantity
                                    ELSE 0
                                END AS cost
                            FROM reservations_items as it
                                INNER JOIN reservations as rez ON rez.id = it.reservation_id
                                INNER JOIN sites as site ON site.id = rez.site_id
                                INNER JOIN zones as zone_one ON zone_one.id = it.from_zone
                                INNER JOIN zones as zone_two ON zone_two.id = it.to_zone
                                INNER JOIN destination_services as serv ON serv.id = it.destination_service_id
                                LEFT OUTER JOIN users as us ON us.id = rez.call_center_agent_id
                                LEFT OUTER JOIN origin_sales as origin ON origin.id = rez.origin_sale_id
                                LEFT OUTER JOIN types_cancellations as tc ON tc.id = rez.cancellation_type_id
                                LEFT OUTER JOIN vehicles as vehicle_one ON vehicle_one.id = it.vehicle_id_one
                                LEFT OUTER JOIN destination_services as d_one ON d_one.id = vehicle_one.destination_service_id
                                LEFT OUTER JOIN vehicles as vehicle_two ON vehicle_two.id = it.vehicle_id_two
                                LEFT OUTER JOIN destination_services as d_two ON d_two.id = vehicle_two.destination_service_id
                                LEFT OUTER JOIN drivers as driver_one ON driver_one.id = it.driver_id_one
                                LEFT OUTER JOIN drivers as driver_two ON driver_two.id = it.driver_id_two

                                LEFT OUTER JOIN types_cancellations as tc_one ON tc_one.id = it.op_one_cancellation_type_id
                                LEFT OUTER JOIN types_cancellations as tc_two ON tc_two.id = it.op_two_cancellation_type_id

                                LEFT OUTER JOIN (
                                    SELECT DISTINCT reservation_id
                                    FROM reservations_media
                                ) as upload ON upload.reservation_id = rez.id
                                LEFT OUTER JOIN (
                                    SELECT DISTINCT reservation_id
                                    FROM reservations_follow_up
                                    WHERE type IN ('CLIENT', 'OPERATION')
                                ) as rfu ON rfu.reservation_id = rez.id

                                -- Nuevos JOINs para la tabla de reembolsos
                                LEFT OUTER JOIN (
                                    SELECT 
                                        reservation_id,
                                        COUNT(*) as refund_count,
                                        SUM(CASE WHEN status != 'REFUND_COMPLETED' THEN 1 ELSE 0 END) as pending_refund_count
                                    FROM reservations_refunds
                                    GROUP BY reservation_id
                                ) as rr ON rr.reservation_id = rez.id

                                LEFT JOIN (
                                        SELECT
                                            it.reservation_id,
                                            SUM(CASE WHEN it.is_round_trip = 1 THEN 2 ELSE 1 END) as quantity
                                        FROM reservations_items as it
                                        GROUP BY it.reservation_id
                                ) as it_counter ON it_counter.reservation_id = it.reservation_id
                                LEFT JOIN (
                                    SELECT 
                                        reservation_id,  
                                        ROUND( COALESCE(SUM(total), 0), 2) as total_sales
                                    FROM sales
                                        WHERE deleted_at IS NULL
                                    GROUP BY reservation_id
                                ) as s ON s.reservation_id = rez.id
                                LEFT JOIN (
                                    SELECT 
                                        reservation_id,
                                        -- ROUND(SUM(CASE 
                                        --     WHEN operation = 'multiplication' THEN total * exchange_rate
                                        --     WHEN operation = 'division' THEN total / exchange_rate
                                        --     ELSE total END), 2) AS total_payments,
                                        ROUND(SUM(CASE
                                            WHEN category IN ('PAYOUT', 'PAYOUT_CREDIT_PAID') THEN 
                                                CASE 
                                                    WHEN operation = 'multiplication' THEN total * exchange_rate
                                                    WHEN operation = 'division' THEN total / exchange_rate
                                                    ELSE total 
                                                END
                                            ELSE 0
                                        END), 2) AS total_payments,

                                        GROUP_CONCAT(DISTINCT payment_method ORDER BY payment_method ASC SEPARATOR ',') AS payment_type_name,

                                        -- Monto en efectivo
                                        ROUND(SUM(CASE 
                                            WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
                                                CASE 
                                                    WHEN operation = 'multiplication' THEN total * exchange_rate
                                                    WHEN operation = 'division' THEN total / exchange_rate
                                                    ELSE total 
                                                END
                                            ELSE 0 
                                        END), 2) AS cash_amount,                                        
                                        -- Referencias de pagos en efectivo concatenadas
                                        CONCAT(
                                            '[',
                                            GROUP_CONCAT(
                                                DISTINCT 
                                                CASE 
                                                    WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
                                                        CONCAT(
                                                            'Referencia: ', IFNULL(reference, 'SIN REFERENCIA'), 
                                                            ' - Monto: ', 
                                                            ROUND(
                                                                CASE 
                                                                    WHEN operation = 'multiplication' THEN total * exchange_rate
                                                                    WHEN operation = 'division' THEN total / exchange_rate
                                                                    ELSE total 
                                                                END, 2
                                                            )
                                                        )
                                                    ELSE NULL
                                                END
                                                SEPARATOR ']\n['
                                            ),
                                            ']'
                                        ) AS cash_references,
                                        -- Comentario de pagos en efectivo concatenadas
                                        GROUP_CONCAT(DISTINCT CASE WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN conciliation_comment END SEPARATOR ', ') AS cash_comments,
                                        -- Nuevo campo: solo IDs de pagos en efectivo concatenados
                                        CONCAT(
                                            '[',
                                            GROUP_CONCAT(
                                                DISTINCT 
                                                CASE 
                                                    WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN id
                                                    ELSE NULL
                                                END
                                                ORDER BY id ASC
                                                SEPARATOR ','
                                            ),
                                            ']'
                                        ) AS cash_payment_ids,
                                        -- Suma de estatus de conciliación (para determinar si todos están conciliados)
                                        SUM(CASE 
                                            WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
                                                CASE WHEN is_conciliated = 1 THEN 1 ELSE 0 END
                                            ELSE 0
                                        END) AS cash_is_conciliated,
                                        -- Contador de pagos en efectivo
                                        SUM(CASE 
                                            WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 1
                                            ELSE 0
                                        END) AS cash_payment_count                                                                       
                                    FROM payments
                                        WHERE deleted_at IS NULL
                                    GROUP BY reservation_id
                                ) as p ON p.reservation_id = rez.id
                            WHERE 1=1 {$queryOne}
                            GROUP BY it.id, rez.id, serv.id, site.id, zone_one.id, zone_two.id, us.name, upload.reservation_id, rfu.reservation_id, it_counter.quantity, rr.reservation_id, rr.refund_count, rr.pending_refund_count, p.cash_amount, p.cash_references, p.cash_comments, p.cash_payment_ids, p.cash_is_conciliated {$queryHaving}

                            UNION

                            SELECT 
                                rez.id as reservation_id,
                                rez.categories,
                                CONCAT(rez.client_first_name, ' ', rez.client_last_name) as full_name,
                                rez.client_email,
                                rez.client_phone,
                                rez.currency,
                                rez.language,
                                rez.is_cancelled,
                                rez.is_commissionable,
                                rez.site_id,
                                rez.pay_at_arrival,
                                rez.reference,
                                rez.affiliate_id,
                                rez.terminal,
                                rez.comments,
                                rez.is_duplicated,
                                rez.open_credit,
                                rez.is_complete,
                                rez.created_at,
                                rez.campaign,
                                rez.reserve_rating,
                                rez.is_last_minute,

                                us.id AS employee_code,
                                us.status AS employee_status,
                                us.name AS employee,

                                site.id as site_code,
                                site.type_site AS type_site,
                                site.color as site_color,
                                site.name as site_name,

                                origin.code AS origin_code,
                                tc.name_es AS cancellation_reason,
                                CASE WHEN upload.reservation_id IS NOT NULL THEN 1 ELSE 0 END as pictures,
                                CASE WHEN rfu.reservation_id IS NOT NULL THEN 1 ELSE 0 END as messages,

                                -- Información de reembolsos
                                CASE WHEN rr.reservation_id IS NOT NULL THEN 1 ELSE 0 END as has_refund_request,
                                COALESCE(rr.refund_count, 0) as refund_request_count,
                                CASE 
                                    WHEN rr.reservation_id IS NULL THEN 'NO_REFUND'
                                    WHEN rr.pending_refund_count > 0 THEN 'REFUND_REQUESTED'
                                    ELSE 'REFUND_COMPLETED'
                                END as refund_status,                                

                                CASE
                                    WHEN rez.is_cancelled = 1   AND rez.was_is_quotation = 1  THEN 'EXPIRED_QUOTATION'
                                    WHEN rez.is_cancelled = 1   THEN 'CANCELLED'                                    
                                    WHEN rez.is_duplicated = 1  THEN 'DUPLICATED'
                                    WHEN rez.open_credit = 1    THEN 'OPENCREDIT'
                                    WHEN rez.is_quotation = 1   THEN 'QUOTATION'
                                    -- WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
                                    WHEN site.is_cxc = 1 AND ( COALESCE(SUM(p.total_payments), 0) = 0 OR ( COALESCE(SUM(p.total_payments), 0) < COALESCE(SUM(s.total_sales), 0) ) ) THEN 'CREDIT'
                                    WHEN rez.pay_at_arrival = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'PAY_AT_ARRIVAL'
                                    WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) > 0 THEN 'PENDING'
                                    WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'CONFIRMED'
                                    ELSE 'UNKNOWN'
                                END AS reservation_status,

                                'departure' as operation_type,
                                'TYPE_TWO' as op_type,
                                it.id,
                                it.code,
                                it.flight_number,
                                it.is_round_trip,
                                it.passengers,
                                it.spam,
                                it.spam_count,
                                it.op_two_pickup as filtered_date,
                                zone_one.id as zone_one_id,
                                zone_one.name as destination_name_from,
                                it.from_name as from_name,
                                zone_one.is_primary as zone_one_is_primary, 
                                zone_one.cut_off_operation as zone_one_cut_off,
                                it.op_one_status as one_service_status,
                                it.op_one_status_operation as one_service_operation_status,
                                it.op_one_time_operation,
                                it.op_one_preassignment,
                                it.op_one_operating_cost,
                                it.op_one_pickup as pickup_from,
                                it.op_one_confirmation,
                                it.op_one_operation_close,
                                it.op_one_comments,
                                it.op_one_cancelled_at,
                                it.op_one_cancellation_level,
                                it.vehicle_id_one,
                                it.driver_id_one,                                
                                zone_two.id as zone_two_id,
                                zone_two.name as destination_name_to,                                
                                it.to_name as to_name,
                                zone_two.is_primary as zone_two_is_primary, 
                                zone_two.cut_off_operation as zone_two_cut_off,
                                it.op_two_status as two_service_status,
                                it.op_two_status_operation as two_service_operation_status,
                                it.op_two_time_operation,
                                it.op_two_preassignment,
                                it.op_two_operating_cost,
                                it.op_two_pickup as pickup_to,
                                it.op_two_confirmation,
                                it.op_two_operation_close,
                                it.op_two_comments,
                                it.op_two_cancelled_at,
                                it.op_two_cancellation_level,
                                it.vehicle_id_two,
                                it.driver_id_two,
                                it.is_open,
                                it.open_service_time,
                                tc_one.name_es AS cancellation_reason_one,
                                tc_two.name_es AS cancellation_reason_two,

                                CASE
                                    WHEN zone_two.is_primary = 0 AND zone_one.is_primary = 1  THEN 'DEPARTURE'
                                    WHEN zone_one.is_primary = 0 AND zone_two.is_primary = 0 THEN 'TRANSFER'
                                    ELSE 'ARRIVAL'
                                END AS final_service_type,

                                serv.id as service_type_id,
                                serv.name as service_type_name,
                                vehicle_one.name as vehicle_one_name,
                                d_one.name as vehicle_name_one,
                                vehicle_two.name as vehicle_two_name,
                                d_two.name as vehicle_name_two,
                                CONCAT(driver_one.names,' ',driver_one.surnames) as driver_one_name,
                                CONCAT(driver_two.names,' ',driver_two.surnames) as driver_two_name,

                                it_counter.quantity,
                                -- GROUP_CONCAT(DISTINCT p.codes_payment ORDER BY p.codes_payment ASC SEPARATOR ',') AS codes_payment,
                                GROUP_CONCAT(
                                    DISTINCT 
                                    CASE
                                        WHEN site.is_cxc = 1 AND p.payment_type_name IS NULL THEN 'CREDIT'
                                        WHEN p.payment_type_name IS NOT NULL THEN p.payment_type_name
                                        WHEN rez.pay_at_arrival = 1 THEN 'CASH'  -- Asumiendo que pay_at_arrival=1 significa pago en efectivo
                                        ELSE 'NO DEFENIDO'                                        
                                    END
                                ORDER BY p.payment_type_name ASC SEPARATOR ', ') AS payment_type_name,

                                -- Nuevos campos para pagos en efectivo
                                COALESCE(p.cash_amount, 0) AS cash_amount,
                                COALESCE(p.cash_references) AS cash_references,
                                COALESCE(p.cash_comments) AS cash_comments,
                                COALESCE(p.cash_payment_ids) AS cash_payment_ids,
                                COALESCE(p.cash_is_conciliated) AS cash_is_conciliated,

                                COALESCE(SUM(s.total_sales), 0) as total_sales,
                                COALESCE(SUM(p.total_payments), 0) as total_payments,
                                COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) AS total_balance,
                                CASE
                                    WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
                                    WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'PAID'
                                    ELSE 'PENDING'
                                END AS payment_status,
                                CASE 
                                    WHEN it.is_round_trip = 1 THEN COALESCE(SUM(s.total_sales), 0) / 2
                                    ELSE COALESCE(SUM(s.total_sales), 0)
                                END AS service_cost,
                                CASE 
                                    WHEN it_counter.quantity > 0 THEN COALESCE(SUM(s.total_sales), 0) / it_counter.quantity
                                    ELSE 0
                                END AS cost                                
                            FROM reservations_items as it
                                INNER JOIN reservations as rez ON rez.id = it.reservation_id
                                INNER JOIN sites as site ON site.id = rez.site_id
                                INNER JOIN zones as zone_one ON zone_one.id = it.from_zone
                                INNER JOIN zones as zone_two ON zone_two.id = it.to_zone
                                INNER JOIN destination_services as serv ON serv.id = it.destination_service_id
                                LEFT OUTER JOIN users as us ON us.id = rez.call_center_agent_id
                                LEFT OUTER JOIN origin_sales as origin ON origin.id = rez.origin_sale_id
                                LEFT OUTER JOIN types_cancellations as tc ON tc.id = rez.cancellation_type_id
                                LEFT OUTER JOIN vehicles as vehicle_one ON vehicle_one.id = it.vehicle_id_one
                                LEFT OUTER JOIN destination_services as d_one ON d_one.id = vehicle_one.destination_service_id
                                LEFT OUTER JOIN vehicles as vehicle_two ON vehicle_two.id = it.vehicle_id_two
                                LEFT OUTER JOIN destination_services as d_two ON d_two.id = vehicle_two.destination_service_id
                                LEFT OUTER JOIN drivers as driver_one ON driver_one.id = it.driver_id_one
                                LEFT OUTER JOIN drivers as driver_two ON driver_two.id = it.driver_id_two

                                LEFT OUTER JOIN types_cancellations as tc_one ON tc_one.id = it.op_one_cancellation_type_id
                                LEFT OUTER JOIN types_cancellations as tc_two ON tc_two.id = it.op_two_cancellation_type_id                                

                                LEFT OUTER JOIN (
                                    SELECT DISTINCT reservation_id
                                    FROM reservations_media
                                ) as upload ON upload.reservation_id = rez.id
                                LEFT OUTER JOIN (
                                    SELECT DISTINCT reservation_id
                                    FROM reservations_follow_up
                                    WHERE type IN ('CLIENT', 'OPERATION')
                                ) as rfu ON rfu.reservation_id = rez.id

                                -- Nuevos JOINs para la tabla de reembolsos
                                LEFT OUTER JOIN (
                                    SELECT 
                                        reservation_id,
                                        COUNT(*) as refund_count,
                                        SUM(CASE WHEN status != 'REFUND_COMPLETED' THEN 1 ELSE 0 END) as pending_refund_count
                                    FROM reservations_refunds
                                    GROUP BY reservation_id
                                ) as rr ON rr.reservation_id = rez.id

                                LEFT JOIN (
                                        SELECT
                                            it.reservation_id,
                                            SUM(CASE WHEN it.is_round_trip = 1 THEN 2 ELSE 1 END) as quantity
                                        FROM reservations_items as it
                                        GROUP BY it.reservation_id
                                ) as it_counter ON it_counter.reservation_id = it.reservation_id
                                LEFT JOIN (
                                    SELECT 
                                        reservation_id,  
                                        ROUND( COALESCE(SUM(total), 0), 2) as total_sales
                                    FROM sales
                                        WHERE deleted_at IS NULL
                                    GROUP BY reservation_id
                                ) as s ON s.reservation_id = rez.id
                                LEFT JOIN (
                                    SELECT 
                                        reservation_id,
                                        -- ROUND(SUM(CASE 
                                        --             WHEN operation = 'multiplication' THEN total * exchange_rate
                                        --             WHEN operation = 'division' THEN total / exchange_rate
                                        --             ELSE total 
                                        --         END), 2) AS total_payments,
                                        ROUND(SUM(CASE
                                            WHEN category IN ('PAYOUT', 'PAYOUT_CREDIT_PAID') THEN 
                                                CASE 
                                                    WHEN operation = 'multiplication' THEN total * exchange_rate
                                                    WHEN operation = 'division' THEN total / exchange_rate
                                                    ELSE total 
                                                END
                                            ELSE 0
                                        END), 2) AS total_payments,

                                        GROUP_CONCAT(DISTINCT payment_method ORDER BY payment_method ASC SEPARATOR ',') AS payment_type_name,

                                        -- Monto en efectivo
                                        ROUND(SUM(CASE 
                                                    WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
                                                    CASE 
                                                        WHEN operation = 'multiplication' THEN total * exchange_rate
                                                        WHEN operation = 'division' THEN total / exchange_rate
                                                        ELSE total 
                                                    END
                                                ELSE 0 
                                        END), 2) AS cash_amount,
                                        -- Referencias de pagos en efectivo concatenadas
                                        CONCAT(
                                            '[',
                                            GROUP_CONCAT(
                                                DISTINCT 
                                                CASE 
                                                    WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
                                                        CONCAT(
                                                            'Referencia: ', IFNULL(reference, 'SIN REFERENCIA'), 
                                                            ' - Monto: ', 
                                                            ROUND(
                                                                CASE 
                                                                    WHEN operation = 'multiplication' THEN total * exchange_rate
                                                                    WHEN operation = 'division' THEN total / exchange_rate
                                                                    ELSE total 
                                                                END, 2
                                                            )
                                                        )
                                                    ELSE NULL
                                                END
                                                SEPARATOR ']\n['
                                            ),
                                            ']'
                                        ) AS cash_references,
                                        -- Comentario de pagos en efectivo concatenadas
                                        GROUP_CONCAT(DISTINCT CASE WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN conciliation_comment END SEPARATOR ', ') AS cash_comments,                                        
                                        -- Nuevo campo: solo IDs de pagos en efectivo concatenados
                                        CONCAT(
                                            '[',
                                            GROUP_CONCAT(
                                                DISTINCT 
                                                CASE 
                                                    WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN id
                                                    ELSE NULL
                                                END
                                                ORDER BY id ASC
                                                SEPARATOR ','
                                            ),
                                            ']'
                                        ) AS cash_payment_ids,
                                        -- Suma de estatus de conciliación (para determinar si todos están conciliados)
                                        SUM(CASE 
                                            WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 
                                                CASE WHEN is_conciliated = 1 THEN 1 ELSE 0 END
                                            ELSE 0
                                        END) AS cash_is_conciliated,
                                        -- Contador de pagos en efectivo
                                        SUM(CASE 
                                            WHEN payment_method = 'CASH' OR payment_method LIKE '%EFECTIVO%' THEN 1
                                            ELSE 0
                                        END) AS cash_payment_count
                                    FROM payments
                                        WHERE deleted_at IS NULL
                                    GROUP BY reservation_id
                                ) as p ON p.reservation_id = rez.id
                            WHERE 1=1 {$queryTwo}
                            GROUP BY it.id, rez.id, serv.id, site.id, zone_one.id, zone_two.id, us.name, upload.reservation_id, rfu.reservation_id, it_counter.quantity, rr.reservation_id, rr.refund_count, rr.pending_refund_count, p.cash_amount, p.cash_references, p.cash_comments, p.cash_payment_ids, p.cash_is_conciliated {$queryHaving}
                            -- ) AS combined_results
                            ORDER BY filtered_date ASC ",[
                                "init_date_one" => $queryData['init'],
                                "init_date_two" => $queryData['end'],
                                "init_date_three" => $queryData['init'],
                                "init_date_four" => $queryData['end'],
                            ]);
    }

    //NO SE ESTA UTILIZANDO POR NINGUN LADO
    public function queryConciliationStripe($query, $query2, $queryData){
        $bookings = DB::select("SELECT 
                                    rez.id AS reservation_id, 
                                    CONCAT(rez.client_first_name,' ',rez.client_last_name) as full_name,
                                    rez.client_email,
                                    rez.client_phone,
                                    rez.currency,
                                    rez.is_cancelled,
                                    rez.is_commissionable,                                    
                                    rez.site_id,
                                    rez.pay_at_arrival,
                                    rez.reference,
                                    rez.affiliate_id,
                                    rez.terminal,
                                    rez.comments,
                                    rez.is_duplicated,
                                    rez.open_credit,
                                    rez.is_complete,
                                    rez.created_at,
                                    rez.is_quotation,
                                    rez.was_is_quotation,
                                    rez.campaign,
                                    rez.reserve_rating,
                                    
                                    us.id AS employee_code,
                                    us.status AS employee_status,
                                    us.name AS employee,

                                    site.id as site_code,
                                    site.type_site AS type_site,
                                    site.name AS site_name,

                                    origin.code AS origin_code,
                                    tc.name_es AS cancellation_reason,
                                    GROUP_CONCAT(DISTINCT it.code ORDER BY it.code ASC SEPARATOR ',') AS reservation_codes,
                                    GROUP_CONCAT(DISTINCT it.zone_one_name ORDER BY it.zone_one_name ASC SEPARATOR ',') AS destination_name_from,
                                    GROUP_CONCAT(DISTINCT it.zone_one_id ORDER BY it.zone_one_id ASC SEPARATOR ',') AS zone_one_id,
                                    GROUP_CONCAT(DISTINCT it.from_name SEPARATOR ',') AS from_name,
                                    GROUP_CONCAT(DISTINCT it.zone_two_name ORDER BY it.zone_two_name ASC SEPARATOR ',') AS destination_name_to,
                                    GROUP_CONCAT(DISTINCT it.zone_two_id ORDER BY it.zone_two_id ASC SEPARATOR ',') AS zone_two_id,
                                    GROUP_CONCAT(DISTINCT it.to_name SEPARATOR ',') AS to_name,
                                    GROUP_CONCAT(DISTINCT it.service_type_id ORDER BY it.service_type_id ASC SEPARATOR ',') AS service_type_id,
                                    GROUP_CONCAT(DISTINCT it.service_type_name ORDER BY it.service_type_name ASC SEPARATOR ',') AS service_type_name,
                                    GROUP_CONCAT(DISTINCT it.pickup_from ORDER BY it.pickup_from ASC SEPARATOR ',') AS pickup_from,
                                    GROUP_CONCAT(DISTINCT it.pickup_to ORDER BY it.pickup_to ASC SEPARATOR ',') AS pickup_to,
                                    GROUP_CONCAT(DISTINCT it.one_service_status ORDER BY it.one_service_status ASC SEPARATOR ',') AS one_service_status,
                                    GROUP_CONCAT(DISTINCT it.two_service_status ORDER BY it.two_service_status ASC SEPARATOR ',') AS two_service_status,
                                    SUM(it.passengers) as passengers,
                                    COALESCE(SUM(it.op_one_pickup_today) + SUM(it.op_two_pickup_today), 0) as is_today,
                                    SUM(it.is_round_trip) as is_round_trip,
                                    MAX(it.is_same_day_round_trip) AS is_same_day_round_trip,
                                    COALESCE(SUM(s.total_sales), 0) as total_sales,
                                    COALESCE(SUM(p.total_payments), 0) as total_payments,
                                    COALESCE(SUM(p.total_payments_stripe), 0) as total_payments_stripe,                                    
                                    COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) AS total_balance,
                                    COALESCE(p.stripe_payment_ids) AS stripe_payment_ids,                                    
                                    CASE
                                        WHEN rez.is_cancelled = 1   AND rez.was_is_quotation = 1  THEN 'EXPIRED_QUOTATION'
                                        WHEN rez.is_cancelled = 1   THEN 'CANCELLED'
                                        WHEN rez.is_duplicated = 1  THEN 'DUPLICATED'
                                        WHEN rez.open_credit = 1    THEN 'OPENCREDIT'
                                        WHEN rez.is_quotation = 1   THEN 'QUOTATION'
                                        WHEN site.is_cxc = 1 AND ( COALESCE(SUM(p.total_payments), 0) = 0 OR ( COALESCE(SUM(p.total_payments), 0) < COALESCE(SUM(s.total_sales), 0) ) ) THEN 'CREDIT'
                                        WHEN rez.pay_at_arrival = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'PAY_AT_ARRIVAL'
                                        WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) > 0 THEN 'PENDING'
                                        WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'CONFIRMED'
                                        ELSE 'UNKNOWN'
                                    END AS reservation_status,
                                    CASE
                                        WHEN site.is_cxc = 1 AND COALESCE(SUM(p.total_payments), 0) = 0 THEN 'CREDIT'
                                        WHEN COALESCE(SUM(s.total_sales), 0) - COALESCE(SUM(p.total_payments), 0) <= 0 THEN 'PAID'
                                        ELSE 'PENDING'
                                    END AS payment_status,
                                    GROUP_CONCAT(
                                        DISTINCT 
                                        CASE
                                            WHEN site.is_cxc = 1 AND p.payment_type_name IS NULL THEN 'CREDIT'
                                            WHEN p.payment_type_name IS NOT NULL THEN p.payment_type_name
                                            WHEN rez.pay_at_arrival = 1 THEN 'CASH'  -- Asumiendo que pay_at_arrival=1 significa pago en efectivo
                                            ELSE 'SIN METODO DE PAGO'
                                        END
                                    ORDER BY p.payment_type_name ASC SEPARATOR ', ') AS payment_type_name,
                                    GROUP_CONCAT(DISTINCT p.reference_stripe ORDER BY p.reference_stripe ASC SEPARATOR ', ') AS reference_stripe,
                                    SUM(p.is_charged) as is_charged,
                                    SUM(p.is_refund) as is_refund,
                                    GROUP_CONCAT(DISTINCT p.date_conciliation ORDER BY p.date_conciliation ASC SEPARATOR ', ') AS date_conciliation,
                                    COALESCE(SUM(p.amount), 0) as amount,
                                    COALESCE(SUM(p.total_net), 0) as total_net,
                                    COALESCE(SUM(p.total_fee), 0) as total_fee,
                                    GROUP_CONCAT(DISTINCT p.deposit_date ORDER BY p.deposit_date ASC SEPARATOR ', ') AS deposit_date
                                FROM reservations as rez
                                    INNER JOIN sites as site ON site.id = rez.site_id
                                    LEFT OUTER JOIN users as us ON us.id = rez.call_center_agent_id
                                    LEFT OUTER JOIN origin_sales as origin ON origin.id = rez.origin_sale_id
                                    LEFT OUTER JOIN types_cancellations as tc ON tc.id = rez.cancellation_type_id
                                    -- JOINs para tabla de ventas (sales)
                                    LEFT JOIN (
                                        SELECT 
                                            reservation_id, 
                                            ROUND( COALESCE(SUM(total), 0), 2) as total_sales
                                        FROM sales
                                            WHERE deleted_at IS NULL 
                                        GROUP BY reservation_id
                                    ) as s ON s.reservation_id = rez.id
                                    -- JOINs para tabla de pagos (payments)
                                    LEFT JOIN (
                                        SELECT 
                                            reservation_id,
                                            ROUND(SUM(CASE 
                                                WHEN operation = 'multiplication' THEN total * exchange_rate
                                                WHEN operation = 'division' THEN total / exchange_rate
                                                ELSE total END
                                                ), 2
                                            ) AS total_payments,
                                            ROUND(SUM(CASE
                                                WHEN category = 'PAYOUT' AND payment_method = 'STRIPE' THEN 
                                                    CASE 
                                                        WHEN operation = 'multiplication' THEN total * exchange_rate
                                                        WHEN operation = 'division' THEN total / exchange_rate
                                                        ELSE total 
                                                    END
                                                    ELSE 0
                                                END), 2
                                            ) AS total_payments_stripe,
                                            CONCAT(
                                                '[',
                                                    GROUP_CONCAT(
                                                        DISTINCT 
                                                        CASE 
                                                            WHEN payment_method = 'STRIPE' OR payment_method LIKE '%STRIPE%' THEN id
                                                            ELSE NULL
                                                        END
                                                        ORDER BY id ASC
                                                        SEPARATOR ','
                                                    ),
                                                ']'
                                            ) AS stripe_payment_ids,
                                            GROUP_CONCAT(DISTINCT payment_method ORDER BY payment_method ASC SEPARATOR ',') AS payment_type_name,
                                            -- Y para payment_details:
                                            GROUP_CONCAT(
                                                DISTINCT CASE
                                                    WHEN category = 'PAYOUT' AND payment_method = 'STRIPE' THEN
                                                        CONCAT(
                                                            reference
                                                        )
                                                    ELSE NULL
                                                END
                                                ORDER BY payment_method ASC SEPARATOR ', '
                                            ) AS reference_stripe,
                                            SUM(is_conciliated) as is_charged,
                                            SUM(is_refund) as is_refund,
                                            GROUP_CONCAT(DISTINCT date_conciliation ORDER BY date_conciliation ASC SEPARATOR ',') AS date_conciliation,
                                            ROUND(SUM(amount), 2) AS amount,
                                            ROUND(SUM(total_net), 2) AS total_net,
                                            ROUND(SUM(total_fee), 2) AS total_fee,
                                            GROUP_CONCAT(DISTINCT deposit_date ORDER BY deposit_date ASC SEPARATOR ',') AS deposit_date
                                        FROM payments
                                            WHERE deleted_at IS NULL
                                        GROUP BY reservation_id, is_conciliated
                                    ) as p ON p.reservation_id = rez.id
                                    -- JOINs para tabla de items de reservacion (reservations_items)
                                    LEFT JOIN (
                                        SELECT  
                                            it.reservation_id, 
                                            it.is_round_trip,
                                            SUM(it.passengers) as passengers,
                                            GROUP_CONCAT(DISTINCT it.code ORDER BY it.code ASC SEPARATOR ',') AS code,
                                            GROUP_CONCAT(DISTINCT zone_one.name ORDER BY zone_one.name ASC SEPARATOR ',') AS zone_one_name,
                                            GROUP_CONCAT(DISTINCT zone_one.id ORDER BY zone_one.id ASC SEPARATOR ',') AS zone_one_id,
                                            GROUP_CONCAT(DISTINCT it.from_name SEPARATOR ',') AS from_name,
                                            GROUP_CONCAT(DISTINCT zone_two.name ORDER BY zone_two.name ASC SEPARATOR ',') AS zone_two_name, 
                                            GROUP_CONCAT(DISTINCT zone_two.id ORDER BY zone_two.id ASC SEPARATOR ',') AS zone_two_id,
                                            GROUP_CONCAT(DISTINCT it.to_name SEPARATOR ',') AS to_name,
                                            GROUP_CONCAT(DISTINCT dest.id ORDER BY dest.id ASC SEPARATOR ',') AS service_type_id,
                                            GROUP_CONCAT(DISTINCT dest.name ORDER BY dest.name ASC SEPARATOR ',') AS service_type_name,
                                            GROUP_CONCAT(DISTINCT it.op_one_pickup ORDER BY it.op_one_pickup ASC SEPARATOR ',') AS pickup_from,
                                            GROUP_CONCAT(DISTINCT it.op_two_pickup ORDER BY it.op_two_pickup ASC SEPARATOR ',') AS pickup_to,
                                            GROUP_CONCAT(DISTINCT it.op_one_status ORDER BY it.op_one_status ASC SEPARATOR ',') AS one_service_status,
                                            GROUP_CONCAT(DISTINCT it.op_two_status ORDER BY it.op_two_status ASC SEPARATOR ',') AS two_service_status,
                                            MAX(CASE WHEN DATE(it.op_one_pickup) = DATE(rez.created_at) THEN 1 ELSE 0 END) AS op_one_pickup_today,
                                            MAX(CASE WHEN DATE(it.op_two_pickup) = DATE(rez.created_at) THEN 1 ELSE 0 END) AS op_two_pickup_today,
                                            -- Nueva condición para verificar si es round trip y las fechas son el mismo día
                                            MAX(CASE WHEN it.is_round_trip = 1 AND DATE(it.op_one_pickup) = DATE(it.op_two_pickup) THEN 1 ELSE 0 END) AS is_same_day_round_trip
                                        FROM reservations_items as it
                                            INNER JOIN zones as zone_one ON zone_one.id = it.from_zone
                                            INNER JOIN zones as zone_two ON zone_two.id = it.to_zone
                                            INNER JOIN destination_services as dest ON dest.id = it.destination_service_id
                                            INNER JOIN reservations as rez ON rez.id = it.reservation_id
                                        GROUP BY it.reservation_id, it.is_round_trip
                                    ) as it ON it.reservation_id = rez.id
                                    WHERE 1=1 {$query}
                                GROUP BY rez.id, site.id, site.type_site, site.name, site.is_cxc, p.stripe_payment_ids {$query2}",
                                    $queryData);

        if(sizeof( $bookings ) > 0):
            usort($bookings, array($this, 'orderByDateTime'));
        endif;

        return $bookings;
    }

    //EL QUERY QUE UTILIZO PARA STRIPE, YA QUE LO REALIZA POR PAGOS
    public function queryConciliation($query, $query2, $queryData){
        $payments = DB::select("SELECT
                                        rez.id AS reservation_id, 
                                        CONCAT(rez.client_first_name,' ',rez.client_last_name) as full_name,
                                        rez.client_email,
                                        rez.client_phone,
                                        rez.currency,
                                        rez.is_cancelled,
                                        rez.is_commissionable,                                    
                                        rez.site_id,
                                        rez.pay_at_arrival,
                                        rez.reference,
                                        rez.affiliate_id,
                                        rez.terminal,
                                        rez.comments,
                                        rez.is_duplicated,
                                        rez.open_credit,
                                        rez.is_complete,
                                        rez.created_at,
                                        rez.is_quotation,
                                        rez.was_is_quotation,
                                        rez.campaign,
                                        rez.reserve_rating,

                                        site.id as site_code,
                                        site.type_site AS type_site,
                                        site.name AS site_name,

                                        origin.code AS origin_code,
                                        tc.name_es AS cancellation_reason,
                                        
                                        it.code AS reservation_codes,
                                        it.zone_one_name AS destination_name_from,
                                        it.zone_one_id AS zone_one_id,
                                        it.from_name AS from_name,
                                        it.zone_two_name AS destination_name_to,
                                        it.zone_two_id AS zone_two_id,
                                        it.to_name AS to_name,
                                        it.service_type_id AS service_type_id,
                                        it.service_type_name AS service_type_name,
                                        it.pickup_from AS pickup_from,
                                        it.pickup_to AS pickup_to,
                                        it.one_service_status AS one_service_status,
                                        it.two_service_status AS two_service_status,
                                        it.passengers AS passengers,
                                        -- COALESCE(SUM(it.op_one_pickup_today) + SUM(it.op_two_pickup_today), 0) as is_today,
                                        -- SUM(it.is_round_trip) as is_round_trip,
                                        -- MAX(it.is_same_day_round_trip) AS is_same_day_round_trip,

                                        s.total_sales as total_sales,

                                        p.description,
                                        p.total AS total_payments_stripe,
                                        p.exchange_rate,
                                        p.operation,
                                        p.payment_method AS payment_type_name,                                                                                                                
                                        p.currency AS currency_payment,
                                        p.reference AS reference_stripe,
                                        p.reference_conciliation,
                                        p.is_conciliated,
                                        p.is_refund,
                                        p.refunded,
                                        p.disputed,
                                        p.date_conciliation,
                                        p.deposit_date,
                                        p.amount,
                                        p.total_fee,
                                        p.total_net,
                                        p.total_final_net,
                                        p.bank_name,
                                        p.created_at As created_payment,
                                        p.updated_at AS updated_payment,

                                        CASE
                                            WHEN rez.is_cancelled = 1 THEN 'CANCELLED'
                                            WHEN rez.open_credit = 1 THEN 'OPENCREDIT'
                                            WHEN rez.is_duplicated = 1 THEN 'DUPLICATED'
                                            WHEN s.total_sales - p.total > 0 THEN 'PENDING'
                                            WHEN s.total_sales - p.total <= 0 THEN 'CONFIRMED'
                                            ELSE 'UNKNOWN'
                                        END AS reservation_status
                                    FROM payments as p
                                        INNER JOIN reservations as rez ON p.reservation_id = rez.id
                                        INNER JOIN sites as site ON site.id = rez.site_id
                                        LEFT OUTER JOIN origin_sales as origin ON origin.id = rez.origin_sale_id
                                        LEFT OUTER JOIN types_cancellations as tc ON tc.id = rez.cancellation_type_id                                        
                                        -- JOINs para tabla de ventas (sales)
                                        LEFT JOIN (
                                            SELECT 
                                                reservation_id, 
                                                ROUND( COALESCE(SUM(total), 0), 2) as total_sales
                                            FROM sales
                                                WHERE deleted_at IS NULL 
                                            GROUP BY reservation_id
                                        ) as s ON s.reservation_id = rez.id
                                        -- JOINs para tabla de items de reservacion (reservations_items)
                                        LEFT JOIN (
                                            SELECT  
                                                it.reservation_id, 
                                                it.is_round_trip,
                                                SUM(it.passengers) as passengers,
                                                GROUP_CONCAT(DISTINCT it.code ORDER BY it.code ASC SEPARATOR ',') AS code,
                                                GROUP_CONCAT(DISTINCT zone_one.name ORDER BY zone_one.name ASC SEPARATOR ',') AS zone_one_name,
                                                GROUP_CONCAT(DISTINCT zone_one.id ORDER BY zone_one.id ASC SEPARATOR ',') AS zone_one_id,
                                                GROUP_CONCAT(DISTINCT it.from_name SEPARATOR ',') AS from_name,
                                                GROUP_CONCAT(DISTINCT zone_two.name ORDER BY zone_two.name ASC SEPARATOR ',') AS zone_two_name, 
                                                GROUP_CONCAT(DISTINCT zone_two.id ORDER BY zone_two.id ASC SEPARATOR ',') AS zone_two_id,
                                                GROUP_CONCAT(DISTINCT it.to_name SEPARATOR ',') AS to_name,
                                                GROUP_CONCAT(DISTINCT dest.id ORDER BY dest.id ASC SEPARATOR ',') AS service_type_id,
                                                GROUP_CONCAT(DISTINCT dest.name ORDER BY dest.name ASC SEPARATOR ',') AS service_type_name,
                                                GROUP_CONCAT(DISTINCT it.op_one_pickup ORDER BY it.op_one_pickup ASC SEPARATOR ',') AS pickup_from,
                                                GROUP_CONCAT(DISTINCT it.op_two_pickup ORDER BY it.op_two_pickup ASC SEPARATOR ',') AS pickup_to,
                                                GROUP_CONCAT(DISTINCT it.op_one_status ORDER BY it.op_one_status ASC SEPARATOR ',') AS one_service_status,
                                                GROUP_CONCAT(DISTINCT it.op_two_status ORDER BY it.op_two_status ASC SEPARATOR ',') AS two_service_status,
                                                MAX(CASE WHEN DATE(it.op_one_pickup) = DATE(rez.created_at) THEN 1 ELSE 0 END) AS op_one_pickup_today,
                                                MAX(CASE WHEN DATE(it.op_two_pickup) = DATE(rez.created_at) THEN 1 ELSE 0 END) AS op_two_pickup_today,
                                                -- Nueva condición para verificar si es round trip y las fechas son el mismo día
                                                MAX(CASE WHEN it.is_round_trip = 1 AND DATE(it.op_one_pickup) = DATE(it.op_two_pickup) THEN 1 ELSE 0 END) AS is_same_day_round_trip
                                            FROM reservations_items as it
                                                INNER JOIN zones as zone_one ON zone_one.id = it.from_zone
                                                INNER JOIN zones as zone_two ON zone_two.id = it.to_zone
                                                INNER JOIN destination_services as dest ON dest.id = it.destination_service_id
                                                INNER JOIN reservations as rez ON rez.id = it.reservation_id
                                            GROUP BY it.reservation_id, it.is_round_trip
                                        ) as it ON it.reservation_id = rez.id
                                    WHERE 1=1 {$query} ",
                                    $queryData);

        if(sizeof( $payments ) > 0):
            usort($payments, array($this, 'orderByDateTime'));
        endif;

        return $payments;
    }

    //TRAEREMOS PAGOS DE PAYPAL QUE TENGA FECHA DE AGREGACIÓN Y NO AYAN SIDO ELIMINADOS
    public function getPaymentsConciliation($method = "", $init = "", $end = ""){
        $query = ( $init != "" && $end != "" ? ' AND rez.created_at BETWEEN "'.$init.'" AND "'.$end.'" ' : "" );
       return DB::select("SELECT 
                                    p.*,
                                    rez.id AS reservation_id,
                                    rez.is_cancelled,
                                    rez.is_duplicated
                                FROM payments AS p
                                    INNER JOIN reservations AS rez ON p.reservation_id = rez.id
                                WHERE 
                                    p.payment_method = :method 
                                    AND p.created_at IS NOT NULL /** LA FECHA DE PAGO NO ES NULO */ 
                                    AND p.deleted_at IS NULL
                                    AND p.date_conciliation IS NULL /** LA FECHA DE CONCILIACION ES NULO */ 
                                    AND (p.reference IS NOT NULL AND p.reference != '')
                                    AND (p.reference LIKE 'pi\_%' OR p.reference LIKE 'py\_%' OR p.reference LIKE 'ch\_%')
                                    -- AND rez.is_cancelled = 0 
                                    AND rez.is_duplicated = 0 ".$query." ", [
                                        'method' => $method
                                    ]);
    }

    private function orderByDateTime($a, $b) {
        return strtotime($b->created_at) - strtotime($a->created_at);
    }
}
