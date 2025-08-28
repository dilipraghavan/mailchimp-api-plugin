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
                            'subscribe',
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
                                ]
                            ]
                            );
    }

    public static function subscribe($request){
        $email = $request->get_param('email');
        $consent = $request->get_param('consent');

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Recieved. We will process your subscription.',
            'data' => ['email_hash' => md5(strtolower(trim($email)))]
        ],200);
    }

    public static function verify_nonce($request){
        $nonce = $request->get_header('X-WP-Nonce');
        $nonce_valid = wp_verify_nonce($nonce, 'wp_rest');
        if($nonce_valid){
             return true;
        }else{
            return new WP_Error('rest_forbidden', 'Invalid or missing nonce.', ['status' => 403]);
        }
    }

} 