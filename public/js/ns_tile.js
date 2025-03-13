class nsTile {
    constructor(_element, _type, _index) {
        this._element = _element;
        this._type = _type;
        this._index = _index;
        this._data = {};
        this._is_animated = false;
        this.init();
    }

    init ()
    {
        if (this._type === 'in' && ! ns_cs.d.bdic[this._index] || this._type === 'out' && ! ns_cs.d.bdoc[this._index]) {
            this.drawEmpty();
        } else {
            this.drawTile();
            this._data = (this._type === 'in') ? ns_cs.d.bdic[this._index] : ns_cs.d.bdoc[this._index];
        }
        let _index = this._index;
        let _title = new nsObject(this._element.querySelector('.ns_tile_title'));
        if (ns_cs.m.buil?.[this._element.dataset.pk]) {
            _title.removeCss('hide');
            _title.text(`Lv.${this._data.level} ` + ns_i18n.t(`build_title_${this._element.dataset.pk}`));
        } else {
            _title.addCss('hide');
        }

        let _button = this._element.querySelector('.ns_tile_event');
        if (_button) {
            let button_id = `build_${this._type}_${_index}`;
            _button.setAttribute('id', `ns_button_${button_id}`);
            ns_button.buttons[button_id] = new nsButtonSet(button_id, null, 'A');
            ns_button.buttons[button_id].mouseUp = function (_e)
            {
                const _parent = _e.target.parentElement;
                let target_id = _parent.id.split('_');
                let castle_type = target_id[1];
                let castle_pk = target_id[2];
                if (ns_castle.castle_mode === 1) { // 건물이동
                    ns_castle.updateBuildMove(castle_type, castle_pk);
                } else if (ns_castle.castle_mode === 2) { // 튜토리얼
                    // 튜토리얼용으로 일단 준비...
                } else { // 0
                    if (_parent.classList.contains('empty')) {
                        ns_dialog.setDataOpen('build_construct', {
                            castle_type: castle_type,
                            castle_pk: castle_pk
                        });
                    } else {
                        ns_dialog.setDataOpen('build_' + ns_cs.m.buil[_parent.dataset.pk].alias, { castle_pk: castle_pk, castle_type: castle_type });
                    }
                }
            }
        }
    }

    drawTile ()
    {
        let d = (this._type === 'in') ? ns_cs.d.bdic[this._index] : ns_cs.d.bdoc[this._index];
        let _object = this._element;
        if (_object) {
            _object.classList.add(`build_${d.m_buil_pk}`);
            _object.dataset.pk = d.m_buil_pk;
            if (d.status === 'U') {
                _object.classList.add('doing');
            } else {
                _object.classList.remove('doing');
            }
            _object.classList.add(`lv${d.level}`);
            if (d.assign_hero_pk !== 0) {
                _object.classList.add('assign');
            } else {
                _object.classList.remove('assign');
            }
        }
        _object.classList.remove('empty');
    }

    drawEmpty ()
    {
        this._element.classList.add('empty');
    }

    buildComplete ()
    {
        if (this._is_animated) {
            return;
        }
        this._is_animated = true;
        this._element.classList.add('build_summons');
        this._element.addEventListener('animationend', (_e) => {
            if (_e.animationName === 'build_summons') {
                this._element.classList.remove('build_summons');
                this._is_animated = false;
            }
        });
        let _p = document.createElement('p');
        _p = new nsObject(_p);
        _p.addCss('ns_tile_effect');
        this._element.appendChild(_p.element);
        _p.setEvent('animationend', (_e) => {
            if (_e.animationName === 'build_complete_y') {
                _p.remove();
            }
        });
    }
}