<div class='wrap'>
    <h1>Mailchimp Reports</h1>

    <form method='GET'>
        <input type='hidden' name='page' value='mc-api-reports' />
        <?php wp_nonce_field('mc_api_reports', 'mc_api_reports_nonce'); ?>
        
        <div class="filter-controls">
            <label for='event_type' >Event Type</label>
            <select id='event_type' name='event_type' >
                <?php
                    foreach ($select_opts as $value => $label) {
                        $selected = $event_type === $value ? 'selected' : '';
                        echo "<option value='{$value}' {$selected}>{$label}</option>";
                    }
                ?>
            </select>
            <br>

            <label for='http_code' >HTTP Code</label>
            <input id='http_code' type='number' name='http_code' value='<?php echo esc_attr($http_code); ?>' />
            <br>
                
            <label for='from_date' >From Date</label>
            <input id='from_date' type='date' name='from_date' value='<?php echo esc_attr($from_date); ?>' /> 
            <br>

            <label for='to_date' >To Date</label>
            <input id='to_date' type='date' name='to_date' value='<?php echo esc_attr($to_date); ?>' />
            <br>

            <button type='submit' class='button'>Submit</button>
            <br>

            <button type='submit' class='button button-secondary' name='mc_export' value='1'>Export CSV</button>
            <br>
        </div>
    </form>

    <?php if($total_rows > 0 ) :
        echo "<p>Show {$first}-{$last} of {$total_rows} results</p>";
    endif; ?>

    <table class='widefat fixed striped table'>
        <thead>
            <tr>
                <th>ID</th>
                <th>Timestamp</th>
                <th>Event Type</th>
                <th>Http Code</th>
                <th>Endpoint</th>
                <th>Email(hashed)</th>
                <th>Message</th>
            </tr>
        </thead>

        <tbody>
            <?php if($rows) : ?>
                <?php foreach ($rows as $event) : ?>
                    <tr>
                        <td> <?php echo esc_html($event['id']); ?> </td>
                        <td> <?php echo esc_html($event['ts_utc']); ?> </td>
                        <td> <?php echo esc_html($event['event_type']); ?> </td>
                        <td> <?php echo esc_html($event['http_code']); ?> </td>
                        <td> <?php echo esc_html($event['endpoint']); ?> </td>
                        <td> <?php echo esc_html($event['email_hash']); ?> </td>
                        <td> <?php echo esc_html($event['message']); ?> </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan='7'>No events found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

         
    <?php if ($total_pages > 1) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php 
                    echo paginate_links([
                        'base'      => add_query_arg('paged','%#%', $base_url),
                        'format'    => '',
                        'prev_text' => '«',
                        'next_text' => '»',
                        'total'     => $total_pages,
                        'current'   => $page,
                    ]);
                ?>
            </div>
        </div>
    <?php endif; ?> 
</div>

    