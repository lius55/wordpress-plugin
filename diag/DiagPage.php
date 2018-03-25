<?php 

class DiagPage {
	
	public static function showPage() {
		?>
		<script src="https://code.jquery.com/jquery-3.3.1.min.js" ></script>
		<script type="text/javascript">
			$(function() {
				$(".show-result").hide();

				let yearStart = <?php echo null != get_option('diag_year_start') ? get_option('diag_year_start') : 1900; ?>;
				let yearEnd = <?php echo null != get_option('diag_year_end') ? get_option('diag_year_end') : 2018; ?>;

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

				$("#sendMail").on('click', function() {

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
						'1900' + 
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

				$("#year").on('change', function() {
					changeYear($(this).val());
				});

				$("#month").on('change', function() {
					changeMonth($("#year").val(), $(this).val());
				});

				$("#diag").on('click', function() {

					let birthday = 
						'1900' + 
						("0" + $("#month").val()).slice(-2) +
						("0" + $("#day").val()).slice(-2);

                    $.ajax({
                        url: "<?php echo get_site_url() . '/wp-json/diag/v1/result/' ?>",
                        type: 'POST',
                        data: { 
                            birthday: birthday
                        },
                        success: function(response) {
                        		if (response.result == 'over times') {
                        			alert("<?php echo get_option('diag_over_times_msg') ? 
                        				get_option('diag_over_times_msg') : '本日はもう診断できません。'; ?>");
                        			return;
                        		}
                        		$("#result-img").attr("src", response.img);
                        		$("#title").html(response.title);
                        		$("#uranai").hide();
                        		$("#result").show();
                        		$('.hide-result').hide();
                        		$('.show-result').hide();
                        		$("#fb-link").attr("href", $("#fb-link").attr("href")+response.id);
                        		$("#line-link").attr("href", $("#line-link").attr("href")+response.id);
                        		$("#twitter-link").attr("href", $("#twitter-link").attr("href")+response.id);
                        },
                        error: function() {
                            alert("エラーが発生しました。");
                        }
                    });

				});

                changeYear($("#year").val());
			});
		</script>

		<?php
		ob_start();
		echo file_get_contents('template/diag-page.tpl.html', true);
		return ob_get_clean();
	}

	function sendMail() {

		$birthday = $_POST['birthday'];
		$email = $_POST['email'];

		$ruleNum = intval(substr($birthday, 4));

		global $wpdb;

		header('Content-type: text/html; charset=utf-8');
		header('Content-Type: application/json');
		$response = new stdClass();		

		// 診断回数1日3回まで
		$ip = $this->getIp();
		$sql = $wpdb->prepare("select count(*) as times from wp_diag_history " . 
						"where date(insert_date) = curdate() and ip='${ip}'", '');
		$result = $wpdb->get_results($sql);
		if ($result && $result[0]->times >= 3) {
			$response->result = 'over times';
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
			die;
		}

		$sql = $wpdb->prepare(
					"select r.img,r.result,r.id,r.title from wp_diag_rule r where `from`<=${ruleNum} and `to`>= ${ruleNum} " .
					"order by r.id asc", '');
		$result = $wpdb->get_results($sql);

		$mailTitle = get_option('diag_mail_title');
		if ($result) {
			$row = $result[0];
			// メール送信
			$mailContent = $this->getMailContent($row->id, $row->title, $row->result, $row->img);
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail($email, $mailTitle, $mailContent, $headers);

			// 履歴挿入
			$sql = 
				$wpdb->prepare(
						"insert into wp_diag_history(email, birthday, ip) " . 
						" values('$email','$birthday', '$ip')", '');
			$wpdb->get_results($sql);
		}

		$response->result = 'success';
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		die;
	}

	public static function getMailContent($id, $title, $result, $img) {
		$template = file_get_contents('template/result-mail.tpl.html', true);
		$template = strtr($template,
						array(
							'${id}' => $id,
							'${title}' => $title,
							'${result}' => $result,
							'${img}' => $img
						));
		return $template;
	}

	public static function getIp() {
		$ip = '';
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

 	function getResult() {

		$birthday = $_POST['birthday'];

		$ruleNum = intval(substr($birthday, 4));

		global $wpdb;

		header('Content-type: text/html; charset=utf-8');
		header('Content-Type: application/json');
		$response = new stdClass();		

		// 診断回数1日3回まで
		$ip = $this->getIp();
		$sql = $wpdb->prepare("select count(*) as times from wp_diag_history " . 
						"where date(insert_date) = curdate() and ip='${ip}'", '');
		$result = $wpdb->get_results($sql);
		if ($result && $result[0]->times >= 3) {
			$response->result = 'over times';
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
			die;	
		}

		$sql = $wpdb->prepare(
					"select r.img,r.result,r.id,r.title from wp_diag_rule r where `from`<=${ruleNum} and `to`>= ${ruleNum} " .
					"order by r.id asc", '');
		$result = $wpdb->get_results($sql);

		if ($result) {
			$row = $result[0];

			// 履歴挿入
			// $sql = 
			// 	$wpdb->prepare(
			// 			"insert into wp_diag_history(email, birthday, ip) " . 
			// 			" values('$email','$birthday', '$ip')", '');
			// $wpdb->get_results($sql);

			$response->img = $row->img;
			$response->result = $row->result;
			$response->id = $row->id;
			$response->title = $row->title;
		}

		$response->result = 'success';
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		die;		
	}
}

?>