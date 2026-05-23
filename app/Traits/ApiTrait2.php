<?php

namespace App\Traits;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

//FACADES
use Illuminate\Support\Facades\Session;

//PACKAGES
use GuzzleHttp\Client;

//EXCEPTION
use Exception;

trait ApiTrait2
{   
    public static $site_id = 1;
    public static $api_key = 'bb65be85-82f9-492f-bbd6-4a698509106a';
    public static $user = 'api';
    public static $password = '1234567890';
    public static $types = [
        "OW" => "one-way",
        "RT" => "round-trip",
    ];

    public static function autocomplete($keyword){
        $headers[] = 'app-key: ' . self::$api_key;
        return self::sendRequest('/api/v1/autocomplete-affiliates', 'POST', ['keyword' => $keyword], $headers);
    }

    public static function empty(){
        $tpv = [
            "type" => "one-way",
            "start" => [
                "place" => "",
                "lat" => "",
                "lng" => "",
                "pickup" => date("Y-m-d H:i"),
            ],
            "end" => [
                "place" => "",
                "lat" => "",
                "lng" => "",
                "pickup" => NULL,
            ],
            "language" => "en",
            "passengers" => 1,
            "currency" => "USD",
            "rate_group" => "xLjDl18", //Grupo de tarifa por defecto...
        ];
        return $tpv;
    }

    public static function init(){
        $response = [
            "status" => false
        ];

        $data = self::sendRequest('/api/v1/oauth', 'POST', array( 'user' => self::$user, 'secret' => self::$password ));        

        if(isset( $data['error'] )):
            $response['code'] = $data['error']['code'];
            $response['message'] = $data['error']['message'];
            return $response;
        endif;
        
        $response['status'] = true;
        $response['data'] = $data;
        return $response;
    }

    public static function checkToken(){
        if (!Session::has('tpv2')):
            $token = self::init();
            $tpv['token'] = [
                "token" => $token['data']['token'],
                "expires_in" => date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . " + " . $token['data']['expires_in'] . " seconds"))
            ];
            Session::put('tpv2', $tpv);
        else:
            $tpv = Session::get('tpv');
            if(isset( $tpv['token']['expires_in'] ) ):
                $nowDate = date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s") . ' - 1440 minutes'));
                if($nowDate <= $tpv['token']['expires_in']):
                    $token = self::init();
                    $tpv['token'] = [
                        "token" => $token['data']['token'],
                        "expires_in" => date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . " + " . $token['data']['expires_in'] . " seconds"))
                    ];
                    Session::put('tpv2', $tpv);
                endif;
            endif;
        endif;
    }

    public static function quote($quote = []){
        $auth = session()->get('auth');
        $headers[] = 'Authorization: Bearer ' . $auth['token'];
       
        $data = [
            'type' => self::$types[ $quote['type'] ],
            'language' => $quote['language'],
            'passengers' => $quote['passengers'],
            'currency' => $quote['currency'],
            'rate_group' => 'xLjDl18',
            'start' => [
                'place' => $quote['from']['name'],
                'lat' => $quote['from']['lat'],
                'lng' => $quote['from']['lng'],
                'pickup' => $quote['from']['pickupDate'],
            ],
            'end' => [
                'place' => $quote['to']['name'],
                'lat' => $quote['to']['lat'],
                'lng' => $quote['to']['lng'],
            ]
        ];

        if($quote['type'] == "RT"):
            $data['end']['pickup'] = $quote['to']['pickupDate'];
        endif;

        return self::sendRequest('/api/v1/quote', 'POST', $data, $headers);
    }

    public static function make($request){
     
        $data = [
            "site_id" => self::$site_id,
            "service_token" => $request->service_token,
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "email_address" => $request->email,
            "phone" => $request->phone,
            "special_request" => $request->special_request,
            "flight_number" => $request->flight_number ?? '',
            "call_center_agent" => $request->id,
            'is_commissionable' => 1,
        ];

        $auth = session()->get('auth');
        $headers[] = 'Authorization: Bearer ' . $auth['token'];    

        return self::sendRequest('/api/v1/create', 'POST', $data, $headers);
    }

    public static function paymentLink($data = []){
        $auth = session()->get('auth');
        $headers[] = 'Authorization: Bearer ' . $auth['token'];

        return self::sendRequest('/api/v1/reservation/payment/handler', 'GET', $data, $headers);
    }

    public static function checkReservation($data){
        return self::sendRequest('/api/v1/reservation/get', 'POST', $data, []);
    }

    public static function sendRequest($end_point, $method = 'GET', $data = null, $headers_merge = []) {
        $headers = array(
            'Content-Type: application/json',
        );

        $headers = array_merge($headers, $headers_merge);

        $url = ( config('app.env') == 'local' ? 'https://api.taxidominicana.com' : 'https://api.taxidominicana.com' ).$end_point;
        // $url = ( config('app.env') == 'local' ? 'https://transportation-api.up.railway.app' : 'https://transportation-api.up.railway.app' ).$end_point;
        $ch = curl_init($url);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        if ($method == 'GET') {
            if ($data) {
                $url .= '?' . http_build_query($data);
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}