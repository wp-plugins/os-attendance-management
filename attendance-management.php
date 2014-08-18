<?php
/*
Plugin Name: 出勤・勤怠管理プラグイン
Plugin URI: http://olivesystem.jp/lp/plugin-am
Description: WordPressで出勤（勤怠）管理ができるプラグインです
Version: 1.0.3
Author: OLIVESYSTEM（オリーブシステム）
Author URI: http://www.olivesystem.com/
*/
// 現在のプラグインバージョン
define('PLUGIN_VERSION','1.0.3');
// 現在のテーブルバージョン
define('PLUGIN_TABLE_VERSION','1.0');
// DBにデータを保存する項目名
define('PLUGIN_VERSION_NAME','os_attendance_management_PluginVersion');
define('PLUGIN_TABLE_VERSION_NAME','os_attendance_management_PluginTableVersion');
define('PLUGIN_DATA_NAME','os_attendance_management_Plugin');
// テーブル名
define('PLUGIN_TABLE_NAME', $wpdb->prefix.'os_attendance_plugin_data');
// プラグインのディレクトリ
define('AM_PLUGIN_DIR', plugin_dir_path(__FILE__));
// テキストメインのPHPファイルをいれているディレクトリ
define('PLUGIN_INCLUDE_FILES', AM_PLUGIN_DIR.'include_files');
// 時刻を日本時間に設定
date_default_timezone_set('Asia/Tokyo');
// 共通class
include AM_PLUGIN_DIR."attendanceClass.php";
// 表示側
include AM_PLUGIN_DIR."attendanceMainClass.php";
$attendanceMain = new AttendanceMain();
// 管理画面側
include AM_PLUGIN_DIR."attendanceUserClass.php";
$attendanceUser = new AttendanceUser();
include AM_PLUGIN_DIR."attendanceAdminClass.php";
$attendanceAdmin = new AttendanceAdmin();
?>
