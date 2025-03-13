$(() => {
    gm_log.init({
        table_title: ['일시', '웹ID', '군주', '좌표', '구분', '자원 종류', '수량', '거래단가', '거래량', '상세'],
        logType: function (_log_type)
        {
            switch (_log_type)
            {
                case 'bid': return '무역장 구매';
                case 'offer': return '무역장 판매';
                case 'cancel_offer': return '무역장 판매 취소';
                case 'cancel_bid': return '무역장 구매 취소';
                case 'delivery': return '무역장 배송 완료';
                default: return _log_type;
            }
        },
        convertValue: function (_k, _v)
        {
            switch (_k) {
                case '0': // 로그일시
                    return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
                case '4':
                    return gm_log.logType(_v);
                case '5':
                    return ns_i18n.t(`resource_${_v}`);
                case '6':
                    return (! _v) ? 0 : ns_util.numberFormat(_v);
                case '8':
                    return (! _v) ? 0 : ns_util.numberFormat(_v);
                case '9':
                    if (! _v) {
                        return _v;
                    }
                    _v = _v.replaceAll('bid lord_pk', '구매 군주 pk');
                    _v = _v.replaceAll('bid posi', '구매 군주 좌표');
                    _v = _v.replaceAll('gold', '황금 차감');
                    _v = _v.replaceAll('offer lord_pk', '판매 군주 pk');
                    _v = _v.replaceAll('offer posi', '판매 군주 좌표');
                    _v = _v.replaceAll('deli_pk', '배송 pk');
                    _v = _v.replaceAll('deal_amount', '거래 자원량');
                    _v = _v.replaceAll('total_price', '거래된 황금');
                    _v = _v.replaceAll(';', '<br />');
                    return _v;
                default:
                    return _v;
            }
        }
    });
});