<?php
$this->setup_db();


if (isset($_POST['srb_save_options'])) {
    if (isset($_POST['srb_once_per_session'])) {
        update_option( 'srb_once_per_session', '1');
    } else {
        update_option( 'srb_once_per_session', '0');
    }

    if (isset($_POST['srb_blacklist_auto_update'])) {
        update_option( 'srb_blacklist_auto_update', '1');

        if ( ! wp_next_scheduled( 'auto_download_blacklist' ) ) {
            wp_schedule_event( time(), 'daily', 'auto_download_blacklist');
        }
        add_action( 'auto_download_blacklist', array($this, 'download_blacklist') );
    } else {
        update_option( 'srb_blacklist_auto_update', '0');

        if ( wp_next_scheduled( 'auto_download_blacklist') ) {
            wp_clear_scheduled_hook( 'auto_download_blacklist' );
        }
    }

    update_option('srb_response', $_POST['srb_response']);
    update_option('srb_redirection', $_POST['srb_redirection']);
}

if (isset($_POST['srb_blacklist_download'])) {
    $this->download_blacklist();
}

if (isset($_POST['srb_blacklist_save'])) {
    $this->save_blacklist();
}

$once = get_option( 'srb_once_per_session') ;
$auto_update = get_option( 'srb_blacklist_auto_update') ;
$response = get_option( 'srb_response') ;
if ($response == false) {
    $response = 405;
    update_option('srb_response', 405);
}
$redirection = get_option( 'srb_redirection') ;

$last_download = get_option( 'srb_blacklist_dl_time' );
if ($last_download !== false) {
    $last_download = date ( 'Y-m-d H:i:s', $last_download );
} else {
    $last_download = '<b>NEVER - please download the blacklist</b>';
}

?>
<div class="wrap">
    <div style="float:right">
        <h3>Want to help ?</h3>
        You have several options :
        <ul>
            <li><a href="mailto:didier@didcode.com">Send us an email</a> with the domains you wish to block.</li>
            <li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ET69EBG2GM7AQ">Make a donation</a>. This free plugin requires a lot of work. Every cent helps :)</li>
            <li><a href="http://www.didcode.com/spam-referrer-block">Link</a>. Help us reach more people by linking to the plugin's homepage</li>
            <li><a href="https://wordpress.org/plugins/spamreferrerblock/">Rate</a> the plugin in Wordpress repository.</li>
        </ul>
    </div>

    <h2>Spam Referrer Block</h2>

    <form method="post">
        <h3>General Options</h3>

        <p>
            <input type="checkbox" id="srb_once_per_session" name="srb_once_per_session" <?php if ($once == 1) { echo 'checked'; } ?>/><label for="srb_once_per_session"> Check only the first request of each session (faster but weaker)</label><br />
            <input type="checkbox" id="srb_blacklist_auto_update" name="srb_blacklist_auto_update" <?php if ($auto_update == 1) { echo 'checked'; } ?>/><label for="srb_blacklist_auto_update"> Keep blacklist up-to-date (daily auto-update)</label><br />
        </p>

        <p>
            <b>What should we do to the evil spammers ?</b><br />
            <input type="radio" id="srb_response_404" name="srb_response" value="404" <?php if ($response == 404) { echo 'checked'; } ?>/><label for="srb_response_404"> Show an error : HTTP <em>404 - Not Found</em></label><br />
            <input type="radio" id="srb_response_405" name="srb_response" value="405" <?php if ($response == 405) { echo 'checked'; } ?>/><label for="srb_response_405"> Show an error : HTTP <em>405 - Method Not Allowed</em></label><br />
            <input type="radio" id="srb_response_301" name="srb_response" value="301" <?php if ($response == 301) { echo 'checked'; } ?>/><label for="srb_response_301"> Redirect them elsewhere : HTTP <em>301 - Permanent</em></label><br />
            <input type="radio" id="srb_response_302" name="srb_response" value="302" <?php if ($response == 302) { echo 'checked'; } ?>/><label for="srb_response_302"> Redirect them elsewhere : HTTP <em>302 - Temporary</em></label><br />
        </p>
        <p>
            If you choose to redirect them, where should they go ? (enter a valid url)<br />
            <input type="text" name="srb_redirection" value="<?= $redirection ?>" />
        </p>


        <?php submit_button('Save','button button-primary','srb_save_options') ?>
        
        <hr />

        <h3>Custom Blacklist</h3>
            <textarea rows="10" cols="60" name="srb_custom_blacklist"></textarea>
            <?php submit_button('Save','button button-primary','srb_blacklist_custom_save') ?>
        
        <hr />

        <h3>Blacklist</h3>

        <div class="srb_info" style="padding:15px; margin-bottom:20px">
            /!\ Unfortunately, <b>this plugin can't remove ALL spam referral traffic</b>. Some domains are massively hijacking Google Analytics ID to push false traffic directly to Google's servers.<br />
            As they do not interfere with your blog or server, the plugin obviously can't do anything against them ; <b>you have to block them in your Analytics account</b>. Click here to view how : <a href="https://support.google.com/analytics/answer/2795830?hl=en">https://support.google.com/analytics/answer/2795830?hl=en</a>.
        </div>

        Blacklist last download date : <?= $last_download ?><br />
        <table class="widefat fixed">
            <tr>
                <th>Domain</th>
                <th>Status</th>
            </tr>
            <?php foreach ($this->blacklist() as $blacklisted) {
                if ($blacklisted->works == 0 || $blacklisted->works == null) {
                    $class = "srb_warning";
                    $status = 'Unknown';
                } elseif ($blacklisted->works == 1) {
                    $class = "srb_success";
                    $status = 'Blocked';
                } else {
                    $class = 'srb_error';
                    $status = 'Can\'t block';
                }
                ?>

                <tr class="<?= $class ?>">
                    <td><?= $blacklisted->item ?></td>
                    <td><?= $status ?></td>
                </tr>
            <?php } ?>
        </table>

<!--        <textarea disabled rows="20" cols="60" id="srb_blacklist" name="srb_blacklist">--><?//= $blacklist ?><!--</textarea>-->

        <?php submit_button('Update Now','button button-primary','srb_blacklist_download') ?>
<!--        <textarea disabled rows="20" cols="60" id="srb_blacklist_add" name="srb_blacklist_add"></textarea>-->
<!--        --><?php //submit_button('Send URLs','button button-primary','srb_blacklist_add_save') ?>
<!--        <em>Submitted URLs will be reviewed and added to the public blacklist</em>-->
<hr />

    </form>
</div>