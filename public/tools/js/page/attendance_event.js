$(document).ready(function(){
	$.post('/admin/gm/api/attendance_event', {}, function(data) {
		if (data.result !== 'ok') {
			alert(data.msg);
			return;
		}
		$('span.attendance_cnt').text(data['attendance_info']['cnt']);
		$('span.attendance_dt').text(data['attendance_info']['dt']);
	}, 'json');
});