class nsQuest
{
    constructor ()
    {
        this.view_pk_01 = null;
        this.view_pk_02 = null;
        this.view_pk_03 = null;
    }

    init ()
    {
        this.drawButtons();
    }

    drawButtons ()
    {
        let quests = Object.entries(ns_cs.d.ques).filter(q => ns_util.isNumeric(q[0]) && ns_util.isNumeric(ns_cs.m.ques[q[1].m_ques_pk]?.sortno)).sort((a, b) => ns_util.math(ns_cs.m.ques[a[1].m_ques_pk].sortno).minus(ns_cs.m.ques[b[1].m_ques_pk].sortno).integer);

        this.view_pk_01 = null;
        this.view_pk_02 = null;
        this.view_pk_03 = null;

        ns_button.buttons[`main_quest_view_01`].obj.removeCss().hide();
        ns_button.buttons[`main_quest_view_02`].obj.removeCss().hide();
        ns_button.buttons[`main_quest_view_03`].obj.removeCss().hide();

        for (let [m_ques_pk, data] of quests) {
            let line = ns_cs.m.ques[m_ques_pk].sortno.substring(0,1);
            let icon = ns_cs.m.ques[m_ques_pk].sortno.substring(4,6);
            if (! ns_cs.m.ques?.[m_ques_pk] || this[`view_pk_0${line}`] !== null) {
                continue;
            }
            this[`view_pk_0${line}`] = m_ques_pk;
            let button_obj = ns_button.buttons[`main_quest_view_0${line}`].obj;
            if (data.status === 'C') {
                button_obj.addCss('complete');
            }
            button_obj.addCss(`quest_view_${icon}`);
            if (ns_util.math(line).eq(1)) {
                button_obj.find('.quest_view_title').text(ns_cs.m.ques[m_ques_pk].sub_title);
            }
            button_obj.show();
        }
    }

    goalCheck (_m_ques_pk)
    {
        if (! ns_cs.d.ques[_m_ques_pk]) {
            return false;
        }
        let m = ns_cs.m.ques[_m_ques_pk];
        if (! ['GIVE_ITEM', 'EXCHANGE_ITEM'].includes(m.goal_type)) {
            return false;
        }
        let condition_check = 0;
        for (let i = 1; ns_util.math(i).lte(m.condition_count); i++) {
            let condition = m['condition_' + i].split(':');
            let d = ns_cs.d.item[condition[0]];
            if (d && ns_util.math(d.item_cnt).gte(condition[1])) {
                condition_check++;
            }
        }
        return ns_util.math(condition_check).eq(m.condition_count) ? 'C' : 'P';
    }
}
let ns_quest = new nsQuest();