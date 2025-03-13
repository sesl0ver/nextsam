ns_dialog.dialogs.item = new nsDialogSet('item', 'dialog_full', 'size-full');
ns_dialog.dialogs.item.cache = {};
ns_dialog.dialogs.item.sorted = null;
ns_dialog.dialogs.item.current_tab = null;

ns_dialog.dialogs.item.cacheContents = function()
{
    this.cont_obj.content_item_list_wrap = new nsObject('.content_item_list_wrap', this.obj);

    // 판매 상품 캐싱
    let exclude = ['500539', '500498', '500695', '500696', '500744', '500743'];
    this.cache['popularity'] = Object.values(ns_cs.m.item).filter(i => i?.m_item_pk && ! exclude.includes(i.m_item_pk) && i.popularity === 'Y' && i.yn_sell === 'Y');
    this.cache['goods'] = Object.values(ns_cs.m.item).filter(i => i?.m_item_pk && ! exclude.includes(i.m_item_pk) && i.yn_sell === 'Y');

    this.cont_obj.item_box_skeleton = new nsObject('#item_box_skeleton');
}

ns_dialog.dialogs.item.draw = function()
{
    let tab = (this.data?.tab) ? ns_button.buttons[`item_tab_${this.data.tab}`] : ns_button.buttons.item_tab_popularity;
    ns_button.toggleGroupSingle(tab);
    this.drawTab();
}

ns_dialog.dialogs.item.erase = function()
{
    if (ns_dialog.dialogs.item_buy.visible) {
        ns_dialog.close('item_buy');
    }
    this.data = null;
}

ns_dialog.dialogs.item.drawTab = function()
{
    let dialog = ns_dialog.dialogs.item;
    // 열려있는 아이템 사용 창 닫기
    if (ns_dialog.dialogs.item_use.visible) {
        ns_dialog.close('item_use');
    }

    dialog.current_tab = ns_button.toggleGroupValue('item_tab')[0].split('_tab_').pop();
    dialog.drawList();
    if (this.data?.m_item_pk) {
        ns_dialog.setDataOpen('item_buy', { m_item_pk: this.data.m_item_pk });
    }
}

ns_dialog.dialogs.item.getType = function (tab)
{
    let types = {
        popularity: 'Y',
        production: 'P',
        speedup: 'S',
        lord: 'L',
        special: 'D',
        package: 'B',
        hero: 'H',
    }
    return types[tab] ?? null;
}

ns_dialog.dialogs.item.drawItemBox = function (_data)
{
    let dialog = ns_dialog.dialogs.item;
    let box = dialog.cont_obj.item_box_skeleton.clone();
    if (_data) {
        let item_image = box.find('.item_image');
        box.find('.item_title').text(ns_i18n.t(`item_title_${_data.m_item_pk}`));
        item_image.addCss(`item_image_${_data.m_item_pk}`);
        if (ns_util.math(ns_cs.m.item[_data.m_item_pk].limit_buy).gt(0)) {
            box.find('.item_count').html(`${ns_i18n.t('buy_limit')}: <span class="item_amount">${ns_cs.d.item_buy?.[_data.m_item_pk]?.item_cnt ?? 0}/${ns_cs.m.item[_data.m_item_pk].limit_buy}</span>`);
        }
        box.find('.qbig_amount').text(_data.price);
        if (_data.sell_type) {
            item_image.addCss(`item_label`);
            item_image.addCss(_data.sell_type);
        }
    } else {
        box.text('');
        box.addCss('empty');
    }
    return box;
}

ns_dialog.dialogs.item.drawList = function()
{
    let dialog = ns_dialog.dialogs.item;
    let tab = dialog.current_tab;

    // 아이템 정렬
    dialog.sorted = (tab === 'popularity') ? dialog.cache.popularity : dialog.cache.goods.filter(i => i.display_type === dialog.getType(tab));
    dialog.sorted = ns_util.arraySort(dialog.sorted, -1, 'orderno');

    dialog.cont_obj.content_item_list_wrap.empty();

    for (let d of dialog.sorted) {
        const box = dialog.drawItemBox(d);
        let buy_count = ns_cs.d.item_buy?.[d.m_item_pk]?.item_cnt ?? 0;
        let limit_buy = Number(ns_cs.m.item[d.m_item_pk].limit_buy);
        if (ns_util.math(limit_buy).gt(0) && ns_util.math(buy_count).gte(limit_buy)) {
            box.addCss('sold_out');
        }
        box.setAttribute('id', `ns_button_item_buy_${d.m_item_pk}`);
        dialog.cont_obj.content_item_list_wrap.append(box);
        ns_button.buttons[`item_buy_${d.m_item_pk}`] = new nsButtonSet(`item_buy_${d.m_item_pk}`, null, 'item');
        ns_button.buttons[`item_buy_${d.m_item_pk}`].mouseUp = function ()
        {
            ns_dialog.setDataOpen('item_buy', { m_item_pk: d.m_item_pk });
        }
    }

    // 리스트가 홀수라면 나머지 한칸 채우기
    let max_list = 8;
    let item_cnt = dialog.sorted.length;
    let dummy_cnt = (item_cnt % 2 !== 0) ? 1 : 0;
    if (item_cnt < max_list) {
        dummy_cnt = max_list - item_cnt;
    }
    for (let i = 0; i < dummy_cnt; i++) {
        let empty = ns_dialog.dialogs.item.drawItemBox();
        dialog.cont_obj.content_item_list_wrap.append(empty);
    }
}


ns_dialog.dialogs.item.timerHandler = function(_recursive)
{
    if (this.base_class && !_recursive) {
        this.timer_handle_p = this.base_class.timerHandler.call(this, true);
    }

    let timer_id = this.tag_id + '_real';

    ns_timer.timers[timer_id] = new nsTimerSet(ns_dialog.dialogs.item.timerHandlerReal, 500, true);
    ns_timer.timers[timer_id].init();

    return ns_timer.timers[timer_id];
}

ns_dialog.dialogs.item.timerHandlerReal = function()
{
    let dialog = ns_dialog.dialogs.item;

    for (let m of dialog.sorted) {
        let buy_count = ns_cs.d.item_buy?.[m.m_item_pk]?.item_cnt ?? 0;
        let buy_limit = ns_cs.m.item?.[m.m_item_pk]?.limit_buy ?? 0;
        if (ns_util.math(buy_limit).gt(0) && ns_button.buttons[`item_buy_${m.m_item_pk}`]) {
            if (ns_button.buttons[`item_buy_${m.m_item_pk}`].obj.find('.item_amount').element) {
                ns_button.buttons[`item_buy_${m.m_item_pk}`].obj.find('.item_amount').text(`${ns_cs.d.item_buy?.[m.m_item_pk]?.item_cnt ?? 0}/${ns_cs.m.item[m.m_item_pk].limit_buy}`);
            }
        }
    }
}

/* ************************************************** */

ns_button.buttons.item_close = new nsButtonSet('item_close', 'button_back', 'item', { base_class: ns_button.buttons.common_close });
ns_button.buttons.item_sub_close = new nsButtonSet('item_sub_close', 'button_full', 'item', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.item_close_all = new nsButtonSet('item_close_all', 'button_close_all', 'item', { base_class: ns_button.buttons.common_close_all });

/* ********** */

ns_button.buttons.item_tab_popularity = new nsButtonSet('item_tab_popularity', 'button_tab', 'item', { toggle_group: 'item_tab' });
ns_button.buttons.item_tab_popularity.mouseUp = function(_e)
{
    let dialog = ns_dialog.dialogs.item;
    dialog.scroll_handle.initScroll();
    ns_button.toggleGroupSingle(this);
    dialog.drawTab();
}

ns_button.buttons.item_tab_production = new nsButtonSet('item_tab_production', 'button_tab', 'item', {base_class:ns_button.buttons.item_tab_popularity, toggle_group:'item_tab'});
ns_button.buttons.item_tab_speedup = new nsButtonSet('item_tab_speedup', 'button_tab', 'item', {base_class:ns_button.buttons.item_tab_popularity, toggle_group:'item_tab'});
ns_button.buttons.item_tab_lord = new nsButtonSet('item_tab_lord', 'button_tab', 'item', {base_class:ns_button.buttons.item_tab_popularity, toggle_group:'item_tab'});
ns_button.buttons.item_tab_special = new nsButtonSet('item_tab_special', 'button_tab', 'item', {base_class:ns_button.buttons.item_tab_popularity, toggle_group:'item_tab'});
ns_button.buttons.item_tab_package = new nsButtonSet('item_tab_package', 'button_tab', 'item', {base_class:ns_button.buttons.item_tab_popularity, toggle_group:'item_tab'});
ns_button.buttons.item_tab_hero = new nsButtonSet('item_tab_hero', 'button_tab', 'item', {base_class:ns_button.buttons.item_tab_popularity, toggle_group:'item_tab'});

ns_button.buttons.item_my_item = new nsButtonSet('item_my_item', 'button_middle_2', 'item');
ns_button.buttons.item_my_item.mouseUp = function(_e)
{
    ns_dialog.close('item');
    ns_dialog.open('my_item');
}

ns_button.buttons.item_cash = new nsButtonSet('item_cash', 'button_empty', 'item');
ns_button.buttons.item_cash.mouseUp = function(_e)
{
    ns_engine.buyQbig();
}

/*ns_button.buttons.per_info = new nsButtonSet('per_info', 'button_middle_2', 'item');
ns_button.buttons.per_info.mouseUp = function(_e)
{
    window.open(ns_engine.cfg.cmd_url_prefix + '/info_link.php?t=per_info', '_system');
};*/

/* ************************************************** */
ns_dialog.dialogs.item_buy = new nsDialogSet('item_buy', 'dialog_pop', 'size-small', { do_content_scroll: false, do_close_all: false });

ns_dialog.dialogs.item_buy.cacheContents = function()
{
    this.cont_obj.content_pop_title  = new nsObject('.content_pop_title', this.obj);

    this.cont_obj.item_image = new nsObject('.item_image', this.obj);
    this.cont_obj.item_description = new nsObject('.item_description', this.obj);

    let self = this;

    this.cont_obj.item_buy_amount = new nsObject('.amount_field', this.obj);
    this.cont_obj.item_buy_amount_slider = new nsObject('[name="develop_slide"]', this.obj);
    this.cont_obj.item_buy_amount_slider.setEvent('input', function(_e){
        let m_item_pk = self.data.m_item_pk;
        let m = ns_cs.m.item[m_item_pk];
        let current_value = self.cont_obj.item_buy_amount_slider.value();
        let total_cost = ns_util.math(m.price).mul(current_value).integer;

        self.cont_obj.item_buy_amount.value(self.cont_obj.item_buy_amount_slider.value());
        self.cont_obj.item_buy_cost.text(total_cost);
    });

    this.cont_obj.item_buy_max_count = new nsObject('#ns_button_item_buy_max_count', this.obj);
    this.cont_obj.item_buy_cost = new nsObject('.inner_asset_text', this.obj);

    this.cont_obj.item_buy_amount.setEvent('input', function(_e){
        let current_value = self.cont_obj.item_buy_amount.value();

        if ( current_value === undefined || current_value === "" || isNaN(current_value) ){ return ;}

        let m_item_pk = self.data.m_item_pk;
        let m = ns_cs.m.item[m_item_pk];
        let maximum_value = self.cont_obj.item_buy_amount_slider.element.max;

        // 숫자 이외의 다른 값은 걸러낸다.
        current_value = Math.min(current_value.replace(/[^0-9]/g, ""), maximum_value);

        let total_cost = ns_util.math(m.price).mul(current_value).integer;

        self.cont_obj.item_buy_amount.value(current_value)
        self.cont_obj.item_buy_amount_slider.value(current_value);
        self.cont_obj.item_buy_cost.text(total_cost);
    });
}

ns_dialog.dialogs.item_buy.draw = function()
{
    if (! this.data?.m_item_pk) {
        ns_dialog.close(this.tag_id);
    }

    let m_item_pk = this.data.m_item_pk;
    let m = ns_cs.m.item[m_item_pk];

    this.cont_obj.content_pop_title.text(ns_i18n.t(`item_title_${m_item_pk}`));

    this.cont_obj.item_image.addCss(`item_image_${m_item_pk}`);
    let description_detail = m.description_detail;
    if (m.use_type === 'package' && m.supply_amount !== '') {
        description_detail = description_detail.replace(/\{\{item\}\}/g, ns_util.convertPackageDescription(m_item_pk));
    }
    this.cont_obj.item_description.html(description_detail);

    let item_count = ns_cs.d.item?.[m_item_pk]?.item_cnt ?? 0;

    let item_buy_max_count = Math.min(ns_util.math(ns_cs.d.cash.qbig.v).div(m.price).integer, 999);
    let limit_buy = Number(ns_cs.m.item[m.m_item_pk].limit_buy ?? '0');
    ns_button.buttons.item_buy_ok.setEnable();
    if (ns_util.math(limit_buy).gt(0) && ns_util.math(item_buy_max_count).gte(limit_buy)) {
        let buy_count = ns_cs.d.item_buy?.[m.m_item_pk]?.item_cnt ?? 0;
        item_buy_max_count = ns_util.math(limit_buy).minus(buy_count).number;
        if (ns_util.math(item_buy_max_count).lte(0)) {
            ns_button.buttons.item_buy_ok.setDisable();
        }
    }
    item_buy_max_count = (item_buy_max_count < 1) ? 1 : item_buy_max_count;

    this.cont_obj.item_buy_max_count.text(item_buy_max_count);
    this.cont_obj.item_buy_amount_slider.setAttribute('min', 1);

    this.cont_obj.item_buy_amount_slider.setAttribute('max', item_buy_max_count);
    this.cont_obj.item_buy_amount_slider.value(1);
    this.cont_obj.item_buy_amount.value(1); // 최저값으로 초기화
    this.cont_obj.item_buy_cost.text(m.price);
}

ns_dialog.dialogs.item_buy.erase = function()
{
    this.cont_obj.item_image.removeCss(`item_image_${this.data.m_item_pk}`);
    this.data = null;
    ns_dialog.close('confirm');
}

/* ************************************************** */
ns_button.buttons.item_buy_close = new nsButtonSet('item_buy_close', 'button_pop_close', 'item_buy', { base_class: ns_button.buttons.common_close });
ns_button.buttons.item_buy_sub_close = new nsButtonSet('item_buy_sub_close', 'button_full', 'item_buy', { base_class: ns_button.buttons.common_sub_close });
ns_button.buttons.item_buy_sub_close.mouseUp = function(_e)
{
    ns_dialog.close('item_buy');
}

ns_button.buttons.item_buy_ok = new nsButtonSet('item_buy_ok', 'button_default', 'item_buy');
ns_button.buttons.item_buy_ok.mouseUp = function(_e)
{
    let dialog =  ns_dialog.dialogs.item_buy;
    let code = dialog.data.m_item_pk;
    let item_count = ns_util.toInteger(dialog.cont_obj.item_buy_amount.value());

    if (ns_util.math(item_count).lte(0)) {
        ns_dialog.setDataOpen('message', { text: ns_i18n.t('msg_need_amount') }); // 갯수를 입력해 주세요.
        return;
    }

    try {
        if (ns_util.math(ns_cs.d.cash.qbig.v).gt(0) && ns_util.math(ns_cs.m.item[code].price).mul(item_count).lte(ns_cs.d.cash.qbig.v)) {
            ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_item_buy_confirm'), // 아이템을 구매하시겠습니까?
                okFunc: () => {

                    let post_data = {};
                    post_data['item_pk'] = code;
                    post_data['count'] = item_count;

                    ns_xhr.post('/api/item/buy', post_data, function(_data)
                    {
                        if(! ns_xhr.returnCheck(_data)) {
                            return;
                        }
                        _data = _data['ns_xhr_return']['add_data'];

                        let _text = ns_i18n.t('msg_item_buy_with_qbig', [
                            ns_i18n.t(`item_title_${_data.item_pk}`),
                            _data.item_count,
                            ns_util.numberFormat(_data.cash)
                        ]);
                        ns_dialog.setDataOpen('message', { text : _text });

                        if (ns_util.math(_data.item_pk).eq(500744) || ns_util.math(_data.item_pk).eq(500498)) {
                            if (ns_dialog.dialogs.limit_buy.visible) {
                                ns_dialog.dialogs.limit_buy.do_not_close = false;
                                window.localStorage.removeItem(ns_cs.d.lord.lord_pk.v + '_limit_buy');
                                ns_dialog.close('limit_buy');
                            }
                        }

                        if (ns_dialog.dialogs.item.visible) {
                            ns_dialog.dialogs.item.drawList();
                        }

                        if (ns_dialog.dialogs.item_buy.visible) {
                            ns_dialog.close('item_buy');
                        }
                    }, { useProgress: true });
                }
            });

        } else {
            ns_dialog.setDataOpen('confirm', { text: ns_i18n.t('msg_buy_qbig_confirm'), // 큐빅이 부족합니다.<br /><br />큐빅을 구매하시겠습니까?
                okFunc: () => {
                    ns_engine.buyQbig();
                }
            });
        }
    } catch (e) {
        console.error(e);
    }
}

ns_button.buttons.item_buy_max_count = new nsButtonSet('item_buy_max_count', 'button_middle_2', 'item_buy');
ns_button.buttons.item_buy_max_count.mouseUp = function(_e)
{
    _e.preventDefault();

    let dialog =  ns_dialog.dialogs.item_buy;
    let m_item_pk = dialog.data.m_item_pk;
    let current_value = Number(dialog.cont_obj.item_buy_amount.value());
    let maximum_value = ns_util.math(dialog.cont_obj.item_buy_amount_slider.element.max).integer;

    if ( maximum_value === current_value) {
        maximum_value = 1;
    }

    let total_cost = ns_util.math(maximum_value).mul(ns_cs.m.item[m_item_pk].price).integer;

    dialog.cont_obj.item_buy_amount.value(maximum_value);
    dialog.cont_obj.item_buy_amount_slider.value(maximum_value);

    dialog.cont_obj.item_buy_cost.text(total_cost);
}

ns_button.buttons.item_buy_amount_increase = new nsButtonSet('button_increase', "button_increase", "item_buy");
ns_button.buttons.item_buy_amount_increase.mouseUp = function(_e)
{
    _e.preventDefault();

    let dialog = ns_dialog.dialogs.item_buy;
    let m_item_pk = ns_dialog.dialogs.item_buy.data.m_item_pk;
    let m = ns_cs.m.item[m_item_pk];

    let current_value = Number(dialog.cont_obj.item_buy_amount.value());
    let maximum_value = Number(dialog.cont_obj.item_buy_amount_slider.element.max);

    current_value = Math.min(++current_value, maximum_value);

    let total_cost = ns_util.math(m.price).mul(current_value).integer;

    dialog.cont_obj.item_buy_amount.value(current_value);
    dialog.cont_obj.item_buy_amount_slider.value(current_value);
    dialog.cont_obj.item_buy_cost.text(total_cost);
}

ns_button.buttons.item_buy_amount_decrease = new nsButtonSet('button_decrease', "button_decrease", "item_buy");
ns_button.buttons.item_buy_amount_decrease.mouseUp = function(_e)
{
    _e.preventDefault();

    let dialog = ns_dialog.dialogs.item_buy;
    let m_item_pk = ns_dialog.dialogs.item_buy.data.m_item_pk;
    let m = ns_cs.m.item[m_item_pk];

    let current_value = Number(dialog.cont_obj.item_buy_amount.value());
    let minimum_value = Number(dialog.cont_obj.item_buy_amount_slider.element.min);

    current_value = Math.max(--current_value, minimum_value);

    let total_cost = ns_util.math(m.price).mul(current_value).integer

    dialog.cont_obj.item_buy_amount.value(current_value);
    dialog.cont_obj.item_buy_amount_slider.value(current_value);
    dialog.cont_obj.item_buy_cost.text(total_cost);
}