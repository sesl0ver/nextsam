$(() => {
    gm_log.init({
        table_title: ['등록시간', '동시접속자'],
        convertValue: function (_k, _v)
        {
            switch (_k) {
                case '0':
                    return moment(_v).tz('Asia/Seoul').format('YYYY-MM-DD HH:mm');
                default:
                    return _v;
            }
        }
    });
});