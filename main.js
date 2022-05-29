/*!
 * author: brevis
 */
$(document).ready(function () {
	var ori_title = ' ' + document.title;
	$('input[name=api-url]').val(sessionStorage.getItem("api"));
	var start = 0;
	var stop = 0;
	$("#stop").hide();
	$("#stop").click(function () {
		start = 0;
		$("#search").show();
		$("#stop").hide();
		if (start == 0) {
			$("#loading").html('');
		}
	});
	$("#cancel").click(function () {
		$("#setupshow").html("");
		$("#setup").show();
	});
	$("#setup").click(function () {
		start = 0;
		var paths = $('input[name=path]').val();
		$("#search").show();
		$("#stop").hide();
		$("#setup").hide();
		if (start == 0) {
			$("#loading").html('');
		}
		var setupurl = paths + 'splt-grab.php?setup=setup';
		$.get(setupurl, function (setupdata) {
			if (setupdata) {
				$("#setupshow").html(setupdata);
			}
		});

	});
	if (start == 0) {
		$("#loading").html('');
	}

	$("#search").click(function () {
		start = 1;
		$("#search").hide();
		$("#stop").show();
		$("#hienhinh").html('');
		$("#ketqua").html('');
		$("#debug").html('');

		var path = $('input[name=path]').val();
		var typeleech = $(".typeleech:checked").val();
		var author = $('select[name=user]').val();
		var showlog = '';
		var countprocess = 0;
		var tientrinh_post = '';
		var apiUrlStr;
		var iurl = 0;
		var pageLinkUrl;
		var pageCount = 0;
		var objJson;
		var procadd;
		var procpost = 0;
		var procnumpage = 0;
		var procsumpost = 0;
		var update = 0;

		apiUrlStr = $('input[name=api-url]').val();
		if (apiUrlStr == "") {
			alert("API không thể để trống");
			$("#search").show();
			$("#stop").hide();
			$("#loading").html('');
			return;
		}
		fetch_data();
		if (start == 0) {
			$("#loading").html('');
		}
		if (sessionStorage.getItem("api") !== apiUrlStr) {
			sessionStorage.setItem("api", apiUrlStr);
		}

		function fetch_data() {
			if (typeleech == "today") {
				loop_get_page(apiUrlStr, typeleech);
			}
			if (typeleech == "full") {
				loop_get_page(apiUrlStr, typeleech);
			}
			if (typeleech == "resume") {
				loop_resume();
			}

			if (start == 0) {
				$("#search").show();
				$("#stop").hide();
				$("#loading").html('');
			}
			return;
		}

		/**
		 * loop get page
		 * @param {*} address
		 * @param {*} fetch_type
		 * @returns 
		 */
		function loop_get_page(address, fetch_type) {
			if (start == 0) {
				return;
			}
			var graburl = path + 'splt-grab.php?url=' + encodeURIComponent(address) + '&type=numpage&fetchtype=' + fetch_type;
			$.get(graburl, function (pageData) {
				objJson = JSON.parse(pageData);

				if (objJson.code > 1) {
					$("#ketqua").append('Thu thập API bị lỗi, kiểm tra lại địa chỉ API<br>Chi tiết: ' + objJson.msg);
					start = 0;
				} else {
					pageCount = objJson.pagecount;
					procnumpage = objJson.page;
					procsumpost = objJson.recordcount;
					pageLinkUrl = objJson.url;
					showlogs();
					loop_get_detail_page(pageLinkUrl, procnumpage);
				}
			});
			return;
		}

		/**
		 * loop get detail page
		 * @param {*} address
		 * @param {*} numpage
		 * @returns
		 */
		function loop_get_detail_page(address, page) {
			if (start == 0) {
				return;
			}
			procnumpage = page;
			procadd = address;

			// Get data mỗi page: procnumpage chạy từ 1 đến pageCount
			var graburl = path + "splt-grab.php?url=" + encodeURIComponent(address) + '&type=pagedetail&numpage=' + procnumpage + '&author=' + author;

			$.get(graburl, function(response) {
				// Hiển thị quá trình. Chi tiết do splt-grab.php xử lý
				objJson = JSON.parse(response);
				procpost += objJson.saved_post;
				showlogs();

				$("#ketqua").html('');
				var li = '';
				objJson.msg.forEach(element => {
					if (element.includes("thành công")) {
						li += '<li style="color: green;">' + element + '</li>';
					} else {
						li += '<li style="color: red;">' + element + '</li>';
					}
				});
				$("#ketqua").append('<ol>'+ li + '</ol>');

				procnumpage ++;
				if (procnumpage <= pageCount) {
					loop_get_detail_page(procadd, procnumpage);
				} else {
					$("#ketqua").append('Hoàn thành thu thập');
					start = 0;
					return;
				}
			});
		}

		/**
		 * Show log
		 * @returns void
		 */
		function showlogs() {
			countprocess++;
			if ((countprocess > 10) && (showlog == 'limit')) {
				countprocess = 0;
				$("#ketqua").html('');
			}
			showprocess();
			if (start == 0) {
				$("#search").show();
				$("#stop").hide();
				$("#loading").html('');
				document.title = 'done|' + ori_title;
			}
			return;
		}

		function showprocess() {

			tientrinh_post = procnumpage + '/' + pageCount;
			document.title = tientrinh_post + '|' + ori_title;
			if (update == 1) {
				$("#tientrinh").html('<font color="green">UPDATE MODE: </font>' + (iurl + 1) + '/' + apiUrlStr.length + '|' + apiUrlStr[iurl] + '| page:' + procnumpage);
			} else {
				$("#tientrinh").html('<font color="green">Quá Trình : </font>' + 'Thu thập trang ' + procnumpage + '/' + pageCount);
			}

			$("#tientrinh2").html('<font color="green">Xử lý bài viết: </font>' + procpost + '/' + procsumpost);
			// $("#tientrinh3").html('<font color="green">Tập Phim : </font>' + (dem_video + 1) + '/' + procsumep);
			return;
		}
		if (start == 0) {
			$("#loading").html('');
		}
	});
});