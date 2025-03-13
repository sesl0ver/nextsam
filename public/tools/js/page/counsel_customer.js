$(document).ready(function(){
	var counsel_pk = false;
	$("#view_counsel").dialog({
		autoOpen: false,
		height: 500,
		width: 700,
		modal: true,
		buttons: {
			"닫기": function() {
				$(this).dialog("close");
			},
			"완료 처리": function() {
				var coun_pk = $(this).data()['counsel_data']['msg']['coun_pk'];
				var is_conclude = $(this).data()['counsel_data']['msg']['is_conclude'];
				var self = $(this);
				if (is_conclude == 'Y') return;
				if (confirm('완료 처리 하시겠습니까?'))
				{
					$.post('./do/counsel_info.php', {'action':'conclude', 'coun_pk':coun_pk}, function(data){
						alert('완료 상태로 처리되었습니다.');
						$('#'+coun_pk).find('td:eq(5)').text('Y');
						self.dialog("close");
					}, 'json');
				}
			}
			
		},
		open: function() {
			var counsel_data = $(this).data()['counsel_data']['msg'];
			$(this).find('p').html(' ');
			var self = $(this);
			$.each(counsel_data, function(k, v) {
				console.log(k);
				self.find('.' + k).html(v);
			});
		},
		close: function() {
			$('#incr_item_form input[type=checkbox]:checked').attr('checked', false);
			$('#incr_item_form input[type=text]').val('');
			$('#incr_item_cause').val('');
		}
	});
	// 서버 목록
	table_to_jqGrid(
		'counsel_customer',
		'pager_counsel_customer',
		'문의 접수',
		['번호', 'Account PK', '제목', '메일', '군주명', '완료', '발신일시'],
		[
	  		{'name' : 'number', 'index' : 'number', 'width' : 40, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'APPS_ID', 'index' : 'APPS_ID', 'width' : 80, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'subject', 'index' : 'subject', 'align' : 'left', 'sortable' : false},
	  		{'name' : 'user_name', 'index' : 'user_name', 'width' : 200, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'lord_name', 'index' : 'lord_name', 'width' : 80, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'is_conclude', 'index' : 'is_conclude', 'width' : 80, 'align' : 'center', 'fixed' : true, 'sortable' : false},
	  		{'name' : 'send_dt', 'index' : 'send_dt', 'width' : 120, 'align' : 'center', 'fixed' : true, 'sortable' : false}
	  	],
	  	{'list_type':$('#list_type').val()}, // 전송할 건 없음
	  	function(id){
			// 행을 클릭할 경우
			$.post('./do/counsel_info.php', {'action':'view', 'coun_pk':id}, function(data){
				$("#view_counsel").data('counsel_data', data).dialog('open');
			}, 'json');
		},
		null // 콜백 없음
	);
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조
	
	$('#sort_btn').bind('mouseup', function(){
		var table = $('<table id="counsel_customer" class="jqGridTable"></table>');
		var div = $('<div id="pager_counsel_customer"></div>');
		var target = $('#search_result').empty();
		
		target.append(table).append(div);
			
		table_to_jqGrid(
			'counsel_customer',
			'pager_counsel_customer',
			'문의 접수',
			['번호', 'Account PK', '제목', '메일', '군주명', '완료', '발신일시'],
			[
		  		{'name' : 'number', 'index' : 'number', 'width' : 40, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		  		{'name' : 'APPS_ID', 'index' : 'APPS_ID', 'width' : 80, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		  		{'name' : 'subject', 'index' : 'subject', 'align' : 'left', 'sortable' : false},
		  		{'name' : 'user_name', 'index' : 'user_name', 'width' : 80, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		  		{'name' : 'lord_name', 'index' : 'lord_name', 'width' : 80, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		  		{'name' : 'is_conclude', 'index' : 'is_conclude', 'width' : 80, 'align' : 'center', 'fixed' : true, 'sortable' : false},
		  		{'name' : 'send_dt', 'index' : 'send_dt', 'width' : 120, 'align' : 'center', 'fixed' : true, 'sortable' : false}
		  	],
		  	{'list_type':$('#list_type').val(),'list_type2':$('#list_type2').val(),'search_keyword':$('#search_keyword').val()}, // 전송할 건 없음
		  	function(id){
				// 행을 클릭할 경우
				$.post('./do/counsel_info.php', {'action':'view', 'coun_pk':id}, function(data){
					$("#view_counsel").data('counsel_data', data).dialog('open');
				}, 'json');
			},
			null // 콜백 없음
		);
	});
});