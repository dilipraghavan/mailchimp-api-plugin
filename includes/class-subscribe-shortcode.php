<?php
namespace MC_API;

class Shortcode{

    private static $should_enqueue = false;

    public static function init(){
        add_action('init', [__CLASS__ , 'register'] );
        add_action('wp_enqueue_scripts', [__CLASS__ , 'maybe_enqueue_assets'] );
    }


    public static function register(){
        add_shortcode( 'mc_subscribe_form', [__CLASS__, 'render_subscription_form'] );
    }

    public static function maybe_enqueue_assets(){
        if(!self::$should_enqueue) return;

        wp_enqueue_script(
                            'mc_api_subscribe_script',
                            MC_API_PLUGIN_URL_PATH .'assets/js/mc-subscribe.js',
                            [],
                            MC_API_PLUGIN_VERSION,
                            true
                        );

        $cfg = [
            'endpoint' => esc_url_raw(rest_url("mc-api/v1/subscribe")),
            'nonce' => wp_create_nonce('wp_rest'),
            'msgs' => [
                'submitting' => 'Submitting...',  
                'genericError' => 'Something went wrong. Please try again.',  
                'ok' => 'Thanks. Check your inbox.',  
            ]
        ];
        wp_add_inline_script( 
                                'mc_api_subscribe_script',
                                'window.MC_API_CFG = ' . wp_json_encode($cfg) . ';',
                                'before' 
                            );


        wp_enqueue_style( 
                            'mc-api-subscribe-style',
                            MC_API_PLUGIN_URL_PATH . 'assets/css/mc-subscribe.css',
                            [],
                            MC_API_PLUGIN_VERSION,
        );                
        
    }

    public static function render_subscription_form($atts, $content){

        self::$should_enqueue = true;

        $atts = shortcode_atts( 
            [
                'consent_label' => 'I agree to receive emails.',
                'button_text' => 'Subscribe'
            ],
            $atts,
            'mc_subscribe_form'
        );

        $admin_post_status = isset($_GET['mc_status']) ? sanitize_text_field($_GET['mc_status']) : '';
        $admin_post_msg = isset($_GET['mc_msg']) ? sanitize_text_field($_GET['mc_msg']) : '';

        $sub_form = "";
        $sub_form_action = admin_url("admin-post.php");
        $sub_form_rest = rest_url("mc-api/v1/subscribe");
        $sub_form .= "<form id='mc_form' method='POST' action='" . esc_url($sub_form_action) . "' data-endpoint='" . esc_url($sub_form_rest) . "' >";
       
        $sub_form .= "<input type='hidden' name='action' value='mc_api_subscribe' />";
        
        $mc_nonce = wp_create_nonce('wp_rest');
        $sub_form .= "<input type='hidden' name='mc_nonce' value='" . esc_attr($mc_nonce) . "' />";
        
        $sub_form .= "<input id='mc_hp' name='mc_hp' class='mc-api-hp' aria-hidden='true' tabindex='-1' autocomplete='off'/>";
       
        $consent_label = esc_html($atts['consent_label']);
        $sub_form .= "<div class='mc-consent-field'>";
        $sub_form .= "<label for='mc_consent' >{$consent_label}</label>";
        $sub_form .= "<input id='mc_consent' type='checkbox' name='mc_consent' value='enabled' required >";
        $sub_form .= "</div>";
        
        $sub_form .= "<div class='mc-email-field'>";
        $sub_form .= "<label for='mc_email' >Email</label>";
        $sub_form .= "<input id='mc_email' type='email' name='mc_email' required >";
        $sub_form .= "</div>";

        $safe_msg = esc_html($admin_post_msg);
        $sub_form .= "<div aria-live='polite' class='mc-api-msg'>{$safe_msg}</div>";

        $sub_form .= "<div class='mc-button-field'>";        
        $button_text = esc_html($atts['button_text']);
        $sub_form .= "<button id='mc_submit_btn' type='submit'>{$button_text}</button>";
        $sub_form .= "</div>";

        $sub_form .= "</form>";
        return $sub_form;
    }

}