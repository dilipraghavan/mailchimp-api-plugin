
<div class='wrap'>
    <h1>Mailchimp API Integration</h1>
    <?php if(!empty($credentails_src)) : ?>
        <p class='description'>
            <?php
                if($credentails_src === 'constant')
                    echo "Using constants from wp-config.php.";
                else if($credentails_src === 'env')
                    echo "Using environment variables.";
            ?>
        </p>
    <?php endif ?>
    <?php settings_errors(); ?>

    <form method='post' action='options.php'>
        <?php
            settings_fields('mc_api_settings_group');
            do_settings_sections(MC_API_SETTINGS_SLUG);
            submit_button("Save Changes");
        ?>
    </form>
    <form method='POST' action=<?php echo esc_url($action_slug); ?> >
        <?php wp_nonce_field('mc_api_conn', 'mc_api_nonce'); ?>
        <button type='submit'>Test Connection</button>
    </form>
</div>
