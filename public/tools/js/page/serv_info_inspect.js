$(document).ready(function(){
	// 서버 목록
	table_to_jqGrid(
		'serv_info_access_allow_ip',
		null,
		'접근이 허용된 ip',
		['허용된 ip', ''],
		[
			{'name' : 'allowed_ip', 'index' : 'allowed_ip', width: 150, 'align' : 'center', 'sortable' : false},
			{'name' : 'dummy', 'index' : 'dummy', width: 150, 'align' : 'center', 'sortable' : false}
	  	],
	  	null, // 전송할 건 없음
	  	function(id){
			// 행을 클릭할 경우
			if (confirm('선택한 ip 주소를 목록에서 삭제하시겠습니까?'))
			{
				var delete_ip = $('#' + id).find('td:first').html();
				var post_data = {'action' : 'del' , 'ip' : delete_ip};

				$.post('/admin/gm/api/manageAllowIp', post_data, (data) => {
					if (data.result == 'ok')
					{
						alert('선택한 허용 ip를 삭제하였습니다.');
						$('#serv_info_access_allow_ip').trigger('reloadGrid');
					} else if (data.result == 'fail') {
						alert(data.msg);
					}
				}, 'json');
			}
		},
		null, // 콜백 없음
		99999
	);

	$( "#add_access_allow_ip_form" ).dialog({
		autoOpen: false,
		height: 160,
		width: 350,
		modal: true,
		buttons: {
			"허용 ip 추가하기": function() {
				var allow_ip = $('#access_allow_ip_input').val();
				if (allow_ip.length < 1)
				{
					alert('접근을 허용할 ip주소를 입력해주십시오.');
					return false;
				}
				else if (!(/^(\d{1,3}||\*{1})\.{1}(\d{1,3}||\*{1})\.{1}(\d{1,3}||\*{1})\.{1}(\d{1,3}||\*{1})$/.test(allow_ip)))
				{
					alert('올바른 ip 주소 형식이 아닙니다.');
					return false;
				}

				var ip_class_spl = allow_ip.split('.');
				for (var i = 0; i < ip_class_spl.length; i++)
				{
					if (ip_class_spl[i] != '*' && (parseInt(ip_class_spl[i], 10) > 255 || parseInt(ip_class_spl[i], 10) < 0))
					{
						alert('올바른 ip 주소 형식이 아닙니다.');
						return false;
					}
				}
				var post_data = {'action' : 'add' , 'ip' : allow_ip};

				$.post('/admin/gm/api/manageAllowIp', post_data, (data) => {
					if (data.result == 'ok')
					{
						alert('점검 중 접근 허용할 ip 주소를 추가하였습니다.');
						$('#serv_info_access_allow_ip').trigger('reloadGrid');
						$(this).dialog("close");
					} else if (data.result == 'fail') {
						alert(data.msg);
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#access_allow_ip_input').val('');
		}
	});

	$('#add_allow_ip').mouseup(function() {
		$( "#add_access_allow_ip_form" ).dialog('open');
	});

	function checkNowInspectState()
	{
		var post_data = {'action' : 'state'};
		$.post('/admin/gm/api/serverInspect', post_data, function(data) {
			if (data.result == 'fail') {
				alert(data.msg);
				return false;
			}
			else
			{
				setNowInspectState(data.inspect);
				$('.current_ip').text(data['current_ip']);
			}
		}, 'json');
	}

	function setNowInspectState (_inspect)
	{
		if (_inspect === 'Y') {
			$('#inspect_on').hide();
			$('#inspect_off').show();
			$('#now_inspect_state').html('점검 중 상태');
		} else if (_inspect === 'N') {
			$('#inspect_on').show();
			$('#inspect_off').hide();
			$('#now_inspect_state').html('점검 중 상태가 아님');
		}
	}

	checkNowInspectState();

	$('#inspect_on').mouseup(function() {
		var post_data = {'action' : 'on'};
		$.post('/admin/gm/api/serverInspect', post_data, function(data) {
			if (data.result == 'fail')
			{
				alert(data.msg);
				return false;
			}
			else
			{
				alert('점검 중 상태로 변경하였습니다. 아직 접속 중인 유저들과의 연결은 끊어지지 않았을 수 있으니 전체 유저킥을 실행하여 주십시오.');
				setNowInspectState(data.inspect);
				$('.current_ip').text(data['current_ip']);
			}
		}, 'json');
	});

	$('#inspect_off').mouseup(function() {
		var post_data = {'action' : 'off'};
		$.post('/admin/gm/api/serverInspect', post_data, function(data) {
			if (data.result == 'fail')
			{
				alert(data.msg);
				return false;
			}
			else
			{
				alert('점검 중 상태를 해제하였습니다.');
				setNowInspectState(data.inspect);
				$('.current_ip').text(data['current_ip']);
			}
		}, 'json');
	});

	$( "#all_user_kick_form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"전체 유저킥 실행": function() {
				var cause = $('#all_user_kick_cause').val();
				if (cause.length < 1)
				{
					alert('사유를 적지 않으면 전체 유저킥을 진행할 수 없습니다.');
					return false;
				}
				var post_data = {'cause' : cause};
				$.post('/admin/gm/api/userKickAll', post_data, (data) => {
					if (data.result == 'ok')
					{
						alert('전체 유저킥을 실행하였습니다.');
						$(this).dialog("close");
					} else if (data.result == 'fail') {
						alert(data.msg);
					}
				}, 'json');
			},
			"취소": function() {
				$(this).dialog("close");
			}
		},
		close: function() {
			$('#block_cause').val('');
		}
	});

	$('#open_all_user_kick_form').mouseup(function() {
		$( "#all_user_kick_form" ).dialog("open");
	});
});