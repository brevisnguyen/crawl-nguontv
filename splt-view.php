<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
function showtime($time)
{
	return date('H:i | d/m/Y', strtotime($time));
}
foreach (get_plugins() as $key => $value) {
	if ($value['Name'] == 'Crawl NguonTV') {
		$thisversion = $value['Version'];
	}
}
?>
<link href="<?php echo home_url(); ?>/wp-content/plugins/crawl-nguontv/style.css" rel="stylesheet" type="text/css" />
<script src="<?php echo home_url(); ?>/wp-content/plugins/crawl-nguontv/jquery-3.5.1.min.js" type="text/javascript"></script>
<script src="<?php echo home_url(); ?>/wp-content/plugins/crawl-nguontv/main.js" type="text/javascript"></script>
<center>
	<h1>Nguon-TV</h1>
</center>
<div id="lastprocess"></div>

<div class="card">
	<div class="card-header">
		<h2>Thông Tin Chi Tiết</h2>
	</div>
	<div class="card-body">
	<h5 class="card-title">API</h5>
		<input name="api-url" style="width: 500px; height: 40px;" placeholder="Nhập api vào đây..." text value="" />

		<h5 class="card-title">Thành Viên</h5>
		<?php
			wp_dropdown_users($args);
		?>
		<input type="hidden" id="path" name="path" value="<?php echo home_url(); ?>/wp-content/plugins/crawl-nguontv/" required="required" />

		<h5 class="card-title">Lấy dữ liệu</h5>
		&emsp;<input type="radio" id="typeleech" class="typeleech" name="typeleech" value="full" />Lấy tất cả dữ liệu<br>
		&emsp;<input type="radio" id="typeleech" class="typeleech" name="typeleech" value="today" checked/>Lấy dữ liệu ngày hôm nay<br>
		<input class="button-primary" type="button" value="Bắt Đầu" id="search" />
		<input class="button-secondary" type="button" value="Dừng" id="stop" />
		<div id="loading"></div>
		<div id="tientrinh"></div>
		<div id="tientrinh1"></div>
		<div id="tientrinh2"></div>
		<div id="tientrinh3"></div><br>
		<div id="debugtop"></div><br>
		<div id="ketqua"></div><br>
		<div id="ketqua1"></div><br>
		<div id="hienhinh"></div>
		<div id="debug"></div>
	</div>
</div>