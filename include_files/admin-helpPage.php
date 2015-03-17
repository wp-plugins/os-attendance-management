<?php
if(class_exists('AttendanceAdmin')){
?>
	<div id="attendance-plugin">
		<?php include_once(OSAM_PLUGIN_INCLUDE_FILES."/admin-head.php"); ?>
		<div class="wrap" id="help-page">
			<h2>ヘルプページ</h2>
			<div class="strong">記事内での表示</div>
			<p>[attendance] というショートコードで記事内に表示できます。</p>
			<p>uidを指定することによって、表示するユーザを指定できます。 [attendance uid=1]</p>
			<p>dayを指定することによって、表示の開始時期を指定できます。[attendance day=2015-01-01]　必ず西暦4桁-月2桁-日2桁で指定してください。</p>
		</div>
	</div>

<?php
}
?>