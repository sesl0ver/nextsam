class nsI18n
{
    static default_lang = 'ko'; // TODO 디폴트 차후 국가에 따라 바꿔서 사용할 수 있게 해야함.
    static _resources;
    constructor ()
    {
        this.lang = this.getLang();
        this.data = ['ko', 'en', 'jp']; // ko: 한국어, en: 영어, jp: 일본어
        self._resources = null;
    }

    init (options = {})
    {
        // 따로 옵션을 설정하지 않은 경우에
        if (! options?.lang) {
            options.lang = this.getLang();
        }

        // 기본 설정
        this.setLang(options.lang);

        // 게임 시작전 사전에 로딩이 필요한 처리
        if (options?.after && typeof options.after === 'function') {
            options.after();
        }
    }

    setResource (_resource)
    {
        self._resources = JSON.parse(decodeURIComponent(_resource));
    }

    setLang (lang = 'ko')
    {
        this.lang = lang;
        window.localStorage.setItem('language', lang);
        return this.lang;
    }

    getLang ()
    {
        let lang = window.localStorage.getItem('language');
        if (! lang) {
            lang = this.setLang(self.default_lang);
        }
        return lang;
    }

    t (code, _replace = [])
    {
        let _text = self._resources?.[this.getLang()]?.[code] ?? `__(${this.getLang()})${code}`;
        for (let _i in _replace) {
            let i = ns_util.math(_i).plus(1).number;
            _text = _text.replaceAll(`{{${i}}}`, _replace[_i]);
        }
        return _text;
    }
}

let ns_i18n = new nsI18n();