<?php
/**
 * Created by PhpStorm.
 * User: dsampaolo
 * Date: 23/12/2014
 * Time: 14:00
 */

if (isset($_POST['srb_save_options'])) {
    if (isset($_POST['srb_once_per_session']) && $_POST['srb_once_per_session'] == 'on') {
        if ( get_option( 'srb_once_per_session') !== false ) {
            update_option( 'srb_once_per_session', '1');
        } else {
            add_option( 'srb_once_per_session', '1');
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
//var_dump($once);

$blacklist = implode("\n",$this->blacklist());
//var_dump($blacklist);
?>
<div class="wrap">
    <h2>Spam Referrer Block</h2>

    <form method="post">
        <h3>General Options</h3>

        <input type="checkbox" id="srb_once_per_session" name="srb_once_per_session" <?php if ($once == 1) { echo 'checked'; } ?>/><label for="srb_once_per_session"> Check only the first request of each session (faster but weaker)</label>

        <?php submit_button('Save','button button-primary','srb_save_options') ?>

        <h3>Blacklist</h3>
        <textarea disabled rows="20" cols="60" id="srb_blacklist" name="srb_blacklist"><?= $blacklist ?></textarea>

        <?php submit_button('Update Blacklist (download)','button button-primary','srb_blacklist_download') ?>
<!--        <textarea disabled rows="20" cols="60" id="srb_blacklist_add" name="srb_blacklist_add"></textarea>-->
<!--        --><?php //submit_button('Send URLs','button button-primary','srb_blacklist_add_save') ?>
<!--        <em>Submitted URLs will be reviewed and added to the public blacklist</em>-->

    </form>
</div>