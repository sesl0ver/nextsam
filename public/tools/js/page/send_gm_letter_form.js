$(document).ready(function() {
	// ->
	var receiver_list = {};

	function addReceiver(server_pk, lord_pk, lord_name)
	{
		if (!server_pk)
		{
			var is_exist = false;

			for (e in receiver_list)
			{
				if (typeof receiver_list[e] == 'string' && receiver_list[e] == 'all')
				{
					is_exist = true;
				}
			}

			if (!is_exist)
			{
				$.each($('#target_server_select > select:first > option'), function(k, v){
					if (String($(v).val()).length > 0 && receiver_list[$(v).val()] != 'all')
					{
						receiver_list[$(v).val()] = 'all';
					}
				});
				return false;
			}
			else
			{
				alert('선택된 서버 중에 이미 전체 수신이 등록된 것이 있으므로 진행할 수 없습니다.');
				return true;
			}
		}
		if (!lord_pk)
		{
			if (receiver_list[server_pk] != 'all')
			{
				receiver_list[server_pk] = 'all';
				return false;
			}
			else
			{
				alert('해당 서버의 전체 유저가 이미 수신 대상으로 등록되어 있으므로 진행할 수 없습니다.');
				return true;
			}
		} else {
			if (typeof receiver_list[server_pk] == 'string' && receiver_list[server_pk] == 'all')
			{
				alert('해당 서버의 전체 유저가 이미 수신 대상으로 등록되어 있으므로 진행할 수 없습니다.');
				return true;
			}

			if (!receiver_list[server_pk])
			{
				receiver_list[server_pk] = [];
			}

			if (receiver_list[server_pk] instanceof Array)
			{
				var is_exist = false;
				for(var i = 0; i < receiver_list[server_pk].length; i++)
				{
					if (receiver_list[server_pk][i]['lord_pk'] == lord_pk)
					{
						is_exist = true;
					}
				}

				if (!is_exist)
				{
					receiver_list[server_pk].push({'lord_pk' : lord_pk, 'lord_name' : lord_name});
					return false;
				}
				else
				{
					alert('이미 수신 대상에 등록된 유저입니다.')
					return true;
				}
			}
			else
			{
				alert('illegal state!');
				return true;
			}
		}
	}

	function delReceiver(server_pk, lord_pk)
	{
		if (!server_pk || !receiver_list[server_pk])
		{
			alert('수신자를 삭제할 서버를 선택하지 않았습니다.');
			return true;
		}

		if (!lord_pk)
		{
			if (typeof receiver_list[server_pk] == 'string' && receiver_list[server_pk] == 'all')
			{
				receiver_list[server_pk] = undefined;
				delete receiver_list[server_pk];
				return false;
			}
			else
			{
				alert('illegal state!');
				return true;
			}
		}
		else
		{
			if (typeof receiver_list[server_pk] == 'string' && receiver_list[server_pk] == 'all')
			{
				alert('해당 서버의 전체 유저가 이미 수신 대상으로 등록되어 있으므로 진행할 수 없습니다.');
				return true;
			}

			if (receiver_list[server_pk] instanceof Array)
			{
				var is_exist = false;
				for(var i = 0; i < receiver_list[server_pk].length; i++)
				{
					if (receiver_list[server_pk][i] == lord_pk)
					{
						is_exist = true;
					}
				}

				var t = [];
				while(receiver_list[server_pk].length > 0)
				{
					var lord_info = receiver_list[server_pk].pop();
					if (lord_info['lord_pk'] != lord_pk)
					{
						t.push(lord_info);
					}
				}
				receiver_list[server_pk] = t;
				return false;
			}
			else
			{
				alert('illegal state!');
				return true;
			}
		}
	}

	$('#lord_list').dialog({
		autoOpen: false,
		width: 520,
		modal: true,
		buttons: {
			"군주 검색" : function() {
				var target_server_type = $('#target_server_select > input[name=target_server]:checked');
				var target_server_pk = $('#target_server_select > select:first > option:selected');
				var lord_name = $('#search_lord_name').val();

				if (target_server_type.length < 1)
				{
					alert('대상 서버를 선택해주세요.');
					return false;
				}

				if (target_server_type.val() == 'select')
				{
					if (target_server_pk.val().length < 1)
					{
						alert('대상 서버를 선택해주세요.');
						return false;
					}
				}

				var result_div = $('#lord_search_result_div');
				result_div.empty();

				var post_data = { 'target_server_type' : target_server_type.val(), 'target_server_pk' : target_server_pk.val(), 'search_lord_name' : lord_name};
				$.post('/admin/gm/api/send_gm_letter_form', post_data, function(data) {
					if (data.result == 'fail') {
						alert(data.msg);
						return false;
					}
					else
					{
						var tbody = $('#searched_lord_list');
						tbody.empty();
						$.each(data.rows, function(k, v){


							var tr = $("<tr id='" + v.id + "' class='ui-widget-content jqgrow ui-row-ltr' style='text-align:center;'></tr>");

							var td1 = $("<td style='width:180px; text-align:center;'>" + v.cell[0] + "</td>");
							var td2 = $("<td style='text-align:center;'>" + v.cell[1] + "</td>");

							tr.append(td1).append(td2);
							tbody.append(tr);

							tr.hover(function(){ $(this).find('td').css('background-color', 'yellow'); }, function(){ $(this).find('td').css('background-color', 'inherit'); });
							tr.click(function(){
								if (!addReceiver(String(v.cell[2]), v.cell[3], v.cell[1]))
								{
									var new_tr = tr.clone().unbind().css('background-color', '');
									$('#gm_letter_receiver_list > tbody').append(new_tr);
									new_tr.hover(function(){ $(this).find('td').css('background-color', 'yellow'); }, function(){ $(this).find('td').css('background-color', 'inherit'); });
									new_tr.click(function(){
										if (confirm('선택한 군주를 수신 목록에서 삭제하시겠습니까?'))
										{
											if (!delReceiver(String(v.cell[2]), v.cell[3]))
											{
												$(this).remove();
											}
										}
									});
									$(this).remove();
								}
							});
						});
					}
				}, 'json');
			},
			"닫기" : function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$(this).dialog("close");
		},
		open: function() {
			$('#target_user_select_one').click().change();
		}
	});

	$('#add_letter_receiver').click(function() {
		$('#lord_list').dialog("open");
	});

	$('#write_letter_form').dialog({
		autoOpen: false,
		width: 520,
		modal: true,
		buttons: {
			"서신 보내기" : function() {
				var subject = $('#letter_subject').val();
				var content = $('#letter_content').val();
				var cause = $('#cause').val();

				if (cause.length < 1)
				{
					alert('GM 서신 발송 사유를 입력하여주십시오.');
					$('#cause').focus();
					return false;
				}

				if (subject.length < 1)
				{
					alert('GM 서신 제목을 입력하여주십시오.');
					$('#letter_subject').focus();
					return false;
				}

				if (content.length < 1)
				{
					alert('GM 서신 내용을 입력하여주십시오.');
					$('#letter_content').focus();
					return false;
				}


				var rthis = this;
				var post_data = {'receiver_list' : receiver_list, 'subject' : subject , 'content' : content, 'cause' : cause};

				$.post('/admin/gm/api/sendGmLetter', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('GM 서신을 발송하였습니다.');
						$('#write_letter_form').dialog("close");
					}
				}, 'json');
			},
			"취소" : function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$(this).dialog("close");
		},
		open: function() {
			var count = 0;
			$.each(receiver_list, function(k, v) { count++; });
			if (count < 1)
			{
				alert('GM 서신을 수신할 대상 유저를 먼저 선택하여 주십시오');
				$(this).dialog("close");
			}
		}
	});

	$('#write_gm_letter').click(function() {
		$('#write_letter_form').dialog("open");
	});

	$('#target_server_all').change(function(){
		$('#target_server_list').attr('disabled', 'disabled');
	});
	$('#target_server_select_one').change(function(){
		$('#target_server_list').removeAttr('disabled');
	});

	$('#target_user_all').change(function() {
		$('#search_lord_name').attr('disabled', 'disabled');
	}).click(function() {
		var target_server_type = $('#target_server_select > input[name=target_server]:checked');
		var target_server_pk = $('#target_server_select > select:first > option:selected');

		if (target_server_type.val() == 'select')
		{
			if (target_server_pk.val().length < 1)
			{
				alert('대상 서버를 선택해주세요.');
				return false;
			}
		}

		var server_pk = (target_server_type.val() == 'all') ? false : target_server_pk.val();

		if (!addReceiver(server_pk))
		{
			var tbody = $('#gm_letter_receiver_list > tbody');
			if (!server_pk)
			{
				// 전체 //
				tbody.empty();
				$.each($('#target_server_select > select:first > option'), function(k, v){
					if (String($(v).val()).length > 0)
					{
						var tr = $("<tr id='" + $(v).val() + '_all' + "' class='ui-widget-content jqgrow ui-row-ltr' style='text-align:center;'></tr>");

						var td1 = $("<td style='width:180px; text-align:center;'>" + $(v).html() + "</td>");
						var td2 = $("<td style='text-align:center;'># 전체 유저 #</td>");
						tr.append(td1).append(td2);
						tbody.append(tr);

						tr.hover(function(){ $(this).find('td').css('background-color', 'yellow'); }, function(){ $(this).find('td').css('background-color', 'inherit'); });
						tr.click(function(){
							if (confirm('선택한 서버의 전체 수신을 취소하시겠습니까?'))
							{
								if (!delReceiver($(v).val()))
								{
									$(this).remove();
								}
							}
						});
					}
				});
			}
			else
			{
				$.each(tbody.find('tr'), function(k, v){
					var info = String($(v).attr('id')).split('_');
					if (info[0] == server_pk)
					{
						$(v).remove();
					}
				});
				var tr = $("<tr id='" + server_pk + '_all' + "' class='ui-widget-content jqgrow ui-row-ltr' style='text-align:center;'></tr>");

				var td1 = $("<td style='width:180px; text-align:center;'>" + target_server_pk.html() + "</td>");
				var td2 = $("<td style='text-align:center;'># 전체 유저 #</td>");
				tr.append(td1).append(td2);
				tbody.append(tr);

				tr.hover(function(){ $(this).find('td').css('background-color', 'yellow'); }, function(){ $(this).find('td').css('background-color', 'inherit'); });
				tr.click(function(){
					if (confirm('선택한 서버의 전체 수신을 취소하시겠습니까?'))
					{
						if (!delReceiver(server_pk))
						{
							$(this).remove();
						}
					}
				});
			}
			$('#lord_list').dialog("close");
		}
	});
	$('#target_user_select_one').change(function(){
		$('#search_lord_name').removeAttr('disabled');
	});
});
