class nsUtil {
    static math (n)
    {
        try {
            let ns_math = new nsMath();
            return ns_math.set(n);
        } catch (e) {
            console.error(e, n);
        }
    }

    static isNaN (n)
    {
        return Number.isNaN(n);
    }

    static isInteger (n)
    {
        return Number.isInteger(n);
    }

    static isNumeric (n)
    {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }

    static isArray (a)
    {
        let ini;
        const _getFuncName = function (fn) {
            let name = (/\W*function\s+([\w\$]+)\s*\(/).exec(fn);
            if (!name) {
                return '(Anonymous)';
            }
            return name[1];
        }

        const _isArray = function (a) {
            if (!a || typeof a !== 'object' || typeof a.length !== 'number') {
                return false;
            }
            let len = a.length;
            a[a.length] = 'bogus';
            if (len !== a.length) {
                a.length -= 1;
                return true;
            }
            delete a[a.length];
            return false;
        }

        if (!a || typeof a !== 'object') {
            return false;
        }

        this.php_js = this.php_js || {};
        this.php_js.ini = this.php_js.ini || {};

        ini = this.php_js.ini['phpjs.objectsAsArrays'];

        return _isArray(a) || ((!ini || ((parseInt(ini.local_value, 10) !== 0 && (!ini.local_value.toLowerCase || ini.local_value.toLowerCase() !== 'off')))) && (Object.prototype.toString.call(a) === '[object Object]' && _getFuncName(a.constructor) === 'Object'));
    }

    static toNumber (n)
    {
        return this.math(n).number;
    }

    static toInteger (n)
    {
        return this.math(n).integer;
    }

    static toFloat (n)
    {
        return parseFloat(n);
    }

    static numberFormat (n = 0)
    {
        return new Intl.NumberFormat().format(n);
    }

    static loadScript (url)
    {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            document.body.appendChild(script);
            script.onload = resolve;
            script.onerror = reject;
            script.async = true;
            script.src = url;
        })
    }

    static getCostsTime (_time, _end_string = 'timer_progress') // 처리중...
    {
        let z = '';
        if (_time <= 0) {
            z = ns_i18n.t(_end_string);
        } else 	if (_time <= 60) {
            z = ns_i18n.t('timer_seconds', [_time]);
        } else if (_time <= 3600) {
            z = ns_i18n.t('timer_minutes', [this.math(_time).div(60).integer, this.math(_time).mod(60).integer]);
        } else if (_time <= 86400) {
            z = ns_i18n.t('timer_hours', [this.math(_time).div(3600).integer, this.math(_time).mod(3600).div(60).integer]);
        } else {
            z = ns_i18n.t('timer_days', [this.math(_time).div(86400).integer, this.math(_time).mod(86400).div(3600).integer]);
        }
        return z;
    }

    static getNeedQbig (_remain_time)
    {
        // let hour_qbig = this.math(_remain_time).div(3600).integer; // 시간당 큐빅 : 60
        let minutes = this.math(_remain_time).mod(3600).integer; // 1분당 큐빅 : 1
        let minute_qbig = this.math(_remain_time).div(60).integer;
        let seconds = this.math(minutes).mod(60).integer;
        if (this.math(seconds).gt(0)) {
            minute_qbig++;
        }
        return Number(minute_qbig);
    }

    static numberSymbol (_number)
    {
        let number_string = _number.toString();
        let str_length = _number.toString().length;
        if (str_length < 4) {
            return _number;
        }
        let symbol_size = ['K', 'M', 'G', 'Y', 'P', 'E', 'Z'];

        let unit = 7;
        for (let i = 0; i < symbol_size.length; i++) {
            let b = 0;
            unit += i * 3;
            if (i === 0) {
                if (str_length < unit) {
                    if (str_length === unit - (unit - 4)) {
                        b = 2;
                        number_string = number_string.substring(0, b);
                        number_string = number_string.substring(0, number_string.length - 1) + "." + number_string.substring(number_string.length - 1, number_string.length);
                    } else {
                        b = (i + 1) * 3;
                        number_string = number_string.substring(0, number_string.length - b);
                    }
                    number_string += symbol_size[i];
                    break;
                }
            } else {
                if (str_length < unit) {
                    if (str_length === unit - 3) {
                        b = 2;
                        number_string = number_string.substring(0, b);
                        number_string = number_string.substring(0, number_string.length - 1) + "." + number_string.substring(number_string.length - 1, number_string.length);
                    } else {
                        b = (i + 1) * 3;
                        number_string = number_string.substring(0, number_string.length - b);
                    }
                    number_string += symbol_size[i];
                    break;
                }
            }
        }
        return number_string;
    }

    static getLevelStr(_level)
    {
        return ns_i18n.t('level_word', [_level]);
    }

    static isTouchDevice ()
    {
        // TODO 모바일 디바인스인지 확인하여 true
        return false;
    }

    static generateUuid ()
    {
        return (typeof crypto.randomUUID === 'function') ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => (c === 'x' ? (Math.random() * 16 | 0) : (Math.random() * 16 | 0) & 0x3 | 0x8).toString(16));
    }

    static arraySort (_array, _type, _key)
    {
        if (_type === -1) {
            _array.sort((a, b) => (ns_util.math(a[_key]).gt(b[_key])) ? 1 : (ns_util.math(a[_key]).lt(b[_key])) ? -1 : 0 );
        } else {
            _array.sort((a, b) => (ns_util.math(b[_key]).gt(a[_key])) ? 1 : (ns_util.math(b[_key]).lt(a[_key])) ? -1 : 0 );
        }
        return _array;
    }

    static updateCurrentResource (_ns_object)
    {
        for (let _type of ['gold', 'food', 'horse', 'lumber', 'iron']) {
            let _obj = _ns_object.find(`.ns_resource_${_type}_curr`);
            if (_obj.element) {
                _obj.text(ns_util.numberFormat(parseInt(ns_cs.getResourceInfo(`${_type}_curr`), 10)));
            }
        }
    }

    static forbiddenWordCheck(return_str = '')
    {
        if (! return_str) {
            return '';
        }

        for (let d of Object.values(ns_cs.m.forb)) {
            let flag = '*'.repeat(d.word.length);
            let regexp = new RegExp(d.word, 'gim');
            return_str = return_str.replace(regexp, flag);
        }

        return return_str;
    }

    static shuffle(array) {
        for (let index = array.length - 1; index > 0; index--) {
            const randomPosition = Math.floor(Math.random() * (index + 1));
            const temporary = array[index];
            array[index] = array[randomPosition];
            array[randomPosition] = temporary;
        }
        return array;
    }

    static positionLink (_content)
    {
        let regexp = new RegExp(/(\d{1,3})x(\d{1,3})/gm);
        _content = _content.replaceAll(regexp, '<a onclick="ns_util.worldLink(\'$1\', \'$2\')" class="text_position_link">$1x$2</a>');
        let wrap = document.createElement('p');
        wrap.innerHTML = _content;
        return wrap.outerHTML;
    }

    static worldLink (_x, _y)
    {
        try {
            ns_dialog.closeAll();
            if (ns_engine.game_data.curr_view !== 'world') {
                ns_engine.toggleWorld();
            }
            ns_world.setPosition(_x, _y);
        } catch (e) {
            console.error(e);
        }
    }

    static getTimestamp (_date)
    {
        return moment(_date).format('X');
    }

    static getDateFormat (_seconds = 0, _format = 'YYYYMMDD', _utc = false)
    {
        if (! _utc) {
            return moment(new Date(_seconds * 1000)).format(_format);
        } else {
            return moment(new Date(_seconds * 1000)).utc().format(_format);
        }
    }

    static getDateFormatMs (_milliseconds = 0, _format = 'YYYYMMDD', _utc = false)
    {
        if (! _utc) {
            return moment(new Date(_milliseconds)).format(_format);
        } else {
            return moment(new Date(_milliseconds)).utc().format(_format);
        }

    }

    static setCookie (_name, _value = null)
    {
        let _date = this.getDateFormatMs(ns_timer.nowMs());
        window.localStorage.setItem(_name, JSON.stringify({ value: _value, expires: _date }));
    }

    static getCookie (_name)
    {
        let _value = window.localStorage.getItem(_name);
        return (_value) ? JSON.parse(_value) : null;
    }

    static hasCookie (_name)
    {
        return !!window.localStorage.getItem(_name);
    }

    static removeCookie (_name)
    {
        window.localStorage.removeItem(_name);
    }

    static checkExpireCookie (_name)
    {
        // 쿠키가 없다면 만료했다고 판단
        if (! this.hasCookie(_name)) {
            return true;
        }
        let _cookie = this.getCookie(_name);
        // 날짜에 의한 쿠키 판단. 일단은 하루단위...
        return ns_util.math(_cookie.expires).lt(this.getDateFormatMs(ns_timer.nowMs()));
    }

    static convertPackageDescription (_item_pk)
    {
        let m = ns_cs.m.item[_item_pk];
        if (m.use_type !== 'package' || m.supply_amount === '') {
            return '';
        }
        return m.supply_amount.split(',').map(o => {
            let i = o.split(':');
            return { pk: i[0], cnt: i[1] }
        }).map(i => `${ns_cs.m.item[i.pk].title} ${i.cnt}개`).join(', ') ?? '';
    }

    static secondToDateTime (_seconds)
    {
        let hours = Math.floor(_seconds / 3600);
        let minutes = Math.floor((_seconds % 3600) / 60);
        let seconds = _seconds % 60;
        hours = (hours < 10) ? `0${hours}` : hours;
        minutes = (minutes < 10) ? `0${minutes}` : minutes;
        seconds = (seconds < 10) ? `0${seconds}` : seconds;
        return `${hours}:${minutes}:${seconds}`;
    }
}
let ns_util = nsUtil;