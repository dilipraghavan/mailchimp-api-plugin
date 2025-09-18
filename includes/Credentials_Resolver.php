<?php
namespace MC_API;

class Credentials_Resolver {

    private static function credentials_resolver(){
        if(defined('MAILCHIMP_API_KEY') && defined('MAILCHIMP_LIST_ID')){
            return [
                'api_key' => trim(sanitize_text_field(MAILCHIMP_API_KEY)),
                'list_id' => trim(sanitize_text_field(MAILCHIMP_LIST_ID)),
                'src' => 'constant'
            ];
        }
        if(getenv('MAILCHIMP_API_KEY') && getenv('MAILCHIMP_LIST_ID')){
            return [
                'api_key' => trim(sanitize_text_field(getenv('MAILCHIMP_API_KEY'))),
                'list_id' => trim(sanitize_text_field(getenv('MAILCHIMP_LIST_ID'))),
                'src' => 'env'
            ];
        }
        if(get_option('mailchimp_api_key') && get_option('mailchimp_list_id')){
            return [
                'api_key' => trim(sanitize_text_field(get_option('mailchimp_api_key'))),
                'list_id' => trim(sanitize_text_field(get_option('mailchimp_list_id'))),
                'src' => 'options'
            ];
        }

        return [
            'api_key' => '',
            'list_id' => '',
            'src' => ''
        ];
    }

    public static function get_credentials(){
        return self::credentials_resolver();
    }
}