$(document).ready(function(){

	$.post('/admin/gm/api/checkNoticeCache', {}, function(data) {
		if (data.result !== 'ok') {
			alert(data.msg);
			return;
		}
		console.log(data);
	}, 'json');

	$('#write_integrate_notice_form').dialog({
		autoOpen: false,
		height: 250,
		width: 400,
		modal: true,
		buttons: {
			'공지 등록' : function(){
				var post_data = {};
				post_data['mode'] = 'write';
				post_data['title'] = $('#integrate_notice_title').val();
				post_data['content'] = $('#integrate_notice_content').val();
				post_data['type'] = $('#integrate_notice_type').val();
				post_data['noti_start_dt'] = convert_datetime_text_to_timestamp(String($('#integrate_notice_start_dt').val()));
				post_data['noti_end_dt'] = convert_datetime_text_to_timestamp(String($('#integrate_notice_end_dt').val()));
				post_data['ordernum'] = $('#integrate_notice_ordernum').val();

				var key_name = {
					'title' : '공지 내용',
					'content' : '링크 주소',
					'type' : '타입',
					'noti_start_dt' : '공지 시작',
					'noti_end_dt' : '공지 종료'
				};
				var is_valid = true;
				$.each(post_data, function(k, v) {
					if (!is_valid) return;
					if (!v) {
						is_valid = false;
						alert(key_name[k] + '이(가) 입력되지 않았습니다.');
					}
				});
				if (!is_valid) return false;
				if (String(post_data['ordernum']).search(/[^\d]+/) >= 0) {
					alert('순번은 반드시 숫자로 입력하여야합니다.');
					return false;
				}
				var rthis = this;
				$.post('./do/do_update_integrate_notice.php', post_data, function(data) {
					if (data.result == 'ok')
					{
						alert('통합 공지를 등록하였습니다.');
						$(rthis).dialog("close");
						document.location.href = document.location.href;
					} else if (data.result == 'fail') {
						alert(data.msg);
					}
				}, 'json');
			}
		},
		close: function() {
			$('#integrate_notice_title').val('');
			$('#integrate_notice_content').val('');
			$('#integrate_notice_start_dt').val('');
			$('#integrate_notice_end_dt').val('');
			$('#integrate_notice_ordernum').val('');
		}
	});

	var selected_noti_pk = false;

	var modify_noti_pk = 0;
	$('#modify_integrate_notice_form').dialog({
		autoOpen: false,
		height: 250,
		width: 400,
		modal: true,
		buttons: {
			'공지 수정' : function(){
				var post_data = {};
				post_data['mode'] = 'update';
				post_data['title'] = $('#integrate_modify_title').val();
				post_data['content'] = $('#integrate_modify_content').val();
				post_data['type'] = $('#integrate_notice_type').val();
				post_data['noti_start_dt'] = convert_datetime_text_to_timestamp(String($('#integrate_modify_start_dt').val()));
				post_data['noti_end_dt'] = convert_datetime_text_to_timestamp(String($('#integrate_modify_end_dt').val()));
				post_data['ordernum'] = $('#integrate_modify_ordernum').val();
				post_data['noti_pk'] = selected_noti_pk;

				var key_name = {
					'title' : '공지 내용',
					'content' : '링크 주소',
					'type' : '타입',
					'noti_start_dt' : '공지 시작',
					'noti_end_dt' : '공지 종료',
					'noti_pk' : '공지 번호'
				};
				var is_valid = true;
				$.each(post_data, function(k, v) {
					if (!is_valid) return;
					if (!v) {
						is_valid = false;
						alert(key_name[k] + '이(가) 입력되지 않았습니다.');
					}
				});
				if (!is_valid) return false;
				if (String(post_data['ordernum']).search(/[^\d]+/) >= 0) {
					alert('순번은 반드시 숫자로 입력하여야합니다.');
					return false;
				}

				var rthis = this;
				$.post('./do/do_update_integrate_notice.php', post_data, function(data) {
					if (data.result == 'ok')
					{
						alert('통합 공지를 수정하였습니다.');
						$(rthis).dialog("close");
						document.location.href = document.location.href;
					} else if (data.result == 'fail') {
						alert(data.msg);
					}
				}, 'json');
			}
		},
		open: function() {
			if(typeof($(this).data()) == 'object')
			{
				var modify_data = {};
				modify_data = $(this).data()['modify_data'];

				$('#integrate_modify_title').val(modify_data.title);
				$('#integrate_modify_content').val(modify_data.content);
				$('#integrate_modify_start_dt').val(modify_data.noti_start_dt.substr(0,16));
				$('#integrate_modify_end_dt').val(modify_data.noti_end_dt.substr(0,16));
				$('#integrate_modify_ordernum').val(modify_data.ordernum);
			}
		},
		close: function() {
			$('#integrate_modify_title').val('');
			$('#integrate_modify_content').val('');
			$('#integrate_modify_start_dt').val('');
			$('#integrate_modify_end_dt').val('');
			$('#integrate_modify_ordernum').val('');
		}
	});

	$('#write_integrate_notice').click(function(){ $("#write_integrate_notice_form").dialog('open'); });

	$("#integrate_notice_start_dt").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});
	$("#integrate_notice_end_dt").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});

	$("#integrate_modify_start_dt").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});
	$("#integrate_modify_end_dt").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});

	$('#delete_integrate_notice').click(function() {
		if (!selected_noti_pk) return false;
		if (String(selected_noti_pk).search(/[^\d]/g) >= 0) return false;
		if (confirm(selected_noti_pk + '번 공지를 삭제하시겠습니까?'))
		{
			var post_data = {};
			post_data['mode'] = 'delete';
			post_data['noti_pk'] = selected_noti_pk;
			$.post('./do/do_update_integrate_notice.php', post_data, function(data) {
				if (data.result == 'ok')
				{
					alert(post_data['noti_pk'] + ' 번 공지를 삭제하였습니다.');
					document.location.href = document.location.href;
				} else if (data.result == 'fail') {
					alert(data.msg);
				}
			}, 'json');
		}
		return true;
	});

	$('#modify_integrate_notice').click(function() {
		if (!selected_noti_pk) return false;
		if (String(selected_noti_pk).search(/[^\d]/g) >= 0) return false;
		if (confirm(selected_noti_pk + '번 공지를 수정하시겠습니까?'))
		{
			var post_data = {};
			post_data['mode'] = 'modify';
			post_data['noti_pk'] = selected_noti_pk;
			$.post('./do/do_update_integrate_notice.php', post_data, function(data) {
				if (data.result == 'ok')
				{
					var modify_data = {};

					modify_data['noti_pk'] = data.selected_noti_pk;
					modify_data['title'] = data.title;
					modify_data['content'] = data.content;
					modify_data['type'] = data.type;
					modify_data['noti_start_dt'] = data.noti_start_dt;
					modify_data['noti_end_dt'] = data.noti_end_dt;
					modify_data['ordernum'] = data.ordernum;

					$("#modify_integrate_notice_form").data('modify_data', modify_data).dialog('open');
				} else if (data.result == 'fail') {
					alert(data.msg);
				}
			}, 'json');
		}
		return true;
	});

	$('#cache_integrate_notice').click(function(){
		var post_data = {};
		post_data['mode'] = 'cache';
		$.post('./do/do_update_integrate_notice.php', post_data, function(data) {
			if (data.result == 'ok')
			{
				alert('통합 공지 내용을 캐싱하였습니다.');
				document.location.href = document.location.href;
			} else if (data.result == 'fail') {
				alert(data.msg);
			}
		}, 'json');
	});

	table_to_jqGrid(
		'serv_detail_info_integrate_notice',
		'pager_serv_detail_info_integrate_notice',
		'통합공지',
		['고유번호', '공지 내용', '링크', '타입', '공지 시작 일시', '공지 종료 일시', '순번'],
		[
	  		{'name' : 'noti_pk', 'index' : 'noti_pk', 'width' : 60, 'fixed' : true, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'title', 'index' : 'title', 'width' : 200, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'content', 'index' : 'content', 'width' : 276, 'fixed' : true, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'noti_type', 'index' : 'noti_type', 'fixed' : true, 'width' : 40, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'noti_start_dt', 'index' : 'noti_start_dt', 'fixed' : true, 'width' : 133, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'noti_end_dt', 'index' : 'noti_end_dt', 'fixed' : true, 'width' : 133, 'align' : 'center', 'sortable' : false},
	  		{'name' : 'ordernum', 'index' : 'ordernum', 'fixed' : true, 'width' : 40, 'align' : 'center', 'sortable' : false},
	  	],
	  	null,
	  	function(noti_pk) {
			selected_noti_pk = noti_pk;
		}
	);
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조
});

function convert_datetime_text_to_timestamp(datetime)
{
	if (/^[\d]{4}\-[0-1][\d]\-[0-3][\d]\s[0-2][\d]\:[0-5][\d]$/.test(datetime))
	{
		datetime = String(datetime).split(/[\-\:\ ]/g);
		var dt = new Date();
		dt.setFullYear(datetime[0], (parseInt(datetime[1], 10) - 1), datetime[2]);
		dt.setHours(datetime[3], datetime[4], 0, 0);
		return parseInt((dt.getTime() / 1000), 10);
	}
	return false;
}