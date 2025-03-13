let noti_pk = 0;

// datetimepicker
// @url https://github.com/xdan/datetimepicker?tab=readme-ov-file
$(document).ready(function() {
	let notice_message = $('#notice_message');
	let target_server = $('input[name=target_server]');
	let notice_start_datetime = $("#notice_start_datetime");
	let notice_end_datetime = $("#notice_end_datetime");
	let repeat_notice = $("#repeat_notice");
	let repeat_notice_time = $("#repeat_notice_time");
	let notice_active = $("#notice_active");
	let modify_pk = 0;

	let dataPickOption = {
		step: 5,
		format:'Y-m-d H:i'
	}

	notice_start_datetime.datetimepicker({
		...dataPickOption,
		minDate: 0,
		onChangeDateTime: function (_current)
		{
			notice_end_datetime.datetimepicker({
				minDate: _current,
			});
			notice_end_datetime.val(notice_start_datetime.val());
		}
	});
	repeat_notice.change(() => {
		if (repeat_notice.is(':checked')) {
			notice_end_datetime.prop('disabled', false);
			repeat_notice_time.prop('disabled', false);
			notice_end_datetime.datetimepicker({
				...dataPickOption,
			});
		} else {
			notice_end_datetime.prop('disabled', true);
			repeat_notice_time.prop('disabled', true).val(1);
		}
		notice_start_datetime.datetimepicker('reset');
		notice_end_datetime.datetimepicker('reset');
	})

	notice_end_datetime.prop('disabled', true);
	repeat_notice_time.prop('disabled', true);
	notice_active.prop('checked', true);

	let add_button = $('#add_notice_message');
	let update_button = $('#update_notice_message');
	let cancel_button = $('#cancel_notice_message');
	update_button.hide();

	add_button.mouseup(function() {
		let target = [];
		target_server.each(function () {
			if ($(this).is(':checked')) {
				target.push($(this).val());
			}
		});

		let start_time = moment(notice_start_datetime.val()).format('X');
		let end_time = moment(notice_end_datetime.val()).format('X');

		if (target.length <= 0) {
			alert('공지를 보낼 서버를 선택하여야 합니다.');
			return;
		}

		if (! notice_message.val() || notice_message.val() === '') {
			alert('공지사항 내용을 입력해주세요.');
			notice_message.focus();
			return;
		}

		let alert_message = '공지를 게시하기전 내용을 다시 한번 확인해주세요.\n공지를 이대로 게시합니까?';
		if (! confirm(alert_message)) {
			return;
		}
		$.post('/admin/gm/api/addChatNotice', {
			target_server: target,
			start_time: start_time,
			end_time: end_time,
			repeat_notice_time: repeat_notice_time.val(),
			repeat_notice: repeat_notice.is(':checked'),
			notice_active: notice_active.is(':checked'),
			notice_message: notice_message.val()
		}, function(data, status) {
			if (data.result === 'ok') {
				getNoticeList();
				clearForm();
			}
		}, 'json');
	});

	function deleteNotice (_pk)
	{
		if (! confirm('해당 공지를 삭제하시겠습니까? 삭제한 공지는 복구 할 수 없습니다.')) {
			return;
		}
		$.post('/admin/gm/api/deleteChatNotice', { pk: _pk }, function(data, status) {
			getNoticeList();
			clearForm();
		}, 'json');
	}

	function modifyNotice (_pk)
	{
		$.post('/admin/gm/api/modifyChatNotice', { pk: _pk }, function(data, status) {
			let row = data.row;
			notice_message.val(row.message);
			for (let pk of Object.values(row.target_server)) {
				$(`input[name=target_server]`).eq(parseInt(pk) - 1).prop('checked', true);
			}
			repeat_notice.prop('checked', String(row['repeat']) === 't');
			repeat_notice_time.val(row.repeat_time);
			notice_active.prop('checked', row.active === 't');
			repeat_notice.trigger('change');
			notice_start_datetime.val(moment(row.start_dt).format('YYYY-MM-DD HH:mm'));
			notice_end_datetime.val(moment(row.end_dt).format('YYYY-MM-DD HH:mm'));
			modify_pk = _pk;

			add_button.hide();
			update_button.show();
		}, 'json');
	}

	function clearForm ()
	{
		notice_message.val('');
		$(`input[name=target_server]`).each(function ()  {
			$(this).prop('checked', false);
		});
		repeat_notice.prop('checked', false);
		repeat_notice_time.val('');
		notice_active.prop('checked', true);
		repeat_notice.trigger('change');
		notice_start_datetime.val('');
		notice_end_datetime.val('');
		modify_pk = 0;
		add_button.show();
		update_button.hide();
	}

	update_button.mouseup(function() {
		let target = [];
		target_server.each(function () {
			if ($(this).is(':checked')) {
				target.push($(this).val());
			}
		});

		let start_time = moment(notice_start_datetime.val()).format('X');
		let end_time = moment(notice_end_datetime.val()).format('X');

		if (target.length <= 0) {
			alert('공지를 보낼 서버를 선택하여야 합니다.');
			return;
		}

		if (! notice_message.val() || notice_message.val() === '') {
			alert('공지사항 내용을 입력해주세요.');
			notice_message.focus();
			return;
		}

		let alert_message = '공지를 수정하기전 내용을 다시 한번 확인해주세요.\n공지를 이대로 수정합니까?';
		if (! confirm(alert_message)) {
			return;
		}
		$.post('/admin/gm/api/updateChatNotice', {
			pk: modify_pk,
			target_server: target,
			start_time: start_time,
			end_time: end_time,
			repeat_notice_time: repeat_notice_time.val(),
			repeat_notice: repeat_notice.is(':checked'),
			notice_active: notice_active.is(':checked'),
			notice_message: notice_message.val()
		}, function(data, status) {
			if (data.result === 'ok') {
				getNoticeList();
				clearForm();
			}
		}, 'json');
	});

	cancel_button.mouseup(function() {
		clearForm();
	});

	function getNoticeList ()
	{

		$.post('/admin/gm/api/chatNoticeList', {}, function(data, status) {
			let tbody = $(`#notice_list`).find('tbody');
			tbody.empty();
			for (let notice of Object.values(data.list)) {
				let tr = $('<tr></tr>');

				tr.append($('<td></td>').text(notice.noti_pk));
				tr.append($('<td></td>').text(notice.message));
				tr.append($('<td></td>').text(notice.target_server));
				tr.append($('<td></td>').html(moment(notice.start_dt).format('YYYY-MM-DD HH:mm') + '<br />~<br />' + moment(notice.end_dt).format('YYYY-MM-DD HH:mm')));
				tr.append($('<td></td>').text(notice.repeat === 't' ? `${notice.repeat_time}분` : '없음'));
				tr.append($('<td></td>').text(notice.active === 't' ? '활성' : '비활성'));
				let next_dt = '종료됨';
				if (notice.used !== 't') {
					next_dt = moment(notice.next_dt).format('YYYY-MM-DD HH:mm');
				}
				tr.append($('<td></td>').text(next_dt));
				tr.append($('<td></td>').text(moment(notice.regist_dt).format('YYYY-MM-DD HH:mm')));

				let _delete = document.createElement('button');
				_delete.innerHTML = '❌';
				_delete.addEventListener('click', () => {
					deleteNotice(notice.noti_pk);
				});
				if (notice.active === 't') {
					$(_delete).prop('disabled', true);
				}
				let _modify = document.createElement('button');
				_modify.innerHTML = '🛠️';
				_modify.addEventListener('click', () => {
					modifyNotice(notice.noti_pk);
				});
				tr.append($('<td></td>').append(_modify).append(_delete));

				tbody.append(tr);
			}
		}, 'json');
	}

	getNoticeList();

	$('#all_target_server').mouseup(function (){
		let check = false;
		target_server.each(function () {
			if (! $(this).is(':checked')) {
				check = true;
				return false;
			}
		});
		if (check) {
			target_server.prop('checked', true);
		} else {
			target_server.prop('checked', false);
		}
	});
});