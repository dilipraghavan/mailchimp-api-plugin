<?php
namespace MC_API;

use WP_REST_Response;
use WP_Error;

class Rest_Subscribe{

    public static function init(){
        add_action('rest_api_init', [__CLASS__, 'register']);
    }

    public static function register(){
        register_rest_route ( 
                            'mc-api/v1',
                            '/subscribe',
                            [
                                'methods' => 'POST',
                                'callback' => [__CLASS__ , 'subscribe'],
                                'permission_callback' => [__CLASS__ , 'verify_nonce'],
                                'args' => 
                                [
                                    'email' => [
                                        'required' => true,
                                        'type' => 'string',
                                        'sanitize_callback' => 'sanitize_email',
                                        'validate_callback' => function($value){
                                            return is_email($value) ? true : new WP_Error('rest_invalid_param', 'Invalid email address.', ['status' => 400]);
                                        }

                                    ],
                                    'consent' => [
                                        'required' => true,
                                        'type' => 'boolean',
                                        'sanitize_callback' => function($value){
                                            return rest_sanitize_boolean($value);
                                        },
                                        'validate_callback' => function($value){
                                            return $value ? true : new WP_Error('rest_invalid_param', 'Consent is required.', ['status' => 400]);
                                        }
                                    ],
                                    'hp' => [
                                        'required' => false,
                                        'type' => 'string',
                                        'sanitize_callback' => 'sanitize_text_field',
                                        'validate_callback' => function($value){
                                            return empty($value) ? true : new WP_Error('rest_forbidden', 'Spam Detected.', ['status' => 403]);
                                        }
                                    ],
                                    'mc_nonce' => [
                                        'required' => true,
                                        'type' => 'string',
                                        'sanitize_callback' => 'sanitize_text_field',
                                    ],

                                ]
                            ]
                            );
    }

    public static function subscribe($request){

        $email = $request->get_param('email');
        $consent = $request->get_param('consent');

        if(!$consent){
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Consent required.',
            ]);
        }

        $cred = Credentials_Resolver::get_credentials();
        $api_key = $cred['api_key'];
        $list_id = $cred['list_id'];
        
        $client = new Mailchimp_Client($api_key, $list_id);
        
        $double_optin = get_option('mailchimp_double_optin', 'yes');
        $status = $double_optin === 'yes' ? 'pending' : 'subscribed';
        $res = $client->upsert_member($email, [], $status);

        if(isset($res['raw'])){
            error_log('raw :' . print_r($res['raw'],true));
        }else{
            error_log('raw not set');
        }

        if($res['ok']){
            Logger::add([
                'event_type' => 'subscribe',
                'http_code' => (int)($res['code'] ?? 200),
                'endpoint' => 'mc-api/v1/subscribe',
                'email_hash' => md5(strtolower(trim($email))),
                'message' => $res['msg'] ?? 'ok',
                'meta' => ['src' => 'rest'],
            ]);
            return new WP_REST_Response([
                'success' => true,
                'message' => $res['msg'],
            ],200);
        }else{
            Logger::add([
                'event_type' => 'error',
                'http_code' => (int)($res['code'] ?? 0),
                'endpoint' => 'mc-api/v1/subscribe',
                'email_hash' => md5(strtolower(trim($email))),
                'message' => $res['msg'] ?? 'error',
                'meta' => ['src' => 'rest'],
            ]);
            $code = $res['code'] ?: 500;
            return new WP_REST_Response([
                'success' => false,
                'message' => $res['msg'],
            ], $code);
        }

    }

    public static function verify_nonce($request){
        $nonce = $request->get_param('mc_nonce');
        $ok = wp_verify_nonce($nonce, 'mc_api_public');
        return $ok ? true : new WP_Error('rest_forbidden','Invalid or missing nonce.',['status'=>403]);
    }


} 