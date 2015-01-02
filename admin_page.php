<?php
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
}

if (isset($_POST['srb_blacklist_download'])) {
    $this->download_blacklist();
}

if (isset($_POST['srb_blacklist_save'])) {
    $this->save_blacklist();
}

$once = get_option( 'srb_once_per_session') ;
$auto_update = get_option( 'srb_blacklist_auto_update') ;

$last_download = get_option( 'srb_blacklist_dl_time' );
if ($last_download !== false) {
    $last_download = date ( 'Y-m-d H:i:s', $last_download );
} else {
    $last_download = '<b>NEVER - please download the blacklist</b>';
}

$blacklist = implode("\n",$this->blacklist());
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

        <input type="checkbox" id="srb_once_per_session" name="srb_once_per_session" <?php if ($once == 1) { echo 'checked'; } ?>/><label for="srb_once_per_session"> Check only the first request of each session (faster but weaker)</label><br />
        <input type="checkbox" id="srb_blacklist_auto_update" name="srb_blacklist_auto_update" <?php if ($auto_update == 1) { echo 'checked'; } ?>/><label for="srb_once_per_session"> Keep blacklist up-to-date (daily auto-update)</label><br />

        <?php submit_button('Save','button button-primary','srb_save_options') ?>
        <hr />

        <h3>Blacklist</h3>
        Blacklist last download date : <?= $last_download ?><br />
        <textarea disabled rows="20" cols="60" id="srb_blacklist" name="srb_blacklist"><?= $blacklist ?></textarea>

        <?php submit_button('Update Now','button button-primary','srb_blacklist_download') ?>
<!--        <textarea disabled rows="20" cols="60" id="srb_blacklist_add" name="srb_blacklist_add"></textarea>-->
<!--        --><?php //submit_button('Send URLs','button button-primary','srb_blacklist_add_save') ?>
<!--        <em>Submitted URLs will be reviewed and added to the public blacklist</em>-->
<hr />

    </form>
</div>