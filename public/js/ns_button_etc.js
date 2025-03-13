/* main_top */
ns_button.buttons.main_view_world = new nsButtonSet('main_view_world', 'button_main_view_world', 'A');
ns_button.buttons.main_view_world.mouseUp = function()
{
    ns_engine.toggleWorld();
}

ns_button.buttons.hero_deck_list = new nsButtonSet('hero_deck_list', 'button_side_hero', 'A');
ns_button.buttons.hero_deck_list.mouseUp = function(e)
{
    ns_hero.toggleDeckList();
}

ns_button.buttons.hero_select_filter = new nsButtonSet('hero_select_filter', 'button_select_box', 'A');
ns_button.buttons.hero_select_filter.mouseUp = function(_e)
{
    ns_dialog.setDataOpen('select_box', {select_box_id: 'hero_select_filter'});
}

ns_button.buttons.hero_select_filter_show_work = new nsButtonSet('hero_select_filter_show_work', 'button_icon_complete', 'A', { button_icon_css: 'button_icon_complete' });
ns_button.buttons.hero_select_filter_show_work.mouseUp = function(_e)
{
    this.toggleClicked();
    ns_hero.deckReload();
};

ns_button.buttons.hero_select_filter_show_idle = new nsButtonSet('hero_select_filter_show_idle', 'button_icon_complete', 'A', { base_class: ns_button.buttons.hero_select_filter_show_work, button_icon_css: 'button_icon_complete' });

ns_button.buttons.main_hero_manage = new nsButtonSet('main_hero_manage', 'button_main_hero_manage', 'A');
ns_button.buttons.main_hero_manage.mouseUp = function(e)
{
    ns_dialog.open('hero_manage'); // 영웅관리
}

ns_button.buttons.main_hero_combination = new nsButtonSet('main_hero_combination', 'button_main_hero_combination', 'A');
ns_button.buttons.main_hero_combination.mouseUp = function(e)
{
    ns_dialog.open('hero_manage_combination'); // 조합
}

ns_button.buttons.main_alliance = new nsButtonSet('main_alliance', 'button_main_alliance', 'A');
ns_button.buttons.main_alliance.mouseUp = function(e)
{
    ns_dialog.open('alliance'); // 동맹
}

ns_button.buttons.main_my_item = new nsButtonSet('main_my_item', 'button_main_my_item', 'A');
ns_button.buttons.main_my_item.mouseUp = function(e)
{
    ns_dialog.open('my_item'); // 보물창고
}

ns_button.buttons.main_magic_cube = new nsButtonSet('main_magic_cube', 'button_main_magic_cube', 'A');
ns_button.buttons.main_magic_cube.mouseUp = function(e)
{
    ns_dialog.open('magic_cube'); // 매직큐브
}

ns_button.buttons.main_shop = new nsButtonSet('main_shop', 'button_main_shop', 'A');
ns_button.buttons.main_shop .mouseUp = function(e)
{
    ns_dialog.open('item'); // 아이템샵
}

ns_button.buttons.main_ranking = new nsButtonSet('main_ranking', 'button_main_ranking', 'A');
ns_button.buttons.main_ranking.mouseUp = function(e)
{
    ns_dialog.open('ranking'); // 랭킹
}

ns_button.buttons.main_package = new nsButtonSet('main_package', 'button_main_package', 'A');
ns_button.buttons.main_package.mouseUp = function(e)
{
    let o = Object.values(ns_engine.game_data.package_data).shift();
    ns_dialog.setDataOpen('package_popup', { m_package_pk: o.m_package_pk });
}

ns_button.buttons.main_terr_info = new nsButtonSet('main_terr_info', 'button_main_terr_info', 'A');
ns_button.buttons.main_terr_info.mouseUp = function(e)
{
    ns_dialog.open('terr_info'); // 랭킹
}

ns_button.buttons.main_quest = new nsButtonSet('main_quest', 'button_main_quest', 'A');
ns_button.buttons.main_quest.mouseUp = function(e)
{
    ns_dialog.open('quest'); // 임무
}

ns_button.buttons.main_quest_view_01 = new nsButtonSet('main_quest_view_01', null, 'A');
ns_button.buttons.main_quest_view_01.mouseUp = function(e)
{
    let num = this.tag_id.split('_').pop();
    if (! ns_util.isNumeric(ns_quest[`view_pk_${num}`])) {
        return;
    }
    ns_dialog.setDataOpen('quest', ns_quest[`view_pk_${num}`]);
}

ns_button.buttons.main_quest_view_02 = new nsButtonSet('main_quest_view_02', null, 'A', { base_class: ns_button.buttons.main_quest_view_01 });
ns_button.buttons.main_quest_view_03 = new nsButtonSet('main_quest_view_03', null, 'A', { base_class: ns_button.buttons.main_quest_view_01 });

ns_button.buttons.main_hero_skill = new nsButtonSet('main_hero_skill', 'button_main_hero_skill', 'A');
ns_button.buttons.main_hero_skill.mouseUp = function()
{
    ns_dialog.open('hero_skill_manage'); // 영웅기술
}

ns_button.buttons.main_time_event = new nsButtonSet('main_time_event', 'button_main_time_event', 'A');
ns_button.buttons.main_time_event.mouseUp = function()
{
    console.log('타임 이벤트!')
}

/*ns_button.buttons.main_npc_point = new nsButtonSet('main_npc_point', 'button_main_raid_list', 'A');
ns_button.buttons.main_npc_point.mouseUp = function()
{
    ns_dialog.open('npc_point'); // 요충지 현황
}*/

ns_button.buttons.main_occupation_point = new nsButtonSet('main_occupation_point', 'button_main_occupation', 'A');
ns_button.buttons.main_occupation_point.mouseUp = function()
{
    ns_dialog.open('occupation_point');
}

ns_button.buttons.main_pickup = new nsButtonSet('main_pickup', 'button_main_pickup', 'A');
ns_button.buttons.main_pickup.mouseUp = function()
{
    ns_dialog.open('hero_pickup');
}

ns_button.buttons.main_build_move_cancel = new nsButtonSet('main_build_move_cancel', 'button_main_build_move_cancel', 'A');
ns_button.buttons.main_build_move_cancel.mouseUp = function()
{
    ns_castle.cancelBuildMove();
}

ns_button.buttons.main_counter_job_list = new nsButtonSet('main_counter_job_list', 'button_main_job_list', 'A');
ns_button.buttons.main_counter_job_list.mouseUp = function()
{
    ns_dialog.open('counter_job_list');
}

ns_button.buttons.main_counter_troop_list = new nsButtonSet('main_counter_troop_list', 'button_main_troop_list', 'A');
ns_button.buttons.main_counter_troop_list.mouseUp = function()
{
    ns_dialog.open('counter_job_list');
    ns_button.buttons.counter_troop_tab.mouseUp();
}

ns_button.buttons.main_report = new nsButtonSet('main_report', 'button_main_report', 'A');
ns_button.buttons.main_report.mouseUp = function()
{
    ns_dialog.open('report'); // 보고서, 외교서신
}

ns_button.buttons.main_menu = new nsButtonSet('main_menu', 'button_main_menu', 'A');
ns_button.buttons.main_menu.mouseUp = function()
{
    ns_dialog.open('menu'); // 메인메뉴
}

ns_button.buttons.main_reload = new nsButtonSet('main_reload', 'button_main_reload', 'A');
ns_button.buttons.main_reload.mouseUp = function()
{
    console.log('재접속!')
}

ns_button.buttons.main_lord_info = new nsButtonSet('main_lord_info', 'button_empty', 'A');
ns_button.buttons.main_lord_info.mouseUp = function (_e) {
    ns_dialog.open('lord_info'); // 군주 정보
}

ns_button.buttons.main_territory_manage = new nsButtonSet('main_territory_manage', 'button_main_terr_manage', 'A');
ns_button.buttons.main_territory_manage.mouseUp = function (_e) {
    ns_dialog.open('territory_manage'); // 영지 정보
}

ns_button.buttons.main_valley_manage = new nsButtonSet('main_valley_manage', 'button_main_valley_manage', 'A');
ns_button.buttons.main_valley_manage.mouseUp = function (_e) {
    ns_dialog.open('valley_manage');
}

ns_button.buttons.main_buy_qbig = new nsButtonSet('main_buy_qbig', 'button_charge', 'A');
ns_button.buttons.main_buy_qbig.mouseUp = function (_e) {
    ns_engine.buyQbig();
}

ns_button.buttons.main_top_population = new nsButtonSet('main_top_population', null, 'A');
ns_button.buttons.main_top_population.mouseUp = function () {
    let code = this.tag_id.split('_top_').pop();
    let tag_id = (code === 'population') ? 'item_quick_use' : 'resource_manage';
    ns_dialog.setDataOpen(tag_id, { type: code });
}
ns_button.buttons.main_top_gold = new nsButtonSet('main_top_gold', null, 'A', { base_class: ns_button.buttons.main_top_population });
ns_button.buttons.main_top_food = new nsButtonSet('main_top_food', null, 'A', { base_class: ns_button.buttons.main_top_population });
ns_button.buttons.main_top_horse = new nsButtonSet('main_top_horse', null, 'A', { base_class: ns_button.buttons.main_top_population });
ns_button.buttons.main_top_lumber = new nsButtonSet('main_top_lumber', null, 'A', { base_class: ns_button.buttons.main_top_population });
ns_button.buttons.main_top_iron = new nsButtonSet('main_top_iron', null, 'A', { base_class: ns_button.buttons.main_top_population });

ns_button.buttons.main_top_chat = new nsButtonSet('main_top_chat', 'button_full', 'A');
ns_button.buttons.main_top_chat.mouseUp = function (_e) {
    ns_dialog.open('chat'); // 채팅
}
