<?php 

class DiagSetting {
	
	function showPage() {

		$options = array("diag_email", "diag_complete_url", "diag_mail_title", "diag_over_times_msg",
						"diag_year_end", "diag_year_start");

		foreach($options as $option) {
			if (isset($_POST[$option])) {
				update_option($option, $_POST[$option]);
			}
		}

		?>
		<div class="contents">
			<h1 class="wp-heading-inline">一般</h1>

			<h2>利用方法について</h2>
			<li>診断ページ</li>
			<div class="detail">
				以下shotcodeをページに記載してください：<br/>
				[diag_show_page]
			</div>
			<li>完了ページ</li>
			<div class="detail">画面画面URLでリダイレクトのURL設定してください。設定なしの場合、アラートのみ表示する。</div>

			<li>メールの利用について</li>
			<div class="detail">
				「WP Mail SMTP」などのプラッグインで事前にメール設定を行ってください。設定しない場合はメール機能利用できません。
				<br/>※送信元もMail SMTP設定で行ってください。
			</div>

			<h2>各種設定</h2>
			<form method="post" action="">
				<table class="form-table">
					<tr>
						<th scope="row"><label>メールタイトル</label></th>
						<td><input name="diag_mail_title" type="text" value="<?php form_option('diag_mail_title'); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label>完了画面URL</label></th>
						<td><input name="diag_complete_url" type="text" value="<?php form_option('diag_complete_url'); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label>診断回数超え時のメッセージ</label></th>
						<td><input name="diag_over_times_msg" type="text" value="<?php form_option('diag_over_times_msg'); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label>診断誕生日</label></th>
						<td>
							<input name="diag_year_start" type="text" value="<?php form_option('diag_year_start'); ?>" class="regular-text input-year"/>年〜
							<input name="diag_year_end" type="text" value="<?php form_option('diag_year_end'); ?>" class="regular-text input-year"/>年
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>

		<style type="text/css">
			h2 { margin-top: 30px; }
			.detail { padding-left: 17px; }
			li { padding-top: 5px; }
			.input-year { width: 80px; }
		</style>
		<?php
	}	
}
?>