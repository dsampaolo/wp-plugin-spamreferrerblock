<?php
/*
Plugin Name: SpamReferrerBlock
Plugin URI:
Description:
Version: 1.2
Author: Didier Sampaolo
Author URI: http://www.didcode.com/
*/

$spb = new SpamReferrerBlock();

class SpamReferrerBlock
{
    function __construct()
    {
        global $wpdb;

        $this->version = '1.0';
        $this->table_name = $wpdb->prefix . 'srb_blacklist';

        register_activation_hook( __FILE__, array($this, 'create_table') );

        if(!is_admin()) {
            add_action('init', array($this, 'filter_trafic'));
        } else {
            add_action('admin_menu', array($this, 'add_menu'));
        }
    }

    function add_menu() {
        add_menu_page('SpamReferrerBlock', 'Spam Referer Block', 'manage_options', 'srb', array($this, 'show_admin_page'));
    }

    function show_admin_page() {
        include('admin_page.php');
    }

    function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            item varchar(255) DEFAULT '' NOT NULL,
            UNIQUE KEY id (id),
            UNIQUE (item)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        add_option( 'srb_db_version', $this->version );

        $this->download_blacklist();
    }

    function download_blacklist() {
        $last_download = get_option( 'srb_blacklist_dl_time' );
        if ( $last_download === false || (date('U') - $last_download) > 900 || true) {

           global $wpdb;
           $url = 'http://www.didcode.com/srb-blacklist-delta.php?updated_at='.get_option( 'srb_blacklist_dl_time' );
           $items = json_decode(file_get_contents($url));
           foreach($items as $item) {
                $esc_item = esc_sql($item);
                $sql = "INSERT IGNORE INTO $this->table_name (item) VALUE ('$esc_item')";
                $wpdb->query($sql);
           }
           $this->updateBlacklistDownloadTime();
        }
    }

    function save_blacklist() {
        $blacklist = explode("\n", $_POST['srb_blacklist']);
        global $wpdb;
        foreach($blacklist as $item) {
            $esc_item = esc_sql($item);
            $wpdb->query("INSERT IGNORE INTO $this->table_name (item) VALUE ('$esc_item')");
        }
    }

    function updateBlacklistDownloadTime() {
        $option_name = 'srb_blacklist_dl_time';
        $new_value = date('U') ;

        if ( get_option( $option_name ) !== false ) {
            update_option( $option_name, $new_value );
        } else {
            add_option( $option_name, $new_value);
        }
    }

    function filter_trafic()
    {
        $once_per_session = get_option( 'srb_once_per_session') ;
        if ($once_per_session === '1') {
            if (isset($_SESSION['SpamReferrerBlock'])) {
                return true;
            }
        }

        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $ref = $_SERVER['HTTP_REFERER'];

            foreach($this->blacklist() as $spammer) {
                if (strpos($ref, $spammer) !== false) {
                    header("HTTP/1.0 405 Method Not Allowed");
                    die();
                }
            }
        }

        $_SESSION['SpamReferrerBlock'] = true;
    }

    function blacklist() {
        $array = '';

        global $wpdb;
        $items = $wpdb->get_results("SELECT item FROM $this->table_name");

        foreach($items as $item) {
            $array[] = $item->item;
        }

        return $array;
    }
}

/*
 * TODO
 *
 * récupérer le delta de la blacklist
 * soumettre de nouvelles URLs à la blacklist (webservice)
 *
 */