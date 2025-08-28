<?php 
namespace MC_API;

class Settings {

    public static function init(){
        add_action('admin_menu', [__CLASS__, 'add_menu_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_post_' . MC_API_ACTIONS_SLUG, [__CLASS__, 'handle_test_connection']);
    }

    public static function add_menu_page(){
        add_options_page( 
                            'Mailchimp API Integration', 
                            'Mailchimp API Integration', 
                            'manage_options',
                            MC_API_SETTINGS_SLUG,
                            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(){

        if($api_data = get_transient( 'mc_api_notice' )){
            $msg = esc_html($api_data['msg']);
            $type = esc_html($api_data['type']);
            $banner_class = 'notice-info';
            if($type === 'success') 
                $banner_class = 'notice-success';

            if($type === 'error') 
                $banner_class = 'notice-error';

            echo"<div class='notice {$banner_class} is-dismissible'><p>{$msg}</p></div>";
            delete_transient('mc_api_notice');
        }

        
        echo "<div class='wrap'>";
        echo "<h1>Mailchimp API Integration</h1>";
        
        $creds = Settings::credentials_resolver();
        if($creds['src'] === 'constant'){
            echo "<p class='description'>Using constants from wp-config.php.</p>";
        }
        if($creds['src'] === 'env'){
            echo "<p class='description'>Using environment variables.</p>";
        }

        settings_errors();

        //Form for API settings
        echo "<form method='post' action='options.php'>";
        settings_fields('mc_api_settings_group');
        do_settings_sections(MC_API_SETTINGS_SLUG);
        submit_button("Save Changes");
        echo "</form>";


        // Form to test connection
        $action_slug = esc_url(admin_url('admin-post.php?action=' . MC_API_ACTIONS_SLUG)); 
        echo "<form method='POST' action='{$action_slug}'>";

        wp_nonce_field('mc_api_conn', 'mc_api_nonce');
        echo "<button type='submit'>Test Connection</button>";
        echo "</form>";
        echo "</div>";
    }

    public static function register_settings(){
        register_setting('mc_api_settings_group', 'mailchimp_api_key', ['sanitize_callback' => [__CLASS__, 'mc_sanitize_api_key']]);
        register_setting('mc_api_settings_group', 'mailchimp_list_id', ['sanitize_callback' => [__CLASS__, 'mc_sanitize_list_id']]);

        add_settings_section('mc_api_credentials_section', 'Mailchimp Credentials', null, MC_API_SETTINGS_SLUG);
        
        add_settings_field('mc_api_key', 'API key', [__CLASS__, 'mc_api_key_callback'], MC_API_SETTINGS_SLUG, 'mc_api_credentials_section');
        add_settings_field('mc_list_id', 'List Id', [__CLASS__, 'mc_list_id_callback'], MC_API_SETTINGS_SLUG, 'mc_api_credentials_section');

    }

    public static function mc_sanitize_api_key($input){

        $sanitized_input = sanitize_text_field($input); 
        $trim_input = trim($sanitized_input);

        $valid_api_key = false;
        
        if(strlen($trim_input) !== 0){
            $hyphen_pos = strpos($trim_input, '-');
            $space_pos = strpos($trim_input, ' ');
            if($hyphen_pos !== false && $space_pos === false){
                $suffix = substr($trim_input, $hyphen_pos+1);
                if(strlen($suffix) > 0){    
                    $valid_api_key = true;
                }
            }
        }
        
        if($valid_api_key){
            return $trim_input;
        }else{
            add_settings_error( 
                'mc_api_key',
                'mc_api_invalid_key_error',
                'Invalid API key',
                'error'
            );
            return get_option('mailchimp_api_key', '');
        }

    }

    public static function mc_api_key_callback(){
        $creds = Settings::credentials_resolver();
        $mc_api_key = isset($creds['api_key']) ? esc_attr($creds['api_key']) : '';
        $mc_src = $creds['src'];
        $disabled= $mc_src !== 'options' ? 'disabled' : '';
        echo "<input type='text' class='regular-text' name='mailchimp_api_key' value='{$mc_api_key}' {$disabled} autocomplete='off' >";
        if($mc_src !== 'options'){
            echo "<p>Override by removing constants and env vars.</p>";
        }
    }

    public static function mc_sanitize_list_id($input){
        
        $sanitized_input = sanitize_text_field($input); 
        $trim_input = trim($sanitized_input);

        $valid_list_id = true;
        
        if(strlen($trim_input) === 0)
            $valid_list_id = false;


        $space_pos = strpos($trim_input, ' ');
        if($space_pos !== false)
            $valid_list_id = false;
        
        if($valid_list_id){
            return $trim_input;
        }else{
            add_settings_error( 
                'mc_list_id',
                'mc_api_invalid_list_id_error',
                'Invalid List Id',
                'error'
            );
            return get_option('mailchimp_list_id', '');
        }
    }

    public static function mc_list_id_callback(){
        $creds = Settings::credentials_resolver();
        $mc_list_id = isset($creds['list_id']) ? esc_attr($creds['list_id']) : '';
        $mc_src = $creds['src'];
        $disabled= $mc_src !== 'options' ? 'disabled' : '';
        echo "<input type='text' class='regular-text' name='mailchimp_list_id' value='{$mc_list_id}' {$disabled} autocomplete='off' >";
        if($mc_src !== 'options'){
            echo "<p>Override by removing constants and env vars.</p>";
        }

    }

    public static function handle_test_connection(){
        if(!current_user_can( 'manage_options')){
            $notice = ['type'=>'error' , 'msg'=>'Not allowed.'];
            set_transient('mc_api_notice', $notice, 30 );
            wp_safe_redirect(admin_url('options-general.php?page='. MC_API_SETTINGS_SLUG));
            exit;
        }

        $nonce = isset($_POST['mc_api_nonce']) ? wp_unslash($_POST['mc_api_nonce']) : '';
        if(!wp_verify_nonce($nonce, 'mc_api_conn')){
            $notice = ['type'=>'error' , 'msg'=>'Error.'];
            set_transient('mc_api_notice', $notice, 30 );
            wp_safe_redirect(admin_url('options-general.php?page='. MC_API_SETTINGS_SLUG));
            exit;
        }

        $credentials = Settings::credentials_resolver();

        $list_id = $credentials['list_id'];
        $api_key = $credentials['api_key'];
        $src = $credentials['src'];
        $credentials_present = $api_key !== '' && $list_id !== '' && $src !== '';
        if(!$credentials_present){
            $notice = ['type'=>'error' , 'msg'=>'Missing API credentials.'];
            set_transient('mc_api_notice', $notice, 30 );
            wp_safe_redirect(admin_url('options-general.php?page='. MC_API_SETTINGS_SLUG));
            exit;
        }

        $valid_key = true;
        $data_center = '';
        $hyphen_pos = strpos($api_key, '-');
        if($hyphen_pos === false){
            $valid_key = false;
        }else{
            $data_center = substr($api_key, $hyphen_pos+1);
            if(strlen($data_center) <= 0)
                $valid_key = false;
        }

        if(!$valid_key){
            $notice = ['type'=>'error' , 'msg'=>'Invalid API credentials.'];
            set_transient('mc_api_notice', $notice, 30 );
            wp_safe_redirect(admin_url('options-general.php?page='. MC_API_SETTINGS_SLUG));
            exit;
        }

        $mc_url = "https://{$data_center}.api.mailchimp.com/3.0/lists/{$list_id}";
        $mc_args = [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
                'User-Agent' => 'mc-api-integration/0.1.0; ' . get_bloginfo('url'),
            ],
            'timeout' => 12,
            'sslverify' => true
        ];
        $mc_response = wp_remote_get($mc_url, $mc_args);
        $is_error = is_wp_error($mc_response);
        $notice = '';

        if($is_error){

            if(defined('WP_DEBUG') && WP_DEBUG){
                error_log('[MC_API]Network error: ' . $mc_response->get_error_message());
            }

            $notice = ['type' => 'error', 'msg' => "Network error contacting mailchimp."];
            set_transient('mc_api_notice', $notice, 30 );
            wp_safe_redirect(admin_url('options-general.php?page='. MC_API_SETTINGS_SLUG));
            exit;
        }

        $response_code = wp_remote_retrieve_response_code($mc_response);
        

        switch($response_code){
            case 200:
                $notice = ['type' => 'success', 'msg' => 'Mailchimp connected successfully.'];
                break;
            case 401:  
                $notice = ['type' => 'error', 'msg' => 'Unauthorized: Invalid API key.'];
                break; 
            case 404:  
                $notice = ['type' => 'error', 'msg' => 'List not found. Please check List id.'];
                break; 
            default:
                $notice = ['type' => 'error', 'msg' => "Unexpected error. (HTTP {$response_code})"];
        }
        
        set_transient('mc_api_notice', $notice, 30 );
        wp_safe_redirect(admin_url('options-general.php?page='. MC_API_SETTINGS_SLUG));
        exit;
    }

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
}