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

      fetch(`/tpv/autocomplete/${keyword}/?uuid=${__uuid.value}`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json'
          }
      }).then((response) => {
          return response.json()
      }).then((data) => {
          setup.makeItems(data,element);
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

    if(element === "aff-input-from-elements"){
        const initInput = document.getElementById('aff-input-from');
        initInput.value = data.name;
        setup.items.from.name = data.name;
        setup.items.from.latitude = data.geo.lat;
        setup.items.from.longitude = data.geo.lng;
        
        var fromLat = document.getElementsByName("from_lat");
            fromLat[0].value = data.geo.lat;
        var fromLng = document.getElementsByName("from_lng");
            fromLng[0].value = data.geo.lng;

    }

    if(element === "aff-input-to-elements"){
        const initInput = document.getElementById('aff-input-to');
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
  saveHotel: function(element, _data){
      let item = {
        name: _data.name,
        address: _data.address,
        start: {
          lat: _data.geo.lat,
          lng: _data.geo.lng,
        },
      };

      $.ajax({
        url: 'https://api.taxidominicana.com/api/v1/hotels/add',
        type: 'POST',
        data: item,
        beforeSend: function() {
          setup.loadingMessage(element);
        },
        success: function(resp) {
          setup.setItem(element, _data);
          alert("Hotel agregado con éxito...");
          const finalElement = document.getElementById(element);
          finalElement.innerHTML = '';          
        },
    }).fail(function(xhr, status, error) {
      Swal.fire(
          'Zona no registrada',
          `No se pudo agregar el hotel, ya que no se tiene ninguna zona que coincida con las coordenadas: (${item.start.lat} ${item.start.lng})`,
          'error'
        );
      console.log(error);
    });
  },
  resetServiceType: function(){
    const _toggleServices = document.querySelectorAll('.aff-toggle-type');
    _toggleServices.forEach(btn => {
      btn.classList.remove('active');
    });    
  },
  resetCurrency: function(){
    const _toggleCurrencys = document.querySelectorAll('.aff-toggle-currency');
    _toggleCurrencys.forEach(btn => {
      btn.classList.remove('active');
    });    
  },
  getLoader: function() {
    return '<span class="container-loader"><i class="fa-solid fa-spinner fa-spin-pulse"></i></span>';
  },
  setTotal: function(total){
    const _total = document.getElementById('formTotal');
    
    if( document.getElementById('formPaymentMethod').value === 'CORTESIA' ) {
      _total.value = 0;
      _total.setAttribute('readonly', true);
    }
    else {
      _total.value = total;
      _total.removeAttribute('readonly');
    }
  },  
  setCortesiaTotal: function(){
    const _total = document.getElementById('formTotal');
    _total.value = 0;
    _total.setAttribute("readonly", true);
  },  
  actionSite: function(__site){
    const __reference       = document.getElementById('formReference');
    const __name            = document.getElementById('formName');
    const __lastname        = document.getElementById('formLastName');
    const __email           = document.getElementById('formEmail');
    const __phone           = document.getElementById('formPhone');
    const __isQuotation     = document.getElementById('formIsQuotation');
    const __paymentMethod   = document.getElementById('formPaymentMethod');
    const __originSale      = document.getElementById('formOriginSale');
    const selectedOption    = __site.options[__site.selectedIndex];

    if( selectedOption.getAttribute('data-type') == "AGENCY" || selectedOption.getAttribute('data-type') == "STAFF" ){
      if( selectedOption.getAttribute('data-type') == "STAFF" ){
        __name.value          = 'STAFF';
        __lastname.value      = 'CT';
      }

      __email.value           = selectedOption.getAttribute('data-email');
      __phone.value           = selectedOption.getAttribute('data-phone');
      __isQuotation.value     = 0;
      __paymentMethod.value   = "CARD";
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
      __isQuotation.setAttribute('readonly', true);
      __paymentMethod.setAttribute('readonly', true);
    }else{
      __name.value            = '';
      __lastname.value        = '';
      __email.value           = '';
      __phone.value           = '';
      __reference.value       = '';
      __isQuotation.value     = 0;
      __paymentMethod.value   = "CASH";
      $("#formOriginSale").selectpicker('val', '');

      __reference.setAttribute('readonly', true);
      __name.removeAttribute('readonly');
      __lastname.removeAttribute('readonly');
      __email.removeAttribute('readonly');
      __phone.removeAttribute('readonly');
      __isQuotation.removeAttribute('readonly');
      __paymentMethod.removeAttribute('readonly');
    }
  }
};

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

const __uuid = document.getElementById('uuid') || { value: '' };
var from_autocomplete = document.getElementById('aff-input-from');
var to_autocomplete = document.getElementById('aff-input-to');
const _formQuotation = document.getElementById('formQuotation');

document.addEventListener('DOMContentLoaded', function() {
  const fechaInput = document.getElementById('bookingPickupForm');
  const rangeCheckbox = document.getElementById('flexSwitchCheckDefault');

  from_autocomplete.addEventListener('keydown', affDelayAutocomplete(function (e) {
    setup.autocomplete( e.target.value, 'aff-input-from-elements');
  }, 500));
  from_autocomplete.addEventListener('focus', (e) => {
    setup.autocomplete( e.target.value, 'aff-input-from-elements');
  });
  to_autocomplete.addEventListener('keydown', affDelayAutocomplete(function (e) {
    setup.autocomplete( e.target.value, 'aff-input-to-elements');
  }, 500));
  to_autocomplete.addEventListener('focus', (e) => {
    setup.autocomplete( e.target.value, 'aff-input-to-elements');
  });  

  let pickerInit = flatpickr("#bookingPickupForm", {
    mode: "single",
    dateFormat: "Y-m-d H:i",
    enableTime: true,
    minDate: "today"
  });
  let pickerEnd = flatpickr("#bookingDepartureForm", {
    mode: "single",
    dateFormat: "Y-m-d H:i",
    enableTime: true,
    minDate: "today"
  });

  document.addEventListener('click', function (event) {
    if (event.target && event.target.id === 'clearBooking') {
      _formQuotation.reset();
    }

    if ( event.target && event.target.classList.contains('aff-toggle-type') ) {
      event.preventDefault();
      setup.resetServiceType();
      const _service = document.getElementById('flexSwitchCheckDefault');
      const _elements = document.querySelector('.elements');
      const _round_trip_element = document.getElementById("departureContainer");

      // Obtener datos del elemento clickeado
      const { type } = event.target.dataset;

      if(type == "RT"){
        _elements.classList.add(type);
        _round_trip_element.classList.remove("d-none");
        _service.value = 1;
      }else{
        _elements.classList.remove('RT');
        _round_trip_element.classList.add("d-none");
        _service.value = 0;
      }
      event.target.classList.add('active');
      const form = document.getElementById('formReservation');
      if (form) {
       const btnQuote = document.getElementById('btnQuote');
       btnQuote.click();
      }
    }

    if ( event.target && event.target.classList.contains('aff-toggle-currency') ) {
      event.preventDefault();
      setup.resetCurrency();
      const _currency = document.getElementById('bookingCurrencyForm');

      // Obtener datos del elemento clickeado
      const { currency } = event.target.dataset;
      _currency.value = currency;
      event.target.classList.add('active');
      const form = document.getElementById('formReservation');
      if (form) {
       const btnQuote = document.getElementById('btnQuote');
       btnQuote.click();
      }
    }
  })

  document.addEventListener('change', function(event){
    if( event.target && event.target.classList.contains('checkButton') ){
      // Obtener datos del elemento clickeado
      const { total } = event.target.dataset;
      setup.setTotal(total);
    }

    if( event.target && event.target.id === 'formPaymentMethod' ) {
      if( event.target.value === 'CORTESIA' ) {
        setup.setTotal(0);
      }
      else {
        const checkedButtonDataset = document.querySelector('.checkButton:checked')?.dataset;
        const _total = document.getElementById('formTotal');

        if(checkedButtonDataset && _total.value == 0) setup.setTotal(checkedButtonDataset.total);
      }
    }
  });

  document.addEventListener('submit', function(event) {
    if (event.target && event.target.id === 'formQuotation') {
      event.preventDefault();    
      const _loadContent = document.getElementById('loadContent');
      const _formData     = new FormData(_formQuotation);
      _formData.append('uuid', __uuid.value);
      const _sendQuote   = document.getElementById('btnQuote');
      _sendQuote.disabled = true;
      _sendQuote.textContent = "Cotizando...";
      _loadContent.innerHTML = setup.getLoader();
      
      Swal.fire({
        title: "Procesando solicitud...",
        text: "Por favor, espera mientras se realiza la cotización.",
        allowOutsideClick: false,
        allowEscapeKey: false, // Esta línea evita que se cierre con ESC
        didOpen: () => {
          Swal.showLoading();
        }
      });
  
      fetch('/tpv/quote', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: _formData
      })
      .then(response => {
          if (!response.ok) {
            return response.json().then(err => { throw err; });
          }        
          return response.text();
      })
      .then(data => {
        Swal.close();
        
        // Mostrar resultado
        loadContent.innerHTML = data;
        window.scrollTo({
          top: _loadContent.offsetTop,
          behavior: 'smooth'
        });
    
        // Ejecutar función adicional si existe
        if (typeof loaderSites === 'function') {
          loaderSites();
        }

        $('.selectpicker').selectpicker({
          liveSearch: true,
          liveSearchNormalize: true,
          size: 'integer'
        });
    
        _sendQuote.disabled = false;
        _sendQuote.textContent = "Cotizar";
      })
      .catch(error => {
        Swal.fire(
          '¡ERROR!',
          error.message || 'Ocurrió un error',
          'error'
        );
        _sendQuote.disabled = false;
        _sendQuote.textContent = "Cotizar";
        _loadContent.innerHTML = "";
      });
    }

    if (event.target && event.target.id === 'formReservation') {
      event.preventDefault();
      const _formReservation = document.getElementById('formReservation');
      const _formData     = new FormData(_formReservation);
      _formData.append('uuid', __uuid.value);
      const _sendReservation   = document.getElementById('sendReservation');
      _sendReservation.disabled = true;
      _sendReservation.textContent = "Enviando...";
  
      Swal.fire({
        title: "Procesando solicitud...",
        text: "Por favor, espera mientras se crea la reservación.",
        allowOutsideClick: false,
        allowEscapeKey: false, // Esta línea evita que se cierre con ESC
        didOpen: () => {
          Swal.showLoading();
        }
      });
  
      fetch('/tpv/create', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: _formData
      })
      .then(response => {
          if (!response.ok) {
            return response.json().then(err => { throw err; });
          }        
          return response.json();
      })
      .then(data => {
        Swal.close();      
        window.location.replace(`/reservations/detail/${data.config.id}`);
        _sendReservation.disabled = false;
        _sendReservation.textContent = "Enviar";
      })
      .catch(error => {
        Swal.fire(
          '¡ERROR!',
          error.message || 'Ocurrió un error',
          'error'
        );
        _sendReservation.disabled = false;
        _sendReservation.textContent = "Enviar";
      });
    }
  }, true); // <- el `true` activa "capturing" y sí detecta el submit
});

function loaderSites(){
  const __site = document.getElementById('formSite');
  if( __site != null ){
    setup.actionSite(__site);
    __site.addEventListener('change', function(event){
      event.preventDefault();
      setup.actionSite(__site);    
    });
  }
}