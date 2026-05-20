//SETUP 
let setup = {
    lang: 'es',
    currency: 'USD',
    deeplink: '/resultados',
    serviceType: 'OW',
    pax: 1,
    items: {
        from: {
            name: '',
            latitude: '',
            longitude: '',
            pickupDate: '',
            pickupTime: '00:00',
        },
        to: {
            name: '',
            latitude: '',
            longitude: '',
            pickupDate: '',
            pickupTime: '00:00',
        },
    },
    setLang: function(lang){
      setup.lang = lang;    
    },
    loadingMessage: function(item){
  
      const loader = document.getElementById(item);
      loader.innerHTML = '';
      
      const div = document.createElement('div');
      div.classList.add("loader");
      const image = document.createElement('img');
      image.width = 35;
      image.height = 35;
      image.src = '/assets/img/loader.gif';
      
      div.appendChild(image);
      loader.appendChild(div);
  
    },
    autocomplete: function(keyword, element){
      let size = keyword.length;
        if(size < 3) return false;
        setup.loadingMessage(element);
  
        fetch(`/tpv/autocomplete/${keyword}`, {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json'
            }
        }).then((response) => {
            return response.json()
        }).then((data) => {
            this.makeItems(data,element);
        }).catch((error) => {
            console.error('Error:', error);
        });
    },
    makeItems: function(data, element){
  
      const finalElement = document.getElementById(element);
            finalElement.innerHTML = '';
  
      for (let key in data) {
        if (data.hasOwnProperty(key)) {
  
          if(data[key].type === "DEFAULT"){
  
              const itemDiv = document.createElement('div');
                    itemDiv.textContent = data[key].name;
                    itemDiv.className = 'default';
  
              const span = document.createElement('span');
                    span.textContent = data[key].address;
  
                    itemDiv.appendChild(span);
                    itemDiv.addEventListener('click', function() { 
                      setup.setItem(element, data[key]);
                    });
  
              finalElement.appendChild(itemDiv);
          }
  
          if(data[key].type === "GCP"){
              const itemNameDiv = document.createElement('div');      
                    itemNameDiv.className = 'GCP';            
  
              const itemInformation = document.createElement('div');
                    itemInformation.textContent = data[key].name;
  
                    const span = document.createElement('span');
                      span.textContent = data[key].address;
  
  
                    itemInformation.appendChild(span);
                    itemInformation.addEventListener('click', function() { 
                      setup.setItem(element, data[key]);
                    });
  
                  const itemButton = document.createElement('button');
                        itemButton.textContent = 'ADD';
                        itemButton.type = 'button';
                        itemButton.addEventListener('click', function() {                         
                          setup.saveHotel(element, data[key]);
                        });
                              
                  itemNameDiv.appendChild(itemInformation);
                  itemNameDiv.appendChild(itemButton);
  
                  finalElement.appendChild(itemNameDiv);
  
          }
  
  
        }
      }
    },
    setItem(element, data = {}){
      const finalElement = document.getElementById(element);
      finalElement.innerHTML = '';
  
      if(element === "from_name_elements"){
          const initInput = document.getElementById('from_name');
          initInput.value = data.name;
          setup.items.from.name = data.name;
          setup.items.from.latitude = data.geo.lat;
          setup.items.from.longitude = data.geo.lng;
          
          var fromLat = document.getElementsByName("from_lat");
              fromLat[0].value = data.geo.lat;
          var fromLng = document.getElementsByName("from_lng");
              fromLng[0].value = data.geo.lng;
  
      }
  
      if(element === "to_name_elements"){
          const initInput = document.getElementById('to_name');
          initInput.value = data.name;
          setup.items.to.name = data.name;
          setup.items.to.latitude = data.geo.lat;
          setup.items.to.longitude = data.geo.lng;
          
          var toLat = document.getElementsByName("to_lat");
              toLat[0].value = data.geo.lat;
  
          var toLng = document.getElementsByName("to_lng");
              toLng[0].value = data.geo.lng;
      }
    },
    saveHotel: function(element, data){
        let item = {
          name: data.name,
          address: data.address,
          start: {
            lat: data.geo.lat,
            lng: data.geo.lng,
          },
        };  
        $.ajax({
          url: 'https://api.caribbean-transfers.com/api/v1/hotels/add',
          type: 'POST',
          data: item,
          beforeSend: function() {
            setup.loadingMessage(element);
          },
          success: function(resp) {
            setup.setItem(element, data);          
            alert("Hotel agregado con éxito...");
            const finalElement = document.getElementById(element);
            finalElement.innerHTML = '';          
          },
      }).fail(function(xhr, status, error) {
        console.log(error);
      });
    },
    /**
     * ===== Render Table Settings ===== *
     * @param {*} table //tabla a renderizar
    */
    actionTable: function(table, param = ""){
        let buttons = [];
        const _settings = {},
            _buttons = table.data('button');

        if( _buttons != undefined && _buttons.length > 0 ){
            _buttons.forEach(_btn => {
                if( _btn.hasOwnProperty('url') ){
                    _btn.action = function(e, dt, node, config){
                        window.location.href = _btn.url;
                    }
                };
                buttons.push(_btn);
            });
        }

        _settings.dom = `<'dt--top-section'<''<'left'l<'dt--pages-count align-self-center'i><'dt-action-buttons align-self-center'B>><'right'f>>>
                         <''tr>
                         <'dt--bottom-section d-sm-flex justify-content-sm-between text-center'<'dt--pagination'p>>`;
        _settings.deferRender = true;
        _settings.responsive = false;
        _settings.buttons =  _buttons;
        _settings.order = [];
        _settings.paging = false;
        _settings.oLanguage = {
            "sProcessing": "Procesando...",
            "sZeroRecords": "No se encontraron resultados",             
            "sInfo": 'Mostrando <strong style="font-size: 20px;">_TOTAL_</strong> registros',
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sSearch": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
            "sSearchPlaceholder": components.getTranslation("table.search") + "...",
            "sLengthMenu": components.getTranslation("table.results") + " :  _MENU_",
            "oPaginate": { 
                "sPrevious": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>', 
                "sNext": '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>' 
            },
        };

        table.DataTable( _settings );
    },
    bsTooltip: function() {
        var bsTooltip = document.querySelectorAll('.bs-tooltip')
        for (let index = 0; index < bsTooltip.length; index++) {
            var tooltip = new bootstrap.Tooltip(bsTooltip[index])
        }
    },
    setStatus: function(_status){
        let alert_type = 'btn-secondary';
        switch (_status) {
            case 'PENDING':
                alert_type = 'btn-secondary';
                break;
            case 'COMPLETED':
            case 'OK':
                alert_type = 'btn-success';
                break;
            case 'NOSHOW':
            case 'C':
                alert_type = 'btn-warning';
                break;
            case 'CANCELLED':
                alert_type = 'btn-danger';
                break;
            case 'E':
                alert_type = 'btn-info';
                break;                        
            default:
                alert_type = 'btn-secondary';
                break;
        }
        return alert_type;
    },
    setPreassignment: function(_operation){
        let alert_type = 'btn-success';
        switch (_operation) {
            case 'ARRIVAL':
                alert_type = 'btn-success';
                break;
            case 'DEPARTURE':
                alert_type = 'btn-primary';
                break;
            case 'TRANSFER':
                alert_type = 'btn-info';
                break;
            default:
                alert_type = 'btn-success';
                break;
        }
        return alert_type;                
    },
    isTime: function(hora) {
        // Expresión regular para validar formato HH:MM
        const regex = /^([01]\d|2[0-3]):([0-5]\d)$/;
        return regex.test(hora);
    },
    obtenerHoraActual: function() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        return `${horas}:${minutos}`;
    },
    calendarFilter: function(selector, options = {}){
        const defaultConfig = {
            mode: "single",
            locale: "es", // Idioma dinámico
            enableTime: false, // Activamos o Desactivamos la selección de hora
            // noCalendar: true, // Ocultamos la selección de fecha
            dateFormat: "Y-m-d", // Formato por defecto
            altInput: true, // Input visual más amigable
            altFormat: "j F Y", // Formato más legible
            allowInput: true,
            // altFormat: "h:i K", // Formato 12h con AM/PM
            defaultDate: "today",
            minDate: "today",
            plugins: [] // Aseguramos que sea un array
        };
    
        let config = { ...defaultConfig, ...options };
    
        const fp = flatpickr(selector, config);
        return fp;
    },    
    fetchData: async function(url) {
        try {
            const _params    = {
                date: document.getElementById('lookup_date').value,
            };

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(_params),
            });
    
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
    
            return await response.text();
        } catch (error) {
            console.error("Error en fetchData:", error);
            return { data: { daily_goal: "N/A", total_day: "N/A", percentage_daily_goal: "N/A", total_month: "N/A", total_services_operated: "N/A", total_pending_services: "N/A" } }; // Devolver valores por defecto
        }
    },
    getLoader: function() {
        return '<span class="container-loader"><i class="fa-solid fa-spinner fa-spin-pulse"></i></span>';
    },
    reloadAll: async function(){
       // Elementos HTML
        const elements = {
            container: document.getElementById("data"),
        };

        // Validar que los elementos existen antes de usarlos
        if (!elements.container) {
            console.error("Error: No se encontraron los elementos del DOM necesarios.");
            return;
        }

        // Actualizar resumen general, al mostrar el loader ANTES de la solicitud
        Object.values(elements).forEach(el => el.innerHTML = setup.getLoader().trim());

        try {
            const data = await setup.fetchData('/management/operation/schedules/get');
            
            // Validar que los elementos existen antes de modificar el contenido
            await Promise.all([
                elements.container.innerHTML = data.trim(),
            ]);

            const __end_check_outs = document.querySelectorAll('.end_check_out_time');
            // console.log(__end_check_outs);
            // if( __end_check_outs.length > 0 ){
            //     __end_check_outs.forEach(__end_check_out => {
            //         if( __end_check_out ){
            //             setup.calendarFilter(__end_check_out, { enableTime: true, noCalendar: true, dateFormat: "H:i", altFormat: "h:i K", defaultDate: __end_check_out.value ?? '', minDate: null });
            //         }            
            //     });
            // }            

            $('.selectpicker').selectpicker({
                liveSearch: true
            });
        } catch (error) {
            console.error("Error al obtener datos:", error);
            Object.values(elements).forEach(el => el.innerHTML = '<p style="color:red;">Error al cargar datos.</p>');
        }
    },
    fetchExacute: function(_params){
        Swal.fire({
            title: "Procesando solicitud...",
            text: "Por favor, espera mientras se realizan los cambios.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('/management/operation/schedules/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },            
            body: JSON.stringify(_params),
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            if( data.hasOwnProperty('status') && data.status == "success" ){
                this.reloadAll();
            }else{
                Swal.fire({
                    icon: data.status,
                    html: data.message,
                    allowOutsideClick: false,
                }).then(() => {
                    Swal.close(); // Cierra el Swal al recibir respuesta
                });                
            }         
        })
        .catch(error => {
            Swal.fire(
                '¡ERROR!',
                error.message || 'Ocurrió un error',
                'error'
            );
        });
    },
    actionSite: function(__site){
        const __reference       = document.getElementById('formReference');
        const __name            = document.getElementById('formName');
        const __lastname        = document.getElementById('formLastName');
        const __email           = document.getElementById('formEmail');
        const __phone           = document.getElementById('formPhone');
        const __originSale      = document.getElementById('formOriginSale');
        const selectedOption    = __site.options[__site.selectedIndex];        

        if( selectedOption.getAttribute('data-type') == "AGENCY" || selectedOption.getAttribute('data-type') == "STAFF" ){
            if( selectedOption.getAttribute('data-type') == "STAFF" ){
                __name.value          = 'STAFF';
                __lastname.value      = 'CT';
            }

            __email.value           = selectedOption.getAttribute('data-email');
            __phone.value           = selectedOption.getAttribute('data-phone');
            $("#formOriginSale").selectpicker('val', ( selectedOption.getAttribute('data-type') == "AGENCY" ? '5' : '13' ));

            if( selectedOption.getAttribute('data-type') == "AGENCY" ){
                console.log("entre");
                __reference.removeAttribute('readonly');
            }

            if( selectedOption.getAttribute('data-type') == "STAFF" ){
                console.log("entre staff");
                __reference.value     = "DIRECTO";
            }

            if( selectedOption.getAttribute('data-type') == "STAFF" ){
                __name.setAttribute('readonly', true);
                __lastname.setAttribute('readonly', true);
            }
            __email.setAttribute('readonly', true);
            __phone.setAttribute('readonly', true);
        }else{
            __name.value            = '';
            __lastname.value        = '';
            __email.value           = '';
            __phone.value           = '';
            __reference.value       = '';
            $("#formOriginSale").selectpicker('val', '');

            __reference.setAttribute('readonly', true);
            __name.removeAttribute('readonly');
            __lastname.removeAttribute('readonly');
            __email.removeAttribute('readonly');
            __phone.removeAttribute('readonly');
        }
    },
    /**
     * 
     * @param {*} params 
     * @returns 
     */
    updateServiceStatus: async function (params) {
        try {
            const response = await fetch(_LOCAL_URL + "/action/updateServiceStatus", {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(params)
            });

            if (!response.ok) throw new Error("Error en la petición");

            const result = await response.json();
            if (result.status !== "success") {
                throw new Error(result.message || "No se pudo actualizar el estatus");
            }

            return true; // Si todo bien, continúa
        } catch (error) {
            Swal.fire("Error", error.message || "Ocurrió un error", "error");
            return false;
        }
    },
    /**
     * 
     * @param {*} files 
     * @param {*} id 
     * @param {*} status 
     * @returns 
     */
    uploadImages: async function (files, id, status = "CANCELLATION") {
        const uploadedImages = [];
        for (const file of files) {
            // Obtener el token CSRF desde el meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const formData = new FormData();
            formData.append("folder", id);
            formData.append("type_media", ( status == "CANCELLED" ? "CANCELLATION" : status ));
            formData.append("file", file);

            try {
                const response = await fetch(_LOCAL_URL + "/reservations/upload", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': csrfToken  // Incluir el token en los headers
                    },                
                    body: formData
                });

                // console.log(response);
                if (response.ok) {
                    const data = await response.json();
                    uploadedImages.push(data.imageUrl); // Suponiendo que el servidor responde con `{ "imageUrl": "URL de la imagen" }`
                } else {
                    throw new Error("Error al subir una imagen.");
                }
            } catch (error) {
                console.error("Error subiendo imagen:", error);
                return [];
            }
        }
        return uploadedImages;
    }
};

const reloadWithLastWindowPosition = () => {
    localStorage.setItem("scrollPos", window.scrollY);
    window.location.reload();
}

const scrollToLastWindowPosition = () => {
    const scroll = localStorage.getItem("scrollPos");
    if (scroll !== null) {
        window.scrollTo(0, parseInt(scroll));
        localStorage.removeItem("scrollPos");
    }
}

document.addEventListener("DOMContentLoaded", scrollToLastWindowPosition);

if( document.querySelector('.table-rendering') != null ){
    setup.actionTable($('.table-rendering'));
}
components.setValueSelectpicker();
// components.formReset();//RESETEA LOS VALORES DE UN FORMULARIO, EN UN MODAL

//CONFIGURACION DE DROPZONE
Dropzone.options.uploadForm = {
    maxFilesize: 5, // Tamaño máximo del archivo en MB
    acceptedFiles: 'image/*,.pdf', // Solo permitir imágenes y archivos PDF
    dictDefaultMessage: 'Arrastra el archivo aquí o haz clic para subirlo (Imágenes/PDF)...',
    addRemoveLinks: false,
    autoProcessQueue: false,
    uploadMultiple: false,
    init: function() {
        const dropzone = this;
        let selectedOption = "OPERATION"; // Valor por defecto

        // Interceptar el evento "addedfile"        
        this.on("addedfile", function(file) {
            // Mostrar mensaje de procesando
            Swal.fire({
                title: "Subiendo imágen...",
                text: "Por favor, espera mientras se carga la imágen.", //Realiza la function de HTML en el Swal
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Procesar el archivo directamente con el valor por defecto
            dropzone.processFile(file);
            console.log('Opción seleccionada:', selectedOption);
        });

        // Añadir el valor seleccionado a los datos enviados
        this.on("sending", function(file, xhr, formData) {
            if (selectedOption) {
                formData.append("type_media", selectedOption); // Agregar la opción seleccionada
            }
        });

        this.on("success", function(file, response) {
            // Limpiar el área de Dropzone
            this.removeAllFiles(true); // 'true' evita que se activen eventos adicionales
            if (response.hasOwnProperty('success') && response.success) {
                Swal.fire({
                    icon: 'success',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 1500,
                    willClose: () => {
                        socket.emit("uploadBookingServer", response.data);
                    }
                })
            }
        });
        this.on("error", function(file, errorMessage) {
            this.removeAllFiles(true); // 'true' evita que se activen eventos adicionales, y elimina el archivo
            components.proccessResponse(errorMessage);
        });
    }
};

//DECLARACION DE VARIABLES
let types_cancellations = {};
const from_autocomplete = document.getElementById('from_name'); //* ===== INPUT AUTOCOMPLETE ORIGIN ===== */
const to_autocomplete = document.getElementById('to_name'); //* ===== INPUT AUTOCOMPLETE DESTINATION ===== */
const sold_in_currency_select = document.getElementById('sold_in_currency'); //* ===== SELECT CURRENCY ===== */
const currency_span = document.getElementById('currency_span'); //* ===== LABEL CURRENCY QUOTATION ===== */
const form = document.getElementById('posForm'); //* ===== FORM CREATE SERVICE ===== */
const submitBtn = document.getElementById('submitBtn'); //* ===== BUTTON CREATE SERVICE ===== */

const __add_preassignments = document.querySelectorAll('.add_preassignment'); //* ===== BUTTONS PRE ASSIGNMENT ===== */
const __vehicles = document.querySelectorAll('.vehicles'); //* ===== SELECT VEHICLES ===== */
const __drivers = document.querySelectorAll('.drivers'); //* ===== SELECT DRIVERS ===== */

const __open_modal_customers = document.querySelectorAll('.__open_modal_customer');
const __open_modal_comments = document.querySelectorAll('.__open_modal_comment');
const __title_modal = document.getElementById('filterModalLabel');
const __button_form = document.getElementById('formComment'); //* ===== BUTTON FORM ===== */
const __btn_preassignment = document.getElementById('btn_preassignment') //* ===== BUTTON PRE ASSIGNMENT GENERAL ===== */
// const __btn_addservice = document.getElementById('btn_addservice') //* ===== BUTTON PRE ASSIGNMENT GENERAL ===== */

const __btn_update_status_operations = document.querySelectorAll('.btn_update_status_operation');
const serviceStatusUpdates = document.querySelectorAll('.serviceStatusUpdate'); //* ===== BUTTON SERVICE STATUS UPDATE ===== */

const __copy_whatsapp = document.querySelector('.copy_whatsapp'); //* ===== BUTTON TO COPY THE CONTENT THAT WILL BE SENT BY WHATSAPP ===== */
const __copy_history = document.querySelector('.copy_history');
const __copy_data_customer = document.querySelector('.copy_data_customer');
const __copy_confirmation = document.querySelector('.copy_confirmation'); //* ===== BUTTON TO COPY CONFIRMATION CONTENT ===== */
const __send_confirmation_whatsapp = document.querySelector('.send_confirmation_whatsapp'); //* ===== BUTTON TO COPY CONFIRMATION CONTENT ===== */

const __is_open = document.getElementById('is_open');
const __notifications = document.querySelectorAll('.notifications');
//DEFINIMOS EL SERVIDOR SOCKET QUE ESCUCHARA LAS PETICIONES
const socket = io( (window.location.hostname == '127.0.0.1' ) ? 'http://localhost:4000': 'https://socket-caribbean-transfers.up.railway.app' );

//VARIABLES
const btnDowloadOperation = document.getElementById('btn_dowload_operation')              //* ===== BUTTON DOWLOAD OPERATION ===== */
const btnDowloadOperationCommissions = document.getElementById('btn_dowload_operation_comission')   //* ===== BUTTON DOWLOAD COMMISSION OPERATION ===== */
const _getSchedules = document.getElementById('getSchedules');
const btnCloseOperation = document.getElementById('btn_close_operation'); //* ===== BUTTON CLOSE OPERATION ===== */
const btnOpenOperation = document.getElementById('btn_open_operation'); //* ===== BUTTON CLOSE OPERATION ===== */

document.addEventListener("DOMContentLoaded", function() {
    const __site = document.getElementById('formSite');
    if( __site ){
        setup.actionSite(__site);
        __site.addEventListener('change', function(event){
            setup.actionSite(__site);
        });
    }

    //FUNCIONALIDAD DE CALENDARIO PARA FILTROS
    let picker = flatpickr("#lookup_date", {
        mode: "single",
        dateFormat: "Y-m-d",
        enableTime: false,
    });

    //FUNCIONALIDAD DE CALENDARIO PARA AGREGAR SERVICIO
    let pickerInit = flatpickr("#departure_date", {
        mode: "single",
        dateFormat: "Y-m-d H:i",
        enableTime: true,
    });

    //FUNCIONALIDADES DE BARRA TOOLS, SON LOS BOTONES DE LA PARTE SUPERIOR
    if( btnDowloadOperation ){
        btnDowloadOperation.addEventListener('click', function(event){
            event.preventDefault();
            let date = document.getElementById('lookup_date').value;

            Swal.fire({
                title: "Procesando solicitud...",
                text: "Por favor, espera mientras se decarga el reporte de operaciones.",
                allowOutsideClick: false,
                allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                didOpen: () => {
                    Swal.showLoading();
                }
            });            

            fetch("/operation/board/exportExcel?date=" + date, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                },
            })
            .then(response => response.blob())
            .then(blob => {
                Swal.close();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'operation_board_'+ date +'.xlsx';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        })
    }

    if( btnDowloadOperationCommissions ){
        btnDowloadOperationCommissions.addEventListener('click', function(event){
            event.preventDefault();
            let date = document.getElementById('lookup_date').value;

            Swal.fire({
                title: "Procesando solicitud...",
                text: "Por favor, espera mientras se decarga el reporte de comisiones de operaciones.",
                allowOutsideClick: false,
                allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("/operation/board/exportExcelCommission?date=" + date, {
                method: "GET",
                headers: {
                    'Content-Type': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                },
            })
            .then(response => response.blob())
            .then(blob => {
                Swal.close();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'commissions_operating_drivers_'+ date +'.xlsx';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('Error:', error);
            });            
        })
    }

    if (_getSchedules) {
        _getSchedules.addEventListener('click', async function(event){
            event.preventDefault();
            $("#schedulesModal").modal('show');
            setup.reloadAll();
        })
    }

    if( btnCloseOperation ){
        btnCloseOperation.addEventListener('click', function(event) {
            event.preventDefault();

            swal.fire({
                text: '¿Está seguro que desea cerrar la operación',
                icon: 'warning',
                inputLabel: "Selecciona la fecha de operación que desea cerrar",
                input: "date",
                inputValue: document.getElementById('lookup_date').value,
                inputValidator: (result) => {
                    return !result && "Selecciona un fecha";
                },
                didOpen: () => {
                    const today = (new Date()).toISOString();
                    Swal.getInput().min = today.split("T")[0];
                },
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false, // Esta línea evita que se cierre con ESC
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Procesando solicitud...",
                        text: "Por favor, espera mientras se realiza el cierre de la operación.",
                        allowOutsideClick: false,
                        allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch("/operation/closeOperation", {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ date: result.value })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // console.log(data);

                        let message = data.message;
                        if(data.hasOwnProperty('items') && data.items.length > 0){
                            data.items.forEach(item => {
                                message += `\n<strong>${item}</strong>`; // Reemplaza nombrePropiedad con la real
                            });
                        }

                        Swal.fire({
                            icon: data.status,
                            html: message,
                            allowOutsideClick: false,
                            allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                        }).then(() => {
                            if( data.status == "success" ){
                                location.reload();
                            }                            
                        });                        
                    })
                    .catch(error => {
                        console.log(data);                        
                        Swal.fire(
                            '¡ERROR!',
                            error.message || 'Ocurrió un error',
                            'error'
                        );
                    });
                }
            });
        });
    }

    if( btnOpenOperation ){
        btnOpenOperation.addEventListener('click', function(event) {
            event.preventDefault();

            swal.fire({
                text: '¿Está seguro que desea abrir la operación',
                icon: 'warning',
                inputLabel: "Selecciona la fecha de operación que desea cerrar",
                input: "date",
                inputValue: document.getElementById('lookup_date').value,
                inputValidator: (result) => {
                    return !result && "Selecciona un fecha";
                },
                didOpen: () => {
                    const today = (new Date()).toISOString();
                    Swal.getInput().min = today.split("T")[0];
                },
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false, // Esta línea evita que se cierre con ESC
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Procesando solicitud...",
                        text: "Por favor, espera mientras se realiza la apertura de la operación.",
                        allowOutsideClick: false,
                        allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch("/operation/openOperation", {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ date: result.value })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        Swal.fire({
                            icon: data.status,
                            html: data.message,
                            allowOutsideClick: false,
                            allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                        }).then(() => {
                            location.reload();
                        });                        
                    })
                    .catch(error => {
                        Swal.fire(
                            '¡ERROR!',
                            error.message || 'Ocurrió un error',
                            'error'
                        );
                    });
                }
            });
        });
    }    

    document.addEventListener("change", components.debounce(async function (event) {
        if (event.target.classList.contains('change_schedule')) {
            event.preventDefault();
            
            // Obtener datos del elemento clickeado
            const { code, type } = event.target.dataset;
            const _params    = {
                code: code,
                type: type,
                value: event.target.value,
            };
            console.log(_params);
            
            // setup.fetchExacute(_params);
        }
    }, 300)); // 300ms de espera antes de ejecutar de nuevo
    
    document.addEventListener("input", components.debounce(async function (event) {
        if (event.target.classList.contains('change_schedule')) {
            event.preventDefault();
            
            const { code, type } = event.target.dataset;            
            const _params    = {
                code: code,
                type: type,
                value: event.target,
            };

            // setup.fetchExacute(_params);
        }
    }, 300)); // 300ms de espera antes de ejecutar de nuevo
    
    //EVENTOS DE BOTONES DEL DATATABLE
    document.addEventListener("change", components.debounce(function (event) {
        //* ===== SELECT VEHICLES ===== */
        if (event.target.classList.contains('vehicles')) {
            // Obtener datos del elemento clickeado
            const { id, item, service, type, service_id } = event.target.dataset;
            let vehicle = event.target.value;
        
            Swal.fire({
                title: "Procesando solicitud...",
                text: "Por favor, espera mientras validamos el costo operativo.",
                allowOutsideClick: false,
                allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("/operation/validateOperatingCosts", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id : id, item_id : item, service_id : service_id })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                let codeRate      = data.codeRate;
                let siteType      = data.siteType;
                let operatingCost = data.value;
                let date          = document.getElementById('lookup_date').value;
                if( data.success && data.value != null ){
                    Swal.fire({
                        title: "Procesando solicitud...",
                        text: "Por favor, espera mientras se asigana la unidad.",
                        allowOutsideClick: false,
                        allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch("/operation/vehicle/set", {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({id : id, item_id : item, service : service, type : type, vehicle_id : vehicle, operating_cost : operatingCost, code_rate: codeRate, site_type : siteType, date : date })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if( data.success ){
                            Swal.fire({
                                icon: "success",
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500,
                                willClose: () => {
                                    socket.emit("setVehicleReservationServer", data.data);
                                }
                            });

                            if( data.data2.value != null ){
                                socket.emit("setDriverReservationServer", data.data2);
                            }
                            reloadWithLastWindowPosition();
                        }                        
                    })
                    .catch(error => {
                        Swal.fire(
                            '¡ERROR!',
                            error.message || 'Ocurrió un error',
                            'error'
                        );
                    });
                }else{
                    swal.fire({
                        inputLabel: "Ingresa el costo operativo",
                        inputPlaceholder: "Ingresa el costo operativo",
                        input: "text",
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Aceptar',
                        cancelButtonText: 'Cancelar',
                        allowOutsideClick: false,
                        allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                        preConfirm: async (login) => {
                            try {
                                if (login == "") {
                                    return Swal.showValidationMessage(`
                                        "Por favor, ingresa el costo operativo"
                                    `);
                                }
                            } catch (error) {
                                Swal.showValidationMessage(`
                                    Request failed: ${error}
                                `);
                            }
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            operatingCost = result.value;
                            Swal.fire({
                                title: "Procesando solicitud...",
                                text: "Por favor, espera mientras se asigana la unidad.",
                                allowOutsideClick: false,
                                allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
        
                            fetch("/operation/vehicle/set", {
                                method: "POST",
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({id : id, item_id : item, service : service, type : type, vehicle_id : vehicle, operating_cost : operatingCost, code_rate: codeRate, site_type : siteType, date : date })
                            })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(err => { throw err; });
                                }
                                return response.json();
                            })
                            .then(data => {
                                if( data.success ){
                                    Swal.fire({
                                        icon: "success",
                                        text: data.message,
                                        showConfirmButton: false,
                                        timer: 1500,
                                        willClose: () => {
                                            socket.emit("setVehicleReservationServer", data.data);
                                        }
                                    });

                                    if( data.data2.value != null ){
                                        socket.emit("setDriverReservationServer", data.data2);
                                    }                                    
                                }                        
                            })
                            .catch(error => {
                                Swal.fire(
                                    '¡ERROR!',
                                    error.message || 'Ocurrió un error',
                                    'error'
                                );
                            });
                        }
                    });                    
                }
            })
            .catch(error => {
                Swal.fire(
                    '¡ERROR!',
                    error.message || 'Ocurrió un error',
                    'error'
                );
            });
        }
    }, 300)); // 300ms de espera antes de ejecutar de nuevo

    // FUNCIONADA PARA EXTRACION DE INFORMACION DE DATOS PARA ENVIAR POR WHATSAPP
    document.addEventListener("click", components.debounce(function (event) {
        if(event.target.classList.contains('extract_whatsapp')){
            event.preventDefault();

            const container = document.getElementById('wrapper_whatsApp');
            const { reservation, item, type } = event.target.dataset;

            container.innerHTML = "";

            fetch("/action/getInformationReservation?id=" + reservation + "&item_id=" + item, {
                method: "GET",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                console.log(data);
                if( data.status == "success" ){
                    let fila = $(event.target).closest('tr');
                    const info = data.data;
                    const item = data.data.items[0];
                    console.log(data, item);                
                    // Extraer la información de las celdas de la fila
                    var hora = fila.find('td').eq(1).text();
                    var vehicle = ( fila.find('td').eq(8).find('button').length > 0 ? fila.find('td').eq(8).find('button .filter-option').text() : fila.find('td').eq(8).text() );
                    var driver = ( fila.find('td').eq(9).find('button').length > 0 ? fila.find('td').eq(9).find('button .filter-option').text() : fila.find('td').eq(9).text() );
                    var time_operation = fila.find('td').eq(11).text();
                    var unit = fila.find('td').eq(14).text();
                    var payment = fila.find('td').eq(15).text();
                    let total = fila.find('td').eq(16).text();
                    let final_service = ( type == "TYPE_ONE" ? item.final_service_type_one : item.final_service_type_two );

                    let message =   '<p class="m-0">Número: ' + ( type == "TYPE_ONE" ? ( item.op_one_preassignment != null ? item.op_one_preassignment : 'SIN PRE ASIGNACIÓN' ) : ( item.op_two_preassignment != null ? item.op_two_preassignment : 'SIN PRE ASIGNACIÓN' ) ) + '</p> \n ' +
                                    '<p class="m-0">Código: ' + item.code + '</p> \n ' +
                                    '<p class="m-0">Hora: ' + hora + '</p> \n ' +
                                    '<p class="m-0">Cliente: ' + info.client_first_name + ' ' + info.client_last_name + '</p> \n ' +
                                    '<p class="m-0">Tipo de servicio: ' + final_service + '</p> \n ' +
                                    '<p class="m-0">Pax: ' + item.passengers + '</p> \n ' +
                                    '<p class="m-0">Origen: ' + ( type == "TYPE_ONE" ? item.from_name : item.to_name ) + '</p> \n ' +
                                    '<p class="m-0">Destino: ' + ( type == "TYPE_ONE" ? item.to_name : item.from_name ) + '</p> \n ' +
                                    '<p class="m-0"># Vuelo: ' + ( final_service == "ARRIVAL" || final_service == "DEPARTURE" ? item.flight_number : 'SIN NUMERO DE VUELO' ) + '</p> \n ' +
                                    '<p class="m-0">Agencia: ' + info.site.name + '</p> \n ' +
                                    '<p class="m-0">Unidad: ' + vehicle + '</p> \n ' +
                                    '<p class="m-0">Conductor: ' + driver + '</p> \n ' +
                                    '<p class="m-0">Estatus de operación: ' + ( type == "TYPE_ONE" ? item.op_one_status_operation : item.op_two_status_operation ) + '</p> \n ' +
                                    '<p class="m-0">Hora de operación: ' + time_operation + '</p> \n ' +
                                    '<p class="m-0">Estatus de reservación: ' + ( type == "TYPE_ONE" ? item.op_one_status : item.op_two_status ) + '</p> \n ' +
                                    '<p class="m-0">Vehículo: ' + unit + '</p> \n ' +
                                    '<p class="m-0">Pago: ' + payment + '</p> \n ' +
                                    '<p class="m-0">Total: ' + total;

                    container.innerHTML = message;
                }
            })
            .catch(error => {
                Swal.fire(
                    '¡ERROR!',
                    error.message || 'Ocurrió un error',
                    'error'
                );
            });
        }

        //* ===== SELECT VEHICLES ===== */
        if (event.target.classList.contains('vehicles')) {
            event.preventDefault();

            // Obtener datos del elemento clickeado
            const { id, item, service, type, service_id } = event.target.dataset;
            let vehicle = event.target.value;
        
            Swal.fire({
                title: "Procesando solicitud...",
                text: "Por favor, espera mientras validamos el costo operativo.",
                allowOutsideClick: false,
                allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("/operation/validateOperatingCosts", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id : id, item_id : item, service_id : service_id })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                let codeRate      = data.codeRate;
                let siteType      = data.siteType;
                let operatingCost = data.value;
                let date          = document.getElementById('lookup_date').value;
                if( data.success && data.value != null ){
                    Swal.fire({
                        title: "Procesando solicitud...",
                        text: "Por favor, espera mientras se asigana la unidad.",
                        allowOutsideClick: false,
                        allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch("/operation/vehicle/set", {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({id : id, item_id : item, service : service, type : type, vehicle_id : vehicle, operating_cost : operatingCost, code_rate: codeRate, site_type : siteType, date : date })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if( data.success ){
                            Swal.fire({
                                icon: "success",
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500,
                                willClose: () => {
                                    socket.emit("setVehicleReservationServer", data.data);
                                }
                            });

                            if( data.data2.value != null ){
                                socket.emit("setDriverReservationServer", data.data2);
                            }
                        }                        
                    })
                    .catch(error => {
                        Swal.fire(
                            '¡ERROR!',
                            error.message || 'Ocurrió un error',
                            'error'
                        );
                    });
                }else{
                    swal.fire({
                        inputLabel: "Ingresa el costo operativo",
                        inputPlaceholder: "Ingresa el costo operativo",
                        input: "text",
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Aceptar',
                        cancelButtonText: 'Cancelar',
                        allowOutsideClick: false,
                        allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                        preConfirm: async (login) => {
                            try {
                                if (login == "") {
                                    return Swal.showValidationMessage(`
                                        "Por favor, ingresa el costo operativo"
                                    `);
                                }
                            } catch (error) {
                                Swal.showValidationMessage(`
                                    Request failed: ${error}
                                `);
                            }
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            operatingCost = result.value;
                            Swal.fire({
                                title: "Procesando solicitud...",
                                text: "Por favor, espera mientras se asigana la unidad.",
                                allowOutsideClick: false,
                                allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
        
                            fetch("/operation/vehicle/set", {
                                method: "POST",
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({id : id, item_id : item, service : service, type : type, vehicle_id : vehicle, operating_cost : operatingCost, code_rate: codeRate, site_type : siteType, date : date })
                            })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(err => { throw err; });
                                }
                                return response.json();
                            })
                            .then(data => {
                                if( data.success ){
                                    Swal.fire({
                                        icon: "success",
                                        text: data.message,
                                        showConfirmButton: false,
                                        timer: 1500,
                                        willClose: () => {
                                            socket.emit("setVehicleReservationServer", data.data);
                                        }
                                    });

                                    if( data.data2.value != null ){
                                        socket.emit("setDriverReservationServer", data.data2);
                                    }                                    
                                }                        
                            })
                            .catch(error => {
                                Swal.fire(
                                    '¡ERROR!',
                                    error.message || 'Ocurrió un error',
                                    'error'
                                );
                            });
                        }
                    });                    
                }
            })
            .catch(error => {
                Swal.fire(
                    '¡ERROR!',
                    error.message || 'Ocurrió un error',
                    'error'
                );
            });
        }

        //* ===== STATUS SERVICES ===== */
        if (event.target.classList.contains('serviceStatusUpdate')) {
            event.preventDefault();

            // Obtener datos del elemento clickeado
            const { reservation, item, service, status, type, key } = event.target.dataset;

            (async () => {
                // Crear un contenedor para Dropzone y el select                
                const dropzoneContainer = document.createElement("div");
                let HTML = "";

                if( status == "CANCELLED" || status == "NOSHOW" ){
                    HTML = `
                        <label for="cancelReason">Selecciona el motivo de cancelación:</label>
                        <select id="cancelReason" class="swal2-input w-100" required>
                            <option value="">Seleccione una opción</option>
                            ${Object.entries(types_cancellations).map(([key, value]) => `<option value="${key}">${value}</option>`).join('')}
                        </select>
                    `;

                    if( status == "CANCELLED" ){
                        HTML += `
                            <label for="additionalNotes">Comentarios adicionales (obligatorio):</label>
                            <input id="additionalNotes" class="swal2-input w-100" placeholder="Ejemplo: imagen 1" required />
                        `;
                    }

                    HTML += `
                        <label for="attachPicture" style="margin-top: 10px;">Debes adjuntar al menos una imagen:</label>
                        <div id="dropzoneService" class="dropzone"></div>
                    `;
                }
                let selectedFiles = []; // Array para almacenar las imágenes seleccionadas
                dropzoneContainer.classList.add('box_cancelation')        
                dropzoneContainer.innerHTML = `
                    <p>${ status == "CANCELLED" || status == "NOSHOW" ? '¿Está seguro de cancelar la reservación? <br>  Esta acción no se puede revertir' : '¿Está seguro de actualizar el estatus? <br> Esta acción no se puede revertir' }</p>
                    ${HTML}
                `;
    
                const { isConfirmed, value } = await swal.fire({
                    html: dropzoneContainer,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Aceptar",
                    cancelButtonText: "Cancelar",
                    allowOutsideClick: false,
                    allowEscapeKey: false, // Esta línea evita que se cierre con ESC
                    didOpen: () => {
                        if( document.getElementById('dropzoneService') != null ){
                            // Inicializar Dropzone
                            new Dropzone("#dropzoneService", {
                                url: "/reservations/upload", // No se enviarán archivos aquí, solo los almacenaremos en memoria
                                maxFilesize: 5, // Tamaño máximo del archivo en MB
                                maxFiles: 5,
                                acceptedFiles: "image/*",
                                dictDefaultMessage: "Arrastra el archivo aquí o haz clic para subirlo (Imágenes/PDF)...",
                                addRemoveLinks: true,
                                dictRemoveFile: "Eliminar imagen",
                                autoProcessQueue: false,
                                init: function () {
                                    this.on("addedfile", file => selectedFiles.push(file));
                                    this.on("removedfile", file => {
                                        selectedFiles = selectedFiles.filter(f => f !== file);
                                    });
                                }
                            });
                        }
                    },
                    preConfirm: () => {
                        if( status == "CANCELLED" || status == "NOSHOW" ){
                            const reason            = document.getElementById("cancelReason").value;
                            const additionalNotes   = document.getElementById("additionalNotes")?.value;
                            const dropzone          = Dropzone.forElement("#dropzoneService");
                
                            if (!reason) {
                                Swal.showValidationMessage("Debes seleccionar un motivo de cancelación.");
                                return false;
                            }
                            
                            if (status == "CANCELLED") {
                                if (!additionalNotes || !additionalNotes.trim()) {
                                    Swal.showValidationMessage("Debes proporcionar comentarios adicionales.");
                                    return false;
                                }
                            }                            
                            
                            if (dropzone.files.length === 0) {
                                Swal.showValidationMessage("Debes subir al menos una imagen.");
                                return false;
                            }
    
                            return { 
                                reason, 
                                additionalNotes: additionalNotes?.trim() || "",
                                images: dropzone.files 
                            };
                        }else{
                            return true;
                        }
                    }            
                });
    
                if (isConfirmed) {
                    const params = {
                        item_id: item,
                        service: service,
                        status: status,
                        type: type,
                        type_cancel: (status == "CANCELLED" || status == "NOSHOW") ? value.reason : "",
                        additional_notes: (status == "CANCELLED" || status == "NOSHOW") ? value.additionalNotes : ""
                    };

                    const __vehicle = document.getElementById('vehicle_id_' + key);
                    const __driver = document.getElementById('driver_id_' + key);
    
                    if ( ( __vehicle.value == 0 ) ) {
                        Swal.fire({
                            text: 'Valida la selección de unidad.',
                            icon: 'error',
                            showConfirmButton: false,
                            timer: 1500,
                            allowOutsideClick: false,
                            allowEscapeKey: false, // Esta línea evita que se cierre con ESC                            
                        });
                    }else{
                        Swal.fire({
                            title: "Actualizando estatus...",
                            text: "Por favor, espera mientras se actualiza el estatus.",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const statusUpdated = await setup.updateServiceStatus(params);

                        if (statusUpdated) {
                            if (status == "CANCELLED" || status == "NOSHOW") {
                                Swal.fire({
                                    title: "Subiendo imágenes...",
                                    text: "Por favor, espera mientras se suben todas las imagenes",
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    didOpen: () => Swal.showLoading()
                                });

                                try {
                                    const uploadedImages = await setup.uploadImages(value.images, reservation, status);

                                    if (uploadedImages.length === value.images.length) {
                                        Swal.fire("Éxito", "Cancelación confirmada y archivos subidos.", "success").then(() => {
                                            reloadWithLastWindowPosition();
                                        });
                                    } else {
                                        Swal.fire("Error", "Algunas imágenes no se pudieron subir. Intenta de nuevo.", "error");
                                    }
                                } catch (err) {
                                    Swal.fire("Error", "Ocurrió un problema al subir las imágenes.", "error");
                                }
                            } else {
                                Swal.fire("Éxito", "Estatus actualizado correctamente.", "success").then(() => {
                                    reloadWithLastWindowPosition();
                                });
                            }
                        }
                    }
                }
            })();            
        }

        //ACTUALIZA LA CONFIRMACIÓN DEL SERVICIO
        if (event.target.classList.contains('confirmService')) {
            event.preventDefault();

            // Definir parámetros de la petición
            const target     = event.target;
            const _params    = {
                item_id: target.dataset.item || "",
                service: target.dataset.service || "",
                status: target.dataset.status || "",
                type: target.dataset.type || "",
            };
            
            Swal.fire({
                html: '¿Está seguro de actualizar el estatus de confirmación del servicio?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Procesando solicitud...",
                        text: "Por favor, espera mientras se actualiza el estatus de confirmación.",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
    
                    fetch('/action/confirmService', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },            
                        body: JSON.stringify(_params)
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        Swal.fire({
                            icon: data.status,
                            html: data.message,
                            allowOutsideClick: false,
                        }).then(() => {
                            reloadWithLastWindowPosition();
                        });
                    })
                    .catch(error => {
                        Swal.fire(
                            '¡ERROR!',
                            error.message || 'Ocurrió un error',
                            'error'
                        );
                    });
                }
            });            
        }
    }, 300)); // 300ms de espera antes de ejecutar de nuevo    
});

function history(){
    setup.bsTooltip();
    const __open_modal_historys = document.querySelectorAll('.__open_modal_history');
    if( __open_modal_historys.length > 0 ){
        __open_modal_historys.forEach(__open_modal_history => {
            __open_modal_history.addEventListener('click', function(){
      
                //DECLARACION DE VARIABLES
                const { type, comment } = this.dataset;
                const __modal = document.getElementById('historyModal');
                const __content = document.getElementById('wrapper_history');
    
                if (type == "history") {
                    $.ajax({
                        url: `/operation/history/get`,
                        type: 'GET',
                        data: { code: this.dataset.code },
                        success: function(resp) {
                            if ( resp.success ) {                                
                                __content.innerHTML = resp.message;
                                $(__modal).modal('show');
                            }
                        }
                    });
                }else{
                    __content.innerHTML = comment;
                    $(__modal).modal('show');
                }
            });
        });
    }
}

function mediaBooking(){
    setup.bsTooltip();
    const __open_modal_medias = document.querySelectorAll('.__open_modal_media');
    if( __open_modal_medias.length > 0 ){
        __open_modal_medias.forEach(__open_modal_media => {
            __open_modal_media.addEventListener('click', function(){
      
                //DECLARACION DE VARIABLES
                const { code } = this.dataset;
                console.log(code);
                $("#historyMediaModal").modal('show');
                $('#media-listing').load('/reservations/upload/' + code + '?type=OPERATION', function(response, status, xhr) {
                    console.log(response);
                    console.log(status);
                    console.log(xhr);
                    if (status == "error") {
                        $('#media-listing').html('Error al cargar el contenido');
                    }
                });
            });
        });
    }
}

function typesCancellations() {
    const __types_cancellations = document.getElementById('types_cancellations');
    if( __types_cancellations != null ){
        let options = JSON.parse(__types_cancellations.value);
        if( options != null && options.length > 0 ){
            // Ordenar las opciones alfabéticamente por 'name_es'
            options.sort((a, b) => a.name_es.localeCompare(b.name_es));        
            options.forEach(option => {
                types_cancellations[option.id] = option.name_es;
            });
        }
    }
}

//FUNCIONALIDAD DEL AUTOCOMPLET
function affDelayAutocomplete(callback, ms) {
    var timer = 0;
    return function () {
        var context = this,
            args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            callback.apply(context, args);
        }, ms || 0);
    };
}
from_autocomplete.addEventListener('keydown', affDelayAutocomplete(function (e) {
    setup.autocomplete( e.target.value, 'from_name_elements');
}, 500));
from_autocomplete.addEventListener('focus', (e) => {
    setup.autocomplete( e.target.value, 'from_name_elements');
});
to_autocomplete.addEventListener('keydown', affDelayAutocomplete(function (e) {
    setup.autocomplete( e.target.value, 'to_name_elements');
}, 500));
to_autocomplete.addEventListener('focus', (e) => {
    setup.autocomplete( e.target.value, 'to_name_elements');
});

//FUNCIONALIDAD DE CAMBIO DE MONEDA
sold_in_currency_select.addEventListener('change', (e) => {
    currency_span.innerText = `(${e.target.value})`;
});

form.addEventListener('submit', function (event) {
    event.preventDefault();

    $('#sold_in_currency').prop('disabled', false);
    $('#total').prop('disabled', false);
    let _params = components.serialize(this,'object');    

    $.ajax({
        type: "POST", // Método HTTP de la solicitud
        url: _LOCAL_URL + "/operation/capture/service",
        data: JSON.stringify(_params), // Datos a enviar al servidor
        dataType: "json", // Tipo de datos que se espera en la respuesta del servidor
        contentType: 'application/json; charset=utf-8',
        beforeSend: function(){
            components.loadScreen();
        },
        success: function(resp) {
            console.log(resp);
            if ( typeof resp === 'object' && 'success' in resp && resp.success ) {
                $("#operationModal").modal('hide');
                socket.emit("addServiceServer", resp);
            } else {
                Swal.fire({
                    title: 'Oops!',
                    icon: 'error',
                    html: 'Ocurrió un error inesperado',
                    timer: 2500,
                });
                $('#sold_in_currency').prop('disabled', true);
                $('#total').prop('disabled', true);
            }
        }
    });
});

//FUNCIONADA PARA EXTRACION DE INFORMACION DE DATOS DE CONFIRMACION PARA ENVIAR POR WHATSAPP
$('#dataManagementOperations').on('click', '.extract_confirmation', function() {
    const  __language = this.dataset.language;
    const  __terminal1 = document.getElementById('terminal1');
    const  __terminal2 = document.getElementById('terminal2');
    const  __terminal3 = document.getElementById('terminal3');

    console.log(__language);    

    let __message_terminal1 = ( __language == "es" ? "<p>Bienvenidos a Cancun1 Gracias por elegir Caribbean Transfers como su medio de transporte. Para asegurar una experiencia de abordaje rápida y eficiente, le pedimos amablemente que nos envíe una foto tipo selfie de usted y de sus acompañantes. Esto permitirá que nuestro conductor lo reconozca fácilmente al momento de su llegada.</p> \n <p>Cuando tengan listo su equipaje los esperamos en el welcome barafuera de la terminal</p> \n " : "<p>Welcome to Cancun1 Thank you for choosing Caribbean Transfers as your transportation provider. To ensure a fast and efficient boarding experience, we kindly ask you to send us a selfie photo of you and your companions. This will allow our driver to easily recognize you upon arrival.</p> \n <p>When you have your bags ready. we will wait for you at the Margarita ville restaurant, out side of the terminal</p> \n " );
    let __message_terminal2 = ( __language == "es" ? "<p>Bienvenidos a Cancun2 Gracias por elegir Caribbean Transfers como su medio de transporte. Para asegurar una experiencia de abordaje rápida y eficiente, le pedimos amablemente que nos envíe una foto tipo selfie de usted y de sus acompañantes. Esto permitirá que nuestro conductor lo reconozca fácilmente al momento de su llegada.</p> \n <p>Cuando tengan listo su equipaje los esperamos en el welcome barafuera de la terminal</p> \n " : "<p>Welcome to Cancun2 Thank you for choosing Caribbean Transfers as your transportation provider. To ensure a fast and efficient boarding experience, we kindly ask you to send us a selfie photo of you and your companions. This will allow our driver to easily recognize you upon arrival.</p> \n <p>When you have your bags ready. we will wait for you at the Margarita ville restaurant, out side of the terminal</p> \n " );
    let __message_terminal3 = ( __language == "es" ? "<p>Bienvenidos a Cancun3 Gracias por elegir Caribbean Transfers como su medio de transporte. Para asegurar una experiencia de abordaje rápida y eficiente, le pedimos amablemente que nos envíe una foto tipo selfie de usted y de sus acompañantes. Esto permitirá que nuestro conductor lo reconozca fácilmente al momento de su llegada.</p> \n <p>Cuando tengan listo su equipaje los esperamos en el welcome barafuera de la terminal</p> \n " : "<p>Welcome to Cancun3 Thank you for choosing Caribbean Transfers as your transportation provider. To ensure a fast and efficient boarding experience, we kindly ask you to send us a selfie photo of you and your companions. This will allow our driver to easily recognize you upon arrival.</p> \n <p>When you have your bags ready. we will wait for you at the Margarita ville restaurant, out side of the terminal</p> \n " );

    // Obtener la fila en la que se encuentra el botón
    var fila = $(this).closest('tr');

    // Extraer la información de las celdas de la fila
    let identificator = ( fila.find('td').eq(0).find('button').text() == "ADD" ? "SIN PRE ASIGNACIÓN" : fila.find('td').eq(0).find('button').text() );

    __message_terminal1 += "<p>" + identificator + "</p>";
    __message_terminal2 += "<p>" + identificator + "</p>";
    __message_terminal3 += "<p>" + identificator + "</p>";

    __terminal1.innerHTML = __message_terminal1;
    __terminal2.innerHTML = __message_terminal2;
    __terminal3.innerHTML = __message_terminal3;
});

if( __is_open != null ){
    __is_open.addEventListener('change', function(){
        const _checkbox_box = document.querySelector('.checkbox_box');
        const _checkbox_time = document.querySelector('.checkbox_time');
        const _open_service_time = document.getElementById('open_service_time');
        if (this.checked) {
            this.value = 1;
            _checkbox_box.classList.remove('col-lg-12');
            _checkbox_box.classList.add('col-lg-6');
            _checkbox_time.classList.remove('d-none');
            _open_service_time.setAttribute('name','open_service_time');
        } else {
            this.value = 0;
            _checkbox_box.classList.remove('col-lg-6');
            _checkbox_box.classList.add('col-lg-12');
            _checkbox_time.classList.add('d-none');
            _open_service_time.removeAttribute('name');
        }
    });
}

if( __btn_preassignment != null ){
  __btn_preassignment.addEventListener('click', function() {
        swal.fire({
            text: '¿Está seguro de pre-asignar los servicios?',
            icon: 'warning',
            inputLabel: "Selecciona la fecha que pre-asignara",
            input: "date",
            inputValue: document.getElementById('lookup_date').value,
            inputValidator: (result) => {
                return !result && "Selecciona un fecha";
            },
            didOpen: () => {
                const today = (new Date()).toISOString();
                Swal.getInput().min = today.split("T")[0];
            },
            showCancelButton: true,
            confirmButtonText: 'Aceptar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if(result.isConfirmed == true){
                $.ajax({
                    type: "POST",
                    url: _LOCAL_URL + "/operation/preassignments",
                    data: JSON.stringify({ date: result.value }), // Datos a enviar al servidor
                    dataType: "json",
                    contentType: 'application/json; charset=utf-8',   
                    beforeSend: function(){
                        components.loadScreen();
                    },
                    success: function(response) {
                        // Manejar la respuesta exitosa del servidor
                        Swal.fire({
                            icon: 'success',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500,
                        });
                        reloadWithLastWindowPosition();
                    }
                });
            }
        });
  });
}

if (__add_preassignments.length > 0) {
  __add_preassignments.forEach(__add_preassignment => {
      __add_preassignment.addEventListener('click', function(event) {
          event.preventDefault();                    
          const { id, reservation, item, operation, service, type } = this.dataset;
          const __date = document.getElementById('lookup_date');
          swal.fire({
              text: '¿Está seguro de pre-asignar el servicio ?',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Aceptar',
              cancelButtonText: 'Cancelar'
          }).then((result) => {
              if(result.isConfirmed == true){
                $.ajax({
                    url: _LOCAL_URL + "/operation/preassignment",
                    type: 'PUT',
                    data: { date : __date.value, id : id, reservation : reservation, reservation_item : item, operation : operation, service : service, type : type },
                    beforeSend: function() {
                        components.loadScreen();
                    },
                    success: function(response) {
                        // Manejar la respuesta exitosa del servidor
                        Swal.fire({
                            icon: "success",
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500,
                            willClose: () => {
                                socket.emit("addPreassignmentServer", response.data);
                            }
                        });
                        reloadWithLastWindowPosition();
                    }
                });
              }
          });
      });
  });
}

if (__drivers.length > 0) {
  __drivers.forEach(__driver => {
      __driver.addEventListener('change', function() {
          const { id, reservation, item, operation, service, type } = this.dataset;
          $.ajax({
              url: `/operation/driver/set`,
              type: 'PUT',
              data: { id : id, reservation : reservation, reservation_item : item, operation : operation, service : service, driver_id : __driver.value, type : type },
              beforeSend: function() {
                  components.loadScreen();
              },
              success: function(resp) {
                  if( resp.success ){
                      Swal.fire({
                          icon: "success",
                          text: resp.message,
                          showConfirmButton: false,
                          timer: 1500,
                          willClose: () => {
                              socket.emit("setDriverReservationServer", resp.data);
                          }
                      });                                
                  }
              }
          });
      });
  });
}

if (__btn_update_status_operations.length > 0) {
  __btn_update_status_operations.forEach(__btn_update_status_operation => {
      __btn_update_status_operation.addEventListener('click', function(event) {
          event.preventDefault();
          let _settings = {};
          const { operation, service, type, status, item, booking, key } = this.dataset;
          console.log(operation, service, status, item, booking, key);
          _settings.text = "¿Está seguro de actualizar el estatus de operación?";
          _settings.icon = 'warning';
          _settings.showCancelButton = true;
          _settings.confirmButtonText = 'Aceptar';
          _settings.cancelButtonText = 'Cancelar';
          if (status == "OK") {
              _settings.inputLabel = "Ingresa la hora de abordaje";
              _settings.input = "time";
              _settings.inputValue = setup.obtenerHoraActual();
              _settings.inputValidator = (result) => {
                  return !result && "Selecciona un horario";
              }
          }
          swal.fire(_settings).then((result) => {
              if(result.isConfirmed == true){
                  $.ajax({
                      url: `/operation/status/operation`,
                      type: 'PUT',
                      data: { id: key, rez_id: booking, item_id: item, operation: operation, service: service, type: type, status: status, time: ( setup.isTime(result.value) ? result.value : "" ) },
                      beforeSend: function() {
                          components.loadScreen();
                      },
                      success: function(resp) {
                          Swal.fire({
                              icon: 'success',
                              text: 'Servicio actualizado con éxito.',
                              showConfirmButton: false,
                              timer: 1500,
                              willClose: () => {
                                  socket.emit("updateStatusOperationServer", resp.data);
                              }
                          });
                            reloadWithLastWindowPosition();
                      }
                  });
              }
          });
      });
  });
}

history();
mediaBooking();
typesCancellations();

if( __open_modal_customers.length > 0 ){
    __open_modal_customers.forEach(__open_modal_customer => {
        __open_modal_customer.addEventListener('click', function(){
  
            //DECLARACION DE VARIABLES
            const __modal = document.getElementById('customerDataModal');
  
            $.ajax({
                url: `/operation/data/customer/get`,
                type: 'GET',
                data: { code: this.dataset.code },
                success: function(resp) {
                    if ( resp.success ) {
                        let message =  '<p class="m-0">Nombre: ' + resp.data.client_first_name + ' ' + resp.data.client_last_name + '</p> \n ' +
                        '<p class="m-0">Correo: ' + resp.data.client_email + '</p> \n ' +
                        '<p class="m-0">Teléfono: ' + resp.data.client_phone + '</p> \n ';

                        const content = document.getElementById('wrapper_data_customer');
                        content.innerHTML = message;
                        $(__modal).modal('show');
                    }                                        
                }
            });
        });
    });
}

//ACCION PARA ABRIR MODAL PARA AÑADIR UN COMENTARIO
if( __open_modal_comments.length > 0 ){
  __open_modal_comments.forEach(__open_modal_comment => {
      __open_modal_comment.addEventListener('click', function(){

          //DECLARACION DE VARIABLES
          const __modal = document.getElementById('messageModal');
          const __title_modal = document.getElementById('messageModalLabel');
          const __form_label = __modal.querySelector('.form-label');

          //SETEAMOS VALORES EN EL MODAL
          __form_label.innerHTML = ( this.dataset.status == 0 ? "Ingresa el comentario" : "Editar el comentario" );

          //APLICA PARA COMENTARIOS
          document.getElementById('id_item').value = this.dataset.id;
          document.getElementById('code_item').value = this.dataset.code;
          document.getElementById('operation_item').value = this.dataset.operation;
          document.getElementById('type_item').value = this.dataset.type;

          //APLICA PARA MEDIA, CARGA DE IMAGENES
          document.getElementById('id').value = this.dataset.id;
          document.getElementById('reservation_id').value = this.dataset.reservation;
          document.getElementById('reservation_item').value = this.dataset.code;

          if (this.dataset.status == 1) {
            $.ajax({
                url: `/operation/comment/get`,
                type: 'GET',
                data: { item_id: this.dataset.code, operation: this.dataset.operation, type: this.dataset.type },
                success: function(resp) {
                    document.getElementById('comment_item').value = resp.message;
                    $(__modal).modal('show');
                }
            });
          }else{
            $(__modal).modal('show');
          }
      });
  });
}

//ACCION DE FORMULARIO
__button_form.addEventListener('submit', function (event) {
  event.preventDefault();
  let _params = components.serialize(this,'object');
  if( _params != null ){
      $.ajax({
          type: "POST", // Método HTTP de la solicitud
          url: _LOCAL_URL + "/operation/comment/add", // Ruta del archivo PHP que manejará la solicitud
          data: JSON.stringify(_params), // Datos a enviar al servidor
          dataType: "json", // Tipo de datos que se espera en la respuesta del servidor
          contentType: 'application/json; charset=utf-8',
          beforeSend: function(){
              components.loadScreen();
          },
          success: function(response) {
              // Manejar la respuesta exitosa del servidor
              $("#messageModal").modal('hide');
              Swal.fire({
                  icon: 'success',
                  text: response.message,
                  showConfirmButton: false,
                  timer: 1500,
                  willClose: () => {
                      socket.emit("addCommentServer", response.data);
                  }
              })
          }
      });
  }else{
      event.stopPropagation();
      components.sweetAlert({"status": "error", "message": "No se definieron parametros"});
  }
});

if ( __copy_whatsapp != null ) {
    __copy_whatsapp.addEventListener('click', function(){
        // Obtiene el div por su ID
        var div = document.getElementById('wrapper_whatsApp');
        // console.log(div);
        // Obtiene el contenido del div y elimina los espacios
        // var contenido = div.textContent.replace(/\s+/g, '');
        var contenido = div.textContent;
        // Usa la API del portapapeles para copiar el contenido
        navigator.clipboard.writeText(contenido).then(function() {
            // Notifica al usuario que el contenido se ha copiado
            // alert('Contenido copiado: ' + contenido);
        }, function(err) {
            // Notifica al usuario en caso de error
            console.error('No se pudo copiar el contenido: ', err);
        });
    });
}

if ( __copy_history != null ) {
    __copy_history.addEventListener('click', function(){
        // Obtiene el div por su ID
        var div = document.getElementById('wrapper_history');
        // console.log(div);
        // Obtiene el contenido del div y elimina los espacios
        // var contenido = div.textContent.replace(/\s+/g, '');
        var contenido = div.textContent;
        // Usa la API del portapapeles para copiar el contenido
        navigator.clipboard.writeText(contenido).then(function() {
            // Notifica al usuario que el contenido se ha copiado
            // alert('Contenido copiado: ' + contenido);
        }, function(err) {
            // Notifica al usuario en caso de error
            console.error('No se pudo copiar el contenido: ', err);
        });
    });    
}

if ( __copy_data_customer != null ) {
    __copy_data_customer.addEventListener('click', function(){
        // Obtiene el div por su ID
        var div = document.getElementById('wrapper_data_customer');
        // console.log(div);
        // Obtiene el contenido del div y elimina los espacios
        // var contenido = div.textContent.replace(/\s+/g, '');
        var contenido = div.textContent;
        // Usa la API del portapapeles para copiar el contenido
        navigator.clipboard.writeText(contenido).then(function() {
            // Notifica al usuario que el contenido se ha copiado
            // alert('Contenido copiado: ' + contenido);
        }, function(err) {
            // Notifica al usuario en caso de error
            console.error('No se pudo copiar el contenido: ', err);
        });
    });    
}

if ( __copy_confirmation != null ){
    __copy_confirmation.addEventListener('click', function(){
        const __tab_pane = document.querySelector("#confirmationModal .tab-pane.active");

        // Obtiene el div por su ID
        const __wrapper = document.getElementById(__tab_pane.dataset.code)
        // Obtiene el contenido del div y elimina los espacios
        // var contenido = div.textContent.replace(/\s+/g, '');  
        var contenido = __wrapper.textContent;
        // Usa la API del portapapeles para copiar el contenido
        navigator.clipboard.writeText(contenido).then(function() {
            // Notifica al usuario que el contenido se ha copiado
            // alert('Contenido copiado: ' + contenido);
            window.open(url, "_blank");
        }, function(err) {
            // Notifica al usuario en caso de error
            console.error('No se pudo copiar el contenido: ', err);
        });
    });
}

if ( __send_confirmation_whatsapp != null ){
    __send_confirmation_whatsapp.addEventListener('click', function(){
        const __tab_pane = document.querySelector("#confirmationModal .tab-pane.active");

        // Obtiene el div por su ID
        const __wrapper = document.getElementById(__tab_pane.dataset.code)
        // Obtiene el contenido del div y elimina los espacios
        // var contenido = div.textContent.replace(/\s+/g, '');  
        var contenido = __wrapper.textContent;
        // Usa la API del portapapeles para copiar el contenido
        navigator.clipboard.writeText(contenido).then(function() {
            // Notifica al usuario que el contenido se ha copiado
            // alert('Contenido copiado: ' + contenido);
            let url = "https://api.whatsapp.com/send?phone=529982942389&text=" + decodeURIComponent(contenido);
            // window.location.href = text;
            window.open(url, "_blank");
        }, function(err) {
            // Notifica al usuario en caso de error
            console.error('No se pudo copiar el contenido: ', err);
        });
    });
}

//FUNCIONALIDAD QUE RECARGA LA PAGINA, CUANDO ESTA DETACTA INACTIVIDAD POR 5 MINUTOS
var inactivityTime = (2 * 60000); // 30 segundos en milisegundos
var timeoutId;

function resetTimer() {
    clearTimeout(timeoutId);          
    timeoutId = setTimeout(refreshPage, inactivityTime);
}

function refreshPage() {
    location.reload();
}
    
document.addEventListener('mousemove', resetTimer);
document.addEventListener('keydown', resetTimer);
        
resetTimer(); 

window.addEventListener('scroll', function() {
  let __table_private = document.getElementById('dataManagementOperations');
  let __thead_private = __table_private.querySelector('thead');
  let __offset_private = __table_private.getBoundingClientRect().top;

  if (window.scrollY > __offset_private) {
    __thead_private.classList.add('fixed-header');
  } else {
    __thead_private.classList.remove('fixed-header');
  }
});

components.renderCheckboxColumns('dataManagementOperations', 'columns');

//EVENTOS SOCKET IO, ESCUCHAN DE LADO DEL CLIENTE
socket.on("addPreassignmentClient", function(data){
    console.log("asignacion");
    console.log(data);
    //DECLARACION DE VARIABLES
    const __btn_preassignment = document.getElementById('btn_preassignment_' + data.item);
    if( __btn_preassignment ){
        const __Row = ( __btn_preassignment != null ? components.closest(__btn_preassignment, 'tr') : null );
        const __Cell = ( __Row != null ? __Row.querySelector('td:nth-child(1)') : "" );
        console.log(__btn_preassignment, __Row, __Cell);
        __btn_preassignment.classList.remove('btn-danger');
        __btn_preassignment.classList.add(setup.setPreassignment(data.operation));
        __btn_preassignment.innerHTML = data.value;
    }

    Snackbar.show({
        text: data.message,
        duration: 5000, 
        pos: 'top-right',
        actionTextColor: '#fff',
        backgroundColor: '#2196f3'
    });
});

socket.on("setVehicleReservationClient", function(data){
    console.log("nueva asignación de unidad");
    console.log(data);
    //DECLARACION DE CONSTANTES
    const __Row = document.getElementById('item-' + data.item);//ROW ES EL TR, DONDE ESTAMOS TRABAJANDO CON EL SELECT DEL VEHICULO
    const __CellVehicle = ( __Row != null ? __Row.querySelector('td:nth-child(9)') : null );//ES LA CELDA DONDE SETEAMOS O IMPRIMIMOS EL VALOR DE VEHICULO
    const __select_vehicle = document.getElementById('vehicle_id_' + data.item);
    const __CellCost = ( __Row != null ? __Row.querySelector('td:nth-child(14)') : null );//ES LA CELDA DONDE SETEAMOS O IMPRIMIMOS EL COSTO OPERATIVO
    
    ( __CellVehicle != null ?  __CellVehicle.dataset.order = data.value : "" );
    ( __CellVehicle != null ?  __CellVehicle.dataset.name = data.name : "" );
    ( __select_vehicle == null && __CellVehicle != null ?  __CellVehicle.innerHTML = data.name : "" );
    
    ( __select_vehicle != null ? __select_vehicle.value = data.value : "" );
    ( __select_vehicle != null ? $('#vehicle_id_' + data.item).selectpicker('val', data.value) : "" );
    
    ( __CellCost != null ? __CellCost.innerHTML = data.cost : "" );
    
    console.log(__select_vehicle, __Row, __CellVehicle, __CellCost);
    Snackbar.show({ 
        text: data.message, 
        duration: 5000, 
        pos: 'top-right',
        actionTextColor: '#fff',
        backgroundColor: '#2196f3'
    });
});

socket.on("setDriverReservationClient", function(data){
    console.log("nueva asignación de conductor");
    console.log(data);
    //DECLARACION DE CONSTANTES
    const __Row = document.getElementById('item-' + data.item);//ROW ES EL TR, DONDE ESTAMOS TRABAJANDO CON EL SELECT DEL CONDUCTOR
    const __CellDriver = ( __Row != null ? __Row.querySelector('td:nth-child(10)') : "" );
    const __select_driver = document.getElementById('driver_id_' + data.item);
        
    ( __CellDriver != null ?  __CellDriver.dataset.order = data.value : "" );
    ( __CellDriver != null ?  __CellDriver.dataset.name = data.name : "" );
    ( __select_driver == null && __CellDriver != null ?  __CellDriver.innerHTML = data.name : "" );
    ( __select_driver != null ? __select_driver.value = data.value : "" );
    ( __select_driver != null ? $('#driver_id_' + data.item).selectpicker('val', data.value) : "" );

    console.log(__select_driver, __Row, __CellDriver);
    Snackbar.show({ 
        text: data.message, 
        duration: 5000, 
        pos: 'top-right',
        actionTextColor: '#fff',
        backgroundColor: '#2196f3'
    });
});

socket.on("updateStatusOperationClient", function(data){
    console.log("operación");
    console.log(data);
    //DECLARACION DE CONSTANTES
    const __Row = document.getElementById('item-' + data.item);//ROW ES EL TR, DONDE ESTAMOS TRABAJANDO CON EL SELECT DEL ESTATUS DE OPERACIÓN
    const __CellStatusOperation = ( __Row != null ? __Row.querySelector('td:nth-child(11)') : "" );
    const __status_operation = document.getElementById('optionsOperation' + data.item);
    const __CellTime = ( __Row != null ? __Row.querySelector('td:nth-child(13)') : "" );
            
    ( __status_operation != null ? __status_operation.classList.remove('btn-secondary', 'btn-success', 'btn-warning', 'btn-danger') : "" );
    ( __status_operation != null ? __status_operation.classList.add(setup.setStatus(data.value)) : "" );
    ( __status_operation != null ? __status_operation.querySelector('span').innerText = data.value : "" );

    ( __CellTime != null ? __CellTime.innerHTML = data.time : "" );

    console.log(__status_operation, __Row, __CellStatusOperation, __CellTime);
    Snackbar.show({ 
        text: data.message,
        duration: 5000, 
        pos: 'top-right',
        actionTextColor: '#fff',
        backgroundColor: '#2196f3'
    });
});

socket.on("updateStatusBookingClient", function(data){
    console.log("reservación");
    console.log(data);
    //DECLARACION DE VARIABLES
    const __Row = document.getElementById('item-' + data.item);//ROW ES EL TR, DONDE ESTAMOS TRABAJANDO CON EL SELECT DEL ESTATUS DE RESERVACIÓN
    const __CellStatusBooking = ( __Row != null ? __Row.querySelector('td:nth-child(14)') : "" );
    const __status_booking = document.getElementById('optionsBooking' + data.item);
        
    ( __status_booking != null ? __status_booking.classList.remove('btn-secondary', 'btn-success', 'btn-warning', 'btn-danger') : "" );
    ( __status_booking != null ? __status_booking.classList.add(setup.setStatus(data.value)) : "" );
    ( __status_booking != null ? __status_booking.querySelector('span').innerText = data.value : "" );

    console.log(__status_booking, __Row, __CellStatusBooking);
    Snackbar.show({
        text: data.message,
        duration: 5000, 
        pos: 'top-right',
        actionTextColor: '#fff',
        backgroundColor: '#2196f3'
    });
});

socket.on("addCommentClient", function(data){
    console.log("comentario");
    console.log(data);

    //DECLARACION DE VARIABLES
    const __btn_comment = document.getElementById('btn_add_modal_' + data.item);
    ( __btn_comment != null ?  __btn_comment.dataset.status = data.status : "" );
    const __comment_new = document.getElementById('comment_new_' + data.item);
    __comment_new.innerHTML = '<div class="btn btn-secondary btn_operations __open_modal_history bs-tooltip w-100 mb-1" title="Ver mensaje de operaciones" data-type="comment" data-comment="'+ data.value +'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-message-circle"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg></div>'

    history();
    setup.bsTooltip();
    Snackbar.show({
        text: data.message,
        duration: 5000,
        pos: 'top-right',
        actionTextColor: '#fff',
        backgroundColor: '#2196f3'
    });
});

socket.on("uploadBookingClient", function(data){
    console.log("upload");
    console.log(data);

    //DECLARACION DE VARIABLES
    const __comment_new = document.getElementById('upload_new_' + data.item);
    __comment_new.innerHTML = '<div class="btn btn-secondary btn_operations __open_modal_media bs-tooltip w-100 mb-1" title="Esta reservación tiene imagenes" data-code="'+ data.reservation +'"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-image"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div>'

    mediaBooking();
    setup.bsTooltip();
    Snackbar.show({
        text: data.message,
        duration: 5000,
        pos: 'top-right',
        actionTextColor: '#fff',
        backgroundColor: '#2196f3'
    });
});

//APLICA CUANDO SE AGREGA UN SERVICIO DESDE EL PANEL DE OPERACIONES
socket.on("addServiceClient", function(data){
    console.log("nuevo servicio");
    console.log(data);
    //DECLARACION DE VARIABLES
    const __btn_comment = document.getElementById('btn_add_modal_' + data.item);
    // const __permission = document.getElementById('permission_reps');
    // if (__permission == null) {
        if( data.success ){
            if( data.today ){
                Swal.fire({
                    text: data.message,
                    showDenyButton: true,
                    showCancelButton: false,
                    confirmButtonText: "Confirmar recargar pagina",
                    denyButtonText: "Cancelar"
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                        location.reload();
                    } else if (result.isDenied) {
                    }
                });
            }else{
                Snackbar.show({
                    text: data.message,
                    duration: 5000, 
                    pos: 'top-right',
                    actionTextColor: '#fff',
                    backgroundColor: '#2196f3'
                });
            }
        }   
    // }
});