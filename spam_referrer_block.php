<?php
/*
Plugin Name: SpamReferrerBlock
Plugin URI: https://wordpress.org/plugins/spamreferrerblock/
Description: Filters your traffic to block hits with false referrers.
Version: 2.0
Author: Didier Sampaolo
Author URI: http://www.didcode.com/
*/

$spb = new SpamReferrerBlock();

class SpamReferrerBlock
{
    function __construct()
    {
        global $wpdb;

        $this->version = '2.0';
        $this->table_name = $wpdb->prefix . 'srb_blacklist';

        register_activation_hook( __FILE__, array($this, 'setup_db') );

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
        wp_enqueue_style( 'srb_admin_style', plugin_dir_url( __FILE__ ).'admin_style.css?v='.$this->version);
        include('admin_page.php');
    }

    function setup_db() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $installed_ver = get_option( "srb_db_version" );

        if ( $installed_ver != $this->version ) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $sql = "CREATE TABLE $this->table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                item varchar(255) DEFAULT '' NOT NULL,
                works tinyint(1),
                UNIQUE KEY id (id),
                UNIQUE (item)
            ) $charset_collate;";

            dbDelta($sql);
            update_option('srb_db_version', $this->version);
        }

        $this->download_blacklist();
    }

    function download_blacklist() {
        $last_download = get_option( 'srb_blacklist_dl_time' );
        if ( $last_download === false || (date('U') - $last_download) > 900 || true) {

           global $wpdb;
           $url = 'http://www.didcode.com/srb-blacklist-delta.php?version='.$this->version.'&updated_at='.get_option( 'srb_blacklist_dl_time' );
           $items = json_decode(file_get_contents($url));

            foreach($items as $item => $status) {
                $esc_item = esc_sql($item);
                $esc_status = esc_sql($status);

                $already = $wpdb->get_row("SELECT * from $this->table_name where item = '$esc_item'");
                if ($already == null) {
                    $sql = "INSERT IGNORE INTO $this->table_name (item, works) VALUE ('$esc_item', '$esc_status')";
                } else {
                    $sql = "UPDATE $this->table_name SET item = '$esc_item', works = '$esc_status' WHERE item = '$esc_item' LIMIT 1";
                }
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
                if (strpos($ref, $spammer->item) !== false) {
                    $response    = get_option('srb_response');
                    $redirection = get_option('srb_redirection');

                    if ($response == 301 || $response == 302) {
                        header('Location:'.$redirection, true, $response );
                        die();
                    } elseif ($response == 404) {
                        header("HTTP/1.0 405 Method Not Allowed");
                        die();
                    } elseif ($response == 404) {
                        header("HTTP/1.0 404 Not Found");
                        die();
                    }
                }
            }
        }

        $_SESSION['SpamReferrerBlock'] = true;
    }

    function blacklist() {
        $array = '';

        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM $this->table_name order by item");

        return $items;
    }
}
