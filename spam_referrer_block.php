<?php
/*
Plugin Name: SpamReferrerBlock
Plugin URI: https://wordpress.org/plugins/spamreferrerblock/
Description: Filters your traffic to block hits with false referrers.
Version: 2.22
Author: Didier Sampaolo
Author URI: http://www.didcode.com/
*/

$spb = new SpamReferrerBlock();

class SpamReferrerBlock
{
    var $version = '2.22';
    var $network_active = false;
    var $table_name = '';

    function __construct()
    {
        global $wpdb;

        if(is_multisite()) {
            if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }
            $this->network_active = is_plugin_active_for_network( 'spamreferrerblock/spam_referrer_block.php' );
        }

        if($this->network_active) {
            // Only use 1 database for network installs
            $this->table_name = $wpdb->base_prefix . 'srb_blacklist';
        } else {
            $this->table_name = $wpdb->prefix . 'srb_blacklist';
        }

        register_activation_hook( __FILE__, array($this, 'setup_db') );

        if(!is_admin()) {
            add_action('init', array($this, 'filter_trafic'));
        } else {
            if($this->network_active) {
                add_action('network_admin_menu', array($this, 'add_network_menu'));
            } else {
                add_action('admin_menu', array($this, 'add_menu'));
            }
        }
    }

    function add_menu() {
        add_submenu_page( 'options-general.php', 'SpamReferrerBlock', 'Spam Referrer Block', 'manage_options', 'srb', array($this, 'show_admin_page'));
    }

    function add_network_menu() {
        add_submenu_page('settings.php', 'SpamReferrerBlock', 'Spam Referrer Block', 'manage_options', 'srb', array($this, 'show_admin_page'));
    }

    function show_admin_page() {
        wp_enqueue_style( 'srb_admin_style', plugin_dir_url( __FILE__ ).'admin_style.css?v='.$this->version);
        include('admin_page.php');
    }

    function setup_db() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $installed_ver = $this->network_active ? get_site_option( "srb_db_version" ) : get_option( "srb_db_version" );

        if ( !is_numeric($installed_ver) || $installed_ver !== $this->version ) {

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $sql = "CREATE TABLE $this->table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                item_hash varchar(42) DEFAULT '' NOT NULL,
                item varchar(255) DEFAULT '' NOT NULL,
                source tinyint(1),
                works tinyint(1),
                UNIQUE KEY id (id),
                UNIQUE (item_hash)
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
            $content = wp_remote_retrieve_body( wp_remote_get($url) );
            if (strlen($content) == 0) {
                echo "There has been an error during the download.";
                return false;
            }
            $items = json_decode($content);

            foreach($items as $item => $status) {
                $esc_item = esc_sql($item);
                $esc_status = esc_sql($status);
                $esc_item_hash = md5($item);

                $already = $wpdb->get_row("SELECT * from $this->table_name where item = '$esc_item'");
                if ($already == null) {
                    $sql = "INSERT IGNORE INTO $this->table_name (item_hash, item, works, source) VALUE ('$esc_item_hash', '$esc_item', '$esc_status', '1')";
                } else {
                    $sql = "UPDATE $this->table_name SET item_hash = '$esc_item_hash', item = '$esc_item', works = '$esc_status', source='1' WHERE item = '$esc_item' LIMIT 1";
                }
                $wpdb->query($sql);
           }

            if (count($items) > 0) {
                $this->updateBlacklistDownloadTime();
            }
        }
    }

    function send_blacklist() {
        global $wpdb;
        $items = $wpdb->get_results("SELECT item from $this->table_name WHERE source != 1");

        $send = array();
        foreach($items as $item) {
            $send[] = $item->item;
        }

        $url = 'http://www.didcode.com/srb-blacklist-receive.php?version='.$this->version.'&items='.implode('|',$send);
        $data = wp_remote_retrieve_body( wp_remote_get($url) );
        if ($data == 'ok') {
            return true;
        }
    }

    function save_blacklist() {
        $blacklist = explode("\n", $_POST['srb_blacklist']);
        global $wpdb;
        foreach($blacklist as $item) {
            $esc_item = esc_sql($item);
            $esc_item_hash = md5($item);
            $sql = "INSERT IGNORE INTO $this->table_name (item_hash, item, source) VALUE ('$esc_item_hash', '$esc_item', 1)";
            $wpdb->query($sql);
        }
    }

    function save_custom_blacklist() {
        $blacklist = explode("\n", trim($_POST['srb_custom_blacklist']));
        global $wpdb;
        foreach($blacklist as $item) {
            $esc_item = esc_sql($item);
            $esc_item_hash = md5($item);
            $sql = "INSERT IGNORE INTO $this->table_name (item_hash, item, source) VALUE ('$esc_item_hash', '$esc_item', 2)";
            $wpdb->query($sql);
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
        $once_per_session = $this->network_active ? get_site_option( 'srb_once_per_session' ) : get_option( 'srb_once_per_session') ;

        if ($once_per_session === '1') {
            if (isset($_SESSION['SpamReferrerBlock'])) {
                return true;
            }
        }

        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $ref = $_SERVER['HTTP_REFERER'];

            foreach($this->blacklist() as $spammer) {
                if ($spammer->item != "" && (strpos($ref, $spammer->item) !== false)) {
                    $response    = $this->network_active ? get_site_option( 'srb_response' ) : get_option('srb_response');
                    $redirection = $this->network_active ? get_site_option( 'srb_redirection' ) : get_option('srb_redirection');

                    if ($response == 301 || $response == 302) {
                        header('Location:'.$redirection, true, $response );
                        die();
                    } elseif ($response == 403) {
                        header("HTTP/1.0 403 Forbidden");
                        die();
                    } elseif ($response == 405) {
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
