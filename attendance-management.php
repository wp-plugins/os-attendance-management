<?php
/*
Plugin Name: 出勤・勤怠管理プラグイン
Plugin URI: http://lp.olivesystem.jp/plugin-am
Description: WordPressで出勤（勤怠）管理ができるプラグインです
Version: 1.1.0
Author: OLIVESYSTEM（オリーブシステム）
Author URI: http://lp.olivesystem.jp/
*/
if(!isset($wpdb)){
	global $wpdb;
}
// 現在のプラグインバージョン
define('OSAM_PLUGIN_VERSION','1.1.0');
// DBにデータを保存する項目名
define('OSAM_PLUGIN_DATA_NAME','os_attendance_management_Plugin');
// テーブル名
define('OSAM_PLUGIN_TABLE_NAME', $wpdb->prefix.'os_attendance_plugin_data');
// プラグインのディレクトリ
define('OSAM_PLUGIN_DIR', plugin_dir_path(__FILE__));
// テキストメインのPHPファイルをいれているディレクトリ
define('OSAM_PLUGIN_INCLUDE_FILES', OSAM_PLUGIN_DIR.'include_files');
// 時刻を日本時間に設定
date_default_timezone_set('Asia/Tokyo');
// 共通class
include OSAM_PLUGIN_DIR."attendanceClass.php";
// 表示側
include OSAM_PLUGIN_DIR."attendanceMainClass.php";
$attendanceMain = new AttendanceMain();
// 管理画面側
include OSAM_PLUGIN_DIR."attendanceUserClass.php";
$attendanceUser = new AttendanceUser();
include OSAM_PLUGIN_DIR."attendanceAdminClass.php";
$attendanceAdmin = new AttendanceAdmin();
?>
