<?php
if(class_exists('AttendanceAdmin')){
$admin_page_view=<<<_EOD_
	<div id="attendance-plugin">
		<div class="wrap">
			<h2>出勤・勤怠管理の基本設定</h2>
			<p style="color:red;">{$message}</p>
			<form action="admin.php?page=attendance-management-options.php" method="POST">
				<table>
					<tr>
						<th scope="row">時間表示</th>
						<td>
							<select name="time_view">
								<option value="0" {$time_view_selected[0]}>24時間表示</option>
								<option value="1" {$time_view_selected[1]}>12時間表示（午前・午後）</option>
								<option value="2" {$time_view_selected[2]}>12時間表示（AM・PM）</option>
							</select>
							<div style="font-size:11px;">※管理画面ではすべて24時間表示です</div>
						</td>
					</tr>
					<tr>
						<th scope="row">時間区切</th>
						<td>
							<input type="radio" name="clock" value="0" id="clock-ng" {$clock_checked[0]} /><label for="clock-ng">文字</label>　<input type="radio" name="clock" value="1" id="clock-ok" {$clock_checked[1]} /><label for="clock-ok">コロン</label>
							<div style="font-size:11px;">12時30分 or 12:30</div>
						</td>
					</tr>
					<tr>
						<th scope="row">勤怠管理を<br />編集できるユーザ</th>
						<td>
							<select name="time_write">
								<option value="admin" {$time_write_selected[0]}>管理者のみ編集可能</option>
								<option value="user" {$time_write_selected[1]}>登録ユーザは自分の勤怠のみ投稿・編集可能</option>
								<option value="user-post" {$time_write_selected[2]}>登録ユーザは自分の勤怠投稿のみ可能</option>
							</select>
							<div style="font-size:11px;">※管理者はすべてのユーザを操作できます</div>
						</td>
					</tr>
					<tr>
						<th scope="row">一覧の表示設定<br />（公開側）</th>
						<td>
							<input type="radio" name="view-list" value="1" id="view-list-one" {$view_list_checked[0]} /><label for="view-list-one">1週間</label>　<input type="radio" name="view-list" value="2" id="view-list-two" {$view_list_checked[1]} /><label for="view-list-two">2週間</label>　<input type="radio" name="view-list" value="3" id="view-list-three" {$view_list_checked[2]} /><label for="view-list-three">3週間</label>　<input type="radio" name="view-list" value="m" id="view-list-month" {$view_list_checked[3]} /><label for="view-list-month">一ヶ月</label>
						</td>
					</tr>
					<tr>
						<th scope="row">一覧の表示設定<br />（管理画面側）</th>
						<td>
							<input type="radio" name="admin-list" value="1" id="admin-list-one" {$admin_list_checked[0]} /><label for="admin-list-one">1週間</label>　<input type="radio" name="admin-list" value="2" id="admin-list-two" {$admin_list_checked[1]} /><label for="admin-list-two">2週間</label>　<input type="radio" name="admin-list" value="3" id="admin-list-three" {$admin_list_checked[2]} /><label for="admin-list-three">3週間</label>　<input type="radio" name="admin-list" value="m" id="admin-list-month" {$admin_list_checked[3]} /><label for="admin-list-month">一ヶ月</label>
						</td>
					</tr>
					<tr class="end">
						<th scope="row">ライセンス</th>
						<td>
							<input type="text" name="license" size="45" value="{$data['license']}" /><br />
							<div style="font-size:11px;">※ライセンスを取得した方のみ、ご記入ください。デフォルトは「free」です。</div>
						</td>
					</tr>
				</table>
				<input type="hidden" name="option" value="option" />
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="更新する" />
				</p>
			</form>
			<p><a href="admin.php?page=attendance-management-format.php">初期化する</a> - <a href="admin.php?page=attendance-management-agreement.php">利用規約</a></p>
		</div>
	</div>

_EOD_
;
echo $admin_page_view;
}
?>