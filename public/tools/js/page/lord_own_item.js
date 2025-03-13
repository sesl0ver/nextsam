$(document).ready(function(){
	// GM 로그
	table_to_jqGrid('lord_own_item', 'pager_lord_own_item', '군주 보유 아이템 목록', ['No.', '아이템명', '아이템코드', '유형', '발급코드', '가격', '수량'], [
		{'name' : 'my_item_pk', 'index' : 'my_item_pk', 'width' : 60, 'align' : 'center', 'sortable' : false},
		{'name' : 'title', 'index' : 'title', 'width' : 130, 'align' : 'center', 'sortable' : false},
		{'name' : 'm_item_pk', 'index' : 'm_item_pk', 'width' : 80, 'align' : 'center', 'sortable' : false},
		{'name' : 'type', 'index' : 'type', 'width' : 40, 'align' : 'center', 'sortable' : false},
		{'name' : 'log_code', 'index' : 'log_code', 'width' : 80, 'align' : 'center', 'sortable' : false},
		{'name' : 'price', 'index' : 'price', 'width' : 80, 'align' : 'center', 'sortable' : false},
		{'name' : 'item_cnt', 'index' : 'item_cnt', 'width' : 80, 'align' : 'center', 'sortable' : false}
	]);
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조

	$("#incr_item_form").dialog({
		autoOpen: false,
		height: 780,
		width: 1480,
		modal: true,
		buttons: {
			"아이템 지급하기": function() {
				var incr_item_pk = [];
				var incr_item_count = [];
				$.each($('#incr_item_form input[type=checkbox]:checked'), function(k, v){
					incr_item_pk.push( $(v).val() );
					incr_item_count.push( $('#m_item_' + $(v).val()).val() );
				});

				// 입력값 확인
				for(var i = 0; i < incr_item_count.length; i++)
				{
					if (isNaN(incr_item_count[i]) || String(incr_item_count[i]).length < 1 || parseInt(incr_item_count[i], 10) < 1)
					{
						alert('지급할 수량이 올바르지 않게 입력된 항목이 있습니다. 다시 확인하여주십시오.');
						$('#m_item_' + incr_item_pk[i]).focus();
						return false;
					}
				}

				var incr_item_cause = $('#incr_item_cause').val();

				if (incr_item_pk.length < 1)
				{
					alert('지급하고자하는 아이템을 선택해주십시오.');
					return false;
				}
				if (incr_item_cause.length < 1)
				{
					alert('지급 사유를 입력하여주십시오.');
					$('#incr_item_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'lord_pk' : gm_info.selected_lord_pk, 'incr_item_pk[]' : incr_item_pk, 'incr_item_count[]' : incr_item_count, 'incr_item_cause' : incr_item_cause};
				$.post('/admin/gm/api/increaseItem', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('아이템을 지급하였습니다.');
						$('#lord_own_item').trigger('reloadGrid');
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#incr_item_form input[type=checkbox]:checked').attr('checked', false);
			$('#incr_item_form input[type=text]').val('');
			$('#incr_item_cause').val('');
		}
	});

	$('#incr_item_form input[type=checkbox]').change(function(){
		var start_td = $(this).parent();
		var highlight = 'yellow';
		if (!$(this).is(':checked'))
		{
			highlight = 'inherit';
		}
		start_td.css('background-color', highlight).next().css('background-color', highlight).next().css('background-color', highlight);
	});

	$("#decr_item_form").dialog({
		autoOpen: false,
		height: 780,
		width: 1340,
		modal: true,
		open: function(){
			var post_data = { 'lord_pk' : gm_info.selected_lord_pk };
			$.post('/admin/gm/api/ownItem', post_data, function(data){
				if (data.result == 'fail')
				{
					alert(data.msg);
					return false;
				} else {
					var own_tbody = $('#desc_item_lord_own_list');
					own_tbody.empty();

					var max_count = 0;

					$.each(data.own_list, function(k, v){ max_count = ((max_count < v.length) ? v.length : max_count); });

					for (var i = 0; i < max_count; i++)
					{
						var tr = $('<tr></tr>');
						var disp_type_arr = ['P', 'S', 'L', 'D', 'B', 'H'];
						for (var j = 0; j < disp_type_arr.length; j++)
						{
							var now_item_elm = false;
							try
							{
								now_item_elm = data.own_list[disp_type_arr[j]][i];
							}
							catch(e)
							{
								now_item_elm = false;
							}

							var td_checkbox = (!now_item_elm) ? $('<td class="check"></td>') : $('<td class="check"><input type="checkbox" id="own_item_' + now_item_elm.item_pk + '" value="' + now_item_elm.item_pk + '"/></td>');
							var td_item_name = (!now_item_elm) ? $('<td></td>') : $('<td>' + now_item_elm.title + '</td>');
							var td_item_cnt = (!now_item_elm) ? $('<td></td>') : $('<td>' + now_item_elm.item_cnt + '</td>');
							var td_decr_input = (!now_item_elm) ? $('<td class="input"></td>') : $('<td class="input"><input type="number" id="decr_m_item_' + now_item_elm.item_pk + '" min="0" max="1000" value="0" /></td>');
							tr.append(td_checkbox).append(td_item_name).append(td_item_cnt).append(td_decr_input);
						}
						own_tbody.append(tr);
					}
				}
			}, 'json');
		},
		buttons: {
			"아이템 회수하기": function() {
				var decr_item_pk = [];
				var decr_item_count = [];
				$.each($('#decr_item_form input[type=checkbox]:checked'), function(k, v){
					decr_item_pk.push( $(v).val() );
					decr_item_count.push( $('#decr_m_item_' + $(v).val()).val() );
				});

				for(var i = 0; i < decr_item_count.length; i++)
				{
					if (isNaN(decr_item_count[i]) || String(decr_item_count[i]).length < 1 || parseInt(decr_item_count[i], 10) < 1)
					{
						alert('지급할 수량이 올바르지 않게 입력된 항목이 있습니다. 다시 확인하여주십시오.');
						$('#decr_m_item_' + decr_item_pk[i]).focus();
						return false;
					}
				}

				var decr_item_cause = $('#decr_item_cause').val();

				if (decr_item_pk.length < 1)
				{
					alert('회수하고자하는 아이템을 선택해주십시오.');
					return false;
				}
				if (decr_item_cause.length < 1)
				{
					alert('회수 사유를 입력하여주십시오.');
					$('#decr_item_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'lord_pk' : gm_info.selected_lord_pk, 'decr_item_pk[]' : decr_item_pk, 'decr_item_count[]' : decr_item_count, 'decr_item_cause' : decr_item_cause};
				$.post('/admin/gm/api/decreaseItem', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('아이템을 회수하였습니다.');
						$('#lord_own_item').trigger('reloadGrid');
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#decr_item_form input[type=checkbox]:checked').removeAttr('checked');
			$('#decr_item_qty').val('');
			$('#decr_item_cause').val('');
		}
	});

	$('#decr_item_form input[type=checkbox]').bind('change', function(){
		var start_td = $(this).parent();
		var highlight = 'yellow';
		if (!$(this).is(':checked'))
		{
			highlight = 'inherit';
		}
		start_td.css('background-color', highlight).next().css('background-color', highlight).next().css('background-color', highlight).next().css('background-color', highlight);
	});

	$('#incr_item').click(function(){ $('#incr_item_form').dialog("open"); });
	$('#decr_item').click(function(){ $('#decr_item_form').dialog("open"); });
});