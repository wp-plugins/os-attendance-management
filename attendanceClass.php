<?php
// エラーを避けるために使用する変数
$plugin_option_data = ''; // オプションデータ用
$plugin_user_data = ''; // ログインユーザデータ
$plugin_sqlfile_check = ''; // SQL実行時に使用
// class開始
class AttendanceClass {

	public function __construct(){

		add_action('plugins_loaded', array('AttendanceClass', 'plugin_get_option'));
		// エラー回避のためプラグイン読み込み時にアクション
		add_action('plugins_loaded', array('AttendanceClass', 'action_level'));
		// ヘッダの処理
		add_action('wp_head', array('AttendanceClass', 'action_head'));

	}
	// オプション取得
	public function plugin_get_option(){

		$GLOBALS['plugin_option_data'] = get_option(PLUGIN_DATA_NAME);

	}
	// ユーザデータ、権限の取得
	public function action_level(){

		// ログインしてるとき、ユーザデータを取得し、グローバル変数へ
		if(is_user_logged_in()){

			global $current_user;
			get_currentuserinfo();
			$arr = array();

			foreach($current_user->data as $key => $user){

				if($key!='user_pass' && $key!='user_activation_key'){
					$arr[$key] = $user;
				}

			}

			$arr['level'] = $current_user->roles[0];
			$GLOBALS['plugin_user_data'] = $arr;

		}else{

			$GLOBALS['plugin_user_data'] = array('level'=>'guest');

		}

	}
	// 管理画面用のCSSを割り当て
	public function admin_css_read(){

		add_action('wp_head', self::admin_css_action());

	}
	private function admin_css_action(){

		$src = plugins_url('attendance-admin.css', __FILE__);
		wp_register_style('attendance-admin', $src);
		wp_enqueue_style('attendance-admin');

	}
	// WP登録ユーザの情報取得
	public function getMember($id='', $order='ASC'){

		$data = array();
		$i = 0;

		// 指定IDユーザのみ
		if(!empty($id)){
			$users = get_users(array('orderby'=>ID,'order'=>$order, 'include'=>$id));
		// 全取得
		}else{
			$users = get_users(array('orderby'=>ID,'order'=>$order));
		}

		foreach($users as $u){

			$data[$i]['id'] = $u->ID;
			$data[$i]['url'] = $u->data->user_url;
			$data[$i]['login'] = $u->data->user_login;
			$data[$i]['nicename'] = $u->data->user_nicename;
			$data[$i]['email'] = $u->data->user_email;
			$data[$i]['registered'] = $u->data->user_registered;
			$data[$i]['status'] = $u->data->user_status;
			$data[$i]['name'] = $u->data->display_name;
			$data[$i]['roles'] = $u->roles[0];
			$i++;

		}

		return $data;

	}
	// データをarrayで整理
	public function arrayData($data=''){

		$arr = array();

		foreach($data as $key => $d){

			if($key=='Submit' || $key=='submit'){

			}elseif($key=='time_view'){
				$arr[$key] = self::sql_escape($d, 1);
			}else{
				$arr[$key] = self::sql_escape($d);
			}

		}

		return $arr;

	}
	/*
	*  共通ページ処理
	*/
	// Page　出勤・勤怠の新規作成
	public function _postPage($message='', $users=''){

		$form_html = self::post_form_page($users);
		$select_options = array();
		$select_options[0] = self::this_month_view();
		$select_options[1] = self::last_month_view();
		$select_options[2] = self::next_month_view();
		include_once(PLUGIN_INCLUDE_FILES."/user-postPage.php");

	}
	// Page　出勤・勤怠の一覧
	public function _listPage($message='', $users_data='', $type=''){

		$users = self::_users_data($users_data);
		$users_html = self::users_html($users, $type); // 検索フォームで使用
		$search_option_html = self::search_ymd_options(); // 検索フォームで使用
		$get_data = self::get_list_sql($_GET);
		$list_html = self::listHtml($get_data['data'], $users);
		$listHidden = self::search_list_hidden();
		$list_message = $get_data['word'];
		$csv_link = self::get_param();

		if(empty($get_data['data'])){
			$message .= '<p style="color:red;">指定した範囲にはデータがありません</p>';
		}

		if(empty($type)){
			$form_url = 'attendance-management-list.php';
		}else{
			$form_url = 'attendance-management-user-list.php';
		}

		include_once(PLUGIN_INCLUDE_FILES."/user-listPage.php");

	}
	/*
	*  POST処理
	*/
	// 変換後リダイレクト
	public function list_search_redirect(){

		global $plugin_user_data;
		$post_url = '';

		// 開始年月日
		if(!empty($_POST['start_y']) && !empty($_POST['start_m']) && !empty($_POST['start_d'])){
				$post_url .= "&start_day=".self::h($_POST['start_y'])."-".self::h($_POST['start_m'])."-".self::h($_POST['start_d']);
				unset($_POST['start_y']);	unset($_POST['start_m']);	unset($_POST['start_d']);
		}
		// 終了年月日
		if(!empty($_POST['end_y']) && !empty($_POST['end_m']) && !empty($_POST['end_d'])){
			$post_url .= "&end_day=".self::h($_POST['end_y'])."-".self::h($_POST['end_m'])."-".self::h($_POST['end_d']);
			unset($_POST['end_y']);	unset($_POST['end_m']);	unset($_POST['end_d']);
		}
		// ユーザid指定
		if( (!empty($_POST['uid']) && $plugin_user_data['level']!='administrator') || (isset($_POST['uid']) && empty($_POST['uid'])) ){
			unset($_POST['uid']);
		}
		/*
		*  $_POST['search_back_month']、$_POST['search_now_month']、$_POST['search_next_month']の処理
		*/
		$now_month = date("Y-m-01", time());
		// 一ヶ月前
		if(!empty($_POST['search_back_month'])){
			$start_day = date("Y-m-01", strtotime($now_month.' -1 month'));
			$post_url .= "&start_day=".$start_day;
			$end_day = date("Y-m-d", strtotime($now_month.' -1 day'));
			$post_url .= "&end_day=".$end_day;
		}elseif(!empty($_POST['search_now_month'])){
			$post_url .= "&start_day=".$now_month;
			$next_month = date("Y-m-01", strtotime($now_month.' +1 month'));
			$end_day = date("Y-m-d", strtotime($next_month.' -1 day'));
			$post_url .= "&end_day=".$end_day;
		}elseif(!empty($_POST['search_next_month'])){
			$next_month = date("Y-m-01", strtotime($now_month.' +1 month'));
			$post_url .= "&start_day=".$next_month;
			$next_next_month = date("Y-m-01", strtotime($next_month.' +1 month'));
			$end_day = date("Y-m-d", strtotime($next_next_month.' -1 day'));
			$post_url .= "&end_day=".$end_day;
		}
		// 上記時のユーザ指定（管理者のみ可能）
		if(!empty($_POST['user_type']) && $plugin_user_data['level']=='administrator' && $_POST['user_type']!='all' && $_POST['user_type']!='only'){
			$post_url .= "&uid=".self::h($_POST['user_type']);
			unset($_POST['user_type']);
		}

		foreach($_POST as $key => $p){

			if($key=='Submit' || $key=='list_search'){
				// 何もしない
			}elseif($key=='keyword'){
				$post_url .= "&".self::h($key)."=".urlencode(self::h($p));
			}else{
				$post_url .= "&".self::h($key)."=".self::h($p);
			}

		}

		if($plugin_user_data['level']=='administrator'){

			wp_safe_redirect(admin_url('/').'admin.php?page=attendance-management-list.php'.$post_url);
			exit;

		}else{

			wp_safe_redirect(admin_url('/').'admin.php?page=attendance-management-user-list.php'.$post_url);
			exit;

		}

	}
	// 編集
	public function post_write(){

		global $plugin_user_data;

		// 管理者以外は自分の勤怠しか操作できない
		if($plugin_user_data['level']=='administrator'){
			return self::update_post_write();
		}else{
			// idが一致しない場合は処理しない
			if($plugin_user_data['ID']!=$_POST['id']){
				wp_safe_redirect(admin_url('/').'admin.php?page=attendance-management-write.php&did=&msg=write-user-ng');
				exit;
			}else{
				return self::update_post_write();
			}
		}

	}
	// 削除
	public function post_delete(){

		global $plugin_user_data;

		// 管理者以外は自分の勤怠しか操作できない
		if($plugin_user_data['level']=='administrator'){
			return self::update_post_delete();
		}else{
			// idが一致しない場合は処理しない
			if($plugin_user_data['ID']!=$_POST['id']){
				wp_safe_redirect(admin_url('/').'admin.php?page=attendance-management-write.php&did=&msg=write-user-ng');
				exit;
			}else{
				return self::update_post_delete();
			}
		}

	}
	/*
	*  GET処理
	*/
	// 指定した勤務idを取得、フォーム表示用に修正
	public function working_get_data(){

		$return_array = array();
		$data = self::get_id_data();
		$uid = $data->user_id;
		$user_data = $data->user_data;
		$wdate = explode(" ", $data->date);
		$work_date = explode("-", $wdate[0]);
		$return_array['data'] = $data;
		if(empty($user_data[nickname])){
			$nickname = '';
		}else{
			$nickname = '('.$user_data[nickname].')';
		}
		$return_array['user_form'] = $user_data[name].$nickname.'<input type="hidden" name="id" value="'.$uid.'" /><input type="hidden" name="did" value="'.$data->data_id.'" />';
		$return_array['form_arr'] = array(
			'0'=>$data->start_time, '1'=>$data->start_i_time, '2'=>$data->finish_time, '3'=>$data->finish_i_time, '4'=>$data->break_start_time, '5'=>$data->break_start_i_time, '6'=>$data->break_finish_time, '7'=>$data->break_finish_i_time,
		);
		$return_array['day_form'] = $work_date[0].'年'.$work_date[1].'月'.$work_date[2].'日<input type="hidden" name="work_day" value="'.$wdate[0].'" />';
		// 休憩のラジオボタンのチェック
		if(empty($data->break_point)){
			$return_array['break_selected'] = array("0"=>' checked', "1"=>'');
		}else{
			$return_array['break_selected'] = array("0"=>'', "1"=>' checked');
		}
		// 残業のラジオボタンのチェック
		if(empty($data->over_point)){
			$return_array['over_selected'] = array("0"=>' checked', "1"=>'');
		}else{
			$return_array['over_selected'] = array("0"=>'', "1"=>' checked');
		}
		// メッセージ
		$return_array['message'] = $data->text;

		return $return_array;

	}
	/*
	*  HTMLの処理
	*/
	// リスト
	public function listHtml($data='', $users='', $type=''){

		$html = '';
		global $plugin_user_data;
		global $plugin_option_data;

		// データがあれば実行
		if(!empty($data[0])){

			if($type!='single'){
				$user_html = '<th>ユーザ名</th>';
			}

			if($plugin_user_data['level']=='administrator'){
				$url = 'attendance-management-write.php';
			}else{
				$url = 'attendance-management-user-write.php';
				$user_html = '';
			}

			$html .="<th>ID</th>{$user_html}<th>勤務日</th><th>開始時間</th><th>終了時間</th><th>休憩開始</th><th>休憩終了</th><th>残業開始</th><th>残業終了</th><th>メッセージ</th><th class=\"right\"></th>";

			$cl = self::clockText($plugin_option_data['clock']);

			$user_arr = array();
			$total_time = 0;
			$break_total_time = 0;
			$over_total_time = 0;

			foreach($data as $key => $d){

				if($type!='single'){
					$uid = $data[$key]->user_id;
					$name = $users[$uid]['name'];
					$user_html = "<td>".$name."</td>";
				}
				// 個別計算用
				if(empty($user_arr[$uid])){
					$user_arr[$uid] = array(
						'name'=>$name, 'work'=>'0', 'break'=>'0', 'over'=>'0',
					);
				}

				// 勤怠時間の加算
				$start_hi_time = $data[$key]->start_time.":".$data[$key]->start_i_time;
				$finish_hi_time = $data[$key]->finish_time.":".$data[$key]->finish_i_time;
				$work_hi = self::time_minus($finish_hi_time, $start_hi_time); // 時分をだす
				$user_arr[$uid]['work'] = self::time_plus($user_arr[$uid]['work'], $work_hi); // 個別
				$total_time = self::time_plus($total_time, $work_hi); // 総数
				//
				$work_day = date("Y年m月d日", strtotime($data[$key]->date));
				$week_name = self::dayOfTheWeek(date("N", strtotime($data[$key]->date)));
				$work_start = $data[$key]->start_time.$cl[0].$data[$key]->start_i_time.$cl[1];
				$work_end= $data[$key]->finish_time.$cl[0].$data[$key]->finish_i_time.$cl[1];
				// 休憩時間
				if(!empty($data[$key]->break_point)){
					$break_start = $data[$key]->break_start_time.$cl[0].$data[$key]->break_start_i_time.$cl[1];
					$break_end= $data[$key]->break_finish_time.$cl[0].$data[$key]->break_finish_i_time.$cl[1];
					// 総休憩時間の算出用
					$break_start_hi_time = $data[$key]->break_start_time.":".$data[$key]->break_start_i_time;
					$break_finish_hi_time = $data[$key]->break_finish_time.":".$data[$key]->break_finish_i_time;
				}else{
					$break_start = ' - ';
					$break_end= ' - ';
					$break_start_hi_time = '00:00';
					$break_finish_hi_time = '00:00';
				}
				// 休憩時間の加算
				$break_hi = self::time_minus($break_finish_hi_time, $break_start_hi_time);
				$user_arr[$uid]['break'] = self::time_plus($user_arr[$uid]['break'], $break_hi); // 個別
				$break_total_time = self::time_plus($break_total_time, $break_hi); // 総数
				// 残業時間
				if(!empty($data[$key]->over_point)){
					$over_start = $data[$key]->over_start_time.$cl[0].$data[$key]->over_start_i_time.$cl[1];
					$over_end= $data[$key]->over_finish_time.$cl[0].$data[$key]->over_finish_i_time.$cl[1];
					// 残業時間の算出用
					$over_start_hi_time = $data[$key]->over_start_time.":".$data[$key]->over_start_i_time;
					$over_finish_hi_time = $data[$key]->over_finish_time.":".$data[$key]->over_finish_i_time;
				}else{
					$over_start = ' - ';
					$over_end= ' - ';
					$over_start_hi_time = '00:00';
					$over_finish_hi_time = '00:00';
				}
				// 休憩時間の加算
				$over_hi = self::time_minus($over_finish_hi_time, $over_start_hi_time);
				$user_arr[$uid]['over'] = self::time_plus($user_arr[$uid]['over'], $over_hi); // 個別
				$over_total_time = self::time_plus($over_total_time, $over_hi); // 総数
				// tr td
				if($plugin_user_data['level']!='administrator'){
					$user_html = '';
				}
				$html .= "<tr><td>".$data[$key]->data_id."</td>{$user_html}<td>".$work_day.$week_name[2]."</td><td class=\"center\">{$work_start}</td><td class=\"center\">{$work_end}</td><td class=\"center\">{$break_start}</td><td class=\"center\">{$break_end}</td><td class=\"center\">{$over_start}</td><td class=\"center\">{$over_end}</td><td>".self::h($data[$key]->text)."</td><td><a href=\"".admin_url('/')."admin.php?page=".$url."&did=".$data[$key]->data_id."\" title=\"ID".$data[$key]->data_id."を編集\">編集</a></td></tr>";

			}

			if($plugin_user_data['level']=='administrator'){
				// 個別時間
				foreach($user_arr as $u){
					$user_total_work = ($u['work'] + $u['over']) - $u['break'];
					$user_total_work = self::floor_point($user_total_work); // 小数第二位を切り捨て
					$html .= "\n<tr class=\"user_total\"><td colspan=\"3\">".$u['name']."の合計時間</td><td colspan=\"2\" class=\"right\">勤務<span>".$u['work']."</span>時間</td><td colspan=\"2\" class=\"right\">休憩<span class=\"green\">".$u['break']."</span>時間</td><td colspan=\"2\" class=\"right\">残業<span>".$u['over']."</span>時間</td><td colspan=\"2\" class=\"right\">実働<span class=\"red\">{$user_total_work}</span>時間</td></tr>\n";
				}
				$colspan = '3';
				$total_title = '総合計時間';
			}else{
				$colspan = '2';
				$total_title = '合計時間';
			}

			// 総実働時間
			$total_work = ($total_time + $over_total_time) - $break_total_time;
			$total_work = self::floor_point($total_work); // 小数第二位を切り捨て
			$html .= "\n<tr class=\"total\"><td colspan=\"{$colspan}\">{$total_title}</td><td colspan=\"2\" class=\"right\">勤務<span>{$total_time}</span>時間</td><td colspan=\"2\" class=\"right\">休憩<span class=\"green\">{$break_total_time}</span>時間</td><td colspan=\"2\" class=\"right\">残業<span>{$over_total_time}</span>時間</td><td colspan=\"2\" class=\"right\">実働<span class=\"red\">{$total_work}</span>時間</td></tr>\n";

		}

		return $html;

	}
	// 今月のoptionタグ
	public function this_month_view(){

		$start = date("Y-m-01", time());
		$start_n = date("N", strtotime($start)); // 開始曜日
		$end = date('Y-m-t', strtotime($start)); // 今月末
		return self::month_options_html($start, $end, $start_n, '1');

	}
	// 先月のoptionタグ
	public function last_month_view(){

		$start = date("Y-m-01", strtotime(date("Y-m-d", time()).' -1 month'));
		$start_n = date("N", strtotime($start)); // 開始曜日
		$end = date('Y-m-t', strtotime($start)); // 先月末
		return self::month_options_html($start, $end, $start_n);

	}
	// 来月のoptionタグ
	public function next_month_view(){

		$start = date("Y-m-01", strtotime(date("Y-m-d", time()).' +1 month'));
		$start_n = date("N", strtotime($start)); // 開始曜日
		$end = date('Y-m-t', strtotime($start)); // 来月末
		return self::month_options_html($start, $end, $start_n);

	}
	// option生成
	private function month_options_html($start, $end, $n='', $type=''){

		$options_html = '';
		$now_day = date("d", time());
		$strt_ex = explode("-", $start);
		$end_ex = explode("-", $end);
		$stop = $end_ex[2];
		$t = 1;

		for($i=0; $i<$stop; $i++){

			$day = $strt_ex[2] + $i;
			$nn = $n + $t;
			$week = self::dayOfTheWeek($nn);

			if($t=='7'){
				$t = 0;
			}else{
				$t++;
			}

			if($type=='1' && $now_day==$day){
				$options_html .= '<option value="'.$strt_ex[0].'-'.$strt_ex[1].'-'.$day.'" selected>本日</option>';
			}elseif($now_day==$day){
				$options_html .= '<option value="'.$strt_ex[0].'-'.$strt_ex[1].'-'.$day.'" selected>'.$strt_ex[0].'年'.$strt_ex[1].'月'.$day.'日'.$week[2].'</option>';
			}else{
				$options_html .= '<option value="'.$strt_ex[0].'-'.$strt_ex[1].'-'.$day.'">'.$strt_ex[0].'年'.$strt_ex[1].'月'.$day.'日'.$week[2].'</option>';
			}

		}

		return $options_html;

	}
	// 時間表示
	public function clockText($clock=''){

		if(empty($clock)){
			$cl = array('0'=>'時', '1'=>'分');
		}else{
			$cl = array('0'=>':', '1'=>'');
		}

		return $cl;

	}
	// 曜日
	public function dayOfTheWeek($str='1'){

		$week = array();
		$week[0] = $str;

		switch($str){
			case 1:
				$week[1] = '月曜日';	$week[2] = '(月)';	break;
			case 2:
				$week[1] = '火曜日';	$week[2] = '(火)';	break;
			case 3:
				$week[1] = '水曜日';	$week[2] = '(水)';	break;
			case 4:
				$week[1] = '木曜日';	$week[2] = '(木)';	break;
			case 5:
				$week[1] = '金曜日';	$week[2] = '(金)';	break;
			case 6:
				$week[1] = '土曜日';	$week[2] = '(土)';	break;
			case 7:
				$week[1] = '日曜日';	$week[2] = '(日)';	break;
			default:
				$week[1] = '月曜日';	$week[2] = '(月)';
		}

		return $week;

	}
	// HTML出力のselectで使用
	// $num=個数 $arr=value値
	public function html_array_select($data='', $num='2', $arr=array('0', '1')){

		$selected = array();
		array_keys($arr);

		for($i=0; $i<$num; $i++){

			$key = $arr[$i];

			if($data==$key){
				$selected[$i] = 'selected';
			}else{
				$selected[$i] = '';
			}

		}

		return $selected;

	}
	// HTML出力のradioで使用
	// $num=個数 $arr=value値
	public function html_array_check($data='', $num='2', $arr=array('0', '1')){

		$checked = array();
		array_keys($arr);

		for($i=0; $i<$num; $i++){

			$key = $arr[$i];

			if($data==$key){
				$checked[$i] = 'checked';
			}else{
				$checked[$i] = '';
			}

		}

		return $checked;

	}
	// 0時～23時
	public function htime_options_set($selected_time='8'){

		$options = '';

		for($i=0; $i<24; $i++){

			if($i==$selected_time){
				$selected = ' selected';
			}else{
				$selected = '';
			}

			$options .= '<option value="'.$i.'"'.$selected.'>'.$i.'時</option>';

		}

		return $options;

	}
	// 0分～55分
	public function itime_options_set($selected_time='30'){

		$options = '';

		for($i=0; $i<12; $i++){

			$t = $i*5;

			if($t==$selected_time){
				$selected = ' selected';
			}else{
				$selected = '';
			}

			$options .= '<option value="'.$t.'"'.$selected.'>'.$t.'分</option>';

		}

		return $options;

	}
	// 検索用年月日option
	public function search_ymd_options(){

		$return_array = array('0', '1', '2', '3', '4', '5');
		global $plugin_option_data;

		if(!empty($plugin_option_data['search_start_year'])){
			$start_year = $plugin_option_data['search_start_year'];
		}else{
			$start_year = '2012';
		}

		$future = date("Y", strtotime("+1 year"));
		$now = date("Y-m-d", time());
		$end_selected_arr = explode("-", $now);
		$back_month_y = date("Y-m-d", strtotime("-2 month"));
		$start_selected_arr = explode("-", $back_month_y);

		// 年
		for($i=0; $i<100; $i++){

			$year = $future - $i;
			// 開始年
			$s_selected = self::match_selected($year, $start_selected_arr[0]);
			// 終了年
			$e_selected = self::match_selected($year, $end_selected_arr[0]);
			// option
			$return_array[0] .= "<option value=\"{$year}\"{$s_selected}>{$year}</option>\n";
			$return_array[3] .= "<option value=\"{$year}\"{$e_selected}>{$year}</option>\n";

			if($start_year==$year){
				break;
			}

		}

		// 月
		for($i=1; $i<13; $i++){

			// 開始年
			$s_selected = self::match_selected($i, $start_selected_arr[1]);
			// 終了年
			$e_selected = self::match_selected($i, $end_selected_arr[1]);
			// option
			$return_array[1] .= "<option value=\"{$i}\"{$s_selected}>{$i}</option>\n";
			$return_array[4] .= "<option value=\"{$i}\"{$e_selected}>{$i}</option>\n";

		}

		// 日
		for($i=1; $i<32; $i++){

			// 開始年
			$s_selected = self::match_selected($i, $start_selected_arr[2]);
			// 終了年
			$e_selected = self::match_selected($i, $end_selected_arr[2]);
			// option
			$return_array[2] .= "<option value=\"{$i}\"{$s_selected}>{$i}</option>\n";
			$return_array[5] .= "<option value=\"{$i}\"{$e_selected}>{$i}</option>\n";

		}

		return $return_array;

	}
	// マッチしていればselectedを返す
	private function match_selected($str1, $str2){

		if($str1==$str2){
			$selected = ' selected';
		}else{
			$selected = '';
		}

		return $selected;

	}
	// リスト一覧の検索フォーム用ユーザ選択HTML
	public function users_html($users, $type=''){

		$html = '<select name="uid">'."\n";

		if(empty($type)){
			$html .= "<option value=''>全ユーザ</option>";
		}

		foreach($users as $key => $u){
			$row = $users[$key];
			$html .= "<option value=\"{$row['id']}\">{$row['name']}</option>";
		}

		$html .= "</select>\n";

		return $html;

	}
	// 新規作成のフォーム
	public function post_form_page($users, $type='', $arr=''){

		$form_html = array();
		$form_html[0] = '';

		if(!empty($type) && $type=='1'){

			$form_html[1] = self::htime_options_set($arr[0]);
			$form_html[2] = self::itime_options_set($arr[1]);
			$form_html[3] = self::htime_options_set($arr[2]);
			$form_html[4] = self::itime_options_set($arr[3]);
			$form_html[5] = self::htime_options_set($arr[4]);
			$form_html[6] = self::itime_options_set($arr[5]);
			$form_html[7] = self::htime_options_set($arr[6]);
			$form_html[8] = self::itime_options_set($arr[7]);
			$form_html[9] = self::htime_options_set('17');
			$form_html[10] = self::itime_options_set();
			$form_html[11] = self::htime_options_set('19');
			$form_html[12] = self::itime_options_set();

		}else{

			foreach($users as $user){

				$form_html[0] .= "<option value=\"".self::h($user['id'])."\">".self::h($user['name'])."</option>\n";

			}

			$form_html[0] = "<select name=\"id\">\n".$form_html[0]."</select>\n";
			$form_html[1] = self::htime_options_set();
			$form_html[2] = self::itime_options_set();
			$form_html[3] = self::htime_options_set('17');
			$form_html[4] = self::itime_options_set();
			$form_html[5] = self::htime_options_set('12');
			$form_html[6] = self::itime_options_set();
			$form_html[7] = self::htime_options_set('13');
			$form_html[8] = self::itime_options_set();
			$form_html[9] = self::htime_options_set('17');
			$form_html[10] = self::itime_options_set();
			$form_html[11] = self::htime_options_set('19');
			$form_html[12] = self::itime_options_set();

		}

		return $form_html;

	}
	//
	private function search_list_hidden(){

		global $plugin_user_data;

		if($plugin_user_data['level']=='administrator'){
			if(empty($_GET['uid'])){
				$html = '<input type="hidden" name="user_type" value="all" />';
			}else{
				$html = '<input type="hidden" name="user_type" value="'.self::h($_GET['uid']).'" />';
			}
		}else{ // 管理者じゃなければ
			$html = '<input type="hidden" name="user_type" value="only" />';
		}
		// キーワード
		if(!empty($_GET['keyword'])){
			$html .= '<input type="hidden" name="keyword" value="'.self::h($_GET['keyword']).'" />';
		}

		return $html;

	}
	/*
	*  ユーザデータ整理
	*/
	public function _users_data($users_data=''){

		$_return = array();

		foreach($users_data as $key => $u){

			$id = $users_data[$key]['id'];
			$_return[$id] = $u;

		}

		return $_return;

	}
	// 時間の合計（加算）
	public function time_plus($total='0', $time='00:00'){

		$arrTime = explode(':', $time);
		$i = $arrTime[1] / 60; // 分を数値にする
		$i = self::floor_point($i); // 小数第二位は切捨て
		$return_data = $total + ($arrTime[0] + $i);
		return $return_data;

	}
	// 時間の減算
	public function time_minus($timeA='00:00', $timeB='00:00'){

		$arrTimeA = explode(':', $timeA);
		$arrTimeB = explode(':', $timeB);
		$time_date = date("H:i", mktime($arrTimeA[0]-$arrTimeB[0], $arrTimeA[1]-$arrTimeB[1], 0));
		return $time_date;

	}
	// 小数点を切り捨て
	public function floor_point($str='', $type=''){

		switch($type){
			// 第一位で切り捨て
			case '1':
				$num = 10;
				break;
			// 第二位で切り捨て
			case '2':
				$num = 100;
				break;
			// 第三位で切り捨て
			case '3':
				$num = 1000;
				break;
			// 第二位で切り捨て
			default:
				$num = 100;
		}

		return floor(($str * $num)) / $num;

	}
	// ヘッダーが送信されているかチェック
	public function header_check(){

		if(headers_sent($filename, $linenum)){

			print_r(headers_list());
			echo "$filename の $linenum 行目でヘッダがすでに送信されています。\n";
			return TRUE;

		}else{

			return FALSE;

		}

	}
	/*
	*  エスケープ
	*/
	// SQLエスケープ
	public function sql_escape($str=''){

		$return_data = '';

		if(isset($str)){
			$return_data = esc_sql($str);
		}

		return $return_data;

	}
	// htmlエスケープ
	public function h($str=''){

		return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');

	}
	// GETをエスケープして返す
	public function get_escape(){

		$arr = array();

		foreach($_GET as $key => $g){

			$arr[$key] = self::h($g);

		}

		return $arr;

	}
	public function get_param($type=''){

		$i = 0;
		$_return = '';

		foreach($_GET as $key => $g){

			if(empty($type) && $i==0){
				$param = "?";
			}else{
				$param = "&";
			}

			$_return .= $param.self::h($key)."=".self::h($g);
			$i++;

		}

		return $_return;

	}
	// $wpdpでのSQLエスケープ
	private function wpdp_prepara($sql, $arr=array()){

		global $wpdb;
		$count = count($arr);
		if(!empty($count)){
			$return_data = $wpdb->prepare($sql, $arr);
		}else{
			$return_data = $sql;
		}

		return $return_data;

	}
	// 配列なら処理（$cols,$tblで使用）
	private function is_array_return($str='', $comma=''){

		$return_data = '';

		// 配列なら処理
		if(is_array($str)){

			foreach($str as $val){
				if($comma=='1'){
					$return_data .= $val.", ";
				}elseif($comma=='2'){
					$return_data .= "'".$val."', ";
				}else{
					$return_data .= '`'.$val.'`, ';
				}
			}

			$return_data = rtrim($return_data, ", ");

		}else{

			if($str=='*'){
				$return_data = $str;
			}else{
				if($comma=='1'){
					$return_data .= $val.", ";
				}elseif($comma=='2'){
					$return_data .= "'".$val."', ";
				}else{
					$return_data = '`'.$str.'`';
				}
			}

		}

		return $return_data;

	}
	/*
	*  メッセージ
	*/
	public function _updateMessage(){

		$return_data = '';

		switch($_GET['msg']){

			case "insert-ok":
				$return_data .= "新規作成しました<br />";
				break;
			case "insert-ng":
				$return_data .= "新規作成に失敗しました<br />";
				break;
			case "write-ok":
				$return_data .= "更新に成功しました<br />";
				break;
			case "write-ng":
				$return_data .= "更新に失敗しました<br />";
				break;
			case "delete-ok":
				$return_data .= "削除に成功しました<br />";
				break;
			case "delete-ng":
				$return_data .= "削除に失敗しました<br />";
				break;
			case "write-user-ng":
				$return_data .= "編集権限のないユーザです<br />";
				break;

		}

		return $return_data;

	}
	/*
	*  SQLテキスト作成
	*/
	// SQLのSELECTテキストを作成
	public function sql_select_txt($tbl, $cols='*', $where){

		// $tblが配列なら処理
		$table = self::is_array_return($tbl);
		// $colsが配列なら処理
		$colum = self::is_array_return($cols);

		if(!empty($where)){
			$where = " WHERE ".$where;
		}

		$sql = "SELECT ".$colum." FROM ".$table.$where;

		return $sql;

	}
	// SQLのINSERTテキストを作成
	public function sql_insert_txt($tbl, $cols='', $values=''){

		// $tblが配列なら処理
		$table = self::is_array_return($tbl);
		$colum = self::is_array_return($cols);
		$value = self::is_array_return($values, 1);

		$sql = "INSERT INTO ".$table." (".$colum.") VALUES (".$value.")";

		return $sql;

	}
	// SQLのUPDATEテキストを作成
	public function sql_update_txt($tbl, $cols_values=array(), $where){

		// $tblが配列なら処理
		$table = self::is_array_return($tbl);
		$update_array = '';

		foreach($cols_values as $key => $val){
			$update_array .= "`".$key."` = ".$val." ,";
		}

		if(!empty($where)){
			$where = " WHERE ".$where;
		}

		$update_array = rtrim($update_array, ",");
		$sql = "UPDATE ".$table." SET ".$update_array.$where;

		return $sql;

	}
	/*
	*  SQL操作
	*/
	// 勤怠の新規作成
	public function post_insert(){

		global $plugin_user_data;
		$now = date("Y-m-d H:i:s", time());
		$cols = array('user_id', 'create_time', 'text', 'start_time', 'start_i_time', 'finish_time', 'finish_i_time', 'break_start_time', 'break_start_i_time', 'break_finish_time', 'break_finish_i_time', 'break_point', 'over_start_time', 'over_start_i_time', 'over_finish_time', 'over_finish_i_time', 'over_point', 'date');
		$values = array('%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s');
		$sql = self::sql_insert_txt(PLUGIN_TABLE_NAME, $cols, $values);
		$date = $_POST['work_day']." 00:00:00";
		$arr = array($_POST['id'], $now, $_POST['text'], $_POST['start_time'], $_POST['start_i_time'], $_POST['finish_time'], $_POST['finish_i_time'], $_POST['break_start_time'], $_POST['break_start_i_time'], $_POST['break_finish_time'], $_POST['break_finish_i_time'], $_POST['break_point'], $_POST['over_start_time'], $_POST['over_start_i_time'], $_POST['over_finish_time'], $_POST['over_finish_i_time'], $_POST['over_point'], $date);
		$insert_id = self::sql_query($sql, $arr, 1);
		return $insert_id;

	}
	// 管理画面での編集データの取得
	public function get_id_data(){

		$sql = "SELECT * FROM ".PLUGIN_TABLE_NAME." WHERE `data_id` = %d";
		$arr = array($_GET['did']);
		$data = self::sql_get($sql, $arr);
		if(!empty($data[0]->user_id)){
			$user_data = self::getMember($data[0]->user_id);
			$data[0]->user_data = $user_data[0];
		}
		return $data[0];

	}
	// 管理画面での編集アップデート処理
	private function update_post_write(){

		$now = date("Y-m-d H:i:s", time());
		$sql = 'UPDATE '.PLUGIN_TABLE_NAME.' SET `text` = %s, `start_time` = %d, `start_i_time` = %d, `finish_time` = %d, `finish_i_time` = %d, `break_start_time` = %d, `break_start_i_time` = %d, `break_finish_time` = %d, `break_finish_i_time` = %d, `break_point` = %d, `over_start_time` = %d, `over_start_i_time` = %d, `over_finish_time` = %d, `over_finish_i_time` = %d, `over_point` = %d, `update_time` = %s WHERE `user_id` = %d AND `data_id` = %d';
		$arr = array($_POST['text'], $_POST['start_time'], $_POST['start_i_time'], $_POST['finish_time'], $_POST['finish_i_time'], $_POST['break_start_time'], $_POST['break_start_i_time'], $_POST['break_finish_time'], $_POST['break_finish_i_time'], $_POST['break_point'], $_POST['over_start_time'], $_POST['over_start_i_time'], $_POST['over_finish_time'], $_POST['over_finish_i_time'], $_POST['over_point'], $now, $_POST['id'], $_POST['did']);
		$return_data = self::sql_query($sql, $arr);

		if(stristr($return_data, "data_id")){
			$update_id = self::h($_POST['did']);
		}else{
			$update_id = '';
		}

		return $update_id;

	}
	// 管理画面での削除処理
	private function update_post_delete(){

		$now = date("Y-m-d H:i:s", time());
		$sql = 'UPDATE '.PLUGIN_TABLE_NAME.' SET `status` = %d , `update_time` = %s WHERE `user_id` = %d AND `data_id` = %d';
		$arr = array('1', $now, $_POST['id'], $_POST['did']);
		$return_data = self::sql_query($sql, $arr);

		if(stristr($return_data, "data_id")){
			$delete_id = self::h($_POST['did']);
		}else{
			$delete_id = '';
		}

		return $delete_id;

	}
	// ヘッダの処理
	public function action_head(){

		$text = '<meta name="generator" content="os-attendance-management" />'."\n";
		echo $text;

	}
	// リスト取得のSQL文
	// ついでに検索条件のテキストも取得
	public function get_list_sql($array=array()){

		global $plugin_user_data;
		global $plugin_option_data;
		$where = '';
		$parameter = array();
		$search_word = '';

		// 管理者以外は自分の勤怠しか操作できない
		if($plugin_user_data['level']!='administrator'){
			$where .= "`user_id`= %d AND ";
			$parameter[] = $plugin_user_data[ID];
		}else{ // 管理者のみ実行可能
			// ユーザIDの指定があれば
			if(!empty($array['uid'])){
				$where .= "`user_id`= %d AND ";
				$parameter[] = $array['uid'];
				$member_data = self::getMember($array['uid']);
				if($plugin_user_data['level']=='administrator'){
					$search_word .= 'ユーザ: '.self::h($member_data[0]['name']).' / ';
				}
			}
		}
		if(!stristr($search_word, "ユーザ") && $plugin_user_data['level']=='administrator'){
			$search_word .= '全ユーザ / ';
		}
		// キーワード
		if(!empty($array['keyword'])){
			$where .= "`text` LIKE %s AND ";
			$parameter[] = "%".urldecode($array['keyword'])."%";
			$search_word .= 'キーワード: '.self::h($array['keyword']).' / ';
		}
		// 開始年月日
		$where .= "`date` >= %s AND ";
		if(empty($array['start_day'])){
			$array['start_day'] = date("Y-m-d", time()); // 今日
		}
		$parameter[] = $array['start_day']." 00:00:00";
		$search_word .= '勤務日: '.self::bar_ymd(self::h($array['start_day'])).'～';
		// 終了年月日
		$where .= "`date` <= %s AND ";
		if(empty($array['end_day'])){
			// 基本設定で設定されている期間にする
			switch($plugin_option_data['admin-list']){
				case '1':
					$end_time = strtotime("+1 week", time()); // 1週間後
					break;
				case '2':
					$end_time = strtotime("+2 week", time()); // 2週間後
					break;
				case '3':
					$end_time = strtotime("+3 week", time()); // 3週間後
					break;
				case 'm':
					$end_time = strtotime("+1 month", time()); // 一ヶ月後
					break;
			}
			$array['end_day'] = date("Y-m-d", $end_time);
		}
		$parameter[] = $array['end_day']." 23:59:59";
		$search_word .= self::bar_ymd(self::h($array['end_day']));

		$where .= "`status`='0'";
		$sql = self::sql_select_txt(PLUGIN_TABLE_NAME, '*', $where);
		$result = self::sql_get($sql, $parameter);

		return array('data'=>$result, 'word'=>$search_word);

	}
	// 2013-10-10 => 2013年10月10日
	private function bar_ymd($data=''){

		$data_ex = explode("-", $data);
		return $data_ex[0]."年".$data_ex[1]."月".$data_ex[2]."日";

	}
	// データ取得　$wpdb->get_results
	public function sql_get($sql, $arr=array()){

		global $wpdb;
		$result = $wpdb->get_results(self::wpdp_prepara($sql, $arr));

		return $result;

	}
	// データ実行　$wpdb->query
	public function sql_query($sql, $arr=array(), $type=''){

		global $wpdb;
		$result = $wpdb->query(self::wpdp_prepara($sql, $arr));
		// 返す値
		if(empty($type)){
			$_return = $wpdb->last_query;
		}elseif($type=='1'){
			$_return = $wpdb->insert_id;
		}
		return $_return;

	}
	// sqlを操作するファイルを読み込み、sqlを実行
	public function sql_performs($sql=''){

		global $plugin_sqlfile_check;
		// 既に読み込まれていないければファイル読み込み
		if($plugin_sqlfile_check!='1'){
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$GLOBALS['plugin_sqlfile_check'] = 1; // 読み込みチェック
		}

		return dbDelta($sql);

	}

}
?>