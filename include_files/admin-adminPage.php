<?php
if(class_exists('AttendanceAdmin')){
?>

	<div id="attendance-plugin">
		<?php include_once(PLUGIN_INCLUDE_FILES."/admin-head.php"); ?>
		<div class="wrap">
			<h2>はじめに</h2>
			<div>
				<p>出勤・勤怠管理プラグインを導入していただき、ありがとうございます。</p>
				<p>当プラグインのご利用は非商用であれば無料です。再配布は許可しておりません。詳しくは、<a href="?page=attendance-management-agreement.php">こちらの利用規約</a>をご覧ください。</p>
				<p>初めてご利用の方は、まず<a href="?page=attendance-management-format.php">初期化</a>してください。</p>
				<p>ご連絡は<a href="http://lp.olivesystem.jp/plugin-am-mail" title="問い合わせ" target="_blank">問い合わせフォーム</a>からお願い致します。</p>
			</div>
			<h2>更新履歴</h2>
			<div>
				<p>2014.07.18  バグの修正</p>
				<p>2013.11.29  バグの修正</p>
			</div>
		</div>
	</div>

<?php
}
?>