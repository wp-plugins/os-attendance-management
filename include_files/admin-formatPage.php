<?php
if(class_exists('AttendanceAdmin')){
$admin_page_view=<<<_EOD_
	<div id="attendance-plugin">
		<div class="wrap">
			<h2>プラグインの初期化</h2>
			<form action="admin.php?page=attendance-management-format.php" method="POST">
				<p>全てのデータを初期化します。全てのデータは削除され、初期設定に戻ります。<br />よろしいですか？</p>
				<input type="hidden" name="format" value="format" />
				<input type="submit" name="Submit" value="初期化する" />
				<p><a href="admin.php?page=attendance-management-options.php">戻る</a></p>
			</form>
		</div>
	</div>

_EOD_
;
echo $admin_page_view;
}
?>