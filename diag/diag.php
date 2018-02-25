<?php
/*
Plugin Name: 誕生日診断orig
Plugin URI: https://github.com/tony56
Description: 誕生日診断origプラグイン
Version: 1.0
Author: tony56
*/
if ( ! defined( 'ABSPATH' ) ) exit;

require_once "submenu/DiagRule.php";
require_once "submenu/DiagSetting.php";
require_once "submenu/DiagHistory.php";
require_once "DiagPage.php";

function getHistroyCsv() {
    $diagHistory = new DiagHistory();
    $diagHistory->getHistroyCsv();
}

function sendMail() {
    $diagPage = new DiagPage();
    $diagPage->sendMail();
}

add_action('rest_api_init', function() {

    register_rest_route('diag/v1', 'history', array(
        'methods'  => 'GET',
        'callback' => 'getHistroyCsv'
    ));

    register_rest_route('diag/v1', 'sendMail', array(
        'methods' => 'POST',
        'callback' => 'sendMail'
    ));
});

class DiagPlugin {

    function __construct() {

        global $wpdb;

        // short code
        add_shortcode('diag_show_page', array( 'DiagPage', 'showPage' ));

        add_action('admin_menu', array($this, 'add_pages'));

        // 回答条件設定テーブル追加
        $sql = $wpdb->prepare("SHOW TABLES LIKE '%wp_diag_rule%'", '');
        $rows = $wpdb->get_results($sql);
        if (!$rows) {
            $sql = $wpdb->prepare("CREATE TABLE `wp_diag_rule` (" .
                "`id` int(11) NOT NULL AUTO_INCREMENT," .
                "`from` int(11), `to` int(11)," .
                "`result` varchar(1000), `img` varchar(100), " . 
                "`insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, " .
                "PRIMARY KEY (`id`)" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8", '');
            $wpdb->get_results($sql);
        }

        // 履歴テーブル追加
        $sql = $wpdb->prepare("SHOW TABLES LIKE '%wp_diag_history%'", '');
        $rows = $wpdb->get_results($sql);
        if (!$rows) {
            $sql = $wpdb->prepare("CREATE TABLE `wp_diag_history` (" .
                    "`id` int(11) NOT NULL AUTO_INCREMENT," .
                    "`email` varchar(256) NOT NULL," .
                    "`birthday` varchar(8) NOT NULL," .
                    "`ip` varchar(128) NULL," .
                    "`insert_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP," .
                    "PRIMARY KEY(`id`)" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8", '');
            $wpdb->get_results($sql);
        }
    }

    function add_pages() {

        $diag_slug = __FILE__;
        $diagRule = new DiagRule();
        $diagSetting = new DiagSetting();
        $diagHistory = new DiagHistory();

        add_menu_page('誕生日診断orig', '誕生日診断orig',  'level_8', $diag_slug, array($diagSetting,'showPage'), '');
        add_submenu_page($diag_slug, '一般', '一般', 'level_8', $diag_slug, array($diagSetting,'showPage'));
        add_submenu_page($diag_slug, '履歴管理', '履歴管理', 'level_8', $diag_slug.'_history', array($diagHistory,'showPage'));
        add_submenu_page($diag_slug, '回答設定', '回答設定', 'level_8', $diag_slug.'_rule', array($diagRule, 'showPage'));
    }
}
plugins_url( 'config.php', __FILE__ );
$diagPlugin = new DiagPlugin;

