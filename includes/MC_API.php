<?php 

namespace MC_API;

class MC_API{

    public function __construct(){
        add_action('plugins_loaded', [$this, 'load_text_domain'] );
    }

    public function load_text_domain(){
        load_plugin_textdomain('mailchimp-api-integration', false, dirname(MC_API_BASENAME) . 'languages' );
    }

}