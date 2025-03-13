<?php

class Condition
{
    public Session $Session;
    public Pg $PgGame;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function conditionCheck($_posi_pk, $_m_cond_pk, $_cast_pk, $_buil_type, $_hero_pk, $now = false): bool
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['building', 'condition', 'technique']);

        // 건물 레벨
        if ($_M['COND'][$_m_cond_pk]['active_buil_level']) {
            $_type = ($_buil_type == 'I') ? 'in' : 'out';

            $this->PgGame->query("SELECT level FROM building_{$_type}_castle WHERE posi_pk = $1 AND {$_type}_castle_pk = $2", [$_posi_pk, $_cast_pk]);
            $level = $this->PgGame->fetchOne();
            if ($level < $_M['COND'][$_m_cond_pk]['active_buil_level']) {
                $NsGlobal->setErrorMessage($i18n->t('msg_construction_need_level')); // 건물 레벨 부족합니다.
                return false;
            }
        }

        // 영웅 배속
        if ($_M['COND'][$_m_cond_pk]['yn_hero_assign_required'] == 'Y') {
            $this->PgGame->query('SELECT assign_hero_pk FROM building_in_castle WHERE posi_pk = $1 AND in_castle_pk = $2', [$_posi_pk, $_cast_pk]);
            $assign_hero_pk = $this->PgGame->fetchOne();
            if (!$assign_hero_pk) {
                $NsGlobal->setErrorMessage($i18n->t('msg_hero_assign_need')); // 영웅이 배속 되어야 합니다.
                return false;
            }
        }

        // 즉시건설에서는 체크하지 않음
        if ($now !== true && $_M['COND'][$_m_cond_pk]['cmd_hero_stat_type']) {
            $this->PgGame->query("SELECT {$_M['CODESET']['HERO_STAT'][$_M['COND'][$_m_cond_pk]['cmd_hero_stat_type']]} FROM my_hero WHERE hero_pk = $1", [$_hero_pk]);
            $stat = $this->PgGame->fetchOne();
            if ($_M['COND'][$_m_cond_pk]['cmd_hero_stat_value'] > $stat) {
                $NsGlobal->setErrorMessage($i18n->t('msg_hero_assign_status')); // 영웅 능력이 부족합니다.
                return false;
            }

        }

        if ($_M['COND'][$_m_cond_pk]['m_buil_pk']) {
            $type = ($_M['BUIL'][$_M['COND'][$_m_cond_pk]['m_buil_pk']]['type'] == 'I') ? 'in' : 'out';
            $this->PgGame->query("SELECT level FROM building_{$type}_castle WHERE posi_pk = $1 AND m_buil_pk = $2 ORDER BY level DESC LIMIT 1", [$_posi_pk, $_M['COND'][$_m_cond_pk]['m_buil_pk']]);
            $level = $this->PgGame->fetchOne();
            if ($_M['COND'][$_m_cond_pk]['m_buil_level'] > $level) {
                $NsGlobal->setErrorMessage($i18n->t('msg_preceding_building_level')); // 선행 건물의 레벨이 부족합니다.
                return false;
            }
        }

        if ($_M['COND'][$_m_cond_pk]['m_tech_pk']) {
            $this->PgGame->query("SELECT {$_M['TECH'][$_M['COND'][$_m_cond_pk]['m_tech_pk']]['code']} FROM technique WHERE posi_pk = $1", [$_posi_pk]);
            $level = $this->PgGame->fetchOne();
            if ($_M['COND'][$_m_cond_pk]['m_tech_level'] > $level) {
                $NsGlobal->setErrorMessage($i18n->t('msg_preceding_technique_level')); // 기술 레벨이 부족합니다.
                return false;
            }
        }

        if ($_M['COND'][$_m_cond_pk]['m_item_pk']) {
            $this->PgGame->query('SELECT item_cnt FROM my_item WHERE lord_pk = $1 AND item_pk = $2', [$this->Session->lord['lord_pk'], $_M['COND'][$_m_cond_pk]['m_item_pk']]);
            $cnt = $this->PgGame->fetchOne();
            if ($_M['COND'][$_m_cond_pk]['m_item_cnt'] > $cnt) {
                $NsGlobal->setErrorMessage($i18n->t('msg_need_item', [$i18n->t("item_title_{$_M['COND'][$_m_cond_pk]['m_item_pk']}")])); // 아이템이 부족합니다.
                return false;
            }
        }

        return true;
    }
}