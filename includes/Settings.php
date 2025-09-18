<?php 
namespace MC_API;

class Settings {

    public static function init(){
        add_action('admin_menu', [__CLASS__, 'add_menu_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_post_' . MC_API_ACTIONS_SLUG, [__CLASS__, 'handle_test_connection']);
        add_action('admin_menu', [__CLASS__, 'add_submenu_page']);
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

    public static function get_credentials(){
        return self::credentials_resolver();
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
        $http_code = isset($_GET['http_code']) ? (absint)($_GET['http_code']) : 0;
        $from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : '';
        $to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : '';
        $page = max(1,(int)($_GET['paged'] ?? 1)); 

        global $wpdb;
        $table = $wpdb->prefix . 'mc_api_events';
        $where = ['1=1'];
        $params = [];
        $rows_per_page = 20;

        if($event_type){
            $where[] = 'event_type=%s';
            $params[] = $event_type;
        }

        if($http_code){
            $where[] = 'http_code=%d';
            $params[] = $http_code;
        }

        if($from_date){
            $where[] = 'ts_utc>=%s';
            $params[] = $from_date . ' 00:00:00';
        }

        if($to_date){
            $where[] = 'ts_utc<=%s';
            $params[] = $to_date . ' 23:59:59';
        }
        
        $where_sql = implode(' AND ', $where);

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

        //Export CSV
        if(isset($_GET['mc_export']) && $_GET['mc_export'] === '1'){
            $rows_csv = $wpdb->get_results($wpdb->prepare($sql_rows, array_merge($params, [5000, 0])),ARRAY_A);
            $date = date('Y-m-d');
            $filename = "mc-api-events-{$date}.csv";
            nocache_headers();
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename={$filename}");
            header("Pragma: no-cache");
            header("Expires: 0");

            $out = fopen('php://output', 'w');
            fputcsv($out, ['ts_utc','event_type','http_code','endpoint','email_hash','message']);

            foreach ($rows_csv as $row){
                fputcsv($out,[ 
                        $row['ts_utc'],
                        $row['event_type'],
                        $row['http_code'],
                        $row['endpoint'],
                        $row['email_hash'],
                        $row['message']
                ]);
            }

            fclose($out);
            exit;
        }

        $rows = $wpdb->get_results($wpdb->prepare($sql_rows, array_merge($params, [$rows_per_page, $offset])),ARRAY_A);
        //Form
        echo "<form method='GET'>";

        echo "<input type='hidden' name='page' value='mc-api-reports' />";
        wp_nonce_field('mc_api_reports', 'mc_api_reports_nonce');

        echo "<label for='event_type' >Event Type</label>";
        echo "<select id='event_type' name='event_type' >";

        $select_opts = [
            '' => 'All',
            'subscribe' => 'Subscribe',
            'error' => 'Error',
            'webhook_unsub' => 'webhook_unsub',
            'webhook_cleaned' => 'webhook_cleaned',
            'test' => 'Test',
        ];

        foreach ($select_opts as $value => $label) {
            $selected = $event_type === $value ? 'selected' : '';
            echo "<option value='{$value}' {$selected}>{$label}</option>";
        }

        echo "</select>";
        echo "<br>";

        echo "<label for='http_code' >HTTP Code</label>";
        echo "<input id='http_code' type='number' name='http_code' value='" . esc_attr($http_code) . "' />";
        echo "<br>";
        
        echo "<label for='from_date' >From Date</label>";
        echo "<input id='from_date' type='date' name='from_date' value='" . esc_attr($from_date) . "' />";
        echo "<br>";

        echo "<label for='to_date' >To Date</label>";
        echo "<input id='to_date' type='date' name='to_date' value='" . esc_attr($to_date) . "' />";
        echo "<br>";

        echo "<button type='submit'>Submit</button>";
        echo "<br>";

        echo "<button type='submit' name='mc_export' value='1'>Export CSV</button>";
        echo "<br>";

        echo "</form>";

        //Display table
        $first = max($offset+1,0);
        $last = min($offset + $rows_per_page, $total_rows);
        if($total_rows > 0 )
            echo "<p>Show {$first}-{$last} of {$total_rows} results</p>";

        echo "<table class='widefat fixed striped'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Timestamp</th>";
        echo "<th>Event Type</th>";
        echo "<th>Http Code</th>";
        echo "<th>Endpoint</th>";
        echo "<th>Email(hashed)</th>";
        echo "<th>Message</th>";
        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";
        if($rows){
            
            foreach ($rows as $event) {
                echo "<tr>";
                echo "<td>" . esc_html($event['id']) . "</td>";
                echo "<td>" . esc_html($event['ts_utc']) . "</td>";
                echo "<td>" . esc_html($event['event_type']) . "</td>";
                echo "<td>" . esc_html($event['http_code']) . "</td>";
                echo "<td>" . esc_html($event['endpoint']) . "</td>";
                echo "<td>" . esc_html($event['email_hash']) . "</td>";
                echo "<td>" . esc_html($event['message']) . "</td>";
                echo "</tr>";
            }
        }else{
            echo "<tr>";
            echo "<td colspan='7'>" . "No events found" . "</td>";
            echo "</tr>";

        }

        echo "</tbody>";
        echo "</table>";

        //Pagination
        $base_url = menu_page_url('mc-api-reports', false);
        if ($total_pages > 1){
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links([
                'base'      => add_query_arg('paged','%#%', $base_url),
                'format'    => '',
                'prev_text' => '«',
                'next_text' => '»',
                'total'     => $total_pages,
                'current'   => $page,
            ]);
            echo '</div></div>';
        }

    }
}