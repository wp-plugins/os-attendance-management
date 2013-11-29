<?php
if(class_exists('AttendanceUser') || class_exists('AttendanceAdmin')){
$admin_page_view=<<<_EOD_
	<div id="attendance-plugin">
		<div class="wrap">
			<h2>はじめに</h2>
			<div>
				<p>出勤・勤怠管理プラグインを導入していただき、ありがとうございます。</p>
				<p>当プラグインのご利用は非商用であれば無料です。再配布は許可しておりません。詳しくは、プラグイン開発者にご連絡ください。</p>
				<p>ご連絡は<a href="http://olivesystem.jp/lp/plugin-am-mail" title="問い合わせ" target="_blank">問い合わせフォーム</a>からお願い致します。</p>
			</div>
		</div>
	</div>

_EOD_
;
echo $admin_page_view;
}
?>