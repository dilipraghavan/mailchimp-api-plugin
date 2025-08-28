<?php 

namespace MC_API;

class Admin_Post_Subscribe{
    public static function init(){
        add_action('admin_post_mc_api_subscribe', [__CLASS__, 'handle']);
        add_action('admin_post_nopriv_mc_api_subscribe', [__CLASS__, 'handle']);
    }

    public static function handle(){
        $nonce = isset($_POST['mc_nonce']) ? sanitize_text_field(wp_unslash($_POST['mc_nonce'])) : '';
        if(!wp_verify_nonce( $nonce, 'wp_rest')){
            self::redirect('bad_nonce', 'Invalid or missing security token');
        }

        $hp_set = isset($_POST['mc_hp']) ? sanitize_text_field(wp_unslash($_POST['mc_hp'])) : '';
        if($hp_set !== ''){
            self::redirect('spam', 'Spam Detected');
        }

        $mc_consent = isset($_POST['mc_consent']) ? wp_unslash($_POST['mc_consent']) : '';
        $consented = in_array($mc_consent,['enabled', 'on', '1'], true);
        if(!$consented){
            self::redirect('no_consent', 'Please agree to get emails.');
        }

        $mc_email = isset($_POST['mc_email']) ? sanitize_email(wp_unslash($_POST['mc_email']))  : '';
        if($mc_email === '' || !is_email($mc_email)){
            self::redirect('no_email', 'Please provide email address.');
        }
        self::redirect('ok', 'Please check your inbox.');
    }

    private static function redirect($mc_status, $mc_msg){
        $redirect = wp_get_referer();
        if(!$redirect){
            $redirect = home_url('/');
        }

        $redirect = add_query_arg(  
            [
                'mc_status' => $mc_status,
                'mc_msg' => $mc_msg,
            ], $redirect
        );
        wp_safe_redirect($redirect);
        exit;
    }
}