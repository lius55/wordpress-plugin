<?php

class DiagHistory {

	function showPage() {

		$msg = '';
		global $wpdb;

		if (isset($_POST["ope"]) && $_POST["ope"] == "clear") {
			$sql = $wpdb->prepare("delete from wp_diag_history", '');
			$wpdb->get_results($sql);
			$msg = "履歴をクリアしました。";
		}

		?>
			<h1 class="wp-heading-inline">履歴管理</h1>
			<script src="https://code.jquery.com/jquery-3.3.1.min.js" ></script>

			<div class="info"><?php echo $msg;?></div>
			<form method="post" action="" id="hisForm">
				<table>
					<tr>
						<td><button type="button" class="button btn-primary" id="download">履歴ダウンロード</button></td>
						<td><button type="button" class="button btn-primary" id="clear">履歴クリア</button></td>
					</tr>
				</table>

				<table class="history striped">
					<tr><th>診断ID</th><th>Eメール</th><th>生年月日</th><th>診断日時</th></tr>
					<?php
						$sql = $wpdb->prepare("select h.birthday,h.email,h.id,h.insert_date from wp_diag_history h order by id asc", '');
						$rows = $wpdb->get_results($sql);
						foreach($rows as $row) {
							echo "<tr><td>$row->id</td><td>$row->email</td><td>$row->birthday</td>" .
								"<td>$row->insert_date</td></tr>";
						}			
					?>
				</table>
			</form>	
			<script type="text/javascript">
				$(function() {

					$("#clear").on('click', function() {
						$('<input />').attr('type', 'hidden')
							.attr('name', 'ope')
							.attr('value', 'clear')
							.appendTo('#hisForm');
						$("#hisForm").submit();
					});

	                let downloadCsvFile = function(data, fileName) {
	                    var downloadData = new Blob([data.join("\r\n")], {"type": "text/csv"});
	                    if (window.navigator.msSaveBlob) {
	                        window.navigator.msSaveBlob(downloadData, fileName); // IE用
	                    } else {
	                        var downloadUrl  = (window.URL || window.webkitURL).createObjectURL(downloadData);
	                        var link = document.createElement('a');
	                        link.href = downloadUrl;
	                        link.download = fileName;
	                        link.click();
	                        (window.URL || window.webkitURL).revokeObjectURL(downloadUrl);
	                    }
	                }

	                let downloadCsv = function(response) {
	                    downloadCsvFile(response.fileContent, 'history.csv');
	                }

					$("#download").on('click', function() {
	                    $.ajax({
	                        url: "<?php echo get_site_url() . '/wp-json/diag/v1/history/' ?>",
	                        success: downloadCsv,
	                        error: function() {
	                            alert("エラーが発生しました。");
	                        }
	                    });
					});

				});
			</script>
			<style type="text/css">
				#hisForm { margin-right: 20px; }
				.history { width: 100%; text-align: center; margin-top: 10px;}
				.history td, .history th { padding: 5px; }
				.info { padding: 10px; color: red; font-weight: bold; padding-left: 0px; }
			</style>
		<?php
	}

	function getHistroyCsv() {

		header('Content-type: text/html; charset=utf-8');
		header('Content-Type: application/json');
		$response = new stdClass();
		// ---------------------
		//       処理開始
		// ---------------------
		$request = json_decode(file_get_contents('php://input'), true);

		global $wpdb;
		$sql = $wpdb->prepare("select h.birthday,h.email,h.id,h.insert_date from wp_diag_history h", '');
		$rows = $wpdb->get_results($sql);

		$response->fileContent[] = "診断ID,メールアドレス,生年月日,姓名,姓,名,診断日時";
		if ($rows) {
			foreach($rows as $row) {
				$line = $row->id . "," . $row->email . "," . $row->birthday . ",,,," . $row->insert_date; 
				$response->fileContent[] = $line;
			}
		}
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		die;
	}
}
?>