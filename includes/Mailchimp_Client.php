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
        $norm_res = $this->get_normalized_response($response);
        
        if($norm_res['ok']){
            $status = isset($norm_res['raw']['status']) ? $norm_res['raw']['status'] : ''; 
            $norm_res['msg'] = $status === 'pending' ? 'Please confirm via email' : 'Subscribed successfully';
        } else if ($norm_res['code'] === 400){
            $title  = isset($norm_res['raw']['title'])  ? (string) $norm_res['raw']['title']  : '';
            $detail = isset($norm_res['raw']['detail']) ? (string) $norm_res['raw']['detail'] : '';

            $member_exists =
                (stripos($title, 'member exists') !== false) ||
                (stripos($detail, 'member exists') !== false) ||
                (stripos($detail, 'already a list member') !== false) ||
                (stripos($detail, 'is already') !== false);
            
            if($member_exists){
                $norm_res['ok'] = true;
                $norm_res['code'] = 200;
                $norm_res['msg'] = 'You are already in the list';
            } else {
                $norm_res['msg'] = 'Please check your input';
            }
        }

        return $norm_res;
        
    }

    private function get_normalized_response($response) {
        $norm_res = [
            'ok' => false,
            'code' => null,
            'msg' => null,
            'raw' => null
        ];

        if(is_wp_error($response['error'])){
            $norm_res['msg'] = 'Network error, please try again later.';
            if(defined('WP_DEBUG') && WP_DEBUG){
                error_log('[MC_API]Network error: ' . $response['error']->get_error_message());
            }
            return $norm_res;
        }

        $norm_res['code'] = $response['code'];
        $norm_res['raw'] = $response['json'];

        switch($norm_res['code']){
            case 200:
            case 201:
                $norm_res['ok'] = true;
                $norm_res['msg'] = 'Request successful.';
                break;
            case 400:
                $norm_res['msg'] = 'A general input error occurred.';
                break;
            case 401:  
                $norm_res['msg'] = 'Unauthorized: Invalid API key.';
                break; 
            case 404:  
                $norm_res['msg'] = 'Resource not found.';
                break; 
            case 429:
                $norm_res['msg'] = 'Too many requests—try again shortly.';
                break;
            case ($norm_res['code'] >= 500 && $norm_res['code'] <= 599):
                $norm_res['msg'] = 'Service issue—please try again later.';
                break;
            default:
                $norm_res['msg'] = "An unexpected error occurred. (HTTP {$norm_res['code']})";
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

    public function get($endpoint) {
        $response  = $this->request('GET', $endpoint);
        return $this->get_normalized_response($response);
    }


    public function is_valid_key() {
        return !empty($this->datacenter);
    }

}