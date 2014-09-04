<?php
if(class_exists('AttendanceAdmin')){
$admin_page_view=<<<_EOD_
	<div id="attendance-plugin">
		<div class="wrap">
			<h2>はじめに</h2>
			<div>
				<p>出勤・勤怠管理プラグインを導入していただき、ありがとうございます。</p>
				<p>当プラグインのご利用は非商用であれば無料です。再配布は許可しておりません。詳しくは、<a href="?page=attendance-management-agreement.php">こちらの利用規約</a>をご覧ください。</p>
				<p>ご連絡は<a href="http://olivesystem.jp/lp/plugin-am-mail" title="問い合わせ" target="_blank">問い合わせフォーム</a>からお願い致します。</p>
			</div>
			<h2>使用方法</h2>
			<div>
				<p>記事内に次のコードを埋め込みます。 <input type="text" value="[attendance]" /></p>
				<p>
				uid=1とユーザIDを指定するとそのユーザだけの勤怠表示できます。 例：<input type="text" value="[attendance uid=1]" /><br />
				該当ユーザがいない場合は表示されません。</p>
			</div>
			<h2>更新履歴</h2>
			<div>
				<p>2014.09.04  使用方法の追加</p>
				<p>2013.11.29  バグの修正</p>
			</div>
		</div>
	</div>

_EOD_
;
echo $admin_page_view;
}
?>