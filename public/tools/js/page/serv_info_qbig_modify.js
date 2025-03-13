$(document).ready(function() {
	$('#give_qbig_form').dialog({
		autoOpen: false,
		height: 270,
		width: 640,
		modal: true,
		buttons: {
			'큐빅 지급하기' : function() {
				var lord_pk = $('#qbig_has_lord_pk').html();
				if (!lord_pk || String(lord_pk).search(/[^\d]+/g) >= 0 || lord_pk.length < 1)
				{
					alert('큐빅을 지급할 군주가 선택되지 않았습니다.');
					return false;
				}

				var cause = $('#give_cause').val();
				if (cause.length < 1)
				{
					alert('큐빅 지급 사유를 입력해주십시오.');
					$('#give_cause').focus();
					return true;
				}

				var amount = $('#give_amount').val();
				if (!amount || amount.length < 1 || amount.search(/[^\d]+/) >= 0)
				{
					alert('지급할 큐빅량은 반드시 숫자로 입력해야합니다.');
					return true;
				}

				var post_data = {'action' : 'incr', 'lord_pk' : lord_pk, 'cause' : cause, 'amount' : amount};

				$.post('/admin/gm/api/modifyQbig', post_data, function(data, status) {
					if (data.result == 'fail')
					{
						alert(data.msg);
						return true;
					} else {
						alert('큐빅이 지급되었습니다.' + "\n" + '지급한 큐빅량 : ' + amount);
						$('#do_search_lord_name').mouseup();
						$('#give_qbig_form').dialog("close");
						return false;
					}
				}, 'json');

			},
			'취소' : function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#give_cause').val('');
			$('#give_amount').val('');
		}
	});

	$('#withdraw_qbig_form').dialog({
		autoOpen: false,
		height: 270,
		width: 640,
		modal: true,
		buttons: {
			'큐빅 회수하기' : function() {
				var lord_pk = $('#qbig_has_lord_pk').html();
				if (!lord_pk || String(lord_pk).search(/[^\d]+/g) >= 0 || lord_pk.length < 1)
				{
					alert('큐빅을 지급할 군주가 선택되지 않았습니다.');
					return false;
				}

				var cause = $('#withdraw_cause').val();
				if (cause.length < 1)
				{
					alert('큐빅 지급 사유를 입력해주십시오.');
					$('#withdraw_cause').focus();
					return true;
				}

				var amount = $('#withdraw_amount').val();
				if (!amount || amount.length < 1 || amount.search(/[^\d]+/) >= 0)
				{
					alert('지급할 큐빅량은 반드시 숫자로 입력해야합니다.');
					return true;
				}

				var post_data = {'action' : 'decr', 'lord_pk' : lord_pk, 'cause' : cause, 'amount' : amount};

				$.post('/admin/gm/api/modifyQbig', post_data, function(data, status) {
					if (data.result == 'fail')
					{
						alert(data.msg);
						return true;
					} else {
						alert('큐빅이 회수되었습니다.' + "\n" + '회수한 큐빅량 : ' + amount);
						$('#do_search_lord_name').mouseup();
						$('#withdraw_qbig_form').dialog("close");
						return false;
					}
				}, 'json');

			},
			'취소' : function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#withdraw_cause').val('');
			$('#withdraw_amount').val('');
		}
	});

	$('#do_search_lord_name').mouseup(function() {
		// 검색 누르면
		// 군주 찾아서 값 채워넣기
		$('#view_info_form').hide();

		var lord_name = $('#lord_name').val();

		$('#qbig_has_lord_pk').html('');
		$('#qbig_has_lord_name').html('');
		$('#qbig_has_qbig').html('');

		if (lord_name.length < 1)
		{
			alert('군주명을 입력해주십시오.');
			return false;
		}

		var post_data = {'view' : gm_info.view_name, 'lord_name' : lord_name};
		$.post('/admin/gm/api/serv_info_qbig_modify', post_data, function(data) {
			if (data.result == 'fail')
			{
				alert(data.msg);
				return false;
			}
			else if (!data.info['lord_pk'] || !data.info['cash'])
			{
				alert('정상적인 데이터를 받지 못하였습니다.');
				return false;
			}
			else
			{
				$('#qbig_has_lord_pk').html(data.info.lord_pk);
				$('#qbig_has_lord_name').html(data.info.lord_name);
				$('#qbig_has_qbig').html(data.info.cash);
				$('#view_info_form').show();
			}
		}, 'json');
	});

	$('#give_qbig').mouseup(function() {
		$('#give_qbig_form').dialog('open');
	});
	$('#withdraw_qbig').mouseup(function() {
		$('#withdraw_qbig_form').dialog('open');
	});
});