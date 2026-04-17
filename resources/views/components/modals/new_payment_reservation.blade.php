<div class="modal fade" id="servicePaymentsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="frm_new_payment">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label" for="servicePaymentsTypeModal">Tipo</label>
                            <select class="form-select mb-2" id="servicePaymentsTypeModal" name="payment_method">
                                <option value="CASH" selected>Efectivo</option>
                                <option value="PAYPAL">PayPal</option>
                                <option value="STRIPE">Stripe</option>
                                <option value="OPENPAY">Openpay</option>
                                <option value="SANTANDER">Santander</option>
                                <option value="TRANSFER">Transferencia</option>
                                <option value="MIFEL">MIFEL</option>
                            </select>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label" for="servicePaymentsDescriptionModal">Descripción / referencia</label>
                            <input type="text" class="form-control mb-2" id="servicePaymentsDescriptionModal" name="reference">
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label" for="servicePaymentsTotalModal">Total</label>
                            <input type="number" class="form-control mb-2" id="servicePaymentsTotalModal" name="total">
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label" for="servicePaymentsCurrencyModalPayment">Moneda</label>
                            <select class="form-select mb-2" id="servicePaymentsCurrencyModalPayment" name="currency">
                                <option value="USD" @if ($currency == 'USD') selected @endif>USD</option>
                                <option value="MXN" @if ($currency == 'MXN') selected @endif>MXN</option>
                            </select>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0" for="servicePaymentsExchangeModal">Tipo de cambio</label>
                                <button type="button" class="btn btn-sm btn-outline-success" id="btn_fill_pending_payment" style="display:none;" title="Calcular tipo de cambio para cubrir el pendiente">Cuadrar tipo de cambio al balance</button>
                            </div>
                            <input type="number" class="form-control mb-2" id="servicePaymentsExchangeModal" name="exchange_rate" value="1.00" readonly>
                        </div>
                    </div>
                    <input type="hidden" name="reservation_id" value="{{ $reservation_id }}" id="reserv_id_pay">
                    <input type="hidden" name="operation" value="multiplication" id="operation_pay">

                    <input type="hidden" name="type_site" value="{{ $type_site }}">
                    <input type="hidden" name="platform" value="{{ $platform }}">
                </form>
                <input type="hidden" id="type_form_pay" value="1">
                <input type="hidden" id="payment_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn_new_payment">Guardar</button>
            </div>
        </div>
    </div>
</div>