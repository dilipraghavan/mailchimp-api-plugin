<?php 
namespace MC_API;

class Logger{
    public static function add($e){
        if(get_option('mailchimp_logging_enabled', 'no') !== 'yes') 
            return;

        global $wpdb;
        $table = $wpdb->prefix . 'mc_api_events';

        $row = [
            'ts_utc'     => gmdate('Y-m-d H:i:s'),
            'event_type' => isset($e['event_type']) ? sanitize_key($e['event_type']) : 'event',
            'http_code'  => isset($e['http_code']) ? (int) $e['http_code'] : null,
            'endpoint'   => isset($e['endpoint']) ? substr(sanitize_text_field($e['endpoint']),0,190) : '',
            'email_hash' => isset($e['email_hash']) ? preg_replace('/[^a-f0-9]/','', strtolower($e['email_hash'])) : null,
            'message'    => isset($e['message']) ? substr(sanitize_text_field($e['message']),0,255) : '',
            'corr_id'    => isset($e['corr_id']) ? substr(sanitize_text_field($e['corr_id']),0,36) : null,
            'meta'       => isset($e['meta']) ? wp_json_encode($e['meta']) : null,
        ];

         $format = ['%s','%s','%d','%s','%s','%s','%s','%s'];
         $wpdb->insert($table, $row, $format);

    }
} 