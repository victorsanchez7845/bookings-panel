<?php

use App\Http\Controllers\Bots\MasterToursController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Accounting\ConciliationController as CONCILIATION;

//DASHBOARD
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\CallCenterController;

//TPV
use App\Http\Controllers\Tpv\TpvController as TPV;
use App\Http\Controllers\Tpv\TpvController2;
use App\Http\Controllers\Tpv\Api\AutocompleteController as APIAutocomplete;
use App\Http\Controllers\Tpv\Api\QuoteController as APIQuote;
use App\Http\Controllers\Bookings\BookingsController;

//FINANCES
use App\Http\Controllers\Finances\RefundsController as             RefundsFinances;
use App\Http\Controllers\Finances\ReceivablesController as         RECEIVABLES;
use App\Http\Controllers\Finances\StripeController as              STRIPE;

//REPORTS
use App\Http\Controllers\Reports\PaymentsController as             PAYMENTS;
use App\Http\Controllers\Reports\CashController as                 CASH;
use App\Http\Controllers\Reports\CancellationsController as        CANCELLATIONS;
use App\Http\Controllers\Reports\CommissionsController as          COMMISSIONS;
use App\Http\Controllers\Reports\SalesController as                SALES;
use App\Http\Controllers\Reports\OperationsController as           OPERATIONSS;
use App\Http\Controllers\Reports\OperationsDataController as       DATAOPERATION;

//MANAGEMENT
use App\Http\Controllers\Management\ConfirmationsController;
use App\Http\Controllers\Management\AfterSalesController;
use App\Http\Controllers\Management\CCFormController as             CCFORM;
use App\Http\Controllers\Management\QuotationController as          QUOTATION;
use App\Http\Controllers\Management\PendingController as            PENDING;
use App\Http\Controllers\Management\SpamController as               SPAM;
use App\Http\Controllers\Management\ReservationsController as       RESERVATIONS;
use App\Http\Controllers\Operations\OperationsController as         Operations;
use App\Http\Controllers\Management\HotelsController as             HOTELS;

//SETTINGS
use App\Http\Controllers\Settings\RoleController as                 ROLES;
use App\Http\Controllers\Settings\UserController as                 USERS;
use App\Http\Controllers\Settings\EnterpriseController as           ENTERPRISES;
use App\Http\Controllers\Settings\SitesController as                SITES;
use App\Http\Controllers\Settings\ZonesController as                ZONES_WEB;
use App\Http\Controllers\Settings\ZonesEnterpriseController as      ZONES_ENTERPRISE;

use App\Http\Controllers\Settings\VehicleController as              VEHICLES;
use App\Http\Controllers\Settings\DriverController as               DRIVERS;
use App\Http\Controllers\Settings\DriverSchedulesController as      SCHEDULES;
use App\Http\Controllers\Settings\ExchangeReportsController as      EXCHANGE_REPORTS;
use App\Http\Controllers\Settings\RatesController as                RATES_WEB;
use App\Http\Controllers\Settings\RatesEnterpriseController as      RATES_ENTERPRISE;
use App\Http\Controllers\Settings\TypesCancellationsController as   TYPES_CANCELLATIONS;
use App\Http\Controllers\Settings\TypesSalesController as           TYPES_SALES;
use App\Http\Controllers\Settings\OperatorFeeController as          OPERATORFEE;
use App\Http\Controllers\Settings\ScheduleRestrictionsController as SCHEDULE_RESTRICTIONS;

//DETAILS RESERVATION
use App\Http\Controllers\Reservations\ReservationsController as     DETAILS_RESERVATION;

//GENERALS
use App\Http\Controllers\Sales\SalesController;
use App\Http\Controllers\Payments\PaymentsController;

//ACTIONS
use App\Http\Controllers\Actions\FinanceController as               FINANCE;
use App\Http\Controllers\Actions\DataController as                  DATA;
use App\Http\Controllers\Actions\ActionsController as               ACTIONS_RESERVATION;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DualDatabaseController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/qr/create/{type}/{id}/{language}', [TpvController2::class, 'createQr'])->name('qr.createQr');
Route::post('/tpv2/autocomplete', [APIAutocomplete::class,'index']);
Route::post('/tpv2/quote', [APIQuote::class,'index']);
Route::post('/tpv2/re-quote', [APIQuote::class,'checkout']);

Route::get('/validate-sales',               [DualDatabaseController::class,  'validateSales']);
Route::get('/validate-payments',            [DualDatabaseController::class,  'validatePayments']);
Route::get('/validate-reservationItems',    [DualDatabaseController::class,  'validateReservationItems']);
Route::get('/insert-reservationItems',      [DualDatabaseController::class,  'insertReservationItems']);

Route::get('/statisticsServer',      [DualDatabaseController::class,  'statisticsServer']);


//TPV AND BOOKING DETAILS
Route::middleware(['locale','ApiChecker','Debug'])->group(function () {
    Route::get('/tpv2/book/{id}', [TpvController2::class, 'book'])->name('tpv.book');
    Route::post('/tpv2/book/{id}/make', [TpvController2::class, 'create'])->name('tpv.create.en');

    Route::get('/thank-you', [TpvController2::class, 'success'])->name('process.success');
    Route::get('/cancel', [TpvController2::class, 'cancel'])->name('process.cancel');
    Route::get('/my-reservation-detail', [BookingsController::class, 'ReservationDetail'])->name('reservation.detail');

    Route::prefix('{locale}')->where(['locale' => '[a-zA-Z]{2}', 'ApiChecker','Debug'])->group(function () {
        Route::get('/tpv2/book/{id}', [TpvController2::class, 'book'])->name('tpv.book.es');
        Route::post('/tpv2/book/{id}/make', [TpvController2::class, 'create'])->name('tpv.create.es');

        Route::get('/thank-you', [TpvController2::class, 'success'])->name('process.success.es');
        Route::get('/cancel', [TpvController2::class, 'cancel'])->name('process.cancel.es');
        Route::get('/my-reservation-detail', [BookingsController::class, 'ReservationDetail'])->name('reservation.detail.es');
    });
});

Route::middleware(['guest','Debug'])->group(function () {
    Route::get('login', [LoginController::class, 'index']);
    Route::post('login', [LoginController::class, 'check'])->name('login');
});

        Route::get('/ejemplo-url', function () {
            echo "hola como estas";
        });

//Meter al middleware para protejer estas rutas...
Route::group(['middleware' => ['auth', 'Debug']], function () {
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/logout/other/{sessionId}', [LoginController::class, 'logoutOtherSession'])->name('logout.other');    
    Route::post('/logout/all', [LoginController::class, 'logoutAllSessions'])->name('logout.all');    

    //BOTS
        //SET RATES MASTER TOUR
        Route::get('/set/rates/MasterTour', [MasterToursController::class, 'ListServicesMasterTour'])->name('list.services.master.tours')->withoutMiddleware(['auth']);
        Route::match(['get', 'post'], '/set/schedules', [SCHEDULES::class, 'botSchedules'])->name('schedules.bot');
        Route::match(['get', 'post'], '/set/processSchedulesForToday', [SCHEDULES::class, 'processSchedulesForToday'])->name('schedules.processForToday');

        //PAYPAL
        Route::get('/bot/conciliation/paypal',                                                  [CONCILIATION::class, 'PayPalPayments'])->name('bot.paypal')->withoutMiddleware(['auth']);
        Route::get('/conciliation/paypalOrders',                                                [CONCILIATION::class, 'PayPalPaymenOrders'])->name('bot.paypal.orders')->withoutMiddleware(['auth']);
        Route::get('/conciliation/paypal/{reference}',                                          [CONCILIATION::class, 'PayPalPaymenReference'])->name('bot.paypal.reference')->withoutMiddleware(['auth']);
        Route::get('/conciliation/paypalOrder/{id}',                                            [CONCILIATION::class, 'PayPalPaymenOrder'])->name('bot.paypal.order')->withoutMiddleware(['auth']);
        //STRIPE
        Route::match(['get', 'post'],  '/bot/conciliation/stripe',                              [CONCILIATION::class, 'stripePayments'])->name('bot.stripe')->withoutMiddleware(['auth']);
        Route::match(['get', 'post'],  '/stripe/payouts',                                       [CONCILIATION::class, 'stripePayouts'])->name('stripe.payouts')->withoutMiddleware(['auth']);
        Route::match(['get', 'post'],  '/stripe/charges/{reference}',                           [CONCILIATION::class, 'stripeChargesReference'])->name('stripe.charges.reference')->withoutMiddleware(['auth']);
        Route::match(['get', 'post'],  '/stripe/payment_intents/{reference}',                   [CONCILIATION::class, 'stripePaymentIntentsReference'])->name('stripe.payment_intents.reference')->withoutMiddleware(['auth']);
        Route::match(['get', 'post'],  '/stripe/balance_transactions/{reference}',              [CONCILIATION::class, 'stripeBalanceTransactionsReference'])->name('stripe.balance_transactions.reference')->withoutMiddleware(['auth']);
        Route::match(['get', 'post'],  '/stripe/payouts/{reference}',                           [CONCILIATION::class, 'stripePayoutsReference'])->name('stripe.payouts.reference')->withoutMiddleware(['auth']);
        Route::match(['get', 'post'],  '/stripeInternal/payouts',                               [CONCILIATION::class, 'stripeInternalPayouts'])->name('stripe.internal.payouts')->withoutMiddleware(['auth']);

    //DASHBOARD
        Route::match(['get', 'post'], '/',                                                      [DashboardController::class, 'index'])->name('dashboard'); // GERENCIA
        Route::match(['get', 'post'], '/callcenters',                                           [CallCenterController::class, 'index'])->name('callcenters.index'); // AGENTES DE CALL CENTER
        Route::match(['post','get'], '/callcenters/sales/get',                                  [CallCenterController::class, 'getSales'])->name('callcenters.sales.get');
        Route::match(['post','get'], '/callcenters/operations/get',                             [CallCenterController::class, 'getOperations'])->name('callcenters.operations.get');
        Route::match(['post','get'], '/callcenters/stats/get',                                  [CallCenterController::class, 'getStats'])->name('callcenters.stats.get');
        Route::match(['post','get'], '/callcenters/stats/charts/sales',                         [CallCenterController::class, 'chartsSales'])->name('callcenters.charts.sales.get');
        Route::match(['post','get'], '/callcenters/stats/charts/opertions',                     [CallCenterController::class, 'chartsOperations'])->name('callcenters.charts.operations.get');
        Route::match(['post','get'], '/destinations/list',                                      [CallCenterController::class, 'destinationsList'])->name('destinations.list');

    //TPV        
        Route::get('/tpv/handler',                                                              [TPV::class, 'handler'])->name('tpv.handler');
        Route::get('/tpv/edit/{code}',                                                          [TPV::class, 'index'])->name('tpv.new');
        Route::POST('/tpv/quote',                                                               [TPV::class, 'quote'])->name('tpv.quote');
        Route::post('/tpv/create',                                                              [TPV::class, 'create'])->name('tpv.create');
        Route::get('/tpv/autocomplete/{keyword}',                                               [TPV::class, 'autocomplete'])->name('tpv.autocomplete');

    //FINANZAS         
        Route::match(['get', 'post'], '/finances/refunds',                                      [RefundsFinances::class, 'index'])->name('finances.refunds'); //REEMBOLSOS
        Route::match(['get', 'post'], '/finances/chargebacks',                                  [RefundsFinances::class, 'index'])->name('finances.chargebacks'); //CONTRAGARGOS
        Route::match(['get', 'post'], '/finances/receivables',                                  [RECEIVABLES::class, 'index'])->name('finances.receivables'); //CUENTAS POR COBRAR
        Route::match(['get', 'post'], '/finances/stripe',                                       [STRIPE::class, 'index'])->name('finances.stripe'); //CONCILIACION DE STRIPE

    //REPORTES
        Route::match(['get', 'post'], '/reports/payments', [PAYMENTS::class, 'index'])->name('reports.payments'); //PAGOS
        Route::match(['get', 'post'], '/reports/cash', [CASH::class, 'index'])->name('reports.cash'); //EFECTIVO
        Route::put('/reports/cash/update-status', [CASH::class, 'update'])->name('reports.cash.action.update'); //EFECTIVO
        ////////////
        Route::match(['get', 'post'], '/reports/cancellations', [CANCELLATIONS::class, 'index'])->name('reports.cancellations'); //CANCELACIONES
        Route::match(['get', 'post'], '/reports/commissions', [COMMISSIONS::class, 'index2'])->name('reports.commissions'); //COMISIONES
        Route::match(['get', 'post'], '/reports/commissions2', [COMMISSIONS::class, 'index'])->name('reports.commissions2'); //COMISIONES
        Route::match(['post','get'], '/reports/sales/data/commissions/get', [COMMISSIONS::class, 'getSales'])->name('reports.sales.data.commissions.get');
        Route::match(['post','get'], '/reports/operations/data/commissions/get', [COMMISSIONS::class, 'getOperations'])->name('reports.operations.data.commissions.get');
        Route::match(['post','get'], '/reports/commissions/data/commissions/get', [COMMISSIONS::class, 'getCommissions'])->name('reports.commissions.data.commissions.get');
        Route::match(['post','get'], '/reports/stats/commissions/get', [COMMISSIONS::class, 'getStats'])->name('reports.stats.get');
        Route::match(['post','get'], '/reports/sales/stats/charts/commissions', [COMMISSIONS::class, 'chartsSales'])->name('reports.sales.stats.charts.commissions.get');
        Route::match(['post','get'], '/reports/operations/stats/charts/commissions', [COMMISSIONS::class, 'chartsOperations'])->name('reports.operations.stats.charts.commissions.get');
        ////////////
        Route::match(['post','get'], '/reports/sales/cancun', [SALES::class, 'index'])->name('reports.sales.cancun'); //VENTAS
        Route::match(['post','get'], '/reports/sales/cabos', [SALES::class, 'index'])->name('reports.sales.cabos'); //VENTAS
        Route::match(['post', 'get'],'/reports/sales/punta-cana',[SALES::class, 'index'])->name('reports.sales.puntacana');  //VENTAS
        
        Route::match(['post','get'], '/reports/operations', [OPERATIONSS::class, 'index'])->name('reports.operations'); //OPERACIONES
        Route::match(['post','get'], '/reports/operations2', [DATAOPERATION::class, 'index'])->name('reports.operations2'); //OPERACIONES

    //GESTION        
        Route::match(['get', 'post'], '/management/confirmations', [ConfirmationsController::class, 'index'])->name('management.confirmations'); //CONFIRMACIONES
        ////////////
        Route::match(['get', 'post'], '/management/aftersales', [AfterSalesController::class, 'index'])->name('management.after.sales'); //POST VENTA, MAENEJO DE SPAM Y RESERVAS PENDIENTES

        Route::match(['get', 'post'], '/management/ccform',         [CCFORM::class, 'index'])->name('management.ccform');
        Route::match(['get'],         '/reports/ccform/pdf',        [CCFORM::class, 'createPDF'])->name('management.ccform.createPDF');

        Route::match(['post'], '/management/quotation/get', [QUOTATION::class, 'get'])->name('management.quotation.get'); // TRAE LAS COTIZACIONES DE AGENTE DE CALL CENTER
        Route::match(['post'], '/management/pending/get', [PENDING::class, 'get'])->name('management.pending.get'); // TRAE LAS RESERVAS PENDIENTES
        Route::match(['post'], '/management/spam/get', [SPAM::class, 'get'])->name('management.spam.get');
        Route::match(['post'], '/management/spam/get/basic-information', [SPAM::class, 'getBasicInformation'])->name('management.spam.get.basicInformation');
        Route::match(['post'], '/management/spam/history/get', [SPAM::class, 'getHistory'])->name('management.spam.get.history');
        Route::match(['post'], '/management/spam/history/add', [SPAM::class, 'addHistory'])->name('management.spam.add.history');
        ////////////
        Route::match(['get', 'post'], '/management/reservations', [RESERVATIONS::class, 'index'])->name('management.reservations'); //RESERVACIONES
        //OPERACIONES
        Route::get('/operation/board', [Operations::class, 'index'])->name('operation.index');
        Route::post('/operation/board', [Operations::class, 'index'])->name('operation.index.search');

        Route::match(['get', 'post'], '/operation/validateOperatingCosts', [Operations::class, 'validateOperatingCosts'])->name('validate.operating.cost');
        Route::post('/operation/vehicle/set', [Operations::class, 'setVehicle'])->name('operation.set.vehicle');

        Route::put('/operation/driver/set', [Operations::class, 'setDriver'])->name('operation.set.driver');    
        Route::put('/operation/status/operation', [Operations::class, 'updateStatusOperation'])->name('operation.status.operation');
        Route::post('/operation/comment/add', [Operations::class, 'addComment'])->name('operation.comment.add');
        Route::get('/operation/comment/get', [Operations::class, 'getComment'])->name('operation.comment.get');
        Route::get('/operation/history/get', [Operations::class, 'getHistory'])->name('operation.history.get');
        Route::get('/operation/data/customer/get', [Operations::class, 'getDataCustomer'])->name('operation.data.customer.get');
        Route::post('/operation/preassignments', [Operations::class, 'preassignments'])->name('operation.preassignments');

        Route::match(['get', 'post'], '/operation/closeOperation', [Operations::class, 'closeOperation'])->name('operation.close.operation');
        Route::match(['get', 'post'], '/operation/openOperation',  [Operations::class, 'openOperation'])->name('operation.open.operation');

        Route::put('/operation/preassignment', [Operations::class, 'preassignment'])->name('operation.preassignment');
        Route::post('/operation/capture/service', [Operations::class, 'createService'])->name('operation.capture.service');
        Route::get('/operation/board/exportExcel', [Operations::class, 'exportExcelBoard'])->name('operation.board.exportExcel');
        Route::get('/operation/board/exportExcelCommission', [Operations::class, 'exportExcelBoardCommision'])->name('operation.board.exportExcelComission');
        Route::match(['get', 'post'], '/management/operation/schedules/get', [Operations::class, 'getSchedules'])->name('management.operation.schedules.get');
        Route::match(['post'], '/management/operation/schedules/update', [Operations::class, 'updateSchedules'])->name('management.operation.schedules.update');

        //HOTELES
        Route::match(['get', 'post'], '/management/hotels', [HOTELS::class, 'index'])->name('management.hotels');
        Route::match(['post'], '/management/hotel/add', [HOTELS::class, 'hotelAdd'])->name('management.hotel.add');
        Route::delete('/management/hotels/{id}', [HOTELS::class, 'delete'])->name('management.hotel.delete');

    //CONFIGURACIONES
        Route::resource('/roles', ROLES::class);
        //USERS
        Route::resource('/users',                                                       USERS::class);
        Route::put('/ChangePass/{user}',                                                [USERS::class, 'change_pass'])->name('users.change_pass');
        Route::put('/ChangeStatus/{user}',                                              [USERS::class, 'change_status'])->name('users.change_status');
        Route::post('/StoreIP',                                                         [USERS::class, 'store_ips'])->name('users.store_ips');
        Route::delete('/DeleteIPs/{ip}',                                                [USERS::class, 'delete_ips'])->name('users.delete_ips');
        //EMPRESAS
        Route::resource('/enterprises',                                                 ENTERPRISES::class);        
        //SITIOS DE EMPRESA
        // Route::resource('/sites', SITES::class);
        Route::match(['GET', 'POST'], '/enterprises/sites/{enterprise}',                [SITES::class, 'index'])->name('enterprises.sites.index');
        Route::match(['GET'],         '/enterprises/sites/{enterprise}/create',         [SITES::class, 'create'])->name('enterprises.sites.create');
        Route::match(['POST'],        '/enterprises/sites/{enterprise}/store',          [SITES::class, 'store'])->name('enterprises.sites.store');
        Route::match(['GET'],         '/enterprises/sites/{enterprise}/edit',           [SITES::class, 'edit'])->name('enterprises.sites.edit');
        Route::match(['PUT'],         '/enterprises/sites/{enterprise}',                [SITES::class, 'update'])->name('enterprises.sites.update');
        Route::match(['DELETE'],      '/enterprises/sites/{enterprise}',                [SITES::class, 'destroy'])->name('enterprises.sites.destroy');

        Route::match(['POST'],        '/enterprises/upload',                            [ENTERPRISES::class, 'uploadMedia'])->name('enterprises.upload');
        Route::match(['GET'],         '/enterprises/upload/{id}',                       [ENTERPRISES::class, 'getMedia'])->name('enterprises.upload.getmedia');
        Route::match(['DELETE'],      '/enterprises/upload/{id}',                       [ENTERPRISES::class, 'deleteMedia'])->name('enterprises.upload.deleteMedia');        

        //ZONAS WEB
        Route::match(['GET', 'POST'], '/enterprises/zones/web/{enterprise}',            [ZONES_WEB::class, 'index'])->name('enterprises.zones.web.index');
        Route::match(['GET'],         '/enterprises/zones/web/{enterprise}/create',     [ZONES_WEB::class, 'create'])->name('enterprises.zones.web.create');
        Route::match(['POST'],        '/enterprises/zones/web/{enterprise}/store',      [ZONES_WEB::class, 'store'])->name('enterprises.zones.web.store');
        Route::match(['GET'],         '/enterprises/zones/web/{enterprise}/edit',       [ZONES_WEB::class, 'edit'])->name('enterprises.zones.web.edit');
        Route::match(['PUT'],         '/enterprises/zones/web/{enterprise}',            [ZONES_WEB::class, 'update'])->name('enterprises.zones.web.update');
        // Route::match(['DELETE'],      '/enterprises/zones/web/{enterprise}',         [ZONES_WEB::class, 'destroy'])->name('enterprises.zones.web.destroy');
        Route::match(['GET'],         '/enterprises/destinations/web/{id}/points',      [ZONES_WEB::class, 'getPoints'])->name('enterprises.destinations.web.getPoints');
        Route::match(['PUT'],         '/enterprises/destinations/web/{id}/points',      [ZONES_WEB::class, 'setPoints'])->name('enterprises.destinations.web.setPoints');    

        Route::match(['GET', 'POST'], '/enterprises/rates/web/{enterprise}',            [RATES_WEB::class, 'index'])->name('enterprises.rates.web.index');
        Route::match(['GET'],         '/enterprises/rates/web/{enterprise}/create',     [RATES_WEB::class, 'create'])->name('enterprises.rates.web.create');
        Route::match(['POST'],        '/enterprises/rates/web/{enterprise}/store',      [RATES_WEB::class, 'store'])->name('enterprises.rates.web.store');
        Route::match(['GET'],         '/enterprises/rates/web/{enterprise}/edit',       [RATES_WEB::class, 'edit'])->name('enterprises.rates.web.edit');
        Route::match(['PUT'],         '/enterprises/rates/web/{enterprise}',            [RATES_WEB::class, 'update'])->name('enterprises.rates.web.update');

        Route::match(['GET'],          '/config/rates/destination/{id}/get',            [RATES_WEB::class, 'items'])->name('config.ratesZones');
        Route::match(['POST'],         '/config/rates/get',                             [RATES_WEB::class, 'getRates'])->name('config.getRates');
        Route::match(['POST'],         '/config/rates/new',                             [RATES_WEB::class, 'newRates'])->name('config.newRates');
        Route::match(['DELETE'],       '/config/rates/delete',                          [RATES_WEB::class, 'deleteRates'])->name('config.deleteRates');
        Route::match(['PUT'],          '/config/rates/update',                          [RATES_WEB::class, 'updateRates'])->name('config.updateRates');        

        //ZONAS DE EMPRESA
        Route::match(['GET', 'POST'], '/enterprises/zones/{enterprise}',                [ZONES_ENTERPRISE::class, 'index'])->name('enterprises.zones.index');
        Route::match(['GET'],         '/enterprises/zones/{enterprise}/create',         [ZONES_ENTERPRISE::class, 'create'])->name('enterprises.zones.create');
        Route::match(['POST'],        '/enterprises/zones/{enterprise}/store',          [ZONES_ENTERPRISE::class, 'store'])->name('enterprises.zones.store');
        Route::match(['GET'],         '/enterprises/zones/{enterprise}/edit',           [ZONES_ENTERPRISE::class, 'edit'])->name('enterprises.zones.edit');
        Route::match(['PUT'],         '/enterprises/zones/{enterprise}',                [ZONES_ENTERPRISE::class, 'update'])->name('enterprises.zones.update');
        // Route::match(['DELETE'],      '/enterprises/zones/{enterprise}',             [ZONES_ENTERPRISE::class, 'destroy'])->name('enterprises.zones.destroy');
        Route::match(['GET'],         '/enterprises/destinations/{id}/points',          [ZONES_ENTERPRISE::class, 'getPoints'])->name('enterprises.destinations.getPoints');
        Route::match(['PUT'],         '/enterprises/destinations/{id}/points',          [ZONES_ENTERPRISE::class, 'setPoints'])->name('enterprises.destinations.setPoints');
        Route::match(['DELETE'],      '/enterprises/destinations/{id}/points',          [ZONES_ENTERPRISE::class, 'deletePoints'])->name('enterprises.destinations.deletePoints');

        //TARIFAS DE EMPRESA
        Route::match(['GET', 'POST'], '/enterprises/rates/{enterprise}',                [RATES_ENTERPRISE::class, 'index'])->name('enterprises.rates.index');
        Route::match(['GET'],         '/enterprises/rates/{enterprise}/create',         [RATES_ENTERPRISE::class, 'create'])->name('enterprises.rates.create');
        Route::match(['POST'],        '/enterprises/rates/{enterprise}/store',          [RATES_ENTERPRISE::class, 'store'])->name('enterprises.rates.store');
        Route::match(['GET'],         '/enterprises/rates/{enterprise}/edit',           [RATES_ENTERPRISE::class, 'edit'])->name('enterprises.rates.edit');
        Route::match(['PUT'],         '/enterprises/rates/{enterprise}',                [RATES_ENTERPRISE::class, 'update'])->name('enterprises.rates.update');

        Route::match(['GET'],         '/config/rates/enterprise/destination/{id}/get',  [RATES_ENTERPRISE::class, 'items'])->name('config.ratesEnterpriseZones');
        Route::match(['POST'],        '/config/rates/enterprise/get',                   [RATES_ENTERPRISE::class, 'getRatesEnterprise'])->name('config.getRatesEnterprise');
        Route::match(['POST'],        '/config/rates/enterprise/new',                   [RATES_ENTERPRISE::class, 'newRates'])->name('config.newRatesEnterprise');
        Route::match(['DELETE'],      '/config/rates/enterprise/delete',                [RATES_ENTERPRISE::class, 'deleteRates'])->name('config.deleteRatesEnterprise');
        Route::match(['PUT'],         '/config/rates/enterprise/update',                [RATES_ENTERPRISE::class, 'updateRates'])->name('config.updateRatesEnterprise');        
        //VEHICULOS
        Route::resource('/vehicles',                                                    VEHICLES::class);
        //CONDUCTORES
        Route::resource('/drivers',                                                     DRIVERS::class);

        //HORARIO DE CONDUCTORES
        Route::match(['get', 'post'], '/schedules',                                     [SCHEDULES::class, 'index'])->name('schedules.index');
        Route::get('/schedules/create',                                                 [SCHEDULES::class, 'create'])->name('schedules.create');
        Route::post('/schedules/store',                                                 [SCHEDULES::class, 'store'])->name('schedules.store');
        Route::get('/schedules/{schedule}/edit',                                        [SCHEDULES::class, 'edit'])->name('schedules.edit');
        Route::put('/schedules/{schedule}',                                             [SCHEDULES::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}',                                          [SCHEDULES::class, 'destroy'])->name('schedules.destroy');
        
        Route::match(['GET', 'POST'], '/schedules/update/schedules',                    [SCHEDULES::class, 'updateSchedules'])->name('schedules.update.driver');
        Route::match(['GET', 'POST'], '/schedules/reload/schedules',                    [SCHEDULES::class, 'reloadSchedules'])->name('schedules.reload');

        Route::post('/schedules/timeCheckIn',                                           [SCHEDULES::class, 'timeCheckIn'])->name('schedules.timeCheckIn');
        Route::post('/schedules/timeCheckout',                                          [SCHEDULES::class, 'timeCheckout'])->name('schedules.timecheckout');

        Route::post('/schedules/unit',                                                  [SCHEDULES::class, 'unit'])->name('schedules.unit');
        Route::post('/schedules/set/unit',                                              [SCHEDULES::class, 'setUnit'])->name('schedules.set.unit');
        Route::post('/schedules/driver',                                                [SCHEDULES::class, 'driver'])->name('schedules.driver');

        Route::post('/schedules/status/driver',                                         [SCHEDULES::class, 'statusDriver'])->name('schedules.status.driver');
        Route::post('/schedules/comments',                                              [SCHEDULES::class, 'comments'])->name('schedules.comments');
        Route::post('/schedules/status',                                                [SCHEDULES::class, 'status'])->name('schedules.status');

        //TIPO DE CAMBIO PARA REPORTES
        Route::get('/config/exchange-reports',                                          [EXCHANGE_REPORTS::class, 'index'])->name('exchanges.index');
        Route::get('/config/exchange-reports/create',                                   [EXCHANGE_REPORTS::class, 'create'])->name('exchanges.create');
        Route::post('/config/exchange-reports/store',                                   [EXCHANGE_REPORTS::class, 'store'])->name('exchanges.store');
        Route::get('/config/exchange-reports/{exchage}/edit',                           [EXCHANGE_REPORTS::class, 'edit'])->name('exchanges.edit');
        Route::put('/config/exchange-reports/{exchage}',                                [EXCHANGE_REPORTS::class, 'update'])->name('exchanges.update');
        Route::delete('/config/exchange-reports/{exchage}',                             [EXCHANGE_REPORTS::class, 'destroy'])->name('exchanges.destroy');

        //SCHEDULE RESTRICTIONS
        Route::get('/config/schedule-restrictions',               [SCHEDULE_RESTRICTIONS::class, 'index'])->name('config.schedule-restrictions.index');
        Route::get('/config/schedule-restrictions/create',        [SCHEDULE_RESTRICTIONS::class, 'create'])->name('config.schedule-restrictions.create');
        Route::post('/config/schedule-restrictions',              [SCHEDULE_RESTRICTIONS::class, 'store'])->name('config.schedule-restrictions.store');
        Route::get('/config/schedule-restrictions/{id}/edit',     [SCHEDULE_RESTRICTIONS::class, 'edit'])->name('config.schedule-restrictions.edit');
        Route::put('/config/schedule-restrictions/{id}',          [SCHEDULE_RESTRICTIONS::class, 'update'])->name('config.schedule-restrictions.update');
        Route::delete('/config/schedule-restrictions/{id}',       [SCHEDULE_RESTRICTIONS::class, 'destroy'])->name('config.schedule-restrictions.destroy');

        //TYPES CANCELLATIONS
        Route::get('/config/types-cancellations',                                       [TYPES_CANCELLATIONS::class, 'index'])->name('config.types-cancellations.index');
        Route::get('/config/types-cancellations/create',                                [TYPES_CANCELLATIONS::class, 'create'])->name('config.types-cancellations.create');
        Route::post('/config/types-cancellations',                                      [TYPES_CANCELLATIONS::class, 'store'])->name('config.types-cancellations.store');
        Route::get('/config/types-cancellations/{cancellation}/edit',                   [TYPES_CANCELLATIONS::class, 'edit'])->name('config.types-cancellations.edit');
        Route::put('/config/types-cancellations/{cancellation}',                        [TYPES_CANCELLATIONS::class, 'update'])->name('config.types-cancellations.update');
        Route::delete('/config/types-cancellations/{cancellation}',                     [TYPES_CANCELLATIONS::class, 'destroy'])->name('config.types-cancellations.destroy');

        //TYPES SALES
        Route::match(['get', 'post'], '/types-sales',                                   [TYPES_SALES::class, 'index'])->name('types.sales.index');
        Route::get('/types-sales/create',                                               [TYPES_SALES::class, 'create'])->name('types.sales.create');
        Route::post('/types-sales/store',                                               [TYPES_SALES::class, 'store'])->name('types.sales.store');
        Route::get('/types-sales/{sale}/edit',                                          [TYPES_SALES::class, 'edit'])->name('types.sales.edit');
        Route::put('/types-sales/{sale}',                                               [TYPES_SALES::class, 'update'])->name('types.sales.update');
        Route::delete('/types-sales/{sale}',                                            [TYPES_SALES::class, 'destroy'])->name('types.sales.destroy');

        Route::resource('operator-fees',                                                OPERATORFEE::class)->except(['show']);
        Route::match(['GET'],'operator-fees/{operator_fee}/history',                    [OPERATORFEE::class, 'show'])->name('operator-fees.show');

        Route::put('/reservations/{reservation}',                                       [DETAILS_RESERVATION::class, 'update'])->name('reservations.update');
        Route::delete('/reservations/{reservation}',                                    [DETAILS_RESERVATION::class, 'destroy'])->name('reservations.destroy');//LA CANCELACIÓNDE LA RESERVA
        Route::delete('/reservations/delete-reservation/{id}',                          [DETAILS_RESERVATION::class, 'deleteReservation'])->name('reservations.deletereservation');
        Route::post('/reservations/delete-reservations',                              [DETAILS_RESERVATION::class, 'deleteReservations'])->name('reservations.deletereservations');
        Route::get('/reservations/detail/{id}',                                         [DETAILS_RESERVATION::class, 'detail'])->name('reservations.details')->where('id', '[0-9]+');
        Route::get('/GetExchange/{reservation}',                                        [DETAILS_RESERVATION::class, 'get_exchange'])->name('reservations.get_exchange');
        Route::post('/reservationsfollowups',                                           [DETAILS_RESERVATION::class, 'followups'])->name('reservations.followups');
        Route::put('/editreservitem/{item}',                                            [DETAILS_RESERVATION::class, 'editreservitem'])->name('reservations.editreservitem');
        Route::post('/edit-reservation-item-comment',                                   [DETAILS_RESERVATION::class, 'editReservationItemComment'])->name('reservations.editReservationItemComment');

        Route::post('/reservations/confirmation/arrival',                               [DETAILS_RESERVATION::class, 'arrivalConfirmation'])->name('reservations.confirmationArrival');
        Route::post('/reservations/confirmation/departure',                             [DETAILS_RESERVATION::class, 'departureConfirmation'])->name('reservations.confirmationDeparture');
        
        Route::post('/reservations/payment-request',                                    [DETAILS_RESERVATION::class, 'paymentRequest'])->name('reservations.paymentRequest');
        Route::post('/reservations/{reservation}/send-provider-voucher',                 [DETAILS_RESERVATION::class, 'sendProviderVoucher'])->name('reservations.sendProviderVoucher');
        Route::post('/reservations/upload',                                             [DETAILS_RESERVATION::class, 'uploadMedia'])->name('reservations.upload');
        Route::post('/reservations/upload/reorder',                                     [DETAILS_RESERVATION::class, 'reorderMedia'])->name('reservations.upload.reorder');
        Route::get('/reservations/upload/{id}',                                         [DETAILS_RESERVATION::class, 'getMedia'])->name('reservations.upload.getmedia');
        Route::delete('/reservations/upload/{id}',                                      [DETAILS_RESERVATION::class, 'deleteMedia'])->name('reservations.upload.deleteMedia');
        Route::post('/reservations/create-payment-link',                                [DETAILS_RESERVATION::class, 'paymentLink'])->name('reservations.paymentLink');


    //ACCIONES GENRALES UTILIZADAS EN DETALLE DE RESERVACION
    Route::resource('/sales',SalesController::class);
    Route::resource('/payments',PaymentsController::class);

    //NOS TRAE DATOS GENERALES
    Route::match(['get'], '/data/typesCancellations',                               [DATA::class, 'typesCancellations'])->name('get.types.cancellations');

    //ACCIONES UTILIZADAS EN FINANZAS
        Route::post('/action/addPaymentRefund',                                     [FINANCE::class, 'addPaymentRefund'])->name('add.payment.refund');
        Route::post('/action/refundNotApplicable',                                  [FINANCE::class, 'refundNotApplicable'])->name('add.not.applicable.refund');
        Route::match(['get', 'post'], '/action/getInformationReservation',          [FINANCE::class, 'getInformationReservation'])->name('get.information.reservation')->withoutMiddleware(['auth']);
        Route::match(['get', 'post'], '/action/getBasicInformationReservation',     [FINANCE::class, 'getBasicInformationReservation'])->name('get.basic-information.reservation');
        Route::match(['get', 'post'], '/action/getPhotosReservation',               [FINANCE::class, 'getPhotosReservation'])->name('get.photos.reservation');
        Route::match(['get', 'post'], '/action/getHistoryReservation',              [FINANCE::class, 'getHistoryReservation'])->name('get.history.reservation');
        Route::match(['get', 'post'], '/action/getPaymentsReservation',             [FINANCE::class, 'getPaymentsReservation'])->name('get.payments.reservation');
    //SE UTILIZA EN LA CONCILIACION DE STRIPE
        Route::match(['get', 'post'], '/action/getChargesStripe',                   [FINANCE::class, 'getChargesStripe'])->name('get.charges.stripe');

    Route::post('/action/addCreditPayment',                                         [FINANCE::class, 'addCreditPayment'])->name('add.credit.payment');

    //ACCIONES UTILIZADAS EN REPORTES
    Route::post('/action/cashConciliation',                                         [FINANCE::class, 'cashConciliation'])->name('cash.payment.conciliation');

    //ACCIONES GENERALES DE DETALLES DE RESERVA
    Route::post('/action/deleteCommission',                                         [ACTIONS_RESERVATION::class, 'deleteCommission'])->name('update.booking.delete.commission');

    Route::post('/action/sendMessageWhatsApp',                                      [ACTIONS_RESERVATION::class, 'sendMessageWhatsApp'])->name('update.booking.send.message.whatsapp');
    Route::post('/action/enablePayArrival',                                         [ACTIONS_RESERVATION::class, 'enablePayArrival'])->name('update.booking.pay.arrival');
    Route::post('/action/enablePlusService',                                        [ACTIONS_RESERVATION::class, 'enablePlusService'])->name('update.booking.plus.service');
    Route::post('/action/markReservationOpenCredit',                                [ACTIONS_RESERVATION::class, 'markReservationOpenCredit'])->name('update.booking.mark.open.credit');
    Route::post('/action/reactivateReservation',                                    [ACTIONS_RESERVATION::class, 'reactivateReservation'])->name('update.booking.reactivate');
    Route::post('/action/refundRequest',                                            [ACTIONS_RESERVATION::class, 'refundRequest'])->name('update.booking.refund.request');
    Route::post('/action/markReservationDuplicate',                                 [ACTIONS_RESERVATION::class, 'markReservationDuplicate'])->name('update.booking.mark.duplicate');    

    Route::put('/action/updateServiceStatus',                                       [ACTIONS_RESERVATION::class, 'updateServiceStatus'])->name('update.service.status');
    Route::post('/action/enabledLike',                                              [ACTIONS_RESERVATION::class, 'enabledLike'])->name('update.booking.like');
    Route::post('/action/deleteItem',                                               [ACTIONS_RESERVATION::class, 'deleteItem'])->name('delete.booking.item');
    Route::post('/action/confirmService',                                           [ACTIONS_RESERVATION::class, 'confirmService'])->name('update.service.confirm');
    Route::post('/action/updateServiceUnlock',                                      [ACTIONS_RESERVATION::class, 'updateServiceUnlock'])->name('update.service.unlock');
});
