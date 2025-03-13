$(document).ready(function() {
	var noti_form = $('#write_top_notice_form');
	var select_id = 0;
	var noti_mode = 'write';
	
	noti_form.dialog({
		autoOpen: false,
		width: 520,
		modal: true,
		buttons: {
			"작성하기" : function() {
				var post_data = {};
				post_data['mode'] = noti_mode;
				post_data['title'] = noti_form.find('#top_notice_title').val();
				post_data['content'] = noti_form.find('#top_notice_content').val();
				post_data['noti_start_dt'] = convert_datetime_text_to_timestamp(String(noti_form.find('#top_notice_start_dt').val()));
				post_data['noti_end_dt'] = convert_datetime_text_to_timestamp(String(noti_form.find('#top_notice_end_dt').val()));
				post_data['ordernum'] = noti_form.find('#top_notice_ordernum').val();
				if(noti_mode=='update')
				{
					post_data['top_noti_pk'] = select_id;
				}
				          
				var key_name = {
					'title' : '공지 내용',
					'content' : '링크 주소',
					'noti_start_dt' : '공지 시작',
					'noti_end_dt' : '공지 종료',
					'ordernum' : '순번'
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
				
				$.post('./do/do_update_top_notice.php', post_data, function(data) {
					if (data.result == 'ok')
					{
						alert('통합 공지를 등록하였습니다.');
						document.location.reload();
					} else if (data.result == 'fail') {
						alert(data.msg);
					}
				}, 'json');
			},
			"취소하기" : function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$(this).dialog("close");
		},
		open: function() {
		}
	});
	
	$('#write_top_notice_btn').click(function() {
		noti_mode = 'write';
		noti_form.dialog("open");
	});
	
	$('#modify_top_notice_btn').click(function() {
		noti_mode = 'modify';
		if(confirm(select_id + '번 공지를 수정하시겠습니까?'))
		{
			var post_data = {};
			post_data['mode'] = noti_mode;
			post_data['top_noti_pk'] = select_id;
			
			$.post('./do/do_update_top_notice.php', post_data, function(data) {
				if (data.result == 'ok')
				{
					noti_mode = 'update';
					noti_form.find('#top_notice_title').val(data.d[0]['noti_title']);
					noti_form.find('#top_notice_content').val(data.d[0]['noti_link']);
					noti_form.find('#top_notice_start_dt').val(data.d[0]['start_dt'].substr(0,16));
					noti_form.find('#top_notice_end_dt').val(data.d[0]['end_dt'].substr(0,16));
					noti_form.find('#top_notice_ordernum').val(data.d[0]['ordernum']);
					
					noti_form.dialog("open");
				} else if (data.result == 'fail') {
					alert(data.msg);
				}
			}, 'json');
		}
	});
	
	$('#delete_top_notice_btn').click(function() {
		if(confirm(select_id + '번 공지를 삭제하시겠습니까?'))
		{
			noti_mode = 'delete';
			var post_data = {};
			post_data['mode'] = noti_mode;
			post_data['top_noti_pk'] = select_id;
			
			$.post('./do/do_update_top_notice.php', post_data, function(data) {
				if (data.result == 'ok')
				{
					alert('통합 공지를 삭제하였습니다.');
					document.location.reload();
				} else if (data.result == 'fail') {
					alert(data.msg);
				}
			}, 'json');
		}
	});
	
	$("#top_notice_start_dt").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});
	$("#top_notice_end_dt").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});
	
	$("#top_modify_start_dt").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});
	$("#top_modify_end_dt").datetimepicker({
		dateFormat : 'yy-mm-dd',
		currentText : 'Now',
		changeYear : true,
		chageMonth : true,
		hour : new Date().getHours(),
		minute : new Date().getMinutes()
	});
	// 서버 목록
	table_to_jqGrid(
		'top_notice_list',
		'pager_top_notice_list',
		'문의 접수',
		['번호', '순번', '내용', '링크', '등록/수정', '시작일', '종료일'],
		[
	  		{'name' : 'number', 'index' : 'number', 'width' : 40, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'ordernum', 'index' : 'ordernum', 'width' : 40, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'noti_title', 'index' : 'noti_title', 'align' : 'center', 'sortable' : false},
	  		{'name' : 'noti_link', 'index' : 'noti_link', 'width' : 230, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'regist_dt', 'index' : 'regist_dt', 'width' : 130, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'start_dt', 'index' : 'start_dt', 'width' : 130, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'end_dt', 'index' : 'end_dt', 'width' : 130, 'align' : 'center', 'fixed' : true, 'sortable' : false}
	  	],
	  	null, // 전송할 건 없음
	  	function(id){
			// 행을 클릭할 경우
			select_id = id;
		},
		null // 콜백 없음
	);
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