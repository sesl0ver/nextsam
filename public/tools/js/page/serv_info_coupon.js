let coupon_pk = 0;

// datetimepicker
// @url https://github.com/xdan/datetimepicker?tab=readme-ov-file
$(document).ready(function() {
	let coupon_item_list = {};

	let item_box = $('#item_box');
	let coupon_use_list = $('#coupon_use_list');

	let coupon_title = $('#coupon_title');
	let coupon_start_datetime = $('#coupon_start_datetime');
	let coupon_end_datetime = $('#coupon_end_datetime');
	let coupon_code = $('#coupon_code');
	let coupon_count = $('#coupon_count');
	let duplicate = $('#duplicate');

	let coupon_item = $('select[name=coupon_item]');
	let item_description = $('#item_description');
	let item_data = $('#item_data tbody');
	let coupon_item_count = $('#coupon_item_count');
	let modify_pk = 0;

	let create_coupon = $('#create_coupon');
	let clear_item_box = $('#clear_item_box');
	let update_coupon = $('#update_coupon');

	let dataPickOption = {
		step: 5,
		format:'Y-m-d H:i'
	}

	coupon_start_datetime.datetimepicker({
		...dataPickOption,
		minDate: 0,
		onChangeDateTime: function (_current)
		{
			coupon_end_datetime.datetimepicker({
				minDate: _current,
			});
			coupon_end_datetime.val(coupon_start_datetime.val());
		}
	});

	coupon_end_datetime.datetimepicker({
		...dataPickOption,
		minDate: 0,
		onChangeDateTime: function (_current)
		{
			coupon_end_datetime.val(coupon_end_datetime.val());
		}
	});

	ns_i18n.init({
		after: () => {
			setTimeout(() => {
				for (let item of Object.values(ns_cs.m.item)) {
					let option = document.createElement('option');
					option.setAttribute('value', item.m_item_pk);
					option.innerHTML = `[${item.m_item_pk}] ` + ns_i18n.t(`item_title_${item.m_item_pk}`);
					coupon_item.append(option);
				}

				coupon_item.change(function () {
					let m_item = ns_cs.m.item[$(this).val()];
					let description_detail = m_item.description_detail;
					if (m_item.use_type === 'package' && m_item.supply_amount !== '') {
						let test = ns_util.convertPackageDescription(m_item.m_item_pk);
						description_detail = description_detail.replace(/\{\{item\}\}/g, test);
					}
					item_description.html(`🎁 ${description_detail}`);
				});
			}, 300);
		}
	});

	$('#add_item').click(() => {
		let _item = coupon_item.val();
		if (coupon_item_list.size >= 10) {
			alert('지급 가능한 아이템은 최대 10개입니다.')
			return;
		}

		if (coupon_item_list.hasOwnProperty(_item)) {
			coupon_item_list[_item] += 1;
		} else {
			coupon_item_list[_item] = parseInt(coupon_item_count.val());
		}
		drawItemBoxList();
	});

	let tbody = $('#coupon_list tbody');
	function drawCouponList ()
	{
		$.post('/admin/gm/api/couponList', {}, (_data, _status) => {
			if (_data.result !== 'ok') {
				alert(_data.msg);
				return;
			}
			tbody.empty();
			for (let row of Object.values(_data.rows)) {
				let tr = $('<tr></tr>');

				tr.append($('<td></td>').text(row.coupon_pk));

				tr.append($('<td></td>').text(row.coupon_title));

				tr.append($('<td></td>').text(row.coupon_code === '' ? '시리얼 전용 쿠폰' : row.coupon_code));

				tr.append($('<td></td>').html(moment(row.start_date).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm') + ' ~ ' + moment(row.end_date).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm')));

				tr.append($('<td></td>').text(`${ns_util.numberFormat(row.coupon_use)} / ${ns_util.numberFormat(row.coupon_count)}`));

				tr.append($('<td></td>').text(ns_util.math(row.coupon_use).div(row.coupon_count).mul(100).toFixed(2) + '%'));

				tr.append($('<td></td>').text(moment(row.create_date).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm')));

				let _modify = $('<button>수정</button>').addClass('manage_button').mouseup(() => {
					modify_pk = row.coupon_pk;
					item_box.show();
					coupon_title.val(row['coupon_title']);
					coupon_code.val(row['coupon_code']).prop('disabled', true);
					coupon_start_datetime.val(moment(row.start_date).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm'));
					coupon_end_datetime.val(moment(row.end_date).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm'));
					duplicate.prop('checked', row['duplicate'] === 't');
					coupon_count.val(row['coupon_count']);
					if (row['coupon_code'] === '') {
						coupon_count.prop('disabled', true); // 시리얼 코드라면 수정 불가
					}
					coupon_item_list = JSON.parse(row.item_data);
					drawItemBoxList();
					create_coupon.hide();
					clear_item_box.hide();
					update_coupon.show();
				});

				let _list = $('<button>다운로드</button>').addClass('manage_button').addClass('manage_button').mouseup(() => {
						if (! confirm(`쿠폰의 발행 정보 및 사용 정보 데이터를 파일로 저장하시겠습니까?`)) {
							return;
						}
						let request = new XMLHttpRequest();
						request.onload = function () {
							if (this.status === 200) {
								let file = new Blob([this.response], {type: this.getResponseHeader('Content-Type')});
								let file_url = URL.createObjectURL(file);
								let a = document.createElement("a");
								if (typeof a.download === 'undefined') {
									window.location = file_url;
								} else {
									a.href = file_url;
									a.download = `${row.coupon_title}.xlsx`;
									document.body.appendChild(a);
									a.click();
								}
							} else {
								alert("Error: " + this.status + "  " + this.statusText);
							}
						}

						request.open('POST', "/admin/gm/api/exportCoupon");
						request.responseType = "blob";
						request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
						request.send("pk=" + row.coupon_pk);
					});

				let _remove = $('<button>삭제</button>').addClass('manage_button').mouseup(() => {
					if (! confirm(`"${row.coupon_title}" 쿠폰을 삭제합니다.\n저장된 쿠폰 데이터도 삭제됩니다.\n삭제한 데이터는 복구 할 수 없습니다.`)) {
						return;
					}

					$.post('/admin/gm/api/removeCoupon', { pk: row.coupon_pk }, (_data, _status) => {
						if (_data.result !== 'ok') {
							alert(_data.msg);
							return;
						}
						drawCouponList();
					}, 'json');
				});

				let manage = $('<td></td>').append(_modify).append(_list).append(_remove);
				tr.append(manage);

				tbody.append(tr);
			}
		}, 'json');
	}

	function drawItemBoxList ()
	{
		item_data.empty();
		for (let [_item_pk, _count] of Object.entries(coupon_item_list)) {
			let tr = document.createElement('tr');

			let td1 = document.createElement('td');
			td1.innerHTML = ns_i18n.t(`item_title_${_item_pk}`);

			let td2 = document.createElement('td');
			td2.innerHTML = String(_count);

			let _remove_button = document.createElement('button');
			_remove_button.innerText = '제거';
			_remove_button.classList.add('manage_button');

			let td3 = document.createElement('td');
			td3.appendChild(_remove_button);

			tr.appendChild(td1);
			tr.appendChild(td2);
			tr.appendChild(td3);

			item_data.append(tr);
		}
	}


	function clearItemBox ()
	{
		coupon_title.val('');
		coupon_start_datetime.val('');
		coupon_end_datetime.val('');
		coupon_code.val('').prop('disabled', false);
		duplicate.prop('checked', false);
		coupon_count.val(100).prop('disabled', false);
		coupon_item_list = {};
		drawItemBoxList();
		modify_pk = 0;
		create_coupon.show();
		clear_item_box.show();
		update_coupon.hide();
	}

	item_box.hide();
	coupon_use_list.hide();
	drawCouponList();

	$('#open_item_box').mouseup(function () {
		clearItemBox();
		item_box.show();
	});

	clear_item_box.mouseup(function () {
		clearItemBox();
	});

	$('#exit_item_box').mouseup(function () {
		clearItemBox();
		item_box.hide();
	});

	create_coupon.mouseup(function () {
		let start_time = moment(coupon_start_datetime.val()).format('X');
		let end_time = moment(coupon_end_datetime.val()).format('X');

		let post_data = {};
		post_data['coupon_title'] = coupon_title.val();
		post_data['start_time'] = start_time;
		post_data['end_time'] = end_time;
		post_data['coupon_code'] = coupon_code.val();
		post_data['duplicate'] = duplicate.is(':checked');
		post_data['coupon_count'] = coupon_count.val();
		post_data['coupon_item_list'] = JSON.stringify(coupon_item_list);

		$.post('/admin/gm/api/createCoupon', post_data, (_data, _status) => {
			if (_data.result !== 'ok') {
				alert(_data.msg);
				return;
			}
			item_box.hide();
			drawCouponList();
		}, 'json');
	});


	update_coupon.mouseup(function () {
		let start_time = moment(coupon_start_datetime.val()).format('X');
		let end_time = moment(coupon_end_datetime.val()).format('X');

		let post_data = {};
		post_data['modify_pk'] = modify_pk;
		post_data['coupon_title'] = coupon_title.val();
		post_data['start_time'] = start_time;
		post_data['end_time'] = end_time;
		post_data['duplicate'] = duplicate.is(':checked');
		post_data['coupon_count'] = coupon_count.val();
		post_data['coupon_item_list'] = JSON.stringify(coupon_item_list);

		$.post('/admin/gm/api/updateCoupon', post_data, (_data, _status) => {
			if (_data.result !== 'ok') {
				alert(_data.msg);
				return;
			}
			alert('쿠폰 정보를 업데이트 하였습니다.');
			item_box.hide();
			drawCouponList();
		}, 'json');
	});
});