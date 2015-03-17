<?php
if(class_exists('AttendanceAdmin')){
?>

	<div id="attendance-plugin">
		<?php include_once(OSAM_PLUGIN_INCLUDE_FILES."/admin-head.php"); ?>
		<div class="wrap">
			<h2>はじめに</h2>
			<div>
				<p>出勤・勤怠管理プラグインを導入していただき、ありがとうございます。</p>
				<p>当プラグインのご利用は無料です。再配布は許可しておりません。詳しくは、<a href="?page=attendance-management-agreement.php">こちらの利用規約</a>をご覧ください。</p>
				<p>初めてご利用の方は、まず<a href="?page=attendance-management-format.php">初期化</a>してください。</p>
				<p>ご連絡は<a href="http://lp.olivesystem.jp/plugin-am-mail" title="問い合わせ" target="_blank">問い合わせフォーム</a>からお願い致します。</p>
			</div>
			<h2>使い方</h2>
			<div>
				<p>[attendance] というショートコードで記事内に表示できます。</p>
				<p>詳しくは<a href="admin.php?page=attendance-management-help.php">ヘルプページ</a>をご覧ください</p>
			</div>
			<h2>注意</h2>
			<div>
				<p>当プラグインを改造して有料販売している業者がいますが、当方とは無関係です。</p>
				<p>危険性がありますので、<a href="https://wordpress.org/plugins/" target="_blank">WordPress公式サイト</a>に登録されていないものはお勧めしません。自己責任でご利用ください。</p>
			</div>
			<h2>更新履歴</h2>
			<div>
				<p>2015-03-17  各箇所の修正、ヘルプページ追加</p>
				<p>2015-03-09  軽微な修正</p>
				<p>2015-02-11  軽微な修正</p>
				<p>2015-02-07  軽微な修正</p>
				<p>2015-02-07  軽微な修正</p>
				<p>2014-07-17  軽微な修正</p>
				<p>2014.07.18  バグの修正</p>
				<p>2013.11.29  バグの修正</p>
			</div>
		</div>
	</div>

<?php
}
?>