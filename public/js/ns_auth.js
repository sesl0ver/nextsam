class nsAuth
{
    constructor ()
    {
        this.only_guest_mode = false;
        this.status = null;

        this.lc = null;
        this.token = null;
        this.anonymous = null;

        this.params = new URLSearchParams(window.location.search);
        this.only_platform_mode = false;
    }

    setAuth (_lc, _token, _uuid)
    {
        window.localStorage.setItem('lc', _lc);
        window.localStorage.setItem('token', _token);
        if (_uuid) {
            window.localStorage.setItem('uuid', _uuid);
        }
    }

    removeAuth ()
    {
        // with Logout
        window.localStorage.removeItem('lc');
        window.localStorage.removeItem('token');
        window.localStorage.removeItem('uuid');
    }

    getUuid ()
    {
        if (this.only_platform_mode) {
            return this.params.get('uuid');
        } else {
            let uuid = window.localStorage.getItem('uuid');
            if (! uuid) {
                uuid = ns_util.generateUuid();
                window.localStorage.setItem('uuid', uuid);
            }
            return uuid;
        }
    }

    getLc ()
    {
        if (this.only_platform_mode) {
            return 2;
        } else {
            return window.localStorage.getItem('lc') ?? '';
        }
    }

    getToken ()
    {
        if (this.only_platform_mode) {
            return this.params.get('token');
        } else {
            return window.localStorage.getItem('token') ?? '';
        }
    }

    getAll ()
    {
        let data = {};
        if (this.only_platform_mode) {
            this.removeAuth();
            data = {
                uuid: null,
                lc: 2,
                token: null,
                // platform: ns_engine.cfg.app_platform,
            }
        } else {
            data = {
                uuid: this.getUuid(),
                lc: this.getLc(),
                token: this.getToken(),
                // platform: ns_engine.cfg.app_platform,
            }
        }
        return data;
    }

    withdraw ()
    {

    }
}
let ns_auth = new nsAuth();