<?php 

class DiagPage {
	
	public static function showPage() {
		?>

		<script src="http://code.jquery.com/jquery-3.3.1.min.js" ></script>
		<?php
			echo file_get_contents('template/diag-page.tpl.html', true);
		?>
		<script type="text/javascript">
			$(function() {
				let yearStart = 1900;
				let yearEnd = 2018;

				for (y = yearStart; y < yearEnd; y++) {
					$("#year").append(`<option value="${y}">${y}</option>`);
				}

				const thirdOneMonth = [1, 3, 5, 7, 8, 10, 12];

				let changeYear = function(year) {
					// 闰年判定

					$("#year").val(year);

					changeMonth(year, $("#month").val());
				}

				let isLeapYear = function (year){
					year = parseInt(year);
					if (year < 4) return false;
					return year % 400 == 0 || (year % 100 != 0 && year % 4 == 0);
				}

				let changeMonth = function(year, month) {
					month = parseInt(month);
					$("#month").val(month);
					let monthDay = 30;
					if (thirdOneMonth.indexOf(month) >= 0) {
						monthDay = 31;
					} else if (month == 2) {
						monthDay = isLeapYear(year) ? 29 : 28;
					}

					$("#day").html('');
					for(day = 1; day <= monthDay; day++) {
						$("#day").append(`<option value="${day}">${day}</option>`);
					}
				}

				let checkEmailAddress = function(str){
				    if(str.match(/.+@.+\..+/)==null){
				        return false;
				    }else{
				        return true;
				    }
				}

				$("#year").on('change', function() {
					changeYear($(this).val());
				});

				$("#month").on('change', function() {
					changeMonth($("#year").val(), $(this).val());
				});

				$("#diag").on('click', function() {

					// validationチェック
					if ($("#email").val().length < 1) {
						alert("メールアドレスを入力してください。");
						return;
					}
					if (!checkEmailAddress($("#email").val())) {
						alert("正しいメールアドレスを入力してください。");
						return;
					}

					let birthday = 
						$("#year").val() + 
						("0" + $("#month").val()).slice(-2) +
						("0" + $("#day").val()).slice(-2);

                    $.ajax({
                        url: "<?php echo get_site_url() . '/wp-json/diag/v1/sendMail/' ?>",
                        type: 'POST',
                        data: { 
                            birthday: birthday,
                            email: $("#email").val()
                        },
                        success: function(response) {
                                <?php 
                                        if (strlen(get_option('diag_complete_url')) > 0) {
                                                ?>
                                                location.href = '<?php echo get_option('diag_complete_url'); ?>';
                                                <?php
                                        } else {
                                                ?>
                                                alert('診断完了しました。メールをご確認ください。');
                                                <?php
                                        }
                                ?>
                        },
                        error: function() {
                            alert("エラーが発生しました。");
                        }
                    });
				});

				changeYear(1920);
			});
		</script>

		<?php
	}

	function sendMail() {

		$birthday = $_POST['birthday'];
		$email = $_POST['email'];

		$ruleNum = intval(substr($birthday, 4));

		global $wpdb;

		$sql = $wpdb->prepare(
					"select r.img,r.result,r.id from wp_diag_rule r where `from`<=${ruleNum} and `to`>= ${ruleNum}", '');
		$result = $wpdb->get_results($sql);

		$mailTitle = get_option('diag_mail_title');
		if ($result) {
			$row = $result[0];
			// メール送信
			$mailContent = $this->getMailContent($row->result, $row->img);
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail($email, $mailTitle, $mailContent, $headers);

			// 履歴挿入
			$sql = $wpdb->prepare("insert into wp_diag_history(email, birthday) values('$email','$birthday')", '');
			$wpdb->get_results($sql);
		}

		header('Content-type: text/html; charset=utf-8');
		header('Content-Type: application/json');
		$response = new stdClass();
		$response->result = 'success';
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		die;
	}

	public static function getMailContent($result, $img) {
		$template = file_get_contents('template/result-mail.tpl.html', true);
		$template = strtr($template,
						array(
							'${result}' => $result,
							'${img}' => $img
						));
		return $template;
	}
}

?>