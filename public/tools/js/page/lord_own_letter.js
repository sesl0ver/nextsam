$(document).ready(function(){
	var letter_info = {};

	// GM 로그
	table_to_jqGrid('lord_own_letter', 'pager_lord_own_letter', '외교서신 현황', ['No.', '타입', '제목', '발신인', '수신인', '수신시간', '확인', '수신삭제', '발신삭제'],
	[
		{'name' : 'no', 'index' : 'no', 'width' : 25, 'align' : 'center', 'sortable' : false},
		{'name' : 'type', 'index' : 'type', 'width' : 30, 'align' : 'center', 'sortable' : false},
		{'name' : 'title', 'index' : 'title', 'align' : 'left', 'sortable' : false},
		{'name' : 'from_name', 'index' : 'from_name', 'width' : 30, 'align' : 'center', 'sortable' : false},
		{'name' : 'to_name', 'index' : 'to_name', 'width' : 30, 'align' : 'center', 'sortable' : false},
		{'name' : 'date', 'index' : 'date', 'width' : 30, 'align' : 'center', 'sortable' : false},
		{'name' : 'read', 'index' : 'read', 'width' : 20, 'align' : 'center', 'sortable' : false},
		{'name' : 'to_delete', 'index' : 'to_delete', 'width' : 20, 'align' : 'center', 'sortable' : false},
		{'name' : 'from_delete', 'index' : 'from_delete', 'width' : 20, 'align' : 'center', 'sortable' : false}
	],
	false,
	function(lett_pk){
		$.ajax({
			'type' : 'POST',
			'url' : '/admin/gm/api/viewLordLetter',
			'data' : {'lett_pk':lett_pk},
			'success' : function(data, textStatus, XMLHttpReq){
				letter_info = data;
				$('#letter_info').dialog('open');
			},
			'error' : function(){
				alert('서버와의 통신 중 에러가 발생하였습니다.');
				return false;
			},
			'dataType' : 'json'
		});
	});
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조
	$('#letter_info').dialog({
		autoOpen: false,
		height: 600,
		width: 800,
		modal: true,
		buttons: {
			'닫기' : function(){ $(this).dialog("close"); }
		},
		open: function() {
			var table = $('#letter_info');
			table.find('td.title').html(letter_info['title']);
			table.find('td.to_name').html(letter_info['to_lord_pk']);
			table.find('td.from_name').html(letter_info['to_lord_pk']);
			table.find('td.content').html(letter_info['content']);
		}
	});
});