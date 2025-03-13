$(document).ready(function(){
	// GM 로그
	table_to_jqGrid('lord_own_skill', 'pager_lord_own_skill', '군주 보유 스킬 목록', ['No.', '스킬명', '스킬코드', '유형', '발급코드', '필요슬롯', '수량'], [
		{'name' : 'my_skill_pk', 'index' : 'my_skill_pk', 'width' : 60, 'align' : 'center', 'sortable' : false},
		{'name' : 'title', 'index' : 'title', 'width' : 130, 'align' : 'center', 'sortable' : false},
		{'name' : 'm_skill_pk', 'index' : 'm_skill_pk', 'width' : 80, 'align' : 'center', 'sortable' : false},
		{'name' : 'type', 'index' : 'type', 'width' : 40, 'align' : 'center', 'sortable' : false},
		{'name' : 'log_code', 'index' : 'log_code', 'width' : 80, 'align' : 'center', 'sortable' : false},
		{'name' : 'use_slot_count', 'index' : 'use_slot_count', 'width' : 80, 'align' : 'center', 'sortable' : false},
		{'name' : 'skill_cnt', 'index' : 'skill_cnt', 'width' : 80, 'align' : 'center', 'sortable' : false}
	]);
	// table_to_jqGrid 함수의 상세는 request_func.js 파일 참조

	$("#incr_skill_form").dialog({
		autoOpen: false,
		height: 780,
		width: 950,
		modal: true,
		buttons: {
			"스킬 지급하기": function() {
				var incr_skill_pk = [];
				var incr_skill_count = [];
				$.each($('#incr_skill_form input[type=checkbox]:checked'), function(k, v){
					incr_skill_pk.push( $(v).val() );
					incr_skill_count.push( $('#m_skill_' + $(v).val()).val() );
				});

				// 입력값 확인
				for(var i = 0; i < incr_skill_count.length; i++)
				{
					if (isNaN(incr_skill_count[i]) || String(incr_skill_count[i]).length < 1 || parseInt(incr_skill_count[i], 10) < 1)
					{
						alert('지급할 수량이 올바르지 않게 입력된 항목이 있습니다. 다시 확인하여주십시오.');
						$('#m_skill_' + incr_skill_pk[i]).focus();
						return false;
					}
				}

				var incr_skill_cause = $('#incr_skill_cause').val();

				if (incr_skill_pk.length < 1)
				{
					alert('지급하고자하는 스킬을 선택해주십시오.');
					return false;
				}
				if (incr_skill_cause.length < 1)
				{
					alert('지급 사유를 입력하여주십시오.');
					$('#incr_skill_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'lord_pk' : gm_info.selected_lord_pk, 'incr_skill_pk[]' : incr_skill_pk, 'incr_skill_count[]' : incr_skill_count, 'incr_skill_cause' : incr_skill_cause};
				$.post('/admin/gm/api/increaseSkill', post_data, function(data){
					if (data.result === 'fail') {
						alert(data.msg);
						return false;
					} else {
						alert('스킬을 지급하였습니다.');
						$('#lord_own_skill').trigger('reloadGrid');
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#incr_skill_form input[type=checkbox]:checked').attr('checked', false);
			$('#incr_skill_form input[type=text]').val('');
			$('#incr_skill_cause').val('');
		}
	});

	$('#incr_skill_form input[type=checkbox]').change(function(){
		var start_td = $(this).parent();
		var highlight = 'yellow';
		if (!$(this).is(':checked'))
		{
			highlight = 'inherit';
		}
		start_td.css('background-color', highlight).next().css('background-color', highlight).next().css('background-color', highlight);
	});

	$("#decr_skill_form").dialog({
		autoOpen: false,
		height: 780,
		width: 1340,
		modal: true,
		open: function(){
			var post_data = {'action' : 'get_lord_own_list', 'lord_pk' : gm_info.selected_lord_pk};
			$.post('/admin/gm/api/ownSkill', post_data, function(data){
				if (data.result === 'fail') {
					alert(data.msg);
					return false;
				} else {
					var own_tbody = $('#desc_skill_lord_own_list');
					own_tbody.empty();

					var max_count = 0;

					$.each(data.own_list, function(k, v){ max_count = ((max_count < v.length) ? v.length : max_count); });

					for (var i = 0; i < max_count; i++)
					{
						var tr = $('<tr></tr>');
						var disp_type_arr = ['D', 'P', 'A', 'B', 'S'];
						for (var j = 0; j < disp_type_arr.length; j++)
						{
							var now_skill_elm = false;
							try
							{
								now_skill_elm = data.own_list[disp_type_arr[j]][i];
							}
							catch(e)
							{
								now_skill_elm = false;
							}

							var td_checkbox = (!now_skill_elm) ? $('<td class="check"></td>') : $('<td class="check"><input type="checkbox" id="own_skill_' + now_skill_elm.m_hero_skil_pk + '" value="' + now_skill_elm.m_hero_skil_pk + '"/></td>');
							var td_skill_name = (!now_skill_elm) ? $('<td></td>') : $('<td>' + now_skill_elm.title + '</td>');
							var td_skill_cnt = (!now_skill_elm) ? $('<td></td>') : $('<td>' + now_skill_elm.skill_cnt + '</td>');
							var td_decr_input = (!now_skill_elm) ? $('<td class="input"></td>') : $('<td class="input"><input type="number" id="decr_m_skill_' + now_skill_elm.m_hero_skil_pk + '" min="0" max="1000" value="0" /></td>');
							tr.append(td_checkbox).append(td_skill_name).append(td_skill_cnt).append(td_decr_input);
						}
						own_tbody.append(tr);
					}
				}
			}, 'json');
		},
		buttons: {
			"스킬 회수하기": function() {
				var decr_skill_pk = [];
				var decr_skill_count = [];
				$.each($('#decr_skill_form input[type=checkbox]:checked'), function(k, v){
					decr_skill_pk.push( $(v).val() );
					decr_skill_count.push( $('#decr_m_skill_' + $(v).val()).val() );
				});

				for(var i = 0; i < decr_skill_count.length; i++)
				{
					if (isNaN(decr_skill_count[i]) || String(decr_skill_count[i]).length < 1 || parseInt(decr_skill_count[i], 10) < 1)
					{
						alert('지급할 수량이 올바르지 않게 입력된 항목이 있습니다. 다시 확인하여주십시오.');
						$('#decr_m_skill_' + decr_skill_pk[i]).focus();
						return false;
					}
				}

				var decr_skill_cause = $('#decr_skill_cause').val();

				if (decr_skill_pk.length < 1)
				{
					alert('회수하고자하는 스킬을 선택해주십시오.');
					return false;
				}
				if (decr_skill_cause.length < 1)
				{
					alert('회수 사유를 입력하여주십시오.');
					$('#decr_skill_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'lord_pk' : gm_info.selected_lord_pk, 'decr_skill_pk[]' : decr_skill_pk, 'decr_skill_count[]' : decr_skill_count, 'decr_skill_cause' : decr_skill_cause};
				$.post('/admin/gm/api/decreaseSkill', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('스킬을 회수하였습니다.');
						$('#lord_own_skill').trigger('reloadGrid');
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#decr_skill_form input[type=checkbox]:checked').removeAttr('checked');
			$('#decr_skill_qty').val('');
			$('#decr_skill_cause').val('');
		}
	});

	$('#decr_skill_form input[type=checkbox]').bind('change', function(){
		var start_td = $(this).parent();
		var highlight = 'yellow';
		if (!$(this).is(':checked'))
		{
			highlight = 'inherit';
		}
		start_td.css('background-color', highlight).next().css('background-color', highlight).next().css('background-color', highlight).next().css('background-color', highlight);
	});

	$('#incr_skill').click(function(){ $('#incr_skill_form').dialog("open"); });
	$('#decr_skill').click(function(){ $('#decr_skill_form').dialog("open"); });
});