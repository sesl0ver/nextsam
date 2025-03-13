class gmLog
{
	constructor ()
	{
		this.data_pickker_options = {
			step: 5,
			format:'Y-m-d H:i'
		};
		this.log_data = [];
		this.list_offset = 1;
	}

	init (_options = {})
	{
		this.list_offset = 1;
		this.table_title = _options?.table_title ?? [];
		this.logType = _options?.logType ?? function () {};
		this.convertValue = _options?.convertValue ?? function () {};
		this.search_time_start = new nsObject('#search_time_start');
		this.search_time_end = new nsObject('#search_time_end');
		this.search_time_start.value(moment().add(-1, 'days').format('YYYY-MM-DD HH:mm'));
		this.search_time_end.value(moment().add(1, 'hours').format('YYYY-MM-DD HH:mm'));
		this.lord_name = new nsObject('#lord_name');
		$(this.search_time_start.element).datetimepicker(this.data_pickker_options);
		$(this.search_time_end.element).datetimepicker(this.data_pickker_options);
		this.search_log = new nsObject('#search_log');
		this.search_log_add = new nsObject('#search_log_add');
		this.search_log_add.element.setAttribute('disabled', '');
		this.offset_x = new nsObject('#offset_x');
		this.offset_y = new nsObject('#offset_y');
		this.search_result = new nsObject('#search_result > table');
		this.search_types = new nsObject('.log_checkbox').findAll('input[type=checkbox]');

		this.search_log.setEvent('mouseup', () => {
			gm_log.list_offset = 1;
			// 각 로그마다 테이블 데이터가 다를 것이므로 따로 처리
			gm_log.requestLogAPI({}, () => {
				gm_log.search_result.find('tbody').empty();
				this.drawTableBody(gm_log.log_data);
			});
		});

		this.search_log_add.setEvent('mouseup', () => {
			// 각 로그마다 테이블 데이터가 다를 것이므로 따로 처리
			gm_log.requestLogAPI({}, () => {
				this.drawTableBody(gm_log.log_data);
			});
		});
	}

	drawTableBody (_log_data)
	{
		for (let _row of Object.values(_log_data)) {
			let btr = new nsObject(document.createElement('tr'));

			for (let [k, v] of Object.entries(_row)) {
				let td1 = new nsObject(document.createElement('td'));
				let o = this.convertValue(k, v);
				if (typeof o === 'object' && o !== null) {
					td1.append(o);
				} else {
					td1.html(o);
				}
				btr.append(td1);
			}

			gm_log.search_result.find('tbody').append(btr);
		}
	}

	getParams (_params = {}) // 필수 파라미터
	{
		let params = new URLSearchParams();
		params.append('search_start', moment(this.search_time_start.value()).format('X'));
		params.append('search_end', moment(this.search_time_end.value()).format('X'));
		params.append('target_server_pk', gm_info.selected_server_pk);
		params.append('lord_name', this.lord_name.value());
		if (this.offset_x.value() && this.offset_y.value()) {
			params.append('offset', this.offset_x.value() + 'x' + this.offset_y.value());
		}
		params.append('list_offset', this.list_offset); // 임시값. 차후 페이징때 바꾸기
		for (let [_key, _value] of Object.entries(_params)) {
			params.append(_key, String(_value));
		}
		if (this.search_types.length > 0) {
			let search_type = [];
			for (let o of this.search_types) {
				if (o.isChecked()) {
					search_type.push(o.value());
				}
			}
			if (search_type.length > 0) {
				params.append('search_type', search_type.join(','));
			}
		}
		return params;
	}

	returnCheck (_data)
	{
		ns_xhr.xhrProgress(false);
		let _return = _data['ns_xhr_return'];
		switch (_return.code) {
			case 'error':
				alert(_return.message);
				return false;
			default:
				return true;
		}
	}

	requestLogAPI (_params, _drawTable)
	{
		ns_xhr.post(`/admin/gm/api/${gm_info['view_name']}`, this.getParams(_params), (_data, _status) => {
			if (! this.returnCheck(_data)) {
				return;
			}
			_data = _data['ns_xhr_return']['add_data'];
			this.log_data = _data;
			this.list_offset++;
			this.drawTable(_drawTable);
			this.search_log_add.element.removeAttribute('disabled');
		});
	}

	drawTable (_drawTable)
	{
		let htr = new nsObject(document.createElement('tr'));
		for (let _title of this.table_title) {
			let th = new nsObject(document.createElement('th'));
			th.text(_title);
			htr.append(th);
		}
		this.search_result.find('thead').empty().append(htr);

		if (typeof _drawTable === "function") {
			_drawTable();
		}
	}

	convertValue (_k, _v)
	{
		return _v;
	}

	logType(_log_type)
	{
		return _log_type;
	}

	convertPositionName (_value)
	{
		let [_posi_pk, _name, _level] = _value.split(':');
		if (ns_i18n.t(_name).substring(0, 2) !== '__') {
			_name = ns_i18n.t(_name);
		}
		return `Lv.${_level} ${_name} (${_posi_pk})`;
	}
}
let gm_log = new gmLog();