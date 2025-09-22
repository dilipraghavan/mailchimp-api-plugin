<?php 
namespace MC_API;

class Settings {

    public static function init(){
        add_action('admin_menu', [__CLASS__, 'add_menu_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_post_' . MC_API_ACTIONS_SLUG, [__CLASS__, 'handle_test_connection']);
        add_action('admin_post_mc_api_export', [__CLASS__, 'handle_export_csv']);
        add_action('admin_menu', [__CLASS__, 'add_submenu_page']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_styles']);
    }


    public static function enqueue_styles($hook) {
        if ('settings_page_mc-api-reports' === $hook || 'toplevel_page_mc-api-settings' === $hook) {
            wp_enqueue_style(
                'mc-api-admin-style',
                MC_API_PLUGIN_URL_PATH . 'assets/css/admin.css',
                [],
                MC_API_PLUGIN_VERSION
            );
        }
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

        $notice = get_transient('mc_api_notice');
        delete_transient('mc_api_notice');

        if($notice){
            $msg = esc_html($notice['msg']);
            $type = esc_html($notice['type']);
            $banner_class = 'notice-info';
            if($type === 'success') 
                $banner_class = 'notice-success';

            if($type === 'error') 
                $banner_class = 'notice-error';

            echo"<div class='notice {$banner_class} is-dismissible'><p>{$msg}</p></div>";
            
        }
        
        $creds = Credentials_Resolver::get_credentials();
        $credentails_src = $creds['src'];

        $action_slug = esc_url(admin_url('admin-post.php?action=' . MC_API_ACTIONS_SLUG)); 
   
        include MC_API_PLUGIN_DIR_PATH . 'includes/templates/admin-settings.php';
    }

    public static function register_settings(){
        register_setting('mc_api_settings_group', 'mailchimp_api_key', ['sanitize_callback' => [__CLASS__, 'mc_sanitize_api_key']]);
        register_setting('mc_api_settings_group', 'mailchimp_list_id', ['sanitize_callback' => [__CLASS__, 'mc_sanitize_list_id']]);
        register_setting('mc_api_settings_group', 'mailchimp_double_optin', ['sanitize_callback' => [__CLASS__, 'mc_sanitize_yes_no']]);
        register_setting('mc_api_settings_group', 'mailchimp_logging_enabled', ['sanitize_callback' => [__CLASS__, 'mc_sanitize_yes_no']]);

        add_settings_section('mc_api_credentials_section', 'Mailchimp Credentials', null, MC_API_SETTINGS_SLUG);
        
        add_settings_field('mc_api_key', 'API key', [__CLASS__, 'mc_api_key_callback'], MC_API_SETTINGS_SLUG, 'mc_api_credentials_section');
        add_settings_field('mc_list_id', 'List Id', [__CLASS__, 'mc_list_id_callback'], MC_API_SETTINGS_SLUG, 'mc_api_credentials_section');
        add_settings_field('mc_double_optin', 'Double Opt-in', [__CLASS__, 'mc_double_optin_callback'], MC_API_SETTINGS_SLUG, 'mc_api_credentials_section');
        add_settings_field('mc_logging_enabled', 'Logging Enabled', [__CLASS__, 'mc_logging_enabled_callback'], MC_API_SETTINGS_SLUG, 'mc_api_credentials_section');
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
        $creds = Credentials_Resolver::get_credentials();
        $mc_api_key = isset($creds['api_key']) ? esc_attr($creds['api_key']) : '';
        $mc_src = $creds['src'];
        $disabled= ($mc_src === 'constant' || $mc_src === 'env') ? 'disabled' : '';
        echo "<input type='text' class='regular-text' name='mailchimp_api_key' value='{$mc_api_key}' {$disabled} autocomplete='off' >";
        if($mc_src === 'constant' || $mc_src === 'env'){
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
        $creds = Credentials_Resolver::get_credentials();
        $mc_list_id = isset($creds['list_id']) ? esc_attr($creds['list_id']) : '';
        $mc_src = $creds['src'];
        $disabled= ($mc_src === 'constant' || $mc_src === 'env') ? 'disabled' : '';
        echo "<input type='text' class='regular-text' name='mailchimp_list_id' value='{$mc_list_id}' {$disabled} autocomplete='off' >";
        if($mc_src === 'constant' || $mc_src === 'env'){
            echo "<p>Override by removing constants and env vars.</p>";
        }
    }

    public static function mc_sanitize_yes_no($value){
        $value = is_string($value) ? strtolower(trim($value)) : '';
        return in_array($value, ['yes', 'no'], true) ? $value : 'yes';
    }

    public static function mc_double_optin_callback(){
        $checked = get_option('mailchimp_double_optin', 'yes'); 
        $checked_yes = $checked === 'yes' ? 'checked' : '';
        $checked_no = $checked === 'no' ? 'checked' : '';

        echo "<fieldset>";

        echo "<label for='mc_double_optin_yes'>";
        echo "<input type='radio' id='mc_double_optin_yes' name='mailchimp_double_optin' value='yes' {$checked_yes}/>";
        echo "Yes - send confirmation email (recommended)</label>";
        echo "<br/>";
        echo "<label for='mc_double_optin_no'>";
        echo "<input type='radio' id='mc_double_optin_no' name='mailchimp_double_optin' value='no' {$checked_no}/>";
        echo "No - subscribe immediately</label>";
        
        echo "</fieldset>";
    }
    
    public static function mc_logging_enabled_callback(){
        $checked = get_option('mailchimp_logging_enabled', 'yes'); 
        $checked_yes = $checked === 'yes' ? 'checked' : '';
        $checked_no = $checked === 'no' ? 'checked' : '';

        echo "<fieldset>";

        echo "<label for='mc_logging_enabled_yes'>";
        echo "<input type='radio' id='mc_logging_enabled_yes' name='mailchimp_logging_enabled' value='yes' {$checked_yes}/>";
        echo "Yes</label>";
        echo "<br/>";
        echo "<label for='mc_logging_enabled_no'>";
        echo "<input type='radio' id='mc_logging_enabled_no' name='mailchimp_logging_enabled' value='no' {$checked_no}/>";
        echo "No</label>";
        
        echo "</fieldset>";
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

        $credentials = Credentials_Resolver::get_credentials();

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

        $client = new Mailchimp_Client($api_key, $list_id);
        $valid_key = $client->is_valid_key();

        if(!$valid_key){
            $notice = ['type'=>'error' , 'msg'=>'Invalid API credentials.'];
            set_transient('mc_api_notice', $notice, 30 );
            wp_safe_redirect(admin_url('options-general.php?page='. MC_API_SETTINGS_SLUG));
            exit;
        }

        $response = $client->get("/lists/{$list_id}");

        $notice = ['type' => 'error', 'msg' => $response['msg']];
        if ($response['ok']) {
            $notice = ['type' => 'success', 'msg' => 'Mailchimp connected successfully.'];
        }
        
        set_transient('mc_api_notice', $notice, 30 );
        wp_safe_redirect(admin_url('options-general.php?page='. MC_API_SETTINGS_SLUG));
        exit;
    }

    public static function add_submenu_page(){
        add_submenu_page(   
                            'options-general.php',
                            'MailChimp Reports',
                            'MC Reports',
                            'manage_options',
                            'mc-api-reports',
                            [__CLASS__, 'render_mc_reports_submenu'],
                        );
    }

    public static function render_mc_reports_submenu(){
        if(!current_user_can('manage_options'))
            wp_die('Not Allowed');

        if(isset($_GET['mc_api_reports_nonce']) && !wp_verify_nonce( $_GET['mc_api_reports_nonce'], 'mc_api_reports'))
            wp_die('Invalid Request');

        $event_type = isset($_GET['event_type']) ? sanitize_key($_GET['event_type']) : '';
        if(!in_array($event_type, ['subscribe', 'error', 'webhook_unsub', 'webhook_cleaned', 'test' ]))
            $event_type='';
        $http_code = isset($_GET['http_code']) ? absint($_GET['http_code']) : 0;
        $from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : '';
        $to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : '';
        $page = max(1,(int)($_GET['paged'] ?? 1)); 

        global $wpdb;
        $table = $wpdb->prefix . 'mc_api_events';
        $rows_per_page = 20;

         
        $query_parts = self::get_filtered_events_query_parts();
        $where_sql = $query_parts['where_sql'];
        $params = $query_parts['params'];

        $sql_total = "SELECT count(*) 
                      FROM {$table} 
                      WHERE {$where_sql}";

        if(count($params) > 0)              
            $total_rows = (int)$wpdb->get_var($wpdb->prepare($sql_total, $params));
        else
            $total_rows = (int)$wpdb->get_var($sql_total);

        $total_pages = max(1, (int)ceil($total_rows/$rows_per_page));
        $offset = ($page-1) * $rows_per_page;
        
        $sql_rows = "SELECT id, ts_utc, event_type, http_code, endpoint, email_hash, message 
                FROM {$table} WHERE {$where_sql}
                ORDER BY id DESC
                LIMIT %d OFFSET %d";

        
        $rows = $wpdb->get_results($wpdb->prepare($sql_rows, array_merge($params, [$rows_per_page, $offset])),ARRAY_A);
        
        //Form vars for HTML template
        $select_opts = [
            '' => 'All',
            'subscribe' => 'Subscribe',
            'error' => 'Error',
            'webhook_unsub' => 'webhook_unsub',
            'webhook_cleaned' => 'webhook_cleaned',
            'test' => 'Test',
        ];


        //Display table vars for HTML template
        $first = max($offset+1,0);
        $last = min($offset + $rows_per_page, $total_rows);

        //Pagination vars for HTML template
        $base_url = menu_page_url('mc-api-reports', false);

        include MC_API_PLUGIN_DIR_PATH . 'includes/templates/admin-reports.php';

    }

    public static function handle_export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Not allowed.');
        }

        $nonce = isset($_POST['mc_api_reports_nonce']) ? wp_unslash($_POST['mc_api_reports_nonce']) : '';
        if (!wp_verify_nonce($nonce, 'mc_api_reports')) {
            wp_die('Invalid Request');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mc_api_events';

        $query_parts = self::get_filtered_events_query_parts();
        $where_sql = $query_parts['where_sql'];
        $params = $query_parts['params'];

        $sql_rows = "SELECT ts_utc, event_type, http_code, endpoint, email_hash, message
                    FROM {$table}
                    WHERE {$where_sql}
                    ORDER BY id DESC
                    LIMIT %d OFFSET %d";

        $rows = $wpdb->get_results(
            $wpdb->prepare($sql_rows, array_merge($params, [5000, 0])),
            ARRAY_A
        );

        if (ob_get_length()) { @ob_end_clean(); }

        $date = gmdate('Y-m-d');
        $filename = "mc-api-events-{$date}.csv";

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}");
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ts_utc','event_type','http_code','endpoint','email_hash','message']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['ts_utc'],
                $row['event_type'],
                $row['http_code'],
                $row['endpoint'],
                $row['email_hash'],
                $row['message'],
            ]);
        }
        fclose($out);
        exit;
    }

    private static function get_filtered_events_query_parts() {
        $event_type = isset($_REQUEST['event_type']) ? sanitize_key($_REQUEST['event_type']) : '';
        if (!in_array($event_type, ['subscribe', 'error', 'webhook_unsub', 'webhook_cleaned', 'test'], true)) {
            $event_type = '';
        }
        $http_code = isset($_REQUEST['http_code']) ? absint($_REQUEST['http_code']) : 0;
        $from_date = isset($_REQUEST['from_date']) ? sanitize_text_field($_REQUEST['from_date']) : '';
        $to_date = isset($_REQUEST['to_date']) ? sanitize_text_field($_REQUEST['to_date']) : '';

        $where = ['1=1'];
        $params = [];

        if ($event_type) {
            $where[] = 'event_type=%s';
            $params[] = $event_type;
        }

        if ($http_code) {
            $where[] = 'http_code=%d';
            $params[] = $http_code;
        }

        if ($from_date) {
            $where[] = 'ts_utc>=%s';
            $params[] = $from_date . ' 00:00:00';
        }

        if ($to_date) {
            $where[] = 'ts_utc<=%s';
            $params[] = $to_date . ' 23:59:59';
        }

        $where_sql = implode(' AND ', $where);

        return [
            'where_sql' => $where_sql,
            'params' => $params
        ];
    }


}