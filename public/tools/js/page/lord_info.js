$(document).ready(function(){
	const lord_name_input = $('#lord_name');
	$("#change_lord_name_form").dialog({
		autoOpen: false,
		height: 240,
		width: 320,
		modal: true,
		buttons: {
			"군주명 변경하기": function() {
				var lord_name = $('#lord_name').val();
				var change_cause = $('#change_lord_name_cause').val();

				if (lord_name.length < 1)
				{
					alert('변경할 군주명을 입력해 주십시오.');
					$('#lord_name').focus();
					return false;
				} else if (lord_name.length < 2) {
					alert('군주명은 최소 2글자를 사용해야합니다.');
					$('#lord_name').focus();
					return false;
				} else if (lord_name.length > 6) {
					alert('군주명은 최대 6글자까지 사용할 수 있습니다.');
					$('#lord_name').focus();
					return false;
				}
				if (String(lord_name).search(/[^\uAC00-\uD7A3a-zA-Z0-9]/g) >= 0)
				{
					alert("변경할 군주명 중에 사용할 수 없는 글자가 있습니다. 군주명은 한글(초성체 제외), 영문자, 숫자만이 가능합니다.");
					$('#lord_name').focus();
					return false;
				}
				if (change_cause.length < 1)
				{
					alert('변경 사유를 입력해 주십시오.');
					$('#change_lord_name_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'action' : 'lord_name', 'lord_pk' : gm_info.selected_lord_pk, 'lord_name' : lord_name, 'change_cause' : change_cause};
				$.post('./do/do_change_name.php', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('군주명을 변경하였습니다.');
						$('#now_lord_name').html(lord_name);
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#lord_name').val('');
			$('#change_lord_name_cause').val('');
		}
	});

	$('#change_lord_name').click(function(){ $("#change_lord_name_form").dialog('open'); });

	$("#change_member_id_form").dialog({
		autoOpen: false,
		height: 240,
		width: 320,
		modal: true,
		buttons: {
			"이메일 변경하기": function() {
				var member_id = $('#member_id').val();
				var change_cause = $('#change_member_id_cause').val();

				if (member_id.length < 1)
				{
					alert('변경할 이메일을 입력해 주십시오.');
					$('#lord_name').focus();
					return false;
				}

				if (change_cause.length < 1)
				{
					alert('변경 사유를 입력해 주십시오.');
					$('#change_lord_name_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'action' : 'member_id', 'lord_pk' : gm_info.selected_lord_pk, 'member_id' : member_id, 'change_cause' : change_cause};
				$.post('./do/do_change_name.php', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('이메일을 변경하였습니다.');
						$('#now_member_id').html(member_id);
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#lord_name').val('');
			$('#change_lord_name_cause').val('');
		}
	});

	$('#change_member_id').click(function(){ $("#change_member_id_form").dialog('open'); });

	$("#change_lord_own_trade_gold_form").dialog({
		autoOpen: false,
		height: 240,
		width: 320,
		modal: true,
		buttons: {
			"보유황금 수정": function() {
				var trade_gold = $('#trade_gold').val();
				var change_cause = $('#change_lord_own_trade_gold_cause').val();


				if (isNaN(trade_gold) || !/^\d+$/.test(trade_gold))
				{
					alert('수량은 숫자형태로 입력하여주십시오.');
					$('#trade_gold').focus();
					return false;
				}
				if (change_cause.length < 1)
				{
					alert('변경 사유를 입력해 주십시오.');
					$('#change_lord_name_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'action' : 'trade_gold', 'lord_pk' : gm_info.selected_lord_pk, 'trade_gold' : trade_gold, 'change_cause' : change_cause};
				$.post('./do/do_change_trade_gold.php', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('보유황금을 변경하였습니다.');
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#trade_gold').val('');
			$('#change_lord_own_trade_gold_cause').val('');
		}
	});

	$("#change_lord_own_point_coin_form").dialog({
		autoOpen: false,
		height: 240,
		width: 320,
		modal: true,
		buttons: {
			"요충지코인 수정": function() {
				var point_coin = $('#point_coin').val();
				var change_cause = $('#change_lord_own_point_coin_cause').val();


				if (isNaN(point_coin) || !/^\d+$/.test(point_coin))
				{
					alert('수량은 숫자형태로 입력하여주십시오.');
					$('#point_coin').focus();
					return false;
				}
				if (change_cause.length < 1)
				{
					alert('변경 사유를 입력해 주십시오.');
					$('#change_lord_own_point_coin_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'action' : 'point_coin', 'lord_pk' : gm_info.selected_lord_pk, 'point_coin' : point_coin, 'change_cause' : change_cause};
				$.post('./do/do_change_point_coin.php', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('요충지코인을 변경하였습니다.');
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#point_coin').val('');
			$('#change_lord_own_point_coin_cause').val('');
		}
	});

	$('#change_lord_own_trade_gold').click(function(){ $("#change_lord_own_trade_gold_form").dialog('open'); });

	$('#change_lord_own_point_coin').click(function(){ $("#change_lord_own_point_coin_form").dialog('open'); });

	$("#change_flag_name_form").dialog({
		autoOpen: false,
		height: 240,
		width: 320,
		modal: true,
		buttons: {
			"깃발명 변경하기": function() {
				var flag_name = $('#flag_name').val();
				var change_cause = $('#change_flag_name_cause').val();

				if (flag_name.length < 1)
				{
					alert('변경할 깃발명을 입력해 주십시오.');
					$('#flag_name').focus();
					return false;
				} else if (flag_name.length > 4) {
					alert('깃발명은 최대 4글자까지 사용할 수 있습니다.');
					$('#flag_name').focus();
					return false;
				}
				if (String(flag_name).search(/[^\uAC00-\uD7A3a-zA-Z0-9]/g) >= 0)
				{
					alert("변경할 깃발명 중에 사용할 수 없는 글자가 있습니다. 깃발명은 한글(초성체 제외), 영문자, 숫자만이 가능합니다.");
					$('#flag_name').focus();
					return false;
				}
				if (change_cause.length < 1)
				{
					alert('변경 사유를 입력해 주십시오.');
					$('#change_flag_name_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'action' : 'flag_name', 'lord_pk' : gm_info.selected_lord_pk, 'flag_name' : flag_name, 'change_cause' : change_cause};
				$.post('./do/do_change_name.php', post_data, function(data){
					if (data.result == 'fail')
					{
						alert(data.msg);
						return false;
					} else {
						alert('깃발명을 변경하였습니다.');
						$('#now_flag_name').html(flag_name);
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#flag_name').val('');
			$('#change_flag_name_cause').val('');
		}
	});

	$('#change_flag_name').click(function(){ $("#change_flag_name_form").dialog('open'); });

	$("#change_lord_intro_form").dialog({
		autoOpen: false,
		height: 360,
		width: 580,
		modal: true,
		buttons: {
			"군주 인사말 변경하기": function() {
				var lord_intro = $('#lord_intro').val();
				var change_cause = $('#change_lord_intro_cause').val();

				if (lord_intro.length > 200) {
					alert('군주 인사말은 최대 200글자까지 사용할 수 있습니다.');
					$('#lord_intro').focus();
					return false;
				}

				if (change_cause.length < 1)
				{
					alert('변경 사유를 입력해 주십시오.');
					$('#change_lord_intro_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'server_pk' : $('#target_server_pk').val(), 'lord_pk' : gm_info.selected_lord_pk, 'lord_intro' : lord_intro, 'change_cause' : change_cause};
				$.post('/admin/gm/api/changeLordIntro', post_data, function(data){
					if (! data.result) {
						alert(data.msg);
						return false;
					} else {
						alert('군주 인사말을 변경하였습니다.');
						$('#lord_intro_pre').html(lord_intro);
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		open: function() {
			$('#lord_intro').val($('#lord_intro_pre').html());
		},
		close: function() {
			$('#change_lord_intro_cause').val('');
		}
	});

	$('#change_lord_intro').click(function(){ $("#change_lord_intro_form").dialog('open'); });

	$("#change_alli_intro_form").dialog({
		autoOpen: false,
		height: 360,
		width: 580,
		modal: true,
		buttons: {
			"동맹 인사말 변경하기": function() {
				var alli_intro = $('#alli_intro').val();
				var change_cause = $('#change_alli_intro_cause').val();

				if (alli_intro.length > 200) {
					alert('동맹 인사말은 최대 200글자까지 사용할 수 있습니다.');
					$('#alli_intro').focus();
					return false;
				}

				if (change_cause.length < 1)
				{
					alert('변경 사유를 입력해 주십시오.');
					$('#change_alli_intro_cause').focus();
					return false;
				}

				var rthis = this;
				var post_data = {'server_pk' : $('#target_server_pk').val(), 'lord_pk' : gm_info.selected_lord_pk, 'alli_intro' : alli_intro, 'change_cause' : change_cause};
				$.post('/admin/gm/api/changeAllyIntro', post_data, function(data){
					if (! data.result) {
						alert(data.msg);
						return false;
					} else {
						alert('동맹 인사말을 변경하였습니다.');
						$('#alli_intro_pre').html(alli_intro);
						$(rthis).dialog("close");
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		open: function() {
			$('#alli_intro').val($('#alli_intro_pre').html());
		},
		close: function() {
			$('#change_alli_intro_cause').val('');
		}
	});

	$('#change_alli_intro').click(function(){ $("#change_alli_intro_form").dialog('open'); });
});