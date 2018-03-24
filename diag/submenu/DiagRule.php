<?php

class DiagRule {

    function __construct() {
		global $wpdb;
    }

	function showPage() {

		global $wpdb;

		if (isset($_POST['ope']) && $_POST['ope'] == 'update') {

			$numArray = $_POST['num'];
			foreach ($numArray as $num) {

				$from = isset($_POST["from_${num}"]) ? ltrim($_POST["from_${num}"], '0') : '';
				$to = isset($_POST["to_${num}"]) ? ltrim($_POST["to_${num}"], '0') : '';
				$result = isset($_POST["result_${num}"]) ? $_POST["result_${num}"] : '';
				$img = isset($_POST["img_${num}"]) ? $_POST["img_${num}"] : '';
				$title = isset($_POST["title_${num}"]) ? $_POST["title_${num}"] : '';

				if (isset($_POST['deleteIds'])) {
					foreach ($_POST['deleteIds'] as $deleteId) {
						$sql = $wpdb->prepare("delete from wp_diag_rule where `id`=${deleteId}", '');
						$wpdb->get_results($sql);
					}
				}
				
				if (isset($_POST["condition_id_${num}"]) && strlen($_POST["condition_id_${num}"]) > 0) {
					$id = $_POST["condition_id_${num}"];
					$sql = $wpdb->prepare("UPDATE wp_diag_rule set `from`=${from},`to`=${to}," .
								"result='${result}',img='${img}',title='${title}' where `id`=${id}", '');
					$wpdb->get_results($sql);
				} else if (strlen($from) > 0 && strlen($to) > 0) {
					$sql = $wpdb->prepare("INSERT INTO wp_diag_rule(`from`,`to`, result, img, title)" . 
							 " VALUES(${from}, ${to}, '${result}', '${img}', '${title}')", '');
					$wpdb->get_results($sql);
				}
			}
		} 

		// DBから検索
		$sql = $wpdb->prepare("SELECT r.id,r.from,r.to,r.result,r.img,r.title FROM wp_diag_rule r ORDER BY `from` asc", '');
		$result = $wpdb->get_results($sql);
	?>
	<h1 class="wp-heading-inline">回答設定</h1>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" ></script>
	<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">

	<div style="margin: 20px 0px;">
		<a href="#" id="addPattern">回答パターン追加</a>
	</div>
	<form method="post" action="">
		<input type="hidden" name="ope" value="update">
		<div id="condition-list"></div>
		<?php submit_button(); ?>
	</form>

	<script type="text/javascript">
		let num = 0;

		let getConditionTemplate = function (num) {
			return `
				<div class="condition" id="condition_${num}">
					<input type="hidden" name="condition_id_${num}"/>
					<input type="hidden" name="num[]" value="${num}"/>
					<div class="condition-title">
						<i class="far fa-times-circle close" target="condition_${num}" num="${num}"></i>
					</div>
					<div class="condition_input">
						<dl>
							<dt>期間</dt>
							<dd><input type="text" name="from_${num}">〜
								<input type="text" name="to_${num}"></dd>
						</dl>
						<dl>
							<dt>アイキャッチ画像</dt>
							<dd><input type="text" name="img_${num}" class="expand"></dd>
						</dl>
						<dl>
							<dt>診断結果タイトル</dt>
							<dd><input type="text" name="title_${num}" class="expand"></dd>
						</dl>
						<dl>
							<dt>診断結果</dt>
							<dd><textarea rows="6" name="result_${num}" class="expand"></textarea></dd>
						</dl>
					</div>
				</div>		
			`; 
		}

		// 画面初期化処理
		let showPattern = function(id, num, from, to, img, result, title) {
			$("#condition-list").append(getConditionTemplate(num));
			$("[name=from_"+num+"]").val(("0" + from).slice(-4));
			$("[name=to_"+num+"]").val(("0" + to).slice(-4));
			$("[name=img_"+num+"]").val(img);
			$("[name=title_"+num+"]").val(title);
			$("[name=result_"+num+"]").val(result);
			$("[name=condition_id_"+num+"]").val(id);
		}
	</script>

	<?php
		if ($result) {
			foreach ($result as $condition) {
				?>
				<script type="text/javascript">
				showPattern(
					<?php echo $condition->id; ?>,
					++num,
					<?php echo $condition->from; ?>,
					<?php echo $condition->to; ?>,
					'<?php echo $condition->img; ?>',
					`<?php echo $condition->result; ?>`,
					'<?php echo $condition->title; ?>'
				);
				</script>
				<?php
			}
		}
	?>

	<script type="text/javascript">
		$(function() {

			$("#addPattern").on('click', function() {
				$("#condition-list").append(getConditionTemplate(++num));
			});

			$('#condition-list').on('click', '.close', function() {
				console.log("target=" + $(this).attr("target"));
				let num = $(this).attr("num");
				if($(`[name=condition_id_${num}]`).val().length > 0) {
					let id = $("[name=condition_id_"+num+"]").val();
					$(`<input type="hidden" name="deleteIds[]" value="${id}"/>`).appendTo("#condition-list");
				}
				$("#" + $(this).attr("target")).remove();
			});
		});
	</script>

	<style type="text/css">
		#condition-list { margin-right: 20px; border: 1px solid #80808094; border-radius: 5px; }
		.condition_input dl { margin: 0px; }
		.condition_input dt { display: inline-block; width: 20%; }
		.condition_input dd { display: inline-block; width: 68%; }
		.expand { width: 100%; }
		.condition-title, .condition_input { padding: 5px; }
		.condition-title i { float: right; margin-top: 5px; font-size: 15px;}
		#addPattern {
		    padding: 4px 8px;
		    text-decoration: none;
		    border: none;
		    border: 1px solid #ccc;
		    border-radius: 2px;
		    background: #f7f7f7;
		    text-shadow: none;
		    font-weight: 600;
		    font-size: 13px;
		    line-height: normal;
		    color: #0073aa; cursor: pointer; outline: 0;
		}
		.condition { padding: 5px; border-bottom: 1px solid #80808094; padding-top: 0px; }
		.condition:nth-child(2n) { }
		.condition:last-child { border-bottom: none; }
	</style>

	<?php
	}
}
?>