const nsObject = function (_selector, _parent = null) {
    try {
        this.element = null;
        if (typeof _selector === 'string') {
            _parent = (! _parent) ? document.querySelector('#main_stage') : (_parent instanceof nsObject) ? _parent.element : _parent;
            this.element =  _parent.querySelector(_selector);
        } else if (typeof _selector === 'object') {
            this.element = _selector;
        }
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.show = function ()
{
    try {
        if (this.element.classList.contains('hide')) {
            this.element.classList.remove('hide');
        }
        if (! this.element.classList.contains('show')) {
            this.element.classList.add('show');
        }
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.hide = function ()
{
    try {
        if (this.element.classList.contains('show')) {
            this.element.classList.remove('show');
        }
        if (! this.element.classList.contains('hide')) {
            this.element.classList.add('hide');
        }
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.toggle = function ()
{
    try {
        if (! this.element.classList.contains('hide')) {
            this.hide();
        } else {
            this.show();
        }
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.find = function (_selector, _input = false)
{
    try {
        return new nsObject(_selector, this.element);
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.findAll = function (_selector, _input = false)
{
    try {
        let list = Array.from(this.element.querySelectorAll(_selector));
        if (list.length < 1) {
            return [];
        }
        return list.map(o => new nsObject(o));
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.count = function (_selector)
{
    try {
        return this.element.querySelectorAll(_selector).length;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.empty = function ()
{
    try {
        this.element.innerText = '';
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.text = function (_text = null, _append = false)
{
    try {
        _text = (typeof _text === 'number') ? String(_text) : _text;
        if (! _text && _text !== '') {
            return this.element.innerText;
        } else {
            if (_append) {
                this.element.innerText += _text;
            } else {
                this.element.innerText = _text;
            }
            return this;
        }
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.html = function (_html, _append = false)
{
    try {
        if (! _html && _html !== '') {
            return this.element.innerHTML;
        } else {
            if (_append) {
                this.element.innerHTML += _html;
            } else {
                this.element.innerHTML = _html;
            }
            return this;
        }
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.append = function (_element)
{
    try {
        _element = (_element instanceof nsObject) ? _element.element : _element;
        this.element.appendChild(_element);
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.empty = function ()
{
    try {
        this.element.replaceChildren();
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.setEvent = function (_event_type, _callback)
{
    try {
        if (Array.isArray(_event_type)) {
            for (let _type of _event_type) {
                this.element.addEventListener(_type, _callback);
            }
        } else {
            this.element.addEventListener(_event_type, _callback);
        }
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.removeEvent = function (_event_type)
{
    try {
        if (Array.isArray(_event_type)) {
            for (let _type of _event_type) {
                removeEventListener(_type, this.element);
            }
        } else {
            removeEventListener(_event_type, this.element);
        }
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.setBlink = function ()
{
    try {
        this.addCss('blink');
        this.setEvent("animationend", (event) => {
            this.removeCss('blink');
            this.removeEvent("animationend");
        });
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.addCss = function (_css)
{
    try {
        if (Array.isArray(_css)) {
            for (let _style of _css) {
                if (! this.element.classList.contains(_style)) {
                    this.element.classList.add(_style);
                }
            }
        } else {
            if (! this.element.classList.contains(_css)) {
                this.element.classList.add(_css);
            }
        }
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.removeCss = function (_css)
{
    try {
        if (! _css) {
            this.element.removeAttribute('class')
        } else if (Array.isArray(_css)) {
            for (let _style of _css) {
                if (this.element.classList.contains(_style)) {
                    this.element.classList.remove(_style);
                }
            }
        } else {
            if (this.element.classList.contains(_css)) {
                this.element.classList.remove(_css);
            }
        }
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.hasCss = function (_css)
{
    try {
        return this.element.classList.contains(_css);
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.setPosition = function (_x, _y)
{
    try {
        _x = _x ?? 0;
        _y = _y ?? 0;
        this.element.style.top = _y + 'px';
        this.element.style.left = _x + 'px';
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.setAttribute = function (_key, _value)
{
    try {
        this.element.setAttribute(_key, _value);
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.removeAttribute = function (_key)
{
    try {
        this.element.removeAttribute(_key);
        return this;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.clone = function ()
{
    try {
        let _clone = this.element.cloneNode(true);
        _clone.removeAttribute('id');
        return new nsObject(_clone);
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.dataSet = function (_key, _value)
{
    try {
        if (! _value) {
            return this.element.dataset[_key];
        } else {
            this.element.dataset[_key] = _value;
            return this;
        }
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.remove = function ()
{
    try {
        this.element.remove();
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.isEmpty = function ()
{
    try {
        return (this.element.childNodes.length < 1);
    } catch (e) {
        console.error(e);
    }
    return false;
}

nsObject.prototype.isChecked = function ()
{
    try {
        let tag_name = this.element.tagName.toLowerCase();
        if (tag_name !== 'input') {
            return false;
        }
        return (this.element.type === 'checkbox') ? this.element.checked : false;
    } catch (e) {
        console.error(e);
    }
}

nsObject.prototype.value = function (_value)
{
    try {
        let tag_name = this.element.tagName.toLowerCase();
        let type = this.element.type;
        if (tag_name === 'input') {
            if (type === 'checkbox') {
                return (this.element.checked) ? this.element.value : false;
            } else { // text, password 등
                if (typeof _value !== 'string' && typeof _value !== 'number') {
                    return this.element.value;
                } else {
                    this.element.value = _value;
                    return this.element.value;
                }
            }
        } else if (tag_name === 'select') {
            if (! _value) {
                return this.element.value;
            } else {
                if (Array.from(this.element.options).some(o => o.value === _value)) {
                    this.element.value = _value;
                }
                return this.element.value;
            }
        } else if (tag_name === 'textarea') {
            if (typeof _value !== 'string' && typeof _value !== 'number') {
                return this.element.value;
            } else {
                this.element.value = _value;
                return this.element.value;
            }
        } else {
            return false;
        }
    } catch (e) {
        console.error(e);
    }
}
