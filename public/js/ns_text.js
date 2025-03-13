class nsText {
    constructor() {
    }

    // description 코드를 배열로 _code = 아이템:상태:명령:좌표:목적지:레벨
    convertCode (_code)
    {
        let [_m_item_pk, _status, _cmd_type, _posi_pk, _position_name, _level] = _code.split(':');
        return {
            m_item_pk: _m_item_pk,
            status: _status,
            cmd_type: _cmd_type,
            posi_pk: _posi_pk,
            position_name: _position_name,
            level: _level,
        }
    }

    // _code = 아이템:상태:명령:좌표:목적지:레벨
    convertTroopDescription (_code)
    {
        let [_m_item_pk, _status, _cmd_type, _posi_pk, _position_name, _level] = _code.split(':');
        _position_name = this.convertTypeToTitle(_position_name);
        _status = this.convertStatusToText(_status);
        _cmd_type = this.convertCmdToText(_cmd_type);
        let description = `(${_status} - ${_cmd_type}) Lv.${_level} ${_position_name} (${_posi_pk})`;
        if (_m_item_pk !== '0') {
            // 이전에는 아이템을 사용한 경우가 있었으나... 황제의 조서 뿐이라 좀 처리가 난감함. 만약을 위해 남겨둠.
            description += ` (${ns_cs.m.item[_m_item_pk].title})`;
        }
        return description;
    }

    // 목적지 명칭을 Title String 으로 변환
    convertTypeToTitle (_position_name)
    {
        return code_set.position_name?.[_position_name] ?? _position_name;
    }

    // 상태를 텍스트화
    convertStatusToText (_status)
    {
        return code_set.troop_status_text?.[_status] ?? _status;
    }

    // 명령을 텍스트화
    convertCmdToText (_cmd_type)
    {
        return code_set.troop_cmd_type_text?.[_cmd_type] ?? _cmd_type;
    }

    // 목적지 이름을 텍스트화
    convertPositionName (_position_name, _with_br = false, _with_posi_pk = true, _with_level = true)
    {
        let [_posi_pk, _name, _level] = _position_name.split(':');
        let title = this.convertTypeToTitle(_name);
        if (_with_level) {
            if (_with_br) {
                title = '<br />' + title;
            }
            title = `Lv.${_level} ` + title;
        }
        if (_with_posi_pk) {
            if (_with_br) {
                title = title + '<br />';
            }
            title = title + ` (${_posi_pk})`;
        }
        return title;
    }

    convertReportSummary (_report_type, _data)
    {
        let __data = (typeof _data === 'string') ? _data.split(':') : _data;
        switch (_report_type) {
            case 'scout_success':
                return [this.convertPositionName(_data), __data[3]];
            case 'enemy_march':
                return [this.convertPositionName(_data)];
            case 'hero_skill_slot_expand':
                return [_data];
            case 'return_finish_1':
            case 'return_finish_2':
            case 'return_finish_3':
            case 'return_finish_4':
            case 'return_finish_5':
            case 'return_finish_6':
            case 'return_finish_7':
            case 'return_finish_8':
            case 'hero_bid_success':
            case 'hero_bid_fail':
                return __data;
            default:
                return _data;
        }
    }

    convertReportTitle (_summary, _data)
    {
        if (! Array.isArray(_data)) {
            return _summary;
        }
        for (let _i in _data) {
            _summary = _summary.replaceAll(`{{${ns_util.math(_i).plus(1).number}}}`, _data[_i]);
        }
        return _summary;
    }
}
let ns_text = new nsText();