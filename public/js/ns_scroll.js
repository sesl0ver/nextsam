class nsScroll {
    constructor(_main, _sub) {
        this._main = _main;
        this._sub = _sub;

        this._id = null;

        this._is_down = false;
        this._is_pause = false;

        this._start = { x: 0, y: 0 };
        this._current = { x: 0, y: 0 };
        this._privious = { x: 0, y: 0 };
        this._transfom = { x: 0, y: 0 };
        this._vel = { x: 0, y: 0 }

        this.init();
    }

    init = () =>
    {
        this.set(0);
        this._main.addEventListener(ns_engine.cfg.mouse_down_event_type, (_e) => {
            if (! ns_engine.trigger.is_touch_device &&  _e.button !== 0) { // 좌클릭인 경우에만 동작
                return;
            }
            this._is_down = true;
            this._start.y = ns_engine.trigger.is_touch_device ? _e.changedTouches[0].pageY : _e.pageY;
            this._privious.y = this.current().y;
            this._transfom.y = this.current().y;
            this.cancel();
        });

        this._main.addEventListener(ns_engine.cfg.mouse_move_event_type, (_e) => {
            this.move(_e);
        });

        this._main.addEventListener("wheel", (_e) => {
            this.cancel();
            const y = this.current().y - (_e.deltaY / 2);
            this.set(y);
        }, {passive: true});

        this._main.addEventListener(ns_engine.cfg.mouse_up_event_type, (_e) => {
            this._is_down = false;
            if (this.current().y === this._privious.y) {
                return;
            }
            this.begin();
        });

        this._main.addEventListener(ns_engine.cfg.mouse_leave_event_type, (_e) => {
            this._is_down = false;
        });
    }

    initScroll ()
    {
        this._vel.y = 0;
        this.cancel();
        this.set(0)
    }

    refreshScroll ()
    {
        this.set(this.current().y);
    }

    initScrollTo (_target = 'top')
    {
        this._vel.y = 0;
        this.cancel();
        this._id = requestAnimationFrame(() => {
            this.initScrollLoop(_target);
        });
    }

    initScrollLoop (_target)
    {
        let _y = this.current().y + this._vel.y;
        let _ly = this._sub.clientHeight * -1 + this._main.clientHeight;
        this.set(_y);
        if ((_target === 'top' && _y >= 0) || (_target === 'bottom' && _y <= _ly)) {
            return;
        }
        this._vel.y = (_target === 'top') ? this._vel.y + 5 : this._vel.y - 5;

        this._id = requestAnimationFrame(() => {
            this.initScrollLoop(_target);
        });
    }

    current ()
    {
        let matrix = new WebKitCSSMatrix(getComputedStyle(this._sub).getPropertyValue("transform"));
        return { x: parseInt(String(matrix.m41)) ?? 0, y: parseInt(String(matrix.m42)) ?? 0 }
    }

    set (_y)
    {
        if (! ns_util.isNumeric(_y)) {
            return;
        }
        _y = parseInt(_y);
        let _ly = this._sub.clientHeight * -1 + this._main.clientHeight - 40;
        _y = (_y >= 0) ? 0 : ((_y <= _ly) ? _ly : _y);
        if (this._main.clientHeight > this._sub.clientHeight) {
            _y = 0;
        }
        this._sub.style.transform = `translateY(${_y}px)`;
    }

    move (_e)
    {
        if (! this._is_down || this._is_pause) {
            return;
        }

        if (_e.target.nodeName === 'INPUT'){
            return;
        }

        _e.stopPropagation();
        _e.preventDefault();

        const y = ns_engine.trigger.is_touch_device ? _e.changedTouches[0].pageY : _e.pageY;
        const w_y = y - this._start.y;
        const p_y = this.current().y;
        this._current.y = this._transfom.y + w_y;
        this.set(this._current.y);
        this._vel.y = this.current().y - p_y;
    }

    begin = () =>
    {
        this.cancel();
        this._id = requestAnimationFrame(this.loop);
    }

    cancel = () =>
    {
        cancelAnimationFrame(this._id);
    }

    loop = () =>
    {
        let _y = this.current().y + this._vel.y;
        this.set(_y);
        let _ly = this._sub.clientHeight * -1 + this._main.clientHeight;
        if (_y > 0) {
            return;
        }
        if (_y < _ly) {
            return;
        }
        this._vel.y *= 0.95;
        if (Math.abs(this._vel.y) <= 0.7){
            return;
        }
        this._id = requestAnimationFrame(this.loop);
    }

    pause ()
    {
        this._is_pause = true;
    }

    resume ()
    {
        this._is_pause = false;
    }
}