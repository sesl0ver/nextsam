$(() => {
    gm_log.init({
        table_title: ['일시', '웹ID', '군주', '좌표', '구매아이템', '구분', '획득량', '큐빅 가격', '구매 자원', '구매 가격'],
        logType: function (_log_type)
        {
            switch (_log_type)
            {
                case 'gold': return '황금';
                case 'food': return '식량';
                case 'lumber': return '목재';
                case 'horse': return '우마';
                case 'iron': return '철강';
                case 'cashitem': return '큐빅 아이템';
                default: return _log_type;
            }
        },
        convertValue: function (_k, _v)
        {
            switch (_k) {
                case '0': // 로그일시
                    return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
                case '4': // 수행건물
                    return (! _v) ? _v : ns_i18n.t(`item_title_${_v}`);
                case '5':
                    return gm_log.logType(_v);
                case '6':
                    return ns_util.numberFormat(_v);
                case '7':
                    return ns_util.numberFormat(_v);
                case '8':
                    return (! _v) ? _v : ns_i18n.t(`resource_${_v}`);
                case '9':
                    return ns_util.numberFormat(_v);
                default:
                    return _v;
            }
        }
    });
});