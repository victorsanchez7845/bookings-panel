    @php
        $links = []; //LINKS GENERALES
        $links_dashboard = [];
        $links_finances = [];
        $links_reports = [];
        $links_reportsSales_destination = [];
        $links_operations = [];
        $links_settings = [];

        //DASHBOARD
            // DASHBOARD GERENCIA
            if(auth()->user()->hasPermission(42)):
                $links_dashboard[] = [
                    'name' => 'Dashboard de Gerencia',
                    'route' => route('dashboard'),
                    'active' => request()->routeIs('dashboard'),
                ];
            endif;
            // DASHBOARD AGENTE DE CALL CENTER
            if(auth()->user()->hasPermission(113) && auth()->user()->is_commission == 1 ):
                $links_dashboard[] = [
                    'name' => 'Dashboard Agente Call Center',
                    'route' => route('callcenters.index'),
                    'active' => request()->routeIs('callcenters.index'),
                ];
            endif;
            array_push($links,[
                'type' => ( empty($links_dashboard) || count($links_dashboard) == 1 ? 'single' : 'multiple' ),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-activity"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
                'code' => 'dashbaoard',
                'name' => 'Dashboard',
                'route' => ( empty($links_dashboard) || count($links_dashboard) == 1 ? ( auth()->user()->is_commission == 1 ? route('callcenters.index') : route('dashboard') ) : route('dashboard') ),
                'active' => request()->routeIs('dashboard', 'callcenters.index'),
                'urls' => $links_dashboard
            ]);

        //TPV
        if(auth()->user()->hasPermission(26)):
            array_push($links,[
                'type' => 'single',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-inbox"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>',
                'code' => 'tpv',
                'name' => 'TPV',
                'route' => route('tpv.handler'),
                'active' => request()->routeIs('tpv.handler')
            ]);
        endif;

        // FINANZAS
        if(auth()->user()->hasPermission(114) || auth()->user()->hasPermission(119) || auth()->user()->hasPermission(121)):
            if(auth()->user()->hasPermission(114)):
                $links_finances[] = [
                    'name' => 'Reembolsos',
                    'route' => route('finances.refunds'),
                    'active' => request()->routeIs('finances.refunds'),
                ];
            endif;
            if(auth()->user()->hasPermission(119)):
                $links_finances[] = [
                    'name' => 'Cuentas por Cobrar',
                    'route' => route('finances.receivables'),
                    'active' => request()->routeIs('reports.receivables'),
                ];
            endif;
            if(auth()->user()->hasPermission(121)):
                $links_finances[] = [
                    'name' => 'Pagos Stripe',
                    'route' => route('finances.stripe'),
                    'active' => request()->routeIs('finances.stripe'),
                ];
            endif;            
            array_push($links,[
                'type' => 'multiple',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bar-chart"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>',
                'code' => 'finances',
                'name' => 'Finanzas',
                'route' => null,
                'active' => request()->routeIs('finances.*'),
                'urls' => $links_finances
            ]);
        endif;

        //REPORTES
        if(auth()->user()->hasPermission(43) || auth()->user()->hasPermission(45) || auth()->user()->hasPermission(50) || auth()->user()->hasPermission(71) || auth()->user()->hasPermission(98)):
            if(auth()->user()->hasPermission(43)):
                $links_reports[] = [
                    'name' => 'Pagos',
                    'route' => route('reports.payments'),
                    'active' => request()->routeIs('reports.payments'),
                ];
            endif;
            if(auth()->user()->hasPermission(50)):
                $links_reports[] = [
                    'name' => 'Efectivo',
                    'route' => route('reports.cash'),
                    'active' => request()->routeIs('reports.cash'),
                ];
            endif;
            if(auth()->user()->hasPermission(71)):
                $links_reports[] = [
                    'name' => 'Cancelaciones',
                    'route' => route('reports.cancellations'),
                    'active' => request()->routeIs('reports.cancellations'),
                ];
            endif;
            if(auth()->user()->hasPermission(45)):
                $links_reports[] = [
                    'name' => 'Comisiones',
                    'route' => route('reports.commissions'),
                    'active' => request()->routeIs('reports.commissions'),
                ];
            endif;

            if(auth()->user()->hasPermission(98)):
                /*
                |--------------------------------------------------------------------------
                | VENTAS TEMPORALMENTE SOLO PARA PUNTA CANA
                |--------------------------------------------------------------------------
                | Se ocultó el submenú de Cancún y Los Cabos. Mientras el panel opere
                | únicamente para Punta Cana, la opción Ventas dirige directamente al
                | reporte de Punta Cana.
                |
                | Para restaurar los destinos anteriores, volver a crear el submenú con
                | las rutas reports.sales.cancun y reports.sales.cabos.
                |--------------------------------------------------------------------------
                */
                $links_reports[] = [
                    'name' => 'Ventas',
                    'route' => route('reports.sales.puntacana'),
                    'active' => request()->routeIs('reports.sales.puntacana'),
                ];
            endif;

            /*
            |--------------------------------------------------------------------------
            | REPORTE DE OPERACIONES OCULTO TEMPORALMENTE
            |--------------------------------------------------------------------------
            | La opción Reportes > Operaciones está desactivada temporalmente.
            | Para volver a mostrarla, cambiar false por true en la condición inferior.
            |--------------------------------------------------------------------------
            */
            if(false && auth()->user()->hasPermission(97)):
                $links_reports[] = [
                    'name' => 'Operaciones',
                    'route' => route('reports.operations'),
                    'active' => request()->routeIs('reports.operations'),
                ];
            endif;

            array_push($links,[
                'type' => 'multiple',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bar-chart"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>',
                'code' => 'reports',
                'name' => 'Reportes',
                'route' => null,
                'active' => request()->routeIs('reports.*'),
                'urls' => $links_reports
            ]);
        endif;

        //GESTION
        if( 
            auth()->user()->hasPermission(39) || // CONFIRMACIONES
            auth()->user()->hasPermission(47) || // POST VENTA
            auth()->user()->hasPermission(126) || // CCFORM
            auth()->user()->hasPermission(10) || // RESERVACIONES
            auth()->user()->hasPermission(76) || // OPERACIÓN
            auth()->user()->hasPermission(123) // HOTELES
        ):
            // CONFIRMACIONES
            if(auth()->user()->hasPermission(39)):
                $links_operations[] = [
                    'name' => 'Confirmaciones',
                    'route' => route('management.confirmations'),
                    'active' => request()->routeIs('management.confirmations'),
                ];
            endif;
            // POST VENTA
            if(auth()->user()->hasPermission(47)):
                $links_operations[] = [
                    'name' => 'Post venta',
                    'route' => route('management.after.sales'),
                    'active' => request()->routeIs('management.after.sales'),
                ];
            endif;
            // CCFORM
            if(auth()->user()->hasPermission(126)):
                $links_operations[] = [
                    'name' => 'CCForm',
                    'route' => route('management.ccform'),
                    'active' => request()->routeIs('management.ccform'),
                ];
            endif;            
            // RESERVACIONES
            if(auth()->user()->hasPermission(10)):
                $links_operations[] = [
                    'name' => 'Reservaciones',
                    'route' => route('management.reservations'),
                    'active' => request()->routeIs('management.reservations'),
                ];
            endif;
            // OPERACIÓN
            if(auth()->user()->hasPermission(76)):
                $links_operations[] = [
                    'name' => 'Operaciones',
                    'route' => route('operation.index'),
                    'active' => request()->routeIs('operation.index','operation.index.search'),
                ];
            endif;
            // HOTELES
            if(auth()->user()->hasPermission(123)):
                $links_operations[] = [
                    'name' => 'Hoteles',
                    'route' => route('management.hotels'),
                    'active' => request()->routeIs('management.hotels'),
                ];
            endif;
            array_push($links,[
                'type' => 'multiple',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-layers"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
                'code' => 'operations',
                'name' => 'Gestion',
                'route' => null,
                'active' => request()->routeIs('management.*','operation.*'),
                'urls' => $links_operations
            ]);
        endif;        

        //CONFIGURACIONES
        // auth()->user()->hasPermission(102)
        // auth()->user()->hasPermission(104)
        // auth()->user()->hasPermission(28)
        // auth()->user()->hasPermission(32)
        if(auth()->user()->hasPermission(6) || auth()->user()->hasPermission(1) || auth()->user()->hasPermission(73) || auth()->user()->hasPermission(74) || auth()->user()->hasPermission(75) || auth()->user()->hasPermission(103) || auth()->user()->hasPermission(108) || auth()->user()->hasPermission(115) || auth()->user()->hasPermission(129)):
            //ROLES
            if(auth()->user()->hasPermission(6)):
                $links_settings[] = [
                    'name' => 'Roles',
                    'route' => route('roles.index'),
                    'active' => request()->routeIs('roles.*'),
                ];
            endif;
            //USUARIOS
            if(auth()->user()->hasPermission(1)):
                $links_settings[] = [
                    'name' => 'Usuarios',
                    'route' => route('users.index'),
                    'active' => request()->routeIs('users.*'),
                ];
            endif;
            //EMPRESAS
            if(auth()->user()->hasPermission(73)):
                $links_settings[] = [
                    'name' => 'Empresas',
                    'route' => route('enterprises.index'),
                    'active' => request()->routeIs('enterprises.*'),
                ];
            endif;
            //SITIOS
            // if(auth()->user()->hasPermission(102)):
            //     $links_settings[] = [
            //         'name' => 'Sitios',
            //         'route' => route('sites.index'),
            //         'active' => request()->routeIs('sites.*'),
            //     ];
            // endif;
            //VEHÍCULOS
            if(auth()->user()->hasPermission(74)):
                $links_settings[] = [
                    'name' => 'Vehiculos',
                    'route' => route('vehicles.index'),
                    'active' => request()->routeIs('vehicles.*'),
                ];
            endif;
            //CONDUCTORES
            if(auth()->user()->hasPermission(75)):
                $links_settings[] = [
                    'name' => 'Conductores',
                    'route' => route('drivers.index'),
                    'active' => request()->routeIs('drivers.*'),
                ];
            endif;
            // if(auth()->user()->hasPermission(75)):
                $links_settings[] = [
                    'name' => 'Horarios',
                    'route' => route('schedules.index'),
                    'active' => request()->routeIs('schedules.*'),
                ];
            // endif;
            //ZONAS
            // if(auth()->user()->hasPermission(28)):
            //     $links_settings[] = [
            //         'name' => 'Zonas',
            //         'route' => route('config.zones'),
            //         'active' => request()->routeIs('config.zones','config.zones.getZones'),
            //     ];
            // endif;
            //TARIFAS
            // if(auth()->user()->hasPermission(32)):
            //     $links_settings[] = [
            //         'name' => 'Tarifas de paginas web',
            //         'route' => route('config.ratesDestination'),
            //         'active' => request()->routeIs('config.ratesDestination','config.ratesZones'),
            //     ];
            // endif;
            //TARIFAS PARA EMPRESAS
            // if(auth()->user()->hasPermission(104)):
            //     $links_settings[] = [
            //         'name' => 'Tarifas de empresas',
            //         'route' => route('config.ratesEnterprise'),
            //         'active' => request()->routeIs('config.ratesEnterprise'),
            //     ];
            // endif;
            //TIPO DE CAMBIO PARA REPORTES
            if(auth()->user()->hasPermission(103)):
                $links_settings[] = [
                    'name' => 'Tipos de cambio reportes',
                    'route' => route('exchanges.index'),
                    'active' => request()->routeIs('exchanges.*'),
                ];
            endif;            
            //TIPOS DE CANCELACIONES
            if(auth()->user()->hasPermission(108)):
                $links_settings[] = [
                    'name' => 'Tipos de cancelaciónes',
                    'route' => route('config.types-cancellations.index'),
                    'active' => request()->routeIs('config.types-cancellations.*'),
                ];
            endif;
            if(auth()->user()->hasPermission(115)):
                $links_settings[] = [
                    'name' => 'Tipos de ventas',
                    'route' => route('types.sales.index'),
                    'active' => request()->routeIs('types.sales.*'),
                ];
            endif;
            if(auth()->user()->hasPermission(129)):
                $links_settings[] = [
                    'name' => 'Costo operativo',
                    'route' => route('operator-fees.index'),
                    'active' => request()->routeIs('operator-fees.*'),
                ];
            endif;
            if(auth()->user()->hasPermission(135)):
                $links_settings[] = [
                    'name'   => 'Restricciones de horarios',
                    'route'  => route('config.schedule-restrictions.index'),
                    'active' => request()->routeIs('config.schedule-restrictions.*'),
                ];
            endif;
            array_push($links,[
                'type' => 'multiple',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
                'code' => 'settings',
                'name' => 'Configuraciones',
                'route' => null,
                'active' => request()->routeIs('users.*','roles.*','enterprises.*','sites.*','vehicles.*','drivers.*','schedules.*','exchanges.*','config.zones','config.zones.getZones','config.ratesDestination','config.ratesZones','config.types-cancellations.*','types.sales.*','config.schedule-restrictions.*'),
                'urls' => $links_settings
            ]);
        endif;
    @endphp
    <!--  BEGIN SIDEBAR  -->
    <div class="sidebar-wrapper sidebar-theme">
        <nav id="sidebar">
            <div class="navbar-nav theme-brand flex-row  text-center">
                <div class="nav-logo">
                    <div class="nav-item theme-logo">
                        <a href="/">
                            <img src="{{ asset('/assets/img/logos/brand.svg') }}" class="navbar-logo" alt="logo">
                        </a>
                    </div>
                    <div class="nav-item theme-text">
                        <a href="/" class="nav-link"> AFILIADOS </a>
                    </div>
                </div>
                <div class="nav-item sidebar-toggle">
                    <div class="btn-toggle sidebarCollapse">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevrons-left"><polyline points="11 17 6 12 11 7"></polyline><polyline points="18 17 13 12 18 7"></polyline></svg>
                    </div>
                </div>
            </div>
            <ul class="list-unstyled menu-categories" id="accordionExample">
                @foreach ($links as $link)
                    @if ( $link['type'] == 'single' )
                        <li class="menu <?=( isset($link['active']) && $link['active'] ? 'active' : '' )?>">
                            <a href="{{ $link['route'] }}" aria-expanded="<?=( isset($link['active']) && $link['active'] ? 'true' : 'false' )?>" class="dropdown-toggle">
                                <div class="">
                                    <?=strval($link['icon'])?>
                                    <span>{{ $link['name'] }}</span>
                                </div>
                            </a>
                        </li>  
                    @else
                        <li class="menu <?=( $link['active'] ? 'active' : '' )?>">
                            <a href="#{{ $link['code'] }}" data-bs-toggle="collapse" aria-expanded="<?=( $link['active'] ? 'true' : 'false' )?>" class="dropdown-toggle">
                                <div class="">
                                    <?=strval($link['icon'])?>
                                    <span>{{ $link['name'] }}</span>
                                </div>
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </div>
                            </a>
                            <ul class="collapse submenu list-unstyled <?=( $link['active'] ? 'show' : '' )?>" id="{{ $link['code'] }}" data-bs-parent="#accordionExample">
                                @if ( isset($link['urls']) )
                                    @foreach ($link['urls'] as $url)
                                        @if ( isset($url['type']) && $url['type'] == "multiple" )
                                            <li class="<?=( $url['active'] ? 'active' : '' )?>">
                                                <a href="#{{ $url['code'] }}" data-bs-toggle="collapse" aria-expanded="<?=( $url['active'] ? 'true' : 'false' )?>" class="dropdown-toggle collapsed">
                                                    {{ $url['name'] }}
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                                </a>
                                                <ul class="list-unstyled sub-submenu collapse <?=( $url['active'] ? 'show' : '' )?>" id="{{ $url['code'] }}" data-bs-parent="#{{ $url['code'] }}">
                                                    @foreach ($url['urls'] as $urls)
                                                        <li class="<?=( $urls['active'] ? 'active' : '' )?>">
                                                            <a href="{{ $urls['route'] }}"> {{ $urls['name'] }} </a>
                                                        </li>                                                
                                                    @endforeach
                                                </ul>
                                            </li>
                                        @else
                                            <li class="<?=( $url['active'] ? 'active' : '' )?>">
                                                <a href="{{ $url['route'] }}"> {{ $url['name'] }} </a>
                                            </li>
                                        @endif
                                    @endforeach
                                @endif
                            </ul>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>
    </div>
    <!--  END SIDEBAR  -->
