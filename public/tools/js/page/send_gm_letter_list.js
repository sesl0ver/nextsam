$(document).ready(function(){
	var counsel_pk = false;
	$("#view_letter").dialog({
		autoOpen: false,
		height: 500,
		width: 700,
		modal: true,
		buttons: {
			"닫기": function() {
				$(this).dialog("close");
			}
			
		},
		open: function() {
			var letter_data = $(this).data()['letter_data'];
			$('#view_letter p.user').html(letter_data['user']);
			$('#view_letter p.subject').html(letter_data['subject']);
			$('#view_letter p.content').html(letter_data['content']);
		},
		close: function() {
			$('#incr_item_form input[type=checkbox]:checked').attr('checked', false);
			$('#incr_item_form input[type=text]').val('');
			$('#incr_item_cause').val('');
		}
	});
	// 서버 목록
	table_to_jqGrid(
		'letter_list',
		'pager_letter_list',
		'GM서신 발신함',
		['발신일시', '수신', '제목', '발신', '내용'],
		[
	  		{'name' : 'regist_dt', 'index' : 'regist_dt', 'width' : 120, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'user', 'index' : 'user', 'width' : 200, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'subject', 'index' : 'subject', 'align' : 'left', 'sortable' : false},
	  		{'name' : 'gm_id', 'index' : 'gm_id', 'width' : 80, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'content', 'index' : 'content', 'width' : 120, 'align' : 'center', 'fixed' : true, 'sortable' : false, 'hidden' : true}
	  	],
	  	null, // 전송할 건 없음
	  	function(id){
			// 행을 클릭할 경우
			data = {};
			data['subject'] = $('#' + id + ' td:gt(1)').html();
			data['user'] = $('#' + id + ' td:gt(0)').html();
			data['content'] = $('#' + id + ' td:gt(3)').html();
			
			$("#view_letter").data('letter_data', data).dialog('open');
		},
		null // 콜백 없음
	);
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조
});