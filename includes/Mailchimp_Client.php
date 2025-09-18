<?php 

namespace MC_API;

use WP_REST_Response;
use WP_Error;
use WP_REST_Request;

class Mailchimp_Client{
    private $api_key;
    private $list_id;
    private $datacenter;
    private $base_url;
    private $valid_cred;

    public function __construct($api_key, $list_id){
        $this->api_key = $api_key;
        $this->list_id = $list_id;
        $this->datacenter = $this->extract_datacenter();
        $this->valid_cred = true;
        if($this->datacenter === ''  || $this->list_id === ''){
            $this->valid_cred = false;
        }

        $this->base_url = "https://{$this->datacenter}.api.mailchimp.com/3.0";
    }

    public function upsert_member($email, $merge_fields = [], $status_if_new = 'subscribed'){
        $norm_res = [
                        'ok'=> false,
                        'code' => null,
                        'msg' => null
                    ];

        if(!$this->valid_cred){
            $norm_res = [
                'ok'=> false,
                'code' => 0,
                'msg' => 'Invalid API credentials.'
            ];
            return $norm_res;
        }

        $merge_fields = is_array($merge_fields) ? $merge_fields : [];
        $status_if_new = ($status_if_new === 'pending') ? 'pending' : 'subscribed';

        if(!is_email( $email)){
            $norm_res = [
                'ok'=> false,
                'code' => 0,
                'msg' => 'Email not valid.'
            ];
            return $norm_res;
        }

        $member_hash = md5(trim(strtolower($email)));
        $endpoint = "/lists/{$this->list_id}/members/{$member_hash}";
        $body = [
            'email_address' => $email,
            'status_if_new' => $status_if_new,
            'merge_fields' => (object)($merge_fields ?? [] ),
        ];
         
        $response = $this->request('PUT', $endpoint, $body );
        if($response['error'] instanceof WP_Error){
            return [    
                'ok'=> false,
                'code' => 0,
                'msg' => 'Network error, please try again later.',
                'raw'=> null
            ];
        }

        error_log('Response code: ' . $response['code'] );
        switch(true){
            case $response['code'] === 200:
            case $response['code'] === 201:
                $status = isset($response['json']['status']) ? $response['json']['status'] : ''; 
                $norm_res = [
                    'ok'=> true,
                    'code' => $response['code'],
                    'msg' => $status === 'pending' ? 'Please confirm via email' : 'Subscribed successfully',
                    'raw'  => $response['json'],
                ];
                break;
            case $response['code'] === 400:

                $title  = isset($response['json']['title'])  ? (string) $response['json']['title']  : '';
                $detail = isset($response['json']['detail']) ? (string) $response['json']['detail'] : '';

                $member_exists =
                    (stripos($title, 'member exists')  !== false) ||
                    (stripos($detail, 'member exists') !== false) ||
                    (stripos($detail, 'already a list member') !== false) ||
                    (stripos($detail, 'is already')    !== false);
                if($member_exists){
                    $norm_res = [
                        'ok'=> true,
                        'code' => 200,
                        'msg' => 'You are already in the list',
                        'raw'  => $response['json'],
                    ];
                }else{
                    $norm_res = [
                        'ok'=> false,
                        'code' => $response['code'],
                        'msg' => 'Please check your input',
                        'raw'  => $response['json'],
                    ];
                }
                break;
            case $response['code'] === 401:
                $norm_res = [
                        'ok'=> false,
                        'code' => $response['code'],
                        'msg' => 'Credentials issue—contact site admin.',
                        'raw'  => $response['json'],
                ];
                break;
            case $response['code'] === 404:
                $norm_res = [
                        'ok'=> false,
                        'code' => $response['code'],
                        'msg' => 'List not found—check settings.',
                        'raw'  => $response['json'],
                ];
                break;
            case $response['code'] === 429:
                $retry_after = isset($response['headers']['retry-after']) ? $response['headers']['retry-after'] : null;
                $norm_res = [
                        'ok'=> false,
                        'code' => $response['code'],
                        'msg' => 'Too many requests—try again shortly.',
                        'raw'  => $response['json'],
                        'retry_after' => $retry_after
                ];
                break;
            case ($response['code'] >=500 && $response['code'] <=599):
                $norm_res = [
                        'ok'=> false,
                        'code' => $response['code'],
                        'msg' => 'Service issue—please try again later.',
                        'raw'  => $response['json'],
                ];
                break;
            default:
                $norm_res = [
                        'ok'=> false,
                        'code' => $response['code'],
                        'msg' => 'Unexpected error.',
                        'raw'  => $response['json'],
                ];

        }

        return $norm_res;
        
    }

    private function extract_datacenter(){
        $hyphen_pos = strpos($this->api_key, '-');
        return $hyphen_pos ? substr($this->api_key,$hyphen_pos + 1) : '';
    }

    private function auth_header(){
        return "Basic " . base64_encode('user:'.$this->api_key);
    }

    private function request($method, $path, $body = null ){
        $url = $this->base_url . $path;
        $response = wp_remote_request( 
                $url,
                [
                    'method' => $method,
                    'headers' => 
                    [
                        'Authorization' => $this->auth_header(),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'User-Agent' => 'mc-api-integration/0.1.0; ' . get_bloginfo('url'),
                    ],
                    'body' => $body ? wp_json_encode($body) : null,
                    'timeout' => 12,
                    'sslverify' => true
                ]   
            );

        if(is_wp_error( $response )){
            return 
            [
                'error' => $response,
                'code' => null,
                'json' => null
            ];
        }else{
            $code = wp_remote_retrieve_response_code($response);
            $json = json_decode(wp_remote_retrieve_body($response),true);
            return 
            [
                'error' => null,
                'code' => $code,
                'json' => is_array($json) ? $json : ''
            ];
        }
        
        
    }


}