<?php
class AttendanceAdmin extends AttendanceClass {

	public function __construct(){

		parent::__construct();
		// プラグインを有効化したとき
		if(function_exists('register_activation_hook')){
			register_activation_hook('attendance-management', array('AttendanceAdmin', 'activationPlugin'));
		}
		// まず実行
		add_action('admin_init', array('AttendanceAdmin', 'actionAdminInit'));
		// POST処理
		//add_action('admin_init', self::admin_post());
		add_action('admin_init', array('AttendanceAdmin', 'admin_post'));
		// 管理画面メニュー
		add_action('admin_menu', array('AttendanceAdmin', 'menuViews'));

	}
	// プラグインメニュー
	public function menuViews(){

		global $am_plugin_option_data; // オプションデータ
		global $am_plugin_user_data; // ユーザデータ

		// CSS
		add_action('admin_init', array('AttendanceClass', 'admin_css_read'));

		// ゲストは管理画面を表示させない。トップページへ
		if(isset($am_plugin_user_data['level']) && $am_plugin_user_data['level']=='guest'){

			wp_safe_redirect(home_url('/'));
			exit;

		// 登録ユーザのみのメニュー表示、処理
		}elseif(isset($am_plugin_option_data['time_write']) && $am_plugin_user_data['level']!='administrator'){

			global $attendanceUser;
			switch($am_plugin_option_data['time_write']){
				case 'user':
					$attendanceUser->menuViews();
					break;
				case 'user-post':
					$attendanceUser->menuViews('1');
					break;
			}

		// 管理者のときのメニュー表示、処理
		}else{

			// POST処理
			if(isset($_GET) && isset($_GET['page'])){
				if(isset($_POST)){
					if(stristr($_GET['page'], "attendance-management") || (isset($_POST['page']) && stristr($_POST['page'], "attendance-management"))){
						if(isset($_POST['format'])){
							self::formatPlugin();
						}elseif(isset($_POST['option'])){
							self::optionPost();
						}
					}
				}
			}

			// メニュー表示
			add_menu_page('出勤・勤怠プラグイン', '出勤・勤怠プラグイン', 'administrator', 'attendance-management-view.php', array('AttendanceAdmin', 'adminPage'));
			add_submenu_page('attendance-management-view.php', '出勤・勤怠管理の基本設定', '基本設定', 'administrator', 'attendance-management-options.php', array('AttendanceAdmin', 'optionPage'));
			add_submenu_page('attendance-management-view.php', '出勤・勤怠の一覧', '出勤・勤怠の一覧', 'administrator', 'attendance-management-list.php', array('AttendanceAdmin', 'listPage'));
			add_submenu_page('attendance-management-view.php', '出勤・勤怠の新規作成', '出勤・勤怠の新規作成', 'administrator', 'attendance-management-post.php', array('AttendanceAdmin', 'postPage'));
			add_submenu_page('attendance-management-view.php', 'プロフ（スキル）設定', 'プロフ（スキル）設定', 'administrator', 'attendance-management-prof-option.php', array('AttendanceProf', 'profOptionPage'));
			add_submenu_page('attendance-management-view.php', 'ヘルプページ', 'ヘルプページ', 'administrator', 'attendance-management-help.php', array('AttendanceAdmin', 'helpPage'));
			// メニューに非表示するページ
			add_submenu_page('attendance-management-list.php', '出勤・勤怠の編集', null, 'administrator', 'attendance-management-write.php', array('AttendanceAdmin', 'writePage'));
			add_submenu_page('attendance-management-options.php', 'プラグインの初期化', null, 'administrator', 'attendance-management-format.php', array('AttendanceAdmin', 'formatPage'));
			add_submenu_page('attendance-management-options.php', '利用規約', null, 'administrator', 'attendance-management-agreement.php', array('AttendanceAdmin', 'agreementPage'));

		}

	}
	/*
	*  ページビュー
	*/
	// Page はじめに
	public function adminPage(){

		include_once(OSAM_PLUGIN_INCLUDE_FILES."/admin-adminPage.php");

	}
	// Page 基本設定
	public function optionPage(){

		$data = $GLOBALS['am_plugin_option_data'];
		$data_list = (isset($data['list'])) ? $data['list'] : '';
		$data_csv = (isset($data['csv'])) ? $data['csv'] : '';
		$message = self::updateMessage();
		$time_view_selected = parent::html_array_select($data['time_view'], '3', array('0', '1', '2'));
		$time_write_selected = parent::html_array_select($data['time_write'], '3', array('admin', 'user', 'user-post'));
		$view_list_checked = parent::html_array_check($data['view-list'], '4', array('1', '2', '3', 'm'));
		$admin_list_checked = parent::html_array_check($data['admin-list'], '4', array('1', '2', '3', 'm'));
		$clock_checked = parent::html_array_check($data['clock'], '2', array('0', '1'));

		include_once(OSAM_PLUGIN_INCLUDE_FILES."/admin-optionPage.php");

	}
	// Page　出勤・勤怠の新規作成
	public function postPage(){

		global $am_plugin_user_data;
		$users = self::getMember();
		$message = self::updateMessage();
		self::_postPage($message, $users);

	}
	// Page　ヘルプページ
	public function helpPage(){

		include_once(OSAM_PLUGIN_INCLUDE_FILES."/admin-helpPage.php");

	}
	// Page　出勤・勤怠の一覧
	public function listPage(){

		$message = '';
		$users_data = parent::getMember();
		self::_listPage($message, $users_data);

	}
	// Page　出勤・勤怠の編集
	public function writePage(){

		global $am_plugin_user_data;
		$data = self::working_get_data();
		$message = self::updateMessage();
		$form_html = self::post_form_page('', '1', $data['form_arr']);
		$form_user_html = $data['user_form'];
		$form_day_html = $data['day_form'];
		$form_message = $data['message'];
		$break_selected = $data['break_selected'];
		$over_selected = $data['over_selected'];
		include_once(OSAM_PLUGIN_INCLUDE_FILES."/user-writePage.php");

	}
	// Page 利用規約
	public function agreementPage(){

		include_once(OSAM_PLUGIN_INCLUDE_FILES."/admin-agreementPage.php");

	}
	// Page　初期化するかどうか確認するページ
	public function formatPage(){

		include_once(OSAM_PLUGIN_INCLUDE_FILES."/admin-formatPage.php");

	}
	/*
	*  初期設定
	*/
	// プラグインが有効化されたときに実行する
	public function activationPlugin(){

		// テーブルが存在するか確認
		$table_exists = self::show_table(OSAM_PLUGIN_TABLE_NAME);
		if(!$table_exists){
			self::newTable();
		}

	}
	// 初期設定
	private function firstOption(){

		// 設定を初期化
		$arr = array(
				'time_view' => '0', 'time_write' => 'admin', 'license' => 'free', 'view-list' => '1', 'admin-list' => '2', 'clock'=>'1',
			);
		update_option(OSAM_PLUGIN_DATA_NAME, $arr);

	}
	/*
	*  設定ページ
	*/
	// プラグインが初期化されたときに実行する
	private function formatPlugin(){

		delete_option(OSAM_PLUGIN_DATA_NAME);
		// テーブルが存在するか確認
		$table_exists = self::show_table(OSAM_PLUGIN_TABLE_NAME);
		// テーブルが存在すればデータ削除、なければテーブルを新規作成
		if($table_exists){

			$sql = "DELETE FROM ".OSAM_PLUGIN_TABLE_NAME.";";
			self::sql_query($sql);

		}else{

			self::newTable();

		}

		self::firstOption();

		// リダイレクト
		if(get_option(OSAM_PLUGIN_DATA_NAME)){
			wp_safe_redirect(admin_url('/').'admin.php?page=attendance-management-options.php&msg=format-ok');
			exit;
		}else{
			wp_safe_redirect(admin_url('/').'admin.php?page=attendance-management-options.php&msg=format-error');
			exit;
		}

	}
	/*
	*  メニューを呼び出す前に実行
	*/
	public function actionAdminInit(){

		global $am_plugin_user_data;

		// 管理者権限のときのみ実行
		if(isset($am_plugin_user_data['level']) && $am_plugin_user_data['level']=='administrator'){

			if(isset($_GET) && isset($_GET['csv_dl'])){
				self::csv_export();
			}

		}

		// jQuery
		wp_enqueue_script('jquery');

	}
	// CSV出力
	private function csv_export(){

		// データ取得
		if(isset($_GET) && isset($_GET['csv_dl'])){
			$data_arr = self::get_list_sql($_GET);
			$now = date("Y-m-d", time()); // 現在時刻
			$csv_word = str_replace(array(": ", " / ", " ", "～"), array("", "_", "_", "から"), $data_arr['word']); // ファイル名に使用

			switch($_GET['csv_dl']){
				case '1':
					$csv_file = "勤怠一覧_".$now."_".$csv_word.'.csv';
					$csv_data = self::csv_data($data_arr['data']);
					break;
				case '2':
					$csv_file = "勤怠合計_".$now."_".$csv_word.'.csv';
					$csv_data = self::csv_data_total($data_arr['data']);
					break;
			}

			// エンコード
			$csv_data = mb_convert_encoding($csv_data, "sjis-win", 'UTF-8');
			// ヘッダー
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename={$csv_file}");
			// データの出力
			echo($csv_data);
			exit();
		}

	}
	// CSVデータ生成(一覧)
	private function csv_data($data=''){

		global $am_plugin_option_data;
		$options = $am_plugin_option_data;
		$options_list = (isset($options['csv'])) ? $options['csv'] : '';
		$user_arr = array();
		$csv_data = '"ユーザ名","勤務日",';
		// 業務
		if(!empty($options_list['work_start'])){ $csv_data .= '"業務開始時刻",'; }
		if(!empty($options_list['work_end'])){ $csv_data .= '"業務終了時刻",'; }
		if(!empty($options_list['work_time'])){ $csv_data .= '"業務時間",'; }
		// 休憩
		if(!empty($options_list['break_start'])){ $csv_data .= '"休憩開始時刻",'; }
		if(!empty($options_list['break_end'])){ $csv_data .= '"休憩終了時刻",'; }
		if(!empty($options_list['break_time'])){ $csv_data .= '"休憩時間",'; }
		// 残業
		if(!empty($options_list['overtime_start'])){ $csv_data .= '"残業開始時刻",'; }
		if(!empty($options_list['overtime_end'])){ $csv_data .= '"残業終了時刻",'; }
		if(!empty($options_list['overtime_time'])){ $csv_data .= '"残業時間",'; }
		// etc
		if(!empty($options_list['daywork'])){ $csv_data .= '"実働時間",'; }
		if(!empty($options_list['message'])){ $csv_data .= '"メッセージ",'; }
		$csv_data .= "\n";
		// foreach
		foreach($data as $d){
			$uid = $d->user_id;
			// ユーザデータ取得
			if(empty($user_arr[$uid])){
				$user = get_users(array('orderby'=>'ID','order'=>'ASC', 'include'=>$uid));
				$user_arr[$uid] = $user;
			}else{
				$user = $user_arr[$uid];
			}
			//
			$start_time = self::work_point(1, $d->start_time, $d->start_i_time);
			$finish_time = self::work_point(1, $d->finish_time, $d->finish_i_time);
			$work_time = self::time_plus(0, self::time_minus($finish_time, $start_time));
			// 休憩処理
			$break_start = self::work_point($d->break_point, $d->break_start_time, $d->break_start_i_time);
			$break_finish = self::work_point($d->break_point, $d->break_finish_time, $d->break_finish_i_time);
			$break_time = self::time_plus(0, self::time_minus($break_finish, $break_start));
			// 残業処理
			$over_start = self::work_point($d->over_point, $d->over_start_time, $d->over_start_i_time);
			$over_finish = self::work_point($d->over_point, $d->over_finish_time, $d->over_finish_i_time);
			$overtime_time = self::time_plus(0, self::time_minus($over_finish, $over_start));
			//
			$daywork = $work_time - $break_time - $overtime_time;
			$date_ex = explode(" ", $d->date);
			$csv_data .= '"'.$user[0]->data->display_name.'","'.$date_ex[0].'",';
			// 業務
			if(!empty($options_list['work_start'])){ $csv_data .= '"'.$start_time.'",'; }
			if(!empty($options_list['work_end'])){ $csv_data .= '"'.$finish_time.'",'; }
			if(!empty($options_list['work_time'])){ $csv_data .= '"'.$work_time.'H",'; }
			// 休憩
			if(!empty($options_list['break_start'])){ $csv_data .= '"'.$break_start.'",'; }
			if(!empty($options_list['break_end'])){ $csv_data .= '"'.$break_finish.'",'; }
			if(!empty($options_list['break_time'])){ $csv_data .= '"'.$break_time.'H",'; }
			// 残業
			if(!empty($options_list['overtime_start'])){ $csv_data .= '"'.$over_start.'",'; }
			if(!empty($options_list['overtime_end'])){ $csv_data .= '"'.$over_start.'",'; }
			if(!empty($options_list['overtime_time'])){ $csv_data .= '"'.$overtime_time.'H",'; }
			// etc
			if(!empty($options_list['daywork'])){ $csv_data .= '"'.$daywork.'H",'; }
			if(!empty($options_list['message'])){ $csv_data .= '"'.self::h($d->text).'",'; }
			$csv_data .= "\n";
		}

		return trim($csv_data);

	}
	// CSVデータ生成(合計)
	private function csv_data_total($data=''){

		$total_time = 0;
		$break_total_time = 0;
		$over_total_time = 0;
		$user_arr = array();
		$csv_data = '"ユーザ名","勤務時間","休憩時間","残業時間","実働時間"'."\n";

		// 加算処理
		foreach($data as $d){
			$uid = $d->user_id;
			// 個別計算用
			if(empty($user_arr[$uid])){
				$user = get_users(array('orderby'=>'ID','order'=>'ASC', 'include'=>$uid));
				$user_arr[$uid] = array(
					'name'=>$user[0]->data->display_name, 'work'=>'0', 'break'=>'0', 'over'=>'0',
				);
			}
			// 勤怠時間の加算
			$start_time = $d->start_time.":".$d->start_i_time;
			$finish_time = $d->finish_time.":".$d->finish_i_time;
			$work_hi = self::time_minus($finish_time, $start_time); // 時分をだす
			$user_arr[$uid]['work'] = self::time_plus($user_arr[$uid]['work'], $work_hi); // 個別
			$total_time = self::time_plus($total_time, $work_hi); // 総数
			// 休憩処理
			$break_start = self::work_point($d->break_point, $d->break_start_time, $d->break_start_i_time);
			$break_finish = self::work_point($d->break_point, $d->break_finish_time, $d->break_finish_i_time);
			$break_hi = self::time_minus($break_finish, $break_start);
			$user_arr[$uid]['break'] = self::time_plus($user_arr[$uid]['break'], $break_hi); // 個別
			$break_total_time = self::time_plus($break_total_time, $break_hi); // 総数
			// 残業処理
			$over_start = self::work_point($d->over_point, $d->over_start_time, $d->over_start_i_time);
			$over_finish = self::work_point($d->over_point, $d->over_finish_time, $d->over_finish_i_time);
			$over_hi = self::time_minus($over_finish, $over_start);
			$user_arr[$uid]['over'] = self::time_plus($user_arr[$uid]['over'], $over_hi); // 個別
			$over_total_time = self::time_plus($over_total_time, $over_hi); // 総数

		}

		// CSVデータ化
		foreach($user_arr as $u){

			$user_total_work = ($u['work'] + $u['over']) - $u['break'];
			$user_total_work = self::floor_point($user_total_work); // 小数第二位を切り捨て
			$csv_data .= '"'.$u['name'].'","'.$u['work'].'","'.$u['break'].'","'.$u['over'].'","'.$user_total_work.'"'."\n";

		}

		// 総数
		$total_work = ($total_time + $over_total_time) - $break_total_time;
		$total_work = self::floor_point($total_work); // 小数第二位を切り捨て
		$csv_data .= '"合計","'.$total_time.'","'.$break_total_time.'","'.$over_total_time.'","'.$total_work.'"';

		return trim($csv_data);

	}
	// 休憩、残業の処理
	private function work_point($point='0', $htime='00', $itime='00'){

		$_return = '';

		if($point=='1'){
			$_return = $htime.':'.$itime;
		}else{
			$_return = 0;
		}

		return $_return;

	}
	/*
	*  メッセージ
	*/
	public function updateMessage(){

		$return_data = '';

		if(isset($_GET) && isset($_GET['msg'])){
			switch($_GET['msg']){

				case "format-ok":
					$return_data .= "初期化しました<br />";
					break;
				case "format-error":
					$return_data .= "初期化に失敗しました<br />";
					break;
				case "ok":
					$return_data .= "更新しました<br />";
					break;
				case "error":
					$return_data .= "更新に失敗しました<br />";
					break;

			}
		}

		$return_data .= self::_updateMessage();

		return $return_data;

	}
	/*
	*  POST処理
	*/
	// ユーザ、管理者の共通のPOST処理
	public function admin_post(){

		global $am_plugin_user_data;

		if(isset($_GET) && isset($_GET['page'])){
			//
			if(isset($_POST) && is_array($_POST)){
				$post = $_POST;
			}else{
				$post = array();
			}
			//
			if(stristr($_GET['page'], "attendance-management") || (isset($post['page']) && stristr($post['page'], "attendance-management"))){
				//
				if(isset($am_plugin_user_data['level']) && $am_plugin_user_data['level']=='guest'){
					wp_safe_redirect(home_url('/'));
					exit;
				}else{
					//
					if(isset($am_plugin_user_data['level']) && $am_plugin_user_data['level']=='administrator'){
						$insert_url = 'attendance-management-post.php';
						$update_url = 'attendance-management-write.php';
					}else{
						$insert_url = 'attendance-management-user-post.php';
						$update_url = 'attendance-management-user-write.php';
					}
					//
					if(!empty($post['new'])){
						$insert_id = self::post_insert();
						// リダイレクト処理
						if(!empty($insert_id)){
							wp_safe_redirect(admin_url('/').'admin.php?page='.$insert_url.'&msg=insert-ok');
							exit;
						}else{
							wp_safe_redirect(admin_url('/').'admin.php?page='.$insert_url.'&msg=insert-ng');
							exit;
						}
					//
					}elseif(!empty($post['Delete'])){
						$delete_id = self::post_delete();
						// リダイレクト処理
						if(!empty($delete_id)){
							wp_safe_redirect(admin_url('/').'admin.php?page='.$insert_url.'&msg=delete-ok');
							exit;
						}else{
							wp_safe_redirect(admin_url('/').'admin.php?page='.$insert_url.'&msg=delete-ng');
							exit;
						}
					}elseif(!empty($post['write'])){
						$update_id = self::post_write();
						// リダイレクト処理
						if(!empty($update_id)){
							wp_safe_redirect(admin_url('/').'admin.php?page='.$update_url.'&did='.$update_id.'&msg=write-ok');
							exit;
						}else{
							wp_safe_redirect(admin_url('/').'admin.php?page='.$update_url.'&msg=write-ng');
							exit;
						}
					}elseif(!empty($post['list_search']) || !empty($post['search_back_month']) || !empty($post['search_now_month']) || !empty($post['search_next_month'])){
						self::list_search_redirect();
					}

				}
			}

		}

	}
	// 管理者権限で管理画面のとき、編集を可能にする
	private function admin_post_write(){

		add_action('admin_init', self::post_write());

	}
	// 基本設定、POSTの処理
	private function optionPost(){

		$update_array = parent::arrayData($_POST);
		update_option(OSAM_PLUGIN_DATA_NAME, $update_array);

		// リダイレクト
		if(get_option(OSAM_PLUGIN_DATA_NAME)){
			wp_safe_redirect(admin_url('/').'admin.php?page=attendance-management-options.php&msg=ok');
			exit;
		}else{
			wp_safe_redirect(admin_url('/').'admin.php?page=attendance-management-options.php&msg=error');
			exit;
		}

	}
	/*
	*  SQL
	*/
	// テーブルの存在チェック
	public function show_table($tbl){

		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tbl));

	}
	// バージョン情報を保存し、プラグイン用のテーブルを新規作成
	private function newTable(){

		global $wpdb;
		// テーブルを作成
		/*
		*  data_id データid、 user_id WPの登録ユーザid、 text テキスト、 status 状態 0=削除　1=実働時間　2＝予定時間　3＝休み、
		*  start_time 開始時間（24時間表記）、 start_i_time 開始分、 finish_time 終了時間（24時間表記）、 finish_i_time 終了分、
		*  break_～ 休憩時間、 break_point 休憩ありなし
		*  over_～ 残業時間、over_point 残業ありなし
		*  date 稼動する年月日、 create_time 作成日、 update_time 更新日、
		*/
		$charset = defined("DB_CHARSET") ? DB_CHARSET : "utf8";
		$sql = "CREATE TABLE " .OSAM_PLUGIN_TABLE_NAME. " (\n".
				"`data_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,\n".
				"`user_id` bigint(20) UNSIGNED DEFAULT '0' NOT NULL,\n".
				"`text` text,\n".
				"`status` int(2) UNSIGNED DEFAULT '0' NOT NULL,\n".
				"`start_time` int(2),\n".
				"`start_i_time` int(2),\n".
				"`finish_time` int(2),\n".
				"`finish_i_time` int(2),\n".
				"`break_start_time` int(2),\n".
				"`break_start_i_time` int(2),\n".
				"`break_finish_time` int(2),\n".
				"`break_finish_i_time` int(2),\n".
				"`break_point` int(1) UNSIGNED DEFAULT '1' NOT NULL,\n".
				"`over_start_time` int(2),\n".
				"`over_start_i_time` int(2),\n".
				"`over_finish_time` int(2),\n".
				"`over_finish_i_time` int(2),\n".
				"`over_point` int(1) UNSIGNED DEFAULT '0' NOT NULL,\n".
				"`date` DATETIME,\n".
				"`create_time` DATETIME,\n".
				"`update_time` TIMESTAMP,\n".
				"UNIQUE(`data_id`)\n".
			") ENGINE = MyISAM DEFAULT CHARSET = ".$charset;
		self::sql_performs($sql);

	}

}
?>