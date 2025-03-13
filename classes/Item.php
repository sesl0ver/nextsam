<?php

class Item
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Alliance $Alliance;
    protected Army $Army;
    protected Resource $Resource;
    protected GoldPop $GoldPop;
    protected FigureReCalc $FigureReCalc;
    protected Effect $Effect;
    protected Cash $Cash;
    protected Quest $Quest;
    protected Power $Power;
    protected Hero $Hero;
    protected HeroSkill $HeroSkill;
    protected HeroTrade $HeroTrade;
    protected Production $Production;
    protected Letter $Letter;
    protected Timer $Timer;
    protected Troop $Troop;
    protected Territory $Territory;
    protected CrossCoupon $CrossCoupon;
    protected Chat $Chat;
    protected Log $Log;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function classAlliance(): void
    {
        if (! isset($this->Allance)) {
            $this->Alliance = new Alliance($this->Session, $this->PgGame);
        }
    }

    function classArmy(): void
    {
        if (! isset($this->Army)) {
            $this->classResource();
            $this->classGoldPop();
            $this->Army = new Army($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    function classResource(): void
    {
        if (! isset($this->Resource)) {
            $this->Resource = new Resource($this->Session, $this->PgGame);
        }
    }

    function classGoldPop(): void
    {
        if (! isset($this->GoldPop)) {
            $this->GoldPop = new GoldPop($this->Session, $this->PgGame);
        }
    }

    function classFigureReCalc(): void
    {
        if (! isset($this->FigureReCalc)) {
            $this->classResource();
            $this->classGoldPop();
            $this->FigureReCalc = new FigureReCalc($this->Session, $this->PgGame, $this->Resource, $this->GoldPop);
        }
    }

    protected function classEffect (): void
    {
        if (! isset($this->Effect)) {
            $this->classResource();
            $this->classGoldPop();
            $this->classFigureReCalc();
            $this->Effect = new Effect($this->Session, $this->PgGame, $this->Resource, $this->GoldPop, $this->FigureReCalc);
        }
    }

    function classCash(): void
    {
        if (! isset($this->Cash)) {
            $this->Cash = new Cash($this->Session, $this->PgGame);
        }
    }

    protected function classLetter (): void
    {
        if (! isset($this->Letter)) {
            $this->Letter = new Letter($this->Session, $this->PgGame);
        }
    }

    function classHero(): void
    {
        if (! isset($this->Hero)) {
            $this->Hero = new Hero($this->Session, $this->PgGame);
        }
    }

    function classHeroTrade(): void
    {
        if (! isset($this->HeroTrade)) {
            $this->HeroTrade = new HeroTrade($this->Session, $this->PgGame);
        }
    }

    function classHeroSkill(): void
    {
        if (! isset($this->HeroSkill)) {
            $this->HeroSkill = new HeroSkill($this->Session, $this->PgGame);
        }
    }

    function classProduction(): void
    {
        if (! isset($this->Production)) {
            $this->Production = new Production($this->Session, $this->PgGame);
        }
    }

    function classCrossCoupon(): void
    {
        if (! isset($this->CrossCoupon)) {
            $this->CrossCoupon = new CrossCoupon();
        }
    }

    protected function classQuest (): void
    {
        if (! isset($this->Quest)) {
            $this->Quest = new Quest($this->Session, $this->PgGame);
        }
    }

    protected function classTerritory (): void
    {
        if (! isset($this->Territory)) {
            $this->Territory = new Territory($this->Session, $this->PgGame);
        }
    }

    protected function classTimer (): void
    {
        if (! isset($this->Timer)) {
            $this->Timer = new Timer($this->Session, $this->PgGame);
        }
    }

    protected function classTroop (): void
    {
        if (! isset($this->Troop)) {
            $this->Troop = new Troop($this->Session, $this->PgGame);
        }
    }

    protected function classPower (): void
    {
        if (! isset($this->Power)) {
            $this->Power = new Power($this->Session, $this->PgGame);
        }
    }

    function classChat(): void
    {
        if (! isset($this->Chat)) {
            $this->Chat = new Chat($this->Session, $this->PgGame);
        }
    }

    protected function classLog (): void
    {
        if (! isset($this->Log)) {
            $this->Log = new Log($this->Session, $this->PgGame);
        }
    }

    function get($_lord_pk): void
    {
        $this->PgGame->query('SELECT item_pk, item_cnt FROM my_item WHERE lord_pk = $1', [$_lord_pk]);
        $items = [];
        while ($this->PgGame->fetch()) {
            $items[$this->PgGame->row['item_pk']] = $this->PgGame->row;
        }
        $this->Session->sqAppend('ITEM', $items, null, $_lord_pk);
    }

    function getItem($_lord_pk, $_item_pk): void
    {
        $this->PgGame->query('SELECT item_pk, item_cnt FROM my_item WHERE lord_pk = $1 AND item_pk = $2', [$_lord_pk, $_item_pk]);
        $items = [];
        while ($this->PgGame->fetch()) {
            $items[$this->PgGame->row['item_pk']] = $this->PgGame->row;
        }
        $this->Session->sqAppend('ITEM', $items, null, $_lord_pk);
    }

    function getBuy($_lord_pk): void
    {
        $this->PgGame->query('SELECT item_pk, item_cnt FROM my_item_buy WHERE lord_pk = $1', [$_lord_pk]);
        $items = [];
        while ($this->PgGame->fetch()) {
            $items[$this->PgGame->row['item_pk']] = $this->PgGame->row;
        }
        $this->Session->sqAppend('ITEM_BUY', $items, null, $_lord_pk);
    }

    function getBuyItem($_lord_pk, $_item_pk): void
    {
        $this->PgGame->query('SELECT item_pk, item_cnt FROM my_item_buy WHERE lord_pk = $1 AND item_pk = $2', [$_lord_pk, $_item_pk]);
        $items = [];
        while ($this->PgGame->fetch()) {
            $items[$this->PgGame->row['item_pk']] = $this->PgGame->row;
        }
        $this->Session->sqAppend('ITEM_BUY', $items, null, $_lord_pk);
    }

    function getItemCount($_lord_pk, $_item_pk, $_for_update = false): int
    {
        $sql = (! $_for_update) ? '' : ' FOR UPDATE';
        $this->PgGame->query('SELECT item_cnt FROM my_item WHERE lord_pk = $1 AND item_pk = $2' .  $sql, [$_lord_pk, $_item_pk]);
        $item_cnt = $this->PgGame->fetchOne();
        return (!$item_cnt) ? 0 : $item_cnt;
    }

    function getItemList($_lord_pk, $_item_pks): array
    {
        $sql = implode(',', $_item_pks);
        $this->PgGame->query("SELECT item_pk, item_cnt FROM my_item WHERE lord_pk = $1 AND item_pk IN ($sql)", [$_lord_pk]);
        $this->PgGame->fetchAll();
        $list = [];
        foreach ($this->PgGame->rows as $row) {
            $list[$row['item_pk']] = $row['item_cnt'];
        }
        return  $list;
    }

    // function useItem($_posi_pk, $_lord_pk, $_item_pk, $_item_cnt, $_flag = null, $_lord_name = null, $_card_type = null, $_use_type = null, $_hero_pk = null, $_state = null, $yn_quest = 'N'): bool|array
    function useItem($_posi_pk, $_lord_pk, $_item_pk, $_item_cnt = 1, $_options = []): bool|array
    {
        // 필수값들 (값 추가시 필수로 옵션값도 추가 필요.
        $options = [
            '_flag' => null,
            '_before_lord_name' => null,
            '_lord_name' => null,
            '_card_type' => null,
            '_use_type' => null,
            '_hero_pk' => null,
            '_state' => null,
            '_yn_quest' => false
        ];
        $options = array_merge($options, $_options);

        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['item', 'hero', 'hero_base']);

        if ($options['_use_type'] == 'item_detail') {
            if ($_M['ITEM'][$_item_pk]['yn_myitem_use'] == 'N') {
                $NsGlobal->setErrorMessage('Error Occurred. [20001]'); // 해당 위치에선 사용할 수 없는 아이템입니다.
                return false;
            }
        }
        // $start_time = Useful::microTimeFloat();

        //아이템 있는지 확인
        $item_curr_cnt = 0;
        if ($_item_cnt > 0) {
            $item_curr_cnt = $this->getItemCount($_lord_pk, $_item_pk); // $_for_update
            if(! $item_curr_cnt) {
                $NsGlobal->setErrorMessage($i18n->t('msg_use_empty_item')); // 해당 아이템이 없습니다.
                return false;
            }

            // 아이템이 부족한가...
            if ($item_curr_cnt < $_item_cnt) {
                $NsGlobal->setErrorMessage($i18n->t('msg_use_item_not_possession')); // 필요 아이템이 부족 합니다.
                return false;
            }
        }

        // 큐빅과 함께 차감되여야 하는 타입의 아이템 체크 - Z 타입.
        if ($_M['ITEM'][$_item_pk]['type'] == 'Z') {
            $this->classCash();
            $cash_result = $this->Cash->decreaseCash($_lord_pk, $_M['ITEM'][$_item_pk]['price'], 'secret_package_pack');
            if(!$cash_result) {
                $NsGlobal->setErrorMessage($i18n->t('msg_qbig_lack')); // 큐빅이 부족합니다.
                return false;
            }
        }

        //친구패키기 아이템 사용전에 영향력을 한번 더 체크
        if ($_item_pk == 500199) {
            $this->PgGame->query('SELECT power FROM lord WHERE lord_pk = $1', [$_lord_pk]);
            $_power = $this->PgGame->fetchOne();
            if($_power < 500) {
                $NsGlobal->setErrorMessage($i18n->t('msg_lord_power_and_above', [500])); // 영향력 500이상 부터 사용 가능합니다.
                return false;
            }
        }

        // 부상자 치료 아이템 예외처리
        if ($_item_pk == 500156 || $_item_pk == 500157) {
            $this->PgGame->query('SELECT m_item_pk FROM territory_item_buff WHERE posi_pk = $1 AND m_item_pk = $2', [$_posi_pk, 500243]);
            if ($this->PgGame->fetch()) {
                $NsGlobal->setErrorMessage($i18n->t('msg_priority_item_restrictions')); // 더 강력한 효과가 적용 되어 있어<br />해당 아이템을 사용할 수 없습니다.<br /><br />현재 사용중인 아이템 사용 완료 후 재 사용 가능합니다.
                return false;
            }
        }

        $ret = null;
        if ($options['_yn_quest'] != 'Y') {
            // TODO 1개씩만 사용 가능한 아이템인지 구분이 필요함.
            //  현재 사용하지 않는 2가지 타입을 이용하여 구분하도록 설정. yn_use_duplication_type = '중복 사용 구분', yn_use_duplication_item = '중복 아이템 사용 가능 여부'
            // if ($_M['ITEM'][$_item_pk]['yn_use_duplication_item'] !== 'Y') {
            //     $_item_cnt = 1;  // 다중 사용이 가능한 경우 무조건 1개씩만 사용되도록 변경
            // }
            // $ret = $this->useTypeItem($_item_pk, $_lord_pk, $_posi_pk, $_item_cnt, $_flag, $_lord_name, $_card_type, $_state, $_hero_pk);
            $ret = $this->useTypeItem($_item_pk, $_lord_pk, $_posi_pk, $_item_cnt, $options);
            if (isset($ret['err'])) {
                $NsGlobal->setErrorMessage($ret['err']); // , null, $ret['add_data']
                return false;
            }
        }

        //아이템 차감
        $prev_cnt = $item_curr_cnt;
        $item_curr_cnt = $item_curr_cnt - $_item_cnt;
        if ($_item_cnt > 0 && $item_curr_cnt <= 0) { // 아이템 소비없이 큐빅만 차감되는 경우엔 $_item_cnt 값이 0으로 오기때문에, $_item_cnt가 0보다 큰것들만 처리해주기 위한 조건
            $r = $this->PgGame->query('DELETE FROM my_item WHERE lord_pk = $1 AND item_pk = $2', [$_lord_pk, $_item_pk]);
            $this->Session->sqAppend('ITEM', [$_item_pk => null]);
        } else { // if ($item_curr_cnt > 1) {
            $r = $this->PgGame->query('UPDATE my_item SET item_cnt = item_cnt - $3 WHERE lord_pk = $1 AND item_pk = $2', [$_lord_pk, $_item_pk, $_item_cnt]);
        }

        if ($r) {
            // Log
            $description = '';
            if (isset($options['_flag'])) {
                $description.= 'flag[' . $options['_flag'] . '];';
            }
            if (isset($options['_lord_name'])) {
                $description.= 'before_lord_name[' . $options['_before_lord_name'] . '];';
                $description.= 'lord_name[' . $options['_lord_name'] . '];';
            }
            if (isset($options['_card_type'])) {
                $description.= 'card_type[' . $options['_card_type'] . '];';
            }
            if (isset($options['_use_type'])) {
                $description.= 'use_type[' . $options['_use_type'] . '];';
            }

            // 공적패일때 경험치 상승 기록
            if (isset($_M['HERO_SKILL_EXP_MEDAL'][$_item_pk])) {
                $description.= 'exp['.$_M['HERO_SKILL_EXP_MEDAL'][$_item_pk].'];hero_pk['.$options['_hero_pk'].']';
            }

            // 아이템 개수 정보
            $this->PgGame->query('SELECT item_cnt FROM my_item WHERE lord_pk = $1 AND item_pk = $2', [$_lord_pk, $_item_pk]);
            $after_cnt = $this->PgGame->fetchOne() ?? 0;
            $description .= 'before_count['.$prev_cnt.'];after_count['.$after_cnt.'];';
            if (in_array($_item_pk, [500122, 500123, 500133, 500017])) {
                $description .= 'moved_posi_pk['.$ret['move_posi_pk'].'];';
                if ($_item_pk != 500017) {
                    $description .= 'target['.$ret['target'].'];';
                }
            }
            $this->classLog();
            $this->Log->setItem($_lord_pk, $this->Session->getPosiPk(), 'use', null, $_item_pk, null, $_item_cnt, $description);
        }

        // $end_time = Useful::microTimeFloat();
        // debug_mesg('D', __CLASS__, __FUNCTION__, __LINE__, 'useitem;time['. ($end_time - $start_time) .'];item_pk['.$_item_pk.']');

        $this->Session->sqAppend('ITEM', [$_item_pk => ['item_cnt' => $this->getItemCount($_lord_pk, $_item_pk)]], null, $_lord_pk, $_posi_pk);

        //퀘스트 체크
        $this->classQuest();
        $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'use_item', 'm_item_pk' => $_item_pk, 'posi_pk' => $_posi_pk]);

        return (! $ret) ? true : $ret;
    }

    function useTypeItem($_item_pk, $_lord_pk, $_posi_pk, $_item_cnt = 1, $_options = []): array|bool
    {
        // TODO PK 번호가 아니라 use_type 으로 구분하여 사용하도록 개선필요함.
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        $ret = match ((INT)$_item_pk) {
            // 평화 서약
            500015 => $this->useStatusTruceItem($_posi_pk, $_item_pk),
            // 깃발명 변경
            500013 => $this->useChangeFlagItem($_lord_pk, $_posi_pk, $_options['_flag']),
            // 군주명 변경
            500014 => $this->useChangeLordNameItem($_lord_pk, $_item_pk, $_options['_lord_name']),
            // 영웅슬롯확장
            500019 => $this->useHeroSlotExpansionItem(),
            // 관직 확장 (매관매직)
            500163 => $this->useOfficerCountExpansionItem($_lord_pk),
            // 격문
            500023 => $this->useIncrLoyaltyItem($_posi_pk),
            // 초급병력지원
            500062 => $this->useBeginnerArmySupportItem($_posi_pk, $_item_pk, $_item_cnt),
            // 하급병력지원
            500504 => $this->useLowArmySupportItem($_posi_pk, $_item_pk, $_item_cnt),
            // 중급병력지원
            500063 => $this->useMiddleArmySupportItem($_posi_pk, $_item_pk, $_item_cnt),
            // 고급병력지원
            500065 => $this->useAdvancedArmySupportItem($_posi_pk, $_item_pk, $_item_cnt),
            // 이벤트 병력 패키지
            500154 => $this->useArmyPackageItem($_posi_pk, $_item_pk, $_item_cnt),
            // 병력팩1, 병력팩2, 병력팩3, 보병 병력지원(300)
            500547, 500548, 500549, 500691 => $this->useArmyPack($_posi_pk, $_item_pk, $_item_cnt),
            // 병력 패키지 아이템
            500448, 500449, 500450, 500451, 500452, 500453, 500454, 500455, 500456, 500457, 500458, 500459, 500460, 500461, 500465, 500466, 500467, 500468, 500469, 500470, 500471, 500472, 500473, 500474, 500475, 500476, 500477, 500478 => $this->useArmyQuestItem($_posi_pk, $_item_pk, $_item_cnt),
            // 초급 공적패 모음함, 중급 공적패 모음함, 고급 공적패 모음함
            500064, 500066, 500068 => $this->useAccomplishmentItem($_item_pk, $_lord_pk, $_item_cnt),
            // 각종 패키지 아이템.
            500067, 500069, 500072, 500073, 500074, 500110, 500111, 500112, 500113, 500124, 500131, 500132, 500136, 500134, 500137, 500138, 500139, 500140, 500141, 500142, 500143, 500144, 500145, 500146, 500147, 500148, 500149, 500150, 500158, 500168, 500169, 500170, 500171, 500172, 500173, 500174, 500204, 500219, 500229, 500230, 500231, 500232, 500233, 500234, 500235, 500236, 500237, 500238, 500239, 500240, 500241, 500246, 500249, 500250, 500251 => $this->usePackageItem($_item_pk, $_lord_pk, $_item_cnt),
            // 영웅 즉시 영입 아이템. TODO 직접 사용 금지 20230920 송누리
            // 500075, 500103, 500247, 500384, 500417, 500496, 500497, 500498, 500499, 500500, 500520, 500521, 500523, 500524, 500525, 500242, 500706, 500707, 500740, 500790, 500791, 500792, 500793, 500794, 500385, 500386, 500387, 500388, 500389, 500390 => $this->usePackageHeroItem($_item_pk, $_lord_pk, $_item_cnt),
            // 촉의 영웅들 TODO 직접 사용 금지 20230920 송누리
            // 500076 => $this->usePackageHeroItem($_item_pk, $_lord_pk, $_item_cnt, 'UB'),
            // 오의 영웅들 TODO 직접 사용 금지 20230920 송누리
            // 500077 => $this->usePackageHeroItem($_item_pk, $_lord_pk, $_item_cnt, 'SK'),
            // 시장 상품목록 갱신
            500016 => $this->useMarketRenewItem($_item_pk, $_lord_pk, $_posi_pk),
            // 매직 큐브
            500061 => ['m_item_pk' => $_item_pk],
            // 황금 자원 아이템
            500086, 500087, 500109, 500162, 500543 => $this->useIncreaseGoldItem($_item_pk, $_posi_pk, $_item_cnt),
            // 큐빅 지급 아이템
            500125, 500126, 500127, 500128, 500129, 500130, 500153, 500152, 500151, 500199, 500268, 500269, 500270, 500271, 500272 => $this->useIncreaseQbigItem($_item_pk, $_lord_pk, $_item_cnt),
            // 각종 자원 아이템
            500088, 500089, 500090, 500091, 500092, 500093, 500094, 500095, 500097, 500098, 500099, 500100, 500101, 500159, 500160, 500161, 500225, 500226, 500227, 500228, 500527, 500528, 500529, 500530, 500531, 500532, 500533, 500534, 500535, 500536, 500537, 500540, 500541, 500542 => $this->useIncreaseResourceItem($_item_pk, $_posi_pk, $_item_cnt),
            // 군주 카드 변경
            500096 => $this->useChangeLordCardItem($_lord_pk, $_item_pk, $_options['_card_type']),
            // 영지 이동 (임의, 사용안함, 주선택, 지역선택, 선택) - TODO 일부 동작 확인 필요.
            500017, 500020, 500122, 500123, 500133 => $this->useMoveTerritory($_lord_pk, $_posi_pk, $_item_pk, $_options['_state']),
            // 동맹의 서약
            500018 => $this->useMaxMemberIncreaseAlliance($_lord_pk, $_item_pk),
            // 건설 큐 증가
            BUILD_QUEUE_INCREASE_ITEM, BUILD_QUEUE2_INCREASE_ITEM => $this->useIncreaseBuildQueue($_posi_pk, $_item_pk),
            // 기술 주머니
            HERO_SKILL_POCKET, HERO_SKILL_BATTLE_POCKET, HERO_SKILL_BATTLE_EXCELLENT_POCKET, HERO_SKILL_NAMED_BOX => $this->useHeroSkillPocket($_lord_pk, $_item_pk),
            // 기술 상자
            HERO_SKILL_COPPER_BOX, HERO_SKILL_SILVER_BOX, HERO_SKILL_GOLD_BOX, HERO_SKILL_HIGH_POCKET, HERO_SKILL_BATTLE_COPPER_BOX, HERO_SKILL_BATTLE_SILVER_BOX, HERO_SKILL_BATTLE_GOLD_BOX => $this->useHeroSkillBox($_lord_pk, $_item_pk, $_posi_pk),
            // 영웅 기술 지급
            500177, 500178, 500179, 500180, 500181, 500182, 500183, 500184, 500185, 500186, 500187, 500188, 500189, 500190, 500191, 500192, 500193, 500194, 500195, 500196, 500197, 500198, 500698 => $this->useHeroSkillPackage($_lord_pk, $_item_pk),
            // 강화 초기화
            500135 => $this->useInitHeroEnchant($_options['_hero_pk'], $_lord_pk, $_posi_pk),
            // 영웅 입찰 초기화 - TODO 사용 할 수 있는 아이템인가?
            500155 => $this->useInitHeroTradeBidCount($_lord_pk),
            // 인구 증가
            500165 => $this->usePopulationIncrease($_lord_pk, $_posi_pk),
            // 요충지 병력 패키지
            500222, 500223, 500224, 500391, 500392, 500393, 500394, 500395, 500396, 500397, 500398, 500399 => $this->usePointArmyItem($_posi_pk, $_item_pk),
            // 군주 등급 상승 보상
            500486, 500487, 500488, 500489, 500490, 500491, 500492, 500493, 500494 => $this->useLordUpgradeRewardItem($_lord_pk, $_item_pk),
            default => false,
        };
        if (isset($_M['ITEM'][$_item_pk]['use_type']) && ! $ret) {
            $ret = match ($_M['ITEM'][$_item_pk]['use_type']) {
                'package' => $this->setSupplyItem($_lord_pk, $_item_pk, $_item_cnt),
                'random' => $this->setRandomItem($_lord_pk, $_item_pk, $_item_cnt),
                'qbigpack' => $this->useIncreaseQbigItem($_item_pk, $_lord_pk, $_item_cnt),
                'coupon' => $this->useCouponItem($_lord_pk, $_item_pk),
                default => false,
            };
        }
        // 배열인데 m_item_pk, item_cnt 가 없는 경우 강제로 입력해줌.
        if (is_array($ret)) {
            if (! array_key_exists('m_item_pk', $ret)) {
                $ret['m_item_pk'] = $_item_pk;
            }
            if (! array_key_exists('item_cnt', $ret)) {
                $ret['item_cnt'] = $_item_cnt;
            }
        }
        return $ret;
    }


    function useCouponItem($_lord_pk, $_m_item_pk): array
    {
        if ($_m_item_pk == 500718) { // 무한돌파 쿠폰
            $type = 'lv2_reward_quest';
        } else if ($_m_item_pk == 500719) { // 해피스트릿
            $type = 'cottage3_reward_hs';
        } else if ($_m_item_pk == 500720) { // 몬스터 디펜걸스
            $type = 'cottage3_reward_md';
        } else if ($_m_item_pk == 500745) { // 극지고2
            $type = 'cottage3_reward_gj';
        } else if ($_m_item_pk == 500747) { // 나이트워치
            $type = 'cottage4_reward_nw';
        }

        $use_by = GAME_SERVER_NAME . '_' . $_lord_pk;

        $this->classCrossCoupon();
        $issues = $this->CrossCoupon->getCoupon($type, $_lord_pk, $use_by);
        if (!$issues) {
            return ['err' => '쿠폰 지급 실패'];
        }

        $this->classLetter();
        foreach ($issues AS $issue) {
            $letter = [];
            $letter['type'] = 'S';
            $letter['title'] = $issue['desc_title'];
            $letter['content'] = $issue['desc_body']. "\n\n쿠폰번호 : ". $issue['coupon'];
            $this->Letter->sendLetter(ADMIN_LORD_PK, [$_lord_pk], $letter, true, 'Y');
            $this->Letter->getUnreadCount($_lord_pk);
        }

        return ['package_type' => 'coupon'];
    }

    function setSupplyItem($_lord_pk, $_m_item_pk, $_item_cnt = 1): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        $arr = [];
        $arr_item_list = explode(',', $_M['ITEM'][$_m_item_pk]['supply_amount']);

        for($i = 0; $i < COUNT($arr_item_list); $i++) {
            $arr_item = explode(':', $arr_item_list[$i]);
            $arr[$arr_item[0]]['item_count'] = $arr_item[1] * $_item_cnt;
        }

        $ret = $this->setGiveItem($arr, $_lord_pk, true, 'usePackageItem['.$_m_item_pk.']');
        if (!$ret) {
            return ['err' => '아이템 지급 실패'];
        }

        return ['package_type' => 'item', 'm_item_pk' => $_m_item_pk, 'item' => $arr];
    }

    function setRandomItem($_lord_pk, $_m_item_pk, $_item_cnt = 1): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item_rand_rate']);

        $arr = [];
        $range_arr = $_M['ITEM_RAND_RATE'][$_m_item_pk];

        for ($i = 0; $i < $_item_cnt; $i++) {
            $range_prev = 1;
            $range_select = null;
            $range_random_key = rand(1, 100000); // 십만

            foreach ($range_arr as $k => $v) {
                if ($v['recalc_rate'] == 0) {
                    continue;
                }
                $next = $range_prev + $v['recalc_rate'];
                if ($range_random_key >= $range_prev && $range_random_key <= $next) {
                    $range_select = $k;
                    break;
                }
                $range_prev = $next;
            }
            if (isset($arr[$range_select])) {
                $arr[$range_select]['item_count'] += $_M['ITEM_RAND_RATE'][$_m_item_pk][$range_select]['result_item_quantity'];
            } else {
                $arr[$range_select]['item_count'] = $_M['ITEM_RAND_RATE'][$_m_item_pk][$range_select]['result_item_quantity'];
            }
        }

        $ret = $this->setGiveItem($arr, $_lord_pk, true, 'usePackageItem['.$_m_item_pk.']');
        if (!$ret) {
            return ['err' => 'Error Occurred. [20002]']; // 아이템 지급 실패
        }

        return ['package_type' => 'item', 'm_item_pk' => $_m_item_pk, 'item' => $arr];
    }

    function usePointArmyItem($_posi_pk, $_item_pk): array
    {
        global $_M, $i18n, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);
        $this->classArmy();
        $r_arr = [];
        if ($_item_pk == 500222) {
            $archer = 7000;
            $armed_infantry = 24500;
            $adv_catapult = 3500;

            if ($this->Army->getPositionArmy($_posi_pk) + $archer + $armed_infantry + $adv_catapult > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410006'] = $archer;
            $r_arr['410009'] = $armed_infantry;
            $r_arr['410014'] = $adv_catapult;

            $this->PgGame->query('SELECT archer, armed_infantry, adv_catapult FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET archer = archer+$2, armed_infantry=armed_infantry+$3, adv_catapult=adv_catapult+$4, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $archer, $armed_infantry, $adv_catapult]);

            // 로그
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->classLog();
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500223) {
            $spearman = 10500;
            $archer = 17500;
            $battering_ram = 7000;
            if ($this->Army->getPositionArmy($_posi_pk) + $spearman + $archer + $battering_ram > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410004'] = $spearman;
            $r_arr['410006'] = $archer;
            $r_arr['410012'] = $battering_ram;

            $this->PgGame->query('SELECT spearman, archer, battering_ram FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET spearman = spearman+$2, archer=archer+$3, battering_ram=battering_ram+$4, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $spearman, $archer, $battering_ram]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500224) {
            $spearman = 7000;
            $armed_infantry = 7000;
            $armed_horseman = 7000;
            $bowman = 3500;
            $catapult = 3500;
            $battering_ram = 7000;

            if ($this->Army->getPositionArmy($_posi_pk) + $spearman + $armed_infantry + $armed_horseman + $bowman + $catapult + $battering_ram > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410004'] = $spearman;
            $r_arr['410009'] = $armed_infantry;
            $r_arr['410010'] = $armed_horseman;
            $r_arr['410011'] = $bowman;
            $r_arr['410013'] = $catapult;
            $r_arr['410012'] = $battering_ram;

            $this->PgGame->query('SELECT spearman, armed_infantry, armed_horseman, bowman, catapult, battering_ram FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET spearman = spearman+$2, armed_infantry=armed_infantry+$3, armed_horseman=armed_horseman+$4, bowman = bowman+$5, catapult=catapult+$6, battering_ram=battering_ram+$7, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $spearman, $armed_infantry, $armed_horseman, $bowman, $catapult, $battering_ram]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500391) {
            $archer = 10000;
            $armed_infantry = 35000;
            $adv_catapult = 5000;

            if ($this->Army->getPositionArmy($_posi_pk) + $archer + $armed_infantry + $adv_catapult > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410006'] = $archer;
            $r_arr['410009'] = $armed_infantry;
            $r_arr['410014'] = $adv_catapult;

            $this->PgGame->query('SELECT archer, armed_infantry, adv_catapult FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET archer = archer+$2, armed_infantry=armed_infantry+$3, adv_catapult=adv_catapult+$4, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $archer, $armed_infantry, $adv_catapult]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500392) {
            $spearman = 15000;
            $archer = 25000;
            $battering_ram = 10000;

            if ($this->Army->getPositionArmy($_posi_pk) + $spearman + $archer + $battering_ram > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410004'] = $spearman;
            $r_arr['410006'] = $archer;
            $r_arr['410012'] = $battering_ram;

            $this->PgGame->query('SELECT spearman, archer, battering_ram FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET spearman = spearman+$2, archer=archer+$3, battering_ram=battering_ram+$4, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $spearman, $archer, $battering_ram]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500393) {
            $spearman = 10000;
            $armed_infantry = 10000;
            $armed_horseman = 10000;
            $bowman = 5000;
            $battering_ram = 5000;
            $catapult = 10000;

            if ($this->Army->getPositionArmy($_posi_pk) + $spearman + $armed_infantry + $armed_horseman + $bowman + $battering_ram + $catapult > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410004'] = $spearman;
            $r_arr['410009'] = $armed_infantry;
            $r_arr['410010'] = $armed_horseman;
            $r_arr['410011'] = $bowman;
            $r_arr['410013'] = $catapult;
            $r_arr['410012'] = $battering_ram;

            $this->PgGame->query('SELECT spearman, armed_infantry, armed_horseman, bowman, catapult, battering_ram FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET spearman = spearman+$2, armed_infantry=armed_infantry+$3, armed_horseman=armed_horseman+$4, bowman = bowman+$5, catapult=catapult+$6, battering_ram=battering_ram+$7, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $spearman, $armed_infantry, $armed_horseman, $bowman, $catapult, $battering_ram]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500394) {
            $armed_infantry = 100000;
            if ($this->Army->getPositionArmy($_posi_pk) + $armed_infantry > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410009'] = $armed_infantry;

            $this->PgGame->query('SELECT armed_infantry FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET armed_infantry = armed_infantry+$2, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $armed_infantry]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500395) {
            $spearman = 50000;
            $horseman = 50000;

            if ($this->Army->getPositionArmy($_posi_pk) + $spearman + $horseman > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410004'] = $spearman;
            $r_arr['410007'] = $horseman;

            $this->PgGame->query('SELECT spearman, horseman FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET spearman = spearman+$2, horseman=horseman+$3, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $spearman, $horseman]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500396) {
            $infantry = 25000;
            $spearman = 25000;
            $armed_horseman = 25000;
            $bowman = 10000;
            $battering_ram = 10000;
            $catapult = 5000;

            if ($this->Army->getPositionArmy($_posi_pk) + $infantry + $spearman + $armed_horseman + $bowman + $battering_ram + $catapult > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410002'] = $infantry;
            $r_arr['410004'] = $spearman;
            $r_arr['410010'] = $armed_horseman;
            $r_arr['410011'] = $bowman;
            $r_arr['410013'] = $catapult;
            $r_arr['410012'] = $battering_ram;

            $this->PgGame->query('SELECT infantry, spearman, armed_horseman, bowman, catapult, battering_ram FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET infantry = infantry+$2, spearman=spearman+$3, armed_horseman=armed_horseman+$4, bowman = bowman+$5, catapult=catapult+$6, battering_ram=battering_ram+$7, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $infantry, $spearman, $armed_horseman, $bowman, $catapult, $battering_ram]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500397) {
            $infantry = 50000;
            $armed_infantry = 35000;
            $spearman = 50000;
            $battering_ram = 15000;

            if ($this->Army->getPositionArmy($_posi_pk) + $infantry + $armed_infantry + $spearman + $battering_ram > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410002'] = $infantry;
            $r_arr['410004'] = $spearman;
            $r_arr['410009'] = $armed_infantry;
            $r_arr['410012'] = $battering_ram;

            $this->PgGame->query('SELECT infantry, armed_infantry, spearman, battering_ram FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $query_params = [$_posi_pk, $infantry, $armed_infantry, $spearman, $battering_ram];
            $this->PgGame->query('UPDATE army SET infantry = infantry+$2, armed_infantry=armed_infantry+$3, spearman=spearman+$4, battering_ram=battering_ram+$5, last_update_dt=now() WHERE posi_pk = $1', $query_params);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500398) {
            $spearman = 50000;
            $horseman = 50000;
            $catapult = 50000;

            if ($this->Army->getPositionArmy($_posi_pk) + $spearman + $horseman + $catapult > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410004'] = $spearman;
            $r_arr['410007'] = $horseman;
            $r_arr['410013'] = $catapult;

            $this->PgGame->query('SELECT spearman, horseman, catapult FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET spearman = spearman+$2, horseman=horseman+$3, catapult=catapult+$4, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $spearman, $horseman, $catapult]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        } else if ($_item_pk == 500399) {
            $infantry = 50000;
            $spearman = 50000;
            $armed_horseman = 25000;
            $bowman = 10000;
            $battering_ram = 10000;
            $catapult = 5000;

            if ($this->Army->getPositionArmy($_posi_pk) + $infantry + $spearman + $armed_horseman + $bowman + $battering_ram + $catapult > TROOP_ARMY_LIMIT) {
                return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])]; // 영지당 보유할 수 있는 총 병력수 {{1}}이 초과되어 해당 아이템을 사용 할 수 없습니다.
            }

            $r_arr = [];
            $r_arr['410002'] = $infantry;
            $r_arr['410004'] = $spearman;
            $r_arr['410010'] = $armed_horseman;
            $r_arr['410011'] = $bowman;
            $r_arr['410012'] = $battering_ram;
            $r_arr['410013'] = $catapult;

            $this->PgGame->query('SELECT spearman, infantry, armed_horseman, bowman, catapult, battering_ram FROM army WHERE posi_pk = $1', [$_posi_pk]);
            $this->PgGame->fetch();
            $curr_row = $this->PgGame->row;
            $this->PgGame->query('UPDATE army SET spearman = spearman+$2, infantry=infantry+$3, armed_horseman=armed_horseman+$4, bowman = bowman+$5, catapult=catapult+$6, battering_ram=battering_ram+$7, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $spearman, $infantry, $armed_horseman, $bowman, $catapult, $battering_ram]);

            // 로그
            $this->classLog();
            $log_description = '';
            foreach ($r_arr as $_m_army_pk => $_value) {
                $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
            }
            $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);
        }

        $this->Army->get($_posi_pk);

        return ['package_type' => 'army', 'm_item_pk' => $_item_pk, 'army' => $r_arr];
    }

    function usePopulationIncrease($_lord_pk, $_posi_pk): array
    {
        global $i18n;
        $this->classGoldPop();

        $this->PgGame->query('SELECT population_max, population_curr FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $population_max = $this->PgGame->row['population_max'];
        $population_curr = $this->PgGame->row['population_curr'];

        if ($population_curr >= $population_max) {
            return ['err' => $i18n->t('msg_quick_use_no_effect')]; // 아이템을 사용하여도 효과가 없습니다.
        }

        $r = $this->GoldPop->increasePopulation($_posi_pk, 2000, $_lord_pk);
        if (!$r) {
            return ['err' => 'Error Occurred. [20003]']; // 아이템 사용에 실패 하였습니다.
        }

        return [];
    }

    function useHeroSkillBox($_lord_pk, $_m_item_pk, $_posi_pk): array
    {
        // HERO_SKILL_COPPER_KEY, HERO_SKILL_SILVER_KEY, HERO_SKILL_GOLD_KEY
        // 1. 해당 상자에 맞는 열쇠가 있는지 확인
        // 2. 있으면 진행, 없으면 error
        // 3. 레어등급 결정하여 나올수 있는 스킬 list만들어서 저장하고 리턴
        global $NsGlobal, $i18n;
        $skill_type = null;
        $this->classHeroSkill();

        $key_pk = 0;
        if ($_m_item_pk == HERO_SKILL_COPPER_BOX) {
            $key_pk = HERO_SKILL_COPPER_KEY;
            $type = 'copper_box';
            $list_cnt = HERO_SKILL_COPPER_BOX_SKILL_COUNT;
        } else if ($_m_item_pk == HERO_SKILL_SILVER_BOX) {
            $key_pk = HERO_SKILL_SILVER_KEY;
            $type = 'silver_box';
            $list_cnt = HERO_SKILL_SILVER_BOX_SKILL_COUNT;
        } else if ($_m_item_pk == HERO_SKILL_GOLD_BOX) {
            $key_pk = HERO_SKILL_GOLD_KEY;
            $type = 'gold_box';
            $list_cnt = HERO_SKILL_GOLD_BOX_SKILL_COUNT;
        } else if ($_m_item_pk == HERO_SKILL_HIGH_POCKET) {
            $type = 'high_pocket';
            $list_cnt = HERO_SKILL_HIGH_POCKET_SKILL_COUNT;
        } else if ($_m_item_pk == HERO_SKILL_BATTLE_COPPER_BOX) {
            $key_pk = HERO_SKILL_BATTLE_COPPER_KEY;
            $type = 'battle_copper_box';
            $list_cnt = HERO_SKILL_BATTLE_BOX_SKILL_COUNT;
            $skill_type = 'B';
        } else if ($_m_item_pk == HERO_SKILL_BATTLE_SILVER_BOX) {
            $key_pk = HERO_SKILL_BATTLE_SILVER_KEY;
            $type = 'battle_silver_box';
            $list_cnt = HERO_SKILL_BATTLE_BOX_SKILL_COUNT;
            $skill_type = 'B';
        } else if ($_m_item_pk == HERO_SKILL_BATTLE_GOLD_BOX) {
            $key_pk = HERO_SKILL_BATTLE_GOLD_KEY;
            $type = 'battle_gold_box';
            $list_cnt = HERO_SKILL_BATTLE_BOX_SKILL_COUNT;
            $skill_type = 'B';
        } else {
            return ['err' => 'Not Found Hero Skill BOX item.'];
        }

        if ($key_pk && !$this->getItemCount($_lord_pk, $key_pk)) {
            return ['err' => $i18n->t('msg_item_box_need_key_item'), 'add_data' => ['key_pk' => $key_pk, 'm_item_pk' => $_m_item_pk]]; // 해당 상자에 사용 가능한 열쇠가 부족합니다.
        }

        // 미선택 상자 있을 경우
        $ret = $this->HeroSkill->getMyHeroSkillBoxList($_lord_pk);
        if ($ret) {
            return ['err' =>  $i18n->t('msg_hero_skill_box_keep'), 'add_data' => ['my_hero_skil_box_pk' => $ret['my_hero_skil_box_pk'], 'm_item_pk' => $ret['m_item_pk'], 'skill_list' => $ret['skill_list']]]; // 미선택 기술 상자가 있어<br />새로운 기술 상자를 열 수 없습니다.<br /><br />상단의 기술관리 버튼을 사용하여<br />미선택 기술을 선택하여 주십시오.
        }

        try {
            $this->PgGame->begin();
            global $_NS_SQ_REFRESH_FLAG;
            $_NS_SQ_REFRESH_FLAG = true;

            $max_cnt = 0;
            $hero_skill_list = [];
            for($i = 0; $i < $list_cnt; $i++) {
                // 스킬 레어도 선택
                $range_select = $this->HeroSkill->setHeroSkillrare($type);

                // 스킬 지급
                $m_skil_pk = $this->HeroSkill->getRandomHeroSkill($range_select, $skill_type, $hero_skill_list);
                if (!$m_skil_pk) {
                    $i--;
                } else {
                    $hero_skill_list[$i] = $m_skil_pk;
                }

                $max_cnt++;
                if ($max_cnt > 10) {
                    throw new Exception('Error Occurred. [20004]'); // 기술 지급 실패 (Err 1)
                }
            }

            // 스킬 상자 리스트에 추가
            $my_hero_skil_box_pk = $this->HeroSkill->setHeroSkillBoxListRegist($_lord_pk, $hero_skill_list, $_m_item_pk);
            if (!$my_hero_skil_box_pk) {
                throw new Exception('Error Occurred. [20005]'); // 기술 지급 실패 (Err 2)
            }

            // 열쇠 사용
            if ($_m_item_pk != HERO_SKILL_HIGH_POCKET) {
                $r = $this->useItem($_posi_pk, $_lord_pk, $key_pk, 1, ['_yn_quest' => true]);
                if (!$r) {
                    throw new Exception('Error Occurred. [20006]'); // 기술 지급 실패 (Err 3)
                }
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';lord_pk['.$_lord_pk.'];m_item_pk['.$_m_item_pk.'];');

            return ['err' => $e->getMessage()];
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        return ['package_type' => 'skill_box', 'my_hero_skil_box_pk' => $my_hero_skil_box_pk, 'm_item_pk' => $_m_item_pk, 'skill_list' => $hero_skill_list];
    }

    function useHeroSkillPocket($_lord_pk, $_m_item_pk): array
    {
        $this->classHeroSkill();

        $type = null;
        $skill_type = null;
        if ($_m_item_pk == HERO_SKILL_POCKET) {
            $type = 'pocket';
        } else if ($_m_item_pk == HERO_SKILL_BATTLE_POCKET) {
            $type = 'battle_pocket';
            $skill_type = 'B';
        } else if ($_m_item_pk ==  HERO_SKILL_BATTLE_EXCELLENT_POCKET) {
            $type = 'battle_excellent_pocket';
            $skill_type = 'B';
        } else if ($_m_item_pk == HERO_SKILL_NAMED_BOX) {
            $type = 'namedhero_skill_box';
            $skill_type = 'R'; // 명장 스킬
        }
        if (! $type) {
            return ['err' => 'Error Occurred. [20007]']; // 기술 지급 실패 (`Err 5`)
        }

        // TODO 아이템 수량에 따른 여러 스킬 지급 구현 필요.

        // 스킬 레어도 선택
        $range_select = $this->HeroSkill->setHeroSkillrare($type);

        // 스킬 발급
        $m_skil_pk = $this->HeroSkill->getRandomHeroSkill($range_select, $skill_type);

        // 스킬 지급
        $r = $this->HeroSkill->setHeroSkillRegist($_lord_pk, $m_skil_pk, 'get_pocket');
        if (!$r) {
            return ['err' => 'Error Occurred. [20008]']; // 기술 지급 실패 (Err 4)
        }

        return ['package_type' => 'skill_pocket', 'm_item_pk' => $_m_item_pk, 'm_skil_pk' => $m_skil_pk];

    }

    function useLordUpgradeRewardItem($_lord_pk, $_m_item_pk): array
    {
        $this->classHeroSkill();

        $m_skil_pk = match (true) {
            ($_m_item_pk == 500486) =>  158207,
            ($_m_item_pk == 500487) =>  158107,
            ($_m_item_pk == 500488) =>  158307,
            ($_m_item_pk == 500489) =>  158407,
            ($_m_item_pk == 500490) =>  158007,
            ($_m_item_pk == 500491) =>  158507,
            ($_m_item_pk == 500492) =>  158607,
            ($_m_item_pk == 500493) =>  158707,
            ($_m_item_pk == 500494) =>  158807,
            default =>  null,
        };
        if (! $m_skil_pk) {
            return ['err' => 'Not Found Reward Item.'];
        }

        // 스킬 지급
        $r = $this->HeroSkill->setHeroSkillRegist($_lord_pk, $m_skil_pk, 'lord_upgrade');
        if (!$r) {
            return ['err' => 'Error Occurred. [20009]']; // 기술 지급 실패 (Err 5)
        }

        return ['package_type' => 'skill_pocket', 'm_item_pk' => $_m_item_pk, 'm_skil_pk' => $m_skil_pk];
    }

    function useHeroSkillPackage($_lord_pk, $_m_item_pk): array
    {
        $this->classHeroSkill();

        $m_skil_pk = match (true) {
            ($_m_item_pk == 500177) => 152103,
            ($_m_item_pk == 500178) => 152003,
            ($_m_item_pk == 500179) => 151903,
            ($_m_item_pk == 500180) => 152303,
            ($_m_item_pk == 500181) => 152203,
            ($_m_item_pk == 500182) => 152104,
            ($_m_item_pk == 500183) => 152004,
            ($_m_item_pk == 500184) => 151904,
            ($_m_item_pk == 500185) => 152304,
            ($_m_item_pk == 500186) => 152204,
            ($_m_item_pk == 500187) => 152503,
            ($_m_item_pk == 500188) => 152603,
            ($_m_item_pk == 500189) => 152504,
            ($_m_item_pk == 500190) => 152604,
            ($_m_item_pk == 500191) => 152505,
            ($_m_item_pk == 500192) => 152605,
            ($_m_item_pk == 500193) => 152404,
            ($_m_item_pk == 500194) => 152405,
            ($_m_item_pk == 500195) => 152406,
            ($_m_item_pk == 500196) => 153002,
            ($_m_item_pk == 500197) => 153003,
            ($_m_item_pk == 500198) => 153004,
            ($_m_item_pk == 500698) => 158907,
            default => null,
        };
        if (! $m_skil_pk) {
            return ['err' => 'Not Found Package Item.'];
        }

        // 스킬 지급
        $r = $this->HeroSkill->setHeroSkillRegist($_lord_pk, $m_skil_pk, 'get_quest');
        if (!$r) {
            return ['err' => 'Error Occurred. [20010]']; // 기술 지급 실패 (Err 5)
        }

        return ['package_type' => 'skill_pocket', 'm_item_pk' => $_m_item_pk, 'm_skil_pk' => $m_skil_pk];
    }

    function useIncreaseBuildQueue($_posi_pk, $_m_item_pk): true
    {
        if ($_m_item_pk == BUILD_QUEUE2_INCREASE_ITEM){
            $query_params = [$_posi_pk, 1, BUILD_QUEUE2_MAX_COUNT];
        } else {
            $query_params = [$_posi_pk, 1, BUILD_QUEUE_MAX_COUNT];
        }
        $this->PgGame->query('UPDATE build SET concurr_max = $3 WHERE posi_pk = $1 AND in_cast_pk = $2', $query_params);
        return true;
    }

    function useMaxMemberIncreaseAlliance($_lord_pk, $m_item_pk): array
    {
        global $i18n;
        $this->classAlliance();
        $alli_pk = $this->Alliance->getAlliancePK($_lord_pk);
        if (!$alli_pk) {
            return ['err' => $i18n->t('msg_item_use_require_alliance')]; // 동맹이 있어야 사용할 수 있는 아이템 입니다.
        }

        if ($this->Alliance->getAllianceMemberLevel($_lord_pk) != 1) {
            return ['err' => $i18n->t('msg_item_use_require_alliance_lord')]; // 동맹의 맹주만사용할 수 있는 아이템 입니다.
        }

        if ($this->Alliance->getMaxMemberCount($alli_pk) >= 100) {
            return ['err' => $i18n->t('msg_item_use_max_alliance_member')]; // 이미 최대 동맹수에 도달하였습니다.
        }

        $this->PgGame->query('UPDATE alliance SET max_member_count = max_member_count + 10 WHERE alli_pk = $1', [$alli_pk]);

        return ['m_item_pk' => $m_item_pk, 'member_count' => $this->Alliance->getMaxMemberCount($alli_pk)];
    }

    function useMoveTerritory($_lord_pk, $_posi_pk, $_m_item_pk, $_state = null): array
    {
        global $i18n;
        $this->classPower();

        $this->PgGame->query('SELECT src_posi_pk FROM troop WHERE src_posi_pk = $1', [$_posi_pk]);
        if ($this->PgGame->fetch()) {
            return ['err' => $i18n->t('msg_move_territory_no_troop')]; // 이동 중이거나 주둔 중인 부대가 있으면 해당 아이템을 사용할 수 없습니다.
        }

        $this->PgGame->query('SELECT dst_posi_pk FROM troop WHERE dst_posi_pk = $1 AND status = $2', [$_posi_pk, 'C']);
        if ($this->PgGame->fetch()) {
            return ['err' => $i18n->t('msg_move_territory_no_deployed')]; // 주둔 중인 동맹 부대가 있으면 해당 아이템을 사용할 수 없습니다.
        }

        $this->PgGame->query('SELECT src_posi_pk FROM troop WHERE src_lord_pk = $1 AND dst_posi_pk = $2', [NPC_TROOP_LORD_PK, $_posi_pk]);
        if ($this->PgGame->fetch()) {
            return ['err' => $i18n->t('msg_move_territory_no_attack')]; // 습격 부대가 공격 중일 경우는 해당 아이템을 사용할 수 없습니다.
        }

        $this->PgGame->query('SELECT COUNT(posi_pk) FROM territory_valley WHERE posi_pk = $1', [$_posi_pk]);
        if ($this->PgGame->fetchOne()) {
            return ['err' => $i18n->t('msg_move_territory_no_valley')]; // 외부 자원지를 보유하고 있을 경우는 해당 아이템을 사용할 수 없습니다.
        }

        //트랜잭션
        try {
            $this->PgGame->begin();

            $r = false;

            if (! in_array($_m_item_pk, [500017, 500122, 500123, 500133])) { // 영지 임의 이동 , 주 선택 영지 이동 , 지역 선택 영지 이동
                throw new Exception('Error Occurred. [20011]'); // 영지 이동 아이템만 사용가능
            }

            if ($_m_item_pk == 500017) {
                // 임의 이동
                $r = $this->PgGame->query('SELECT getrandomposition(\''. $_posi_pk . '\',' . $_lord_pk .')');
            } else if ($_m_item_pk == 500122) {
                // 주 선택 이동
                if (!$_state || ($_state < 1 || $_state > 9)) {
                    return ['err' => 'Error Occurred. [20012]']; // 선택된 주가 없으므로 해당 아이템을 사용할 수 없습니다.
                }
                $r = $this->PgGame->query('SELECT getstateposition(\''. $_posi_pk . '\',' . $_lord_pk . ',' . $_state .')');
            } else if ($_m_item_pk == 500123) { // 지역 선택 이동
                if (!$_state || ($_state < 1 || $_state > 729)) {
                    return ['err' => 'Error Occurred. [20013]']; // 선택된 지역이 없으므로 해당 아이템을 사용할 수 없습니다.
                }
                $r = $this->PgGame->query('SELECT getareaposition(\''. $_posi_pk . '\',' . $_lord_pk . ',' . $_state .')');
            } else if ($_m_item_pk == 500133) { // 근처 영지 이동
                $position = explode('x', $_state);
                $positions = [];
                for ($i = $position[0] - 2; $i < (int)$position[0] + 3; $i++) {
                    for ($j = $position[1] - 2; $j < (int)$position[1] + 3; $j++) {
                        $positions[] = '\''.$i .'x'.$j.'\'';
                    }
                }
                $str = implode(',', $positions);
                $this->PgGame->query("SELECT posi_pk FROM position WHERE posi_pk IN ({$str}) AND TYPE IN ('A', 'E') AND lord_pk is null limit 1 offset random() FOR UPDATE");
                $move_posi_pk = $this->PgGame->fetchOne();
                if (!$move_posi_pk) { // 이동할 공간이 없음
                    return ['err' => $i18n->t('msg_move_territory_no_field')]; // 이동 가능한 평지가 없습니다.<br/><br/>(범위 내에 평지가 없거나 다른 군주의 소속입니다.)
                }
                $r = $this->PgGame->query('UPDATE position SET lord_pk = $1, update_point_dt = now() WHERE posi_pk = $2', [$_lord_pk, $move_posi_pk]);
                if (!$r || $this->PgGame->getAffectedRows() == 0) {
                    // 실패 처리
                    $r = false;
                }
            }

            if (!$r) {
                throw new Exception('Error Occurred. [20014]'); // 영지 이동에 실패 하였습니다.
            } else {
                if ($_m_item_pk != 500133) {
                    $move_posi_pk = $this->PgGame->fetchOne();
                }

                if (!$move_posi_pk) {
                    throw new Exception('Error Occurred. [20015]'); // 영지 이동에 실패 하였습니다.(1)
                }

                // 영지이동 프로시저 호출
                $r = $this->PgGame->query('SELECT setmoveterritory(\''. $_posi_pk . '\',' . $_lord_pk . ',' . '\''.$move_posi_pk .'\')');
                if ($r != 1) {
                    throw new Exception('Error Occurred. [20016]'); // 영지 이동에 실패 하였습니다.(2)
                }

                $this->PgGame->query('SELECT COUNT(posi_pk) FROM territory WHERE posi_pk = $1', [$move_posi_pk]);
                if ($this->PgGame->fetchOne() != 1) {
                    throw new Exception('Error Occurred. [20017]'); // 영지 이동에 실패 하였습니다.(3)
                }

                //2013.02.15 영향력 계산후 업데이트
                $new_territory_power = $this->Power->getBuildingPower($move_posi_pk) + $this->Power->getTechniquePower($move_posi_pk);
                $this->PgGame->query('UPDATE territory SET power = $1 WHERE posi_pk = $2', [$new_territory_power, $move_posi_pk]);

                // 내가 목적지인 타이머가 있는지 검사
                $this->PgGame->query('SELECT troo_pk, dst_time_pk FROM troop WHERE dst_posi_pk = $1', [$_posi_pk]);
                $this->PgGame->fetchAll();
                $rows = $this->PgGame->rows;
                if ($rows) {
                    $this->classTimer();
                    $this->classTroop();
                    foreach($rows AS $k => $v) {
                        if ($v['dst_time_pk']){
                            // 타이머 취소
                            $this->Timer->cancel($v['dst_time_pk'], $_lord_pk);
                            // 기존 troop.to_position update(현재타입으로..)
                            $this->PgGame->query('UPDATE troop SET to_position = $1, dst_lord_pk = $3 WHERE troo_pk = $2', [$this->Troop->getPositionName($_posi_pk), $v['troo_pk'], NPC_TROOP_LORD_PK]);
                        }
                    }
                }

                // 점령선포 삭제
                $this->classTerritory();
                $this->Territory->cancelOccupationInform($_posi_pk, $_lord_pk);
            }

            $this->Session->setLoginReload();
            $this->Session->setCurrentPosition($move_posi_pk);
            $this->Session->sqInit($move_posi_pk, true); // 세션업데이트
            $this->classTimer();
            $this->Timer->get($move_posi_pk); // 변경된 타이머 갱신을 위해

            // 이동 후 보호 TODO 기획상 제거하는 것으로 변경
            // $this->classTerritory();
            // $this->Territory->setTruceStatus($move_posi_pk, 'M', 500108);

            $this->PgGame->commit();
        } catch (Exception $e){
            // 실패, sq 무시
            $this->PgGame->rollback();

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];');

            return ['err' => $e->getMessage()];
        }

        return ['m_item_pk' => $_m_item_pk, 'move_posi_pk' => $move_posi_pk, 'target' => $_state];
    }

    function useMarketRenewItem($_m_item_pk, $_lord_pk, $_posi_pk): array
    {
        // 리스트 만들기
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['building']);

        // 전체 개수
        $max_cnt = $_M['BUIL'][PK_BUILDING_MARKET]['level']['10']['variation_1'];

        $this->PgGame->query('SELECT level FROM building_in_castle WHERE posi_pk = $1 AND m_buil_pk = $2', [$_posi_pk, PK_BUILDING_MARKET]);
        $buil_level = $this->PgGame->fetchOne();

        // 내가 보여줘야 할 개수
        $my_list_cnt = $_M['BUIL'][PK_BUILDING_MARKET]['level'][$buil_level]['variation_1'];

        // 시장 목록 비율
        $rate = ['gold' => 0.3, 'food' => 0.1, 'horse' => 0.1, 'lumber' => 0.05, 'iron' => 0.05, 'cashitem' => 0.4];
        $market_list = [];

        foreach($rate AS $k => $v) {
            $market_list[$k] = $max_cnt * $v * (1/($max_cnt/$my_list_cnt));
        }

        $create_list = [];
        $remain_list = [];
        $cnt = 0;
        $list_cnt = 0;
        foreach($market_list AS $k => $v) {
            //1이상일 경우
            if ($v >= 1) {
                // 상품 리스트 만들기
                for($i = 0; $i < floor($v); $i++) {
                    $create_list[$cnt] = ['sale_type' => $k];  // 종류
                    $cnt++;
                }
                $list_cnt += floor($v);
            }

            // 남은 리스트 만들기 위한....
            if ($v - floor($v) > 0) {
                $remain_list[$k] = $k;
            }
        }

        shuffle($create_list);

        // 리스트 남은 갯수
        $remain_list_cnt = $my_list_cnt - $list_cnt;
        shuffle($remain_list);

        for ($i = 0; $i < $remain_list_cnt; $i++) {
            $create_list[$cnt] = ['sale_type' => $remain_list[$i]];  // 종류
            $cnt++;
        }

        // 수량 결정하기
        $this->PgGame->query('SELECT level FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $lord_level = $this->PgGame->fetchOne();

        $arr_type = [];
        foreach($create_list AS &$v) {
            $pay_type = 'R';
            $m_item_pk = null;

            if ($v['sale_type'] != 'cashitem') { // 캐쉬 아이템이 아닌 경우 (금, 식량, 목재 등등)
                // 판매할 상품의 최소량 , 최대량
                $min_amount = $_M['MARKET_SALE_AMOUNT'][$lord_level][$v['sale_type']] - ($_M['MARKET_SALE_AMOUNT'][$lord_level][$v['sale_type']] / 2);
                $max_amount = $_M['MARKET_SALE_AMOUNT'][$lord_level][$v['sale_type']] + ($_M['MARKET_SALE_AMOUNT'][$lord_level][$v['sale_type']] / 2);

                // 판매량 결정 // 최대 최소 +- 50%
                $amount = rand($min_amount, $max_amount);
                $amount = (INT)(floor($amount / 100)) * 100;
                $v['sale_amount'] = $amount;

                // 지불할 자원 종류 결정
                if ($_M['QBIG_TRANSRATE_VALUE'][$v['sale_type']] < $amount) {
                    $value = rand(1, 100);
                    $pay_type = ($value >= 70) ? 'Q' : 'R'; // Q = 자원 + 큐빅
                }

                $qbig = 0;

                if ($pay_type == 'Q') {
                    // 페이타입에 맞게 지불가격 결정
                    $max_qbig = $amount / $_M['QBIG_TRANSRATE_VALUE'][$v['sale_type']];

                    // 지불 큐빅 결정
                    $qbig = rand(1, (INT)$max_qbig);
                    // 판매 상품이 자원인 경우 큐빅 제한 200
                    $qbig = ($qbig > 200) ? 200 : $qbig;
                    $qbig = ($qbig < 1) ? 1 : $qbig;
                }

                // 지불할 자원 종류 결정 - 판매하는 자원과는 겹치지 않도록
                $type_array = ['gold', 'food', 'horse', 'lumber', 'iron'];
                if (($key = array_search($v['sale_type'], $type_array)) !== false) {
                    unset($type_array[$key]);
                }
                shuffle($type_array);
                $type = reset($type_array);

                // 지불할 자원량 결정
                $qbig_transrate_value = $_M['QBIG_TRANSRATE_VALUE'][$v['sale_type']];
                $v['pay_reso_amount'] = ($amount - ($qbig * $qbig_transrate_value));
                $v['pay_reso_amount'] *= ($_M['QBIG_TRANSRATE_VALUE'][$type] / $qbig_transrate_value);
            } else {	// 캐쉬 아이템인 경우
                $v['sale_amount'] = 1; // 캐쉬 아이템은 오로지 1개

                $NsGlobal->requireMasterData(['item']);
                $m_item_array = $_M['ITEM'];

                // 아이템 결정
                $m_item_array = array_filter($m_item_array, function ($v) {
                    return $v['yn_market_sale'] === 'Y'; // 상점에서 파는 물품만 필터링.
                });
                shuffle($m_item_array); // 섞고
                $m_item_pk = $m_item_array[0]['m_item_pk']; // 첫번째 물품 return;

                $item_price = (INT)$_M['ITEM'][$m_item_pk]['price'] * (rand(100, 120) / 100);

                $r = rand(1, 100);
                if ($r == 1) { // 1~5% 이하로 큐빅 책정
                    $qbig_pct = rand(1, 5);
                } else if ($r > 1 && $r <= 20) { //15~80% 미만으로 가격 책정
                    $qbig_pct = rand(15, 79);
                } else if ($r > 20 && $r <= 100) { // 80~90%로 가격 설정
                    $qbig_pct = rand(80, 90);
                }
                $qbig = round($item_price * ($qbig_pct / 100));
                $qbig = ($qbig < 1) ? 1 : $qbig;

                // 현재 계산된 큐빅값이 캐시샵 판매가보다 높을 경우엔 -1 큐빅 해줌.
                if ((INT)$_M['ITEM'][$m_item_pk]['price'] <= $qbig) {
                    $qbig = (INT)$_M['ITEM'][$m_item_pk]['price'] - 1;
                }

                // 지불할 자원 종류 결정
                $type_array = ['gold', 'food', 'horse', 'lumber', 'iron'];
                shuffle($type_array);
                $type = reset($type_array);

                // 큐빅 지불하고 남은 양을 자원으로 환산
                $v['pay_reso_amount'] = $_M['QBIG_TRANSRATE_VALUE'][$type] * ($item_price - $qbig);
                $v['pay_reso_amount'] = (INT)(floor($v['pay_reso_amount'] / 100)) * 100;

                $pay_type = 'Q';
            }

            $v['pay_type'] = $pay_type;
            $v['pay_cash_amount'] = $qbig;
            $v['m_item_pk'] = $m_item_pk;
            $v['pay_reso_type'] = $type;
        }

        //기존리스트 삭제
        $this->PgGame->query('DELETE FROM market WHERE posi_pk = $1', [$_posi_pk]);

        // DB로 저장
        for($i = 0; $i < COUNT($create_list); $i++) {
            $this->PgGame->query('INSERT INTO market (posi_pk, sale_type, m_item_pk, sale_amount, pay_type, pay_reso_type, pay_reso_amount, pay_cash_amount, register_dt) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, now())', [$_posi_pk, $create_list[$i]['sale_type'], $create_list[$i]['m_item_pk'], (INT)$create_list[$i]['sale_amount'], $create_list[$i]['pay_type'], $create_list[$i]['pay_reso_type'], (INT)$create_list[$i]['pay_reso_amount'], (INT)$create_list[$i]['pay_cash_amount']]);
        }
        $this->PgGame->query('UPDATE territory SET market_sale_up_dt = now() WHERE posi_pk = $1', [$_posi_pk]);

        return ['m_item_pk' => $_m_item_pk];
    }

    function useStatusTruceItem($_posi_pk, $_m_item_pk, $_time = 43200): array // 12시간
    {
        global $i18n;
        $this->PgGame->query('SELECT status_truce FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        $status_truce = $this->PgGame->fetchOne();
        if ($status_truce == 'Y') {
            return ['err' => $i18n->t('msg_item_use_failed_truce_status')]; // 보호모드 중에는 사용할 수 없습니다.
        }

        $this->PgGame->query('SELECT date_part(\'epoch\', truce_up_dt)::integer as truce_up_dt FROM lord WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);
        $truce_up_dt = $this->PgGame->fetchOne();
        if ($truce_up_dt) {
            $remain_dt = time() - $truce_up_dt;
            if ( $remain_dt < $_time) {
                return ['err' => $i18n->t('msg_item_use_failed_remain_time', [Useful::readableTime($_time - $remain_dt)])]; // TODO 문구 교체 필요
            }
        }

        //전체 영지를 체크하기 위해 모든 영지의 posi_pk를 알아옴.
        $this->PgGame->query('SELECT posi_pk FROM position WHERE lord_pk = $1 AND type = $2', [$this->Session->lord['lord_pk'], 'T']);
        $this->PgGame->fetchAll();
        //배열로 저장 - 문자열이기 때문에 ''로 감싸줌.
        $all_posi_pk = [];
        foreach ($this->PgGame->rows as $row) {
            $all_posi_pk[] = "'{$row['posi_pk']}'";
        }
        $all_posi_pk_str = implode(',', $all_posi_pk);

        // 다른 군주가  공격 부대 출발 했을 경우
        $this->PgGame->query("SELECT dst_posi_pk FROM troop WHERE src_lord_pk <> $4 
AND (dst_posi_pk IN (SELECT valley_posi_pk FROM territory_valley WHERE posi_pk IN ($all_posi_pk_str)) or dst_posi_pk IN ($all_posi_pk_str))
AND status IN ($1, $2) AND cmd_type = $3", ['M', 'B', 'A', NPC_TROOP_LORD_PK]);
        if ($this->PgGame->fetch()) {
            return ['err' => $i18n->t('msg_move_territory_already_attack')]; // 이미 공격 부대가 출발하여 아이템 사용이 불가합니다.
        }

        // 내 영지 중심
        $this->PgGame->query("select src_posi_pk from troop where src_posi_pk IN ($all_posi_pk_str) 
AND (status IN ($2, $3) OR (status = $4 AND dst_posi_pk NOT IN (SELECT valley_posi_pk FROM territory_valley WHERE posi_pk = $1)))", [$_posi_pk, 'M', 'B', 'C']);
        if ($this->PgGame->fetch()) {
            return ['err' => $i18n->t('msg_move_territory_already_troop')]; // 출병중인 부대가 있어서 아이템 사용 불가합니다.
        }

        $this->classTerritory();
        $this->Territory->setTruceStatus($_posi_pk, 'I', $_m_item_pk);

        return ['m_item_pk' => $_m_item_pk];
    }

    function finishTruceItem($_posi_pk): void
    {
        $this->PgGame->query('UPDATE territory SET status_truce = $2, truce_up_dt = now() WHERE posi_pk = $1', [$_posi_pk, 'N']);
    }

    // TODO 깃발명 기능 사용안함. 번역 제외
    function useChangeFlagItem($_lord_pk, $_posi_pk, $_flag): array
    {
        global $i18n;
        $this->PgGame->query('SELECT date_part(\'epoch\', flag_up_dt)::integer FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $last_up_dt = $this->PgGame->FetchOne();
        $remain_dt = time() - $last_up_dt;
        if ( $remain_dt < 86400) {
            return ['err' => $i18n->t('msg_item_use_failed_remain_time', [Useful::readableTime(86400 - $remain_dt)])];
        }
        // 문자열 입력 검사
        if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z]/u", $_flag) > 0) {
            return ['err' => '깃발명은 오로지 한글, 영문, 숫자만 사용해야합니다.'];
        }

        if (iconv_strlen($_flag, 'UTF-8') < 1){
            return ['err' => '변경할 깃발명을 입력해 주십시오.'];
        } else if (iconv_strlen($_flag, 'UTF-8') > 4) {
            return ['err' => '깃발명은 최대 4글자까지 사용할 수 있습니다.'];
        }

        // 금지어 검사
        $ret = Useful::forbiddenWord($_flag);
        if (!$ret['ret']) {
            return ['err' => '입력하신 깃발명의 ['.$ret['str'].']은(는) 사용할 수 없습니다.'];
        }

        // 예약어 검사
        if(!Useful::reservedWord($_flag)) {
            return ['err' => '입력하신 깃발명은 사용할 수 없습니다.['.$_flag.']'];
        }
        // 트랜잭션
        try {
            $this->PgGame->begin();

            $this->PgGame->query('SELECT flag FROM territory WHERE posi_pk IN (SELECT posi_pk FROM position WHERE lord_pk = $1) LIMIT 1', [$_lord_pk]);
            if (!$this->PgGame->fetch()) {
                throw new Exception('lord don`t have any position');
            }

            $prev_flag = $this->PgGame->row['flag'];

            $r = $this->PgGame->query('UPDATE territory SET flag = $2 WHERE posi_pk in (SELECT posi_pk FROM position WHERE lord_pk = $1)', [$_lord_pk, $_flag]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('territory table flag update error');
            }

            $r = $this->PgGame->query('UPDATE lord SET flag_up_dt = now() WHERE lord_pk = $1', [$_lord_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('lord table flag_up_dt update error');
            }

            $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE lord_pk = $1', [$_lord_pk]);


            $this->PgGame->commit();
        } catch (Exception $e){
            // 실패, sq 무시
            $this->PgGame->rollback();

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';posi_pk['.$_posi_pk.'];hero_pk['.$_hero_pk.']');

            // 에러 메시지 추가
            return ['err' => '깃발명 변경 아이템 사용중 에러가 발생 했습니다.'];
        }

        $this->Session->sqAppend('TERR', ['flag' => $_flag]);

        $this->classLog();
        $this->Log->setChangeName($_lord_pk, $_posi_pk, 'change_flag_name', $prev_flag.';'.$_flag.';');

        return ['flag' => $_flag];
    }

    function useChangeLordNameItem($_lord_pk, $_m_item_pk, $_lord_name): array
    {
        global $i18n;
        // 군주명 마지막 업데이트시간 검사(604800 : 일주일)
        $this->PgGame->query('SELECT lord_name, date_part(\'epoch\', lord_name_up_dt)::integer as lord_name_up_dt FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        if (!$this->PgGame->fetch()) {
            return ['err' => 'Error Occurred. [20018]']; // 해당하는 군주를 찾을 수 없습니다.
        }

        $prev_lord_name = $this->PgGame->row['lord_name'];
        $last_up_dt = $this->PgGame->row['lord_name_up_dt'];

        $remain_dt = time() - $last_up_dt;

        if ( $remain_dt < 604800) {
            return ['err' => $i18n->t('msg_item_use_failed_remain_time', [Useful::readableTime(604800 - $remain_dt)])]; // 제한시간 남음
        }

        // 문자열 입력 검사
        if (preg_match("/[^\x{AC00}-\x{D7A3}0-9a-zA-Z]/u", $_lord_name) > 0) {
            return ['err' => $i18n->t('msg_lord_name_confine_language')]; // 군주명은 오로지 한글, 영문, 숫자만 가능합니다.
        }

        if (iconv_strlen($_lord_name, 'UTF-8') < 1) {
            return ['err' => $i18n->t('msg_change_lord_name_empty')]; // 변경할 군주명을 입력해 주십시오.
        } else if (iconv_strlen($_lord_name, 'UTF-8') < 2) {
            return ['err' => $i18n->t('msg_change_lord_name_min')]; // 군주명은 최소 2글자를 사용해야합니다.
        } else if (iconv_strlen($_lord_name, 'UTF-8') > 6) {
            return ['err' => $i18n->t('msg_change_lord_name_max')]; // 군주명은 최대 6글자까지 사용할 수 있습니다.
        }

        // 금지어 검사
        $ret = Useful::forbiddenWord($_lord_name);
        if (!$ret['ret']) {
            return ['err' => $i18n->t('msg_unavailable_lord_name', [$ret['str']])]; // 입력하신 군주명의 [{{1}}]은(는) 사용할 수 없습니다.
        }

        // 예약어 검사
        if(!Useful::reservedWord($_lord_name)) {
            return ['err' => $i18n->t('msg_unavailable_lord_name', [$_lord_name])]; // 입력하신 군주명의 [{{1}}]은(는) 사용할 수 없습니다.
        }

        // 군주명 중복 검사
        $this->PgGame->query('SELECT count(lord_pk) FROM lord WHERE lord_name_lower = lower($1)', [$_lord_name]);
        if ($this->PgGame->fetchOne() > 0) {
            return ['err' => $i18n->t('msg_already_lord_name')]; // 이미 사용 중인 군주명 입니다.
        }

        // 군주명 업데이트
        $this->PgGame->query('UPDATE lord SET lord_name = $2, lord_name_lower = lower($3), lord_name_up_dt = now() WHERE lord_pk = $1', [$_lord_pk, $_lord_name, $_lord_name]);

        $this->PgGame->query('UPDATE position SET last_update_dt = now() WHERE lord_pk = $1', [$_lord_pk]);

        // 영웅거래
        $this->PgGame->query('UPDATE hero_trade SET lord_name = $2 WHERE lord_pk = $1', [$_lord_pk, $_lord_name]);
        // 동맹
        $this->PgGame->query('UPDATE alliance SET lord_name = $2 WHERE master_lord_pk = $1', [$_lord_pk, $_lord_name]);

        $this->Session->setLoginReload();
        $this->Session->sqAppend('LORD', ['lord_name_up_dt' => time()]);

        $this->classChat();
        $this->Chat->updateChatSession($this->Session->lord, 'lord_name', $_lord_name);

        $this->classLog();
        $this->Log->setChangeName($_lord_pk, null, 'change_lord_name', $prev_lord_name.';'.$_lord_name.';');

        $this->classQuest();
        $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'lord_name_change']);

        return ['lord_name' => $_lord_name];
    }

    function useHeroSlotExpansionItem(): array
    {
        global $i18n;
        if ($this->Session->lord['num_slot_guest_hero'] >= NUM_SLOT_GUEST_HERO) {
            return ['err' => $i18n->t('msg_hero_slot_max')]; // 더 이상 영웅 슬롯을 확장할 수 없습니다.
        }
        $num_slot_guest_hero = $this->Session->lord['num_slot_guest_hero'] + 10;
        $this->PgGame->query('UPDATE lord SET num_slot_guest_hero = $2 WHERE lord_pk = $1', [$this->Session->lord['lord_pk'], $num_slot_guest_hero]);

        $this->Session->setLoginReload();

        return ['num_slot_guest_hero' => $num_slot_guest_hero];
    }

    function useOfficerCountExpansionItem($_lord_pk): array
    {
        global $i18n;
        $this->PgGame->query('SELECT m_offi_pk FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $m_offi_pk = $this->PgGame->fetchOne();
        if ($m_offi_pk >= OFFICER_COUNT_MAX) {
            return ['err' => $i18n->t('msg_hero_office_max')]; // 더 이상 관직 추가가 불가능 합니다.<br />(관직 추가는 최대 10개까지 가능)
        }

        $m_offi_pk = !$m_offi_pk ? 110121:$m_offi_pk + 1;
        $r = $this->PgGame->query('UPDATE lord SET m_offi_pk = $2 WHERE lord_pk = $1', [$_lord_pk, $m_offi_pk]);
        if (!$r) {
            return ['err' => 'Error Occurred. [20019]']; // 관직 추가에 실패 하였습니다.
        }

        $officer_count = $m_offi_pk - 110120;
        $this->Session->sqAppend('LORD', ['m_offi_pk' => $m_offi_pk]);

        return ['officer_count' => $officer_count];
    }

    function useIncrLoyaltyItem($_posi_pk): array
    {
        global $i18n;
        $this->PgGame->query('SELECT loyalty, date_part(\'epoch\', loyalty_item_up_dt)::integer as loyalty_item_up_dt FROM territory WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();

        if ($this->PgGame->row['loyalty'] >= 100) {
            return ['err' => $i18n->t('msg_population_loyalty_max')]; // 더 이상 민심을 올릴 수 없습니다.
        }

        $remain_dt = time() - $this->PgGame->row['loyalty_item_up_dt'];

        if ( $remain_dt < 3600) {
            return ['err' => $i18n->t('msg_item_use_failed_remain_time', [Useful::readableTime(3600 - $remain_dt)])];
        }

        $loyalty = $this->PgGame->row['loyalty'] + 50;
        if($loyalty > 100) {
            $loyalty = 100;
        }


        // 저장
        $this->classGoldPop();
        $this->GoldPop->save($_posi_pk);

        $this->PgGame->query('UPDATE territory SET loyalty = $2, loyalty_item_up_dt = now() WHERE posi_pk = $1', [$_posi_pk, $loyalty]);

        // 갱신
        $this->GoldPop->get($_posi_pk);

        $this->Session->sqAppend('TERR', ['loyalty' => $loyalty]);

        return [];
    }

    function useBeginnerArmySupportItem($_posi_pk, $_item_pk, $_item_cnt = 1): array
    {
        global $i18n, $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);
        $worker = 0;
        $infantry = 0;
        $scout = 0;
        $horseman = 0;
        $archer = 0;
        for ($i = 0; $i < $_item_cnt; $i++) {
            $worker += rand(30, 50);   //민병
            $infantry += rand(30, 50); //보병
            $scout += rand(30, 50);	   //정찰병
            $horseman += rand(30, 50); //기병
            $archer += rand(30, 50);   //궁병
        }

        $this->classArmy();
        if ($this->Army->getPositionArmy($_posi_pk) + $worker + $infantry + $scout + $horseman + $archer > TROOP_ARMY_LIMIT) {
            return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])];
        }

        $r_arr = [];
        $r_arr['410001'] = $worker;
        $r_arr['410002'] = $infantry;
        $r_arr['410005'] = $scout;
        $r_arr['410007'] = $horseman;
        $r_arr['410006'] = $archer;

        $query_params = [$_posi_pk];
        $this->PgGame->query('SELECT worker, infantry, scout, horseman, archer FROM army WHERE posi_pk = $1', $query_params);
        $this->PgGame->fetch();
        $curr_row = $this->PgGame->row;
        $query_params = [$_posi_pk, $worker, $infantry, $scout, $horseman, $archer];
        $this->PgGame->query('UPDATE army SET worker = worker+$2, infantry=infantry+$3, scout=scout+$4, horseman=horseman+$5, archer=archer+$6, last_update_dt=now() WHERE posi_pk = $1', $query_params);

        // 로그
        $this->classLog();
        $log_description = '';
        foreach ($r_arr as $_m_army_pk => $_value) {
            $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
        }
        $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);

        $this->Army->get($_posi_pk);

        return ['package_type' => 'army', 'm_item_pk' => $_item_pk, 'army' => $r_arr];
    }

    function useLowArmySupportItem($_posi_pk, $_item_pk, $_item_cnt = 1): array
    {
        global $i18n, $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);
        $worker = 0;
        $infantry = 0;
        $pikeman = 0;
        $spearman = 0;
        for ($i = 0; $i < $_item_cnt; $i++) {
            $worker += rand(30, 50);   //민병
            $infantry += rand(30, 50); //보병
            $pikeman += rand(30, 50);  //극병
            $spearman += rand(30, 50); //창병
        }

        $this->classArmy();
        if ($this->Army->getPositionArmy($_posi_pk) + $worker + $infantry + $pikeman + $spearman > TROOP_ARMY_LIMIT) {
            return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])];
        }

        $r_arr = [];
        $r_arr['410001'] = $worker;
        $r_arr['410002'] = $infantry;
        $r_arr['410003'] = $pikeman;
        $r_arr['410004'] = $spearman;

        $query_params = [$_posi_pk];
        $this->PgGame->query('SELECT worker, infantry, pikeman, spearman FROM army WHERE posi_pk = $1', $query_params);
        $this->PgGame->fetch();
        $curr_row = $this->PgGame->row;
        $query_params = [$_posi_pk, $worker, $infantry, $pikeman, $spearman];
        $this->PgGame->query('UPDATE army SET worker = worker+$2, infantry=infantry+$3, pikeman=pikeman+$4, spearman=spearman+$5, last_update_dt=now() WHERE posi_pk = $1', $query_params);

        // 로그
        $this->classLog();
        $log_description = '';
        foreach ($r_arr as $_m_army_pk => $_value) {
            $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
        }
        $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);

        $this->Army->get($_posi_pk);

        return ['package_type' => 'army', 'm_item_pk' => $_item_pk, 'army' => $r_arr];
    }

    function useMiddleArmySupportItem($_posi_pk, $_item_pk, $_item_cnt = 1): array
    {
        global $i18n, $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);
        $armed_infantry = 0;
        $armed_horseman = 0;
        $bowman = 0;
        for ($i = 0; $i < $_item_cnt; $i++) {
            $armed_infantry += rand(30,50); //중갑보병
            $armed_horseman += rand(30,50); //중갑기병
            $bowman += rand(30,50);         //노궁병
        }

        $this->classArmy();
        if ($this->Army->getPositionArmy($_posi_pk) + $armed_infantry + $armed_horseman + $bowman > TROOP_ARMY_LIMIT) {
            return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])];
        }

        $r_arr = [];
        $r_arr['410009'] = $armed_infantry;
        $r_arr['410010'] = $armed_horseman;
        $r_arr['410011'] = $bowman;

        $query_params = [$_posi_pk];
        $this->PgGame->query('SELECT armed_infantry, armed_horseman, bowman FROM army WHERE posi_pk = $1', $query_params);
        $this->PgGame->fetch();
        $curr_row = $this->PgGame->row;
        $query_params = [$_posi_pk, $armed_infantry, $armed_horseman, $bowman];
        $this->PgGame->query('UPDATE army SET armed_infantry = armed_infantry+$2, armed_horseman=armed_horseman+$3, bowman=bowman+$4, last_update_dt=now() WHERE posi_pk = $1', $query_params);

        // 로그
        $this->classLog();
        $log_description = '';
        foreach ($r_arr as $_m_army_pk => $_value) {
            $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
        }
        $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', 'armed_infantry[curr['.$curr_row['armed_infantry'].'];update['.$armed_infantry.'];];armed_horseman[curr['.$curr_row['armed_horseman'].'];update['.$armed_horseman.'];];bowman[curr['.$curr_row['bowman'].'];update['.$bowman.'];];');

        $this->Army->get($_posi_pk);

        return ['package_type' => 'army', 'm_item_pk' => $_item_pk, 'army' => $r_arr];
    }

    function useAdvancedArmySupportItem($_posi_pk, $_item_pk, $_item_cnt = 1): array
    {
        global $i18n, $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);
        $battering_ram = 0;
        $catapult = 0;
        $adv_catapult = 0;
        for ($i = 0; $i < $_item_cnt; $i++) {
            $battering_ram += rand(30, 50); //공성추
            $catapult += rand(30, 50);      //투석기
            $adv_catapult += rand(30, 50);  //팔륜누차
        }

        $this->classArmy();
        if ($this->Army->getPositionArmy($_posi_pk) + $battering_ram + $catapult + $adv_catapult > TROOP_ARMY_LIMIT) {
            return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])];
        }

        $r_arr = [];
        $r_arr['410012'] = $battering_ram;
        $r_arr['410013'] = $catapult;
        $r_arr['410014'] = $adv_catapult;

        $this->PgGame->query('SELECT battering_ram, catapult, adv_catapult FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $curr_row = $this->PgGame->row;
        $this->PgGame->query('UPDATE army SET battering_ram = battering_ram+$2, catapult=catapult+$3, adv_catapult=adv_catapult+$4, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk, $battering_ram, $catapult, $adv_catapult]);

        // 로그
        $this->classLog();
        $log_description = '';
        foreach ($r_arr as $_m_army_pk => $_value) {
            $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
        }
        $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);

        $this->Army->get($_posi_pk);

        return ['package_type' => 'army', 'm_item_pk' => $_item_pk, 'army' => $r_arr];
    }

    function useArmyPackageItem($_posi_pk, $_item_pk, $_item_cnt = 1): array
    {
        global $i18n, $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);
        $r_arr = [];
        $r_arr['410001'] = 500 * $_item_cnt;
        $r_arr['410002'] = 500 * $_item_cnt;
        $r_arr['410003'] = 500 * $_item_cnt;
        $r_arr['410004'] = 500 * $_item_cnt;
        $r_arr['410005'] = 500 * $_item_cnt;
        $r_arr['410006'] = 500 * $_item_cnt;
        $r_arr['410009'] = 500 * $_item_cnt;
        $r_arr['410011'] = 500 * $_item_cnt;

        $this->classArmy();
        if ($this->Army->getPositionArmy($_posi_pk) + 4000 > TROOP_ARMY_LIMIT) {
            return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])];
        }

        $this->PgGame->query('SELECT worker, infantry, pikeman, spearman, scout, archer, armed_infantry, bowman FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $curr_row = $this->PgGame->row;
        $r = $this->PgGame->query('UPDATE army SET worker = worker + 500, infantry = infantry + 500, pikeman = pikeman + 500, spearman = spearman + 500, scout = scout + 500, archer = archer + 500, armed_infantry = armed_infantry + 500, bowman = bowman + 500, last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk]);
        if (!$r) {
            return ['err' => 'Error Occurred. [20020]'];
        }

        // 로그
        $this->classLog();
        $log_description = '';
        foreach ($r_arr as $_m_army_pk => $_value) {
            $log_description .= "{$_m_army_pk}[curr[{$curr_row[$_M['ARMY'][$_m_army_pk]['code']]}];update[$_value];];";
        }
        $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);

        $this->Army->get($_posi_pk);

        return ['package_type' => 'army', 'm_item_pk' => $_item_pk, 'army' => $r_arr];
    }

    function useArmyPack($_posi_pk, $_item_pk, $_item_cnt = 1): array
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['army', 'item']);

        $r_arr = [];

        // 추가될 경우 수정 할 부분 시작
        if ($_item_pk == 500547) { // 병력팩1
            $r_arr['410002'] = 500 * $_item_cnt;
            $r_arr['410006'] = 500 * $_item_cnt;
        } else if ($_item_pk == 500548) { // 병력팩2
            $r_arr['410002'] = 1000 * $_item_cnt;
            $r_arr['410004'] = 1000 * $_item_cnt;
            $r_arr['410006'] = 1000 * $_item_cnt;
        } else if ($_item_pk == 500549) { // 병력팩3
            $r_arr['410007'] = 5000 * $_item_cnt;
        } else if ($_item_pk == 500691) {  // 보병 병력지원(300)
            $r_arr['410002'] = 300 * $_item_cnt;
        }
        // 추가될 경우 수정 할 부분 끝

        $add_army = 0;
        $addSelector = '';
        foreach($r_arr AS $k => $v) {
            if ($add_army > 0)
            {
                $addSelector .= ', ';
            }
            $add_army += $v;
            $addSelector .= $_M['ARMY'][$k]['code'];
        }

        $this->classArmy();
        if ($this->Army->getPositionArmy($_posi_pk) + $add_army > TROOP_ARMY_LIMIT) {
            return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])];
        }

        // 현재 병력 알아오기
        $this->PgGame->query('SELECT '.$addSelector.' FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetch();
        $curr_row = $this->PgGame->row;

        // 업데이트
        $addSql = '';
        $log_description = '';
        $i = 0;
        foreach($r_arr AS $k => $v) {
            if ($i > 0) {
                $addSql .= ', ';
            }
            $addSql .= $_M['ARMY'][$k]['code'].' = '.$_M['ARMY'][$k]['code'].' + '.$v;
            $log_description .= "{$k}[curr[{$curr_row[$_M['ARMY'][$k]['code']]}];update[$v];];";
            $i++;
        }

        $r = $this->PgGame->query('UPDATE army SET '.$addSql.' WHERE posi_pk = $1', [$_posi_pk]);
        if (!$r) {
            return ['err' => 'Error Occurred. [20021]'];
        }


        // 로그
        $this->classLog();
        $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', $log_description);

        $this->Army->get($_posi_pk);

        return ['package_type' => 'army', 'm_item_pk' => $_item_pk, 'army' => $r_arr];
    }

    function useArmyQuestItem($_posi_pk, $_m_item_pk, $_item_cnt = 1): array
    {
        global $i18n, $_M_ARMY_C, $NsGlobal;
        $NsGlobal->requireMasterData(['army']);
        $r_arr = [];
        $army_cnt = 0;
        switch ($_m_item_pk) {
            case 500448 :
                $r_arr['410001'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'worker';
                break;
            case 500449 :
                $r_arr['410002'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'infantry';
                break;
            case 500450 :
                $r_arr['410003'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'pikeman';
                break;
            case 500451 :
                $r_arr['410005'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'scout';
                break;
            case 500452 :
                $r_arr['410004'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'spearman';
                break;
            case 500453 :
                $r_arr['410009'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'armed_infantry';
                break;
            case 500454 :
                $r_arr['410006'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'archer';
                break;
            case 500455 :
                $r_arr['410007'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'horseman';
                break;
            case 500456 :
                $r_arr['410010'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'armed_horseman';
                break;
            case 500457 :
                $r_arr['410008'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'transporter';
                break;
            case 500458 :
                $r_arr['410011'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'bowman';
                break;
            case 500459 :
                $r_arr['410012'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'battering_ram';
                break;
            case 500460 :
                $r_arr['410013'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'catapult';
                break;
            case 500461 :
                $r_arr['410014'] = 20000 * $_item_cnt;
                $army_cnt = 20000 * $_item_cnt;
                $code = 'adv_catapult';
                break;
            case 500465 :
                $r_arr['410001'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'worker';
                break;
            case 500466 :
                $r_arr['410002'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'infantry';
                break;
            case 500467 :
                $r_arr['410003'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'pikeman';
                break;
            case 500468 :
                $r_arr['410005'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'scout';
                break;
            case 500469 :
                $r_arr['410004'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'spearman';
                break;
            case 500470 :
                $r_arr['410009'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'armed_infantry';
                break;
            case 500471 :
                $r_arr['410006'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'archer';
                break;
            case 500472 :
                $r_arr['410007'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'horseman';
                break;
            case 500473 :
                $r_arr['410010'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'armed_horseman';
                break;
            case 500474 :
                $r_arr['410008'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'transporter';
                break;
            case 500475 :
                $r_arr['410011'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'bowman';
                break;
            case 500476 :
                $r_arr['410012'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'battering_ram';
                break;
            case 500477 :
                $r_arr['410013'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'catapult';
                break;
            case 500478 :
                $r_arr['410014'] = 100000 * $_item_cnt;
                $army_cnt = 100000 * $_item_cnt;
                $code = 'adv_catapult';
                break;
        }

        $this->classArmy();
        if ($this->Army->getPositionArmy($_posi_pk) + $army_cnt > TROOP_ARMY_LIMIT) {
            return ['err' => $i18n->t('msg_item_use_army_limit', [TROOP_ARMY_LIMIT])];
        }

        $this->PgGame->query('SELECT ' . $code . ' FROM army WHERE posi_pk = $1', [$_posi_pk]);
        $curr_row = $this->PgGame->fetchOne();

        $r = $this->PgGame->query('UPDATE army SET '.$code.' = '.$code .' + '.$army_cnt.', last_update_dt=now() WHERE posi_pk = $1', [$_posi_pk]);
        if (!$r) {
            return ['err' => 'Error Occurred. [20022]'];
        }

        // 로그
        $this->classLog();
        $this->Log->setArmy(null, $_posi_pk, 'increase_army_item', "{$_M_ARMY_C[$code]['m_army_pk']}[curr[$curr_row];update[$army_cnt];];");

        $this->Army->get($_posi_pk);

        return ['package_type' => 'army', 'm_item_pk' => $_m_item_pk, 'army' => $r_arr];
    }

    function useAccomplishmentItem($_item_pk, $_lord_pk, $_item_cnt = 1): array
    {
        $first_card = 0;
        $second_card = 0;
        for ($i = 0; $i < $_item_cnt; $i++) {
            $item_cnt = rand(3, 4);
            $first_card += rand(0, $item_cnt);
            $second_card += $item_cnt - $first_card;
        }

        $arr = [];
        if ($_item_pk == 500064) {
            $arr['500007']['item_count'] = $first_card;
            $arr['500008']['item_count'] = $second_card;
        } else if ($_item_pk == 500066) {
            $arr['500009']['item_count'] = $first_card;
            $arr['500010']['item_count'] = $second_card;
        } else if ($_item_pk == 500068) {
            $arr['500011']['item_count'] = $first_card;
            $arr['500012']['item_count'] = $second_card;
        }

        $this->setGiveItem($arr, $_lord_pk, true, 'useAccomplishmentItem');

        return ['package_type' => 'item', 'm_item_pk' => $_item_pk, 'item' => $arr];
    }

    function usePackageItem($_item_pk, $_lord_pk, $_item_cnt = 1): array
    {
        $arr = [];
        if ($_item_pk == 500067) { // 부국 패키지
            $arr['500023']['item_count'] = 2 * $_item_cnt;	// 격문
            $arr['500024']['item_count'] = 2 * $_item_cnt;	// 쟁기
            $arr['500025']['item_count'] = 2 * $_item_cnt;	// 건초
            $arr['500026']['item_count'] = 2 * $_item_cnt;	// 도끼
            $arr['500027']['item_count'] = 2 * $_item_cnt;	// 용광로
            $arr['500028']['item_count'] = 2 * $_item_cnt;	// 상인들의 지원
            $arr['500033']['item_count'] = 2 * $_item_cnt;	// 난민수용
            $arr['500036']['item_count'] = 5 * $_item_cnt;	// 일반 지원령
        } else if ($_item_pk == 500073) {	// 강병 패키지
            $arr['500044']['item_count'] = 2 * $_item_cnt;	// 대형 군기
            $arr['500045']['item_count'] = 2 * $_item_cnt;	// 신나는 군악
            $arr['500046']['item_count'] = 2 * $_item_cnt;	// 큰 방패
            $arr['500047']['item_count'] = 2 * $_item_cnt;	// 상비약
            $arr['500051']['item_count'] = 3 * $_item_cnt;	// 첩보자금
            $arr['500057']['item_count'] = 2 * $_item_cnt;	// 지혈산
            $arr['500058']['item_count'] = 2 * $_item_cnt;	// 금창약
        } else if ($_item_pk == 500069) {	// 초급 군주용 보물상자
            //$arr['500061']['item_count'] = 1;	// 행운의 주화 (제거)
            $arr['500114']['item_count'] = 3 * $_item_cnt;	// 기술주머니 3개
            $arr['500104']['item_count'] = 1 * $_item_cnt;	// 탐색강화
            $arr['500024']['item_count'] = 2 * $_item_cnt;	// 쟁기
            $arr['500025']['item_count'] = 2 * $_item_cnt;	// 건초
            $arr['500026']['item_count'] = 2 * $_item_cnt;	// 도끼
            $arr['500027']['item_count'] = 2 * $_item_cnt;	// 용광로
            $arr['500028']['item_count'] = 2 * $_item_cnt;	// 상인들의 지원
            $arr['500036']['item_count'] = 5 * $_item_cnt;	// 일반 지원령
        } else if ($_item_pk == 500072) {	// 중급 군주용 보물상자
            $arr['500015']['item_count'] = 1 * $_item_cnt;	// 평화서약
            $arr['500037']['item_count'] = 2 * $_item_cnt;	// 긴급 방비령
            $arr['500040']['item_count'] = 1 * $_item_cnt;	// 긴급 충원령
            $arr['500045']['item_count'] = 1 * $_item_cnt;	// 신나는 군악
            $arr['500046']['item_count'] = 1 * $_item_cnt;	// 큰 방패
            $arr['500047']['item_count'] = 1 * $_item_cnt;	// 상비약
            $arr['500051']['item_count'] = 2 * $_item_cnt;	// 첩보자금
            $arr['500057']['item_count'] = 2 * $_item_cnt;	// 지혈산
        } else if ($_item_pk == 500074) {	// 고급 군주용 보물상자
            $arr['500004']['item_count'] = 1 * $_item_cnt;	// 의천검
            $arr['500003']['item_count'] = 1 * $_item_cnt;	// 손자병법
            $arr['500022']['item_count'] = 1 * $_item_cnt;	// 기문오행술
            $arr['500023']['item_count'] = 1 * $_item_cnt;	// 격문
            $arr['500044']['item_count'] = 2 * $_item_cnt;	// 대형 군기
            $arr['500045']['item_count'] = 2 * $_item_cnt;	// 신나는 군악
            $arr['500046']['item_count'] = 2 * $_item_cnt;	// 큰 방패
            $arr['500059']['item_count'] = 5 * $_item_cnt;	// 속명단
        } else if ($_item_pk == 500110) {	// 보따리(적)
            $arr['500061']['item_count'] = 1 * $_item_cnt;	// 행운의 주화
            $arr['500102']['item_count'] = 1 * $_item_cnt;	// 건설허가서
            $arr['500104']['item_count'] = 1 * $_item_cnt;	// 탐색강화
            $arr['500036']['item_count'] = 1 * $_item_cnt;	// 일반지원령
            $arr['500087']['item_count'] = 1 * $_item_cnt;	// 황금(10만)
        } else if ($_item_pk == 500111) {	// 보따리(청)
            $arr['500061']['item_count'] = 1 * $_item_cnt;	// 행운의 주화
            $arr['500102']['item_count'] = 1 * $_item_cnt;	// 건설허가서
            $arr['500036']['item_count'] = 1 * $_item_cnt;	// 일반지원령
            $arr['500087']['item_count'] = 1 * $_item_cnt;	// 황금(10만)
        } else if ($_item_pk == 500112) {	// 보따리(흑)
            $arr['500061']['item_count'] = 1 * $_item_cnt;	// 행운의 주화
            $arr['500036']['item_count'] = 1 * $_item_cnt;	// 일반지원령exit
            $arr['500086']['item_count'] = 1 * $_item_cnt;	// 황금(1만)
        } else if ($_item_pk == 500124) {	// 우수 영웅 패키지(x5)
            $arr['500103']['item_count'] = 5 * $_item_cnt;	// 우수 영웅
        } else if ($_item_pk == 500113) {	// 2011년 토끼
            $arr['500126']['item_count'] = 1 * $_item_cnt;	// 10큐빅
            $arr['500086']['item_count'] = 1 * $_item_cnt;	// 황금(1만)
            $arr['500085']['item_count'] = 1 * $_item_cnt;	// 영석 1개
            $arr['500114']['item_count'] = 1 * $_item_cnt;	// 기술주머니 1개
            for ($i = 0; $i < $_item_cnt; $i++) {
                $range_random_key = rand(1, 100000); // 십만
                if ($range_random_key <= 1000) { // 1%
                    $m_item_pk = 500042; // '특급 지원령'
                } else if ($range_random_key <= 3000) { // 2%
                    $m_item_pk = 500041; // '일급 지원령'
                } else if ($range_random_key <= 10000) { // 7%
                    $m_item_pk = 500039; // '이급 지원령'
                } else if ($range_random_key <= 25000) { // 15%
                    $m_item_pk = 500038; // '삼급 지원령'
                } else { // 75%
                    $m_item_pk = 500036; // '일반 지원령'
                }
                if (isset($arr[$m_item_pk])) {
                    $arr[$m_item_pk]['item_count'] += 1; // 시간단축 아이템 1종
                } else {
                    $arr[$m_item_pk]['item_count'] = 1; // 시간단축 아이템 1종
                }
            }
        } else if ($_item_pk == 500131) {	// 기술업데이트패키지
            $arr['500114']['item_count'] = 3 * $_item_cnt;	// 기술주머니 3개
            $arr['500115']['item_count'] = 2 * $_item_cnt;	// 우수기술상자 2개
            $arr['500118']['item_count'] = 5 * $_item_cnt;	// 우수기술열쇠 5개
            $arr['500116']['item_count'] = 1 * $_item_cnt;	// 고급기술상자 1개
            $arr['500119']['item_count'] = 2 * $_item_cnt;	// 고급기술열쇠2개
        } else if ($_item_pk == 500132) {	// 쏜다이벤트패키지
            $arr['500103']['item_count'] = 3 * $_item_cnt;	// 우수 영웅 즉시 영입
            $arr['500015']['item_count'] = 3 * $_item_cnt;	// 평화서약
            $arr['500102']['item_count'] = 3 * $_item_cnt;	// 건설허가서
        } else if ($_item_pk == 500136) {	// 영자의마음패키지
            $arr['500128']['item_count'] = 1 * $_item_cnt;	// 큐빅패키지(100)
            $arr['500104']['item_count'] = 5 * $_item_cnt;	// 탐색강화
            $arr['500061']['item_count'] = 5 * $_item_cnt;	// 행운의 주화
            $arr['500017']['item_count'] = 1 * $_item_cnt;	// 영지이동(랜덤)
            $arr['500055']['item_count'] = 1 * $_item_cnt;	// 삼고초려
            $arr['500003']['item_count'] = 1 * $_item_cnt;	// 손자병법
            $arr['500004']['item_count'] = 1 * $_item_cnt;	// 의천검
            $arr['500005']['item_count'] = 1 * $_item_cnt;	// 맹덕신서
        } else if ($_item_pk == 500134) { // 정착 지원 상자
            $arr['500086']['item_count'] = 10 * $_item_cnt;  // 황금 1만
            $arr['500088']['item_count'] = 10 * $_item_cnt;  // 식량 1만
            $arr['500090']['item_count'] = 10 * $_item_cnt;  // 우마 1만
            $arr['500092']['item_count'] = 10 * $_item_cnt;  // 목재 1만
            $arr['500094']['item_count'] = 10 * $_item_cnt;  // 철강 1만
            $arr['500102']['item_count'] = 1 * $_item_cnt;   // 건설허가서
            $arr['500083']['item_count'] = 1 * $_item_cnt;   // 태수의 인장
            $arr['500084']['item_count'] = 1 * $_item_cnt;   // 황제의 조서
        } else if ($_item_pk == 500137) { // 우수기술열쇠 패키지(x5)
            $arr['500118']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500138) { // 고급기술열쇠 패키지(x5)
            $arr['500119']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500139) { // 희귀기술열쇠 패키지(x5)
            $arr['500120']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500140) { // 우수 영웅 영입 패키지(x5)
            $arr['500103']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500141) { // 기술 열쇠 패키지
            $arr['500118']['item_count'] = 1 * $_item_cnt;
            $arr['500119']['item_count'] = 1 * $_item_cnt;
            $arr['500120']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500142)	{ // 강화 패키지
            $arr['500001']['item_count'] = 1 * $_item_cnt;
            $arr['500002']['item_count'] = 1 * $_item_cnt;
            $arr['500003']['item_count'] = 1 * $_item_cnt;
            $arr['500004']['item_count'] = 1 * $_item_cnt;
            $arr['500005']['item_count'] = 1 * $_item_cnt;
            $arr['500135']['item_count'] = 1 * $_item_cnt;
            $arr['500085']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500143) { // 랭킹 1위 패키지
            $arr['500151']['item_count'] = 1 * $_item_cnt;
            $arr['500022']['item_count'] = 1 * $_item_cnt;
            $arr['500042']['item_count'] = 1 * $_item_cnt;
            $arr['500102']['item_count'] = 1 * $_item_cnt;
            $arr['500148']['item_count'] = 1 * $_item_cnt;
            $arr['500149']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500144) { // 랭킹 2위 패키지
            $arr['500129']['item_count'] = 1 * $_item_cnt;
            $arr['500041']['item_count'] = 1 * $_item_cnt;
            $arr['500102']['item_count'] = 1 * $_item_cnt;
            $arr['500148']['item_count'] = 1 * $_item_cnt;
            $arr['500149']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500145) { // 랭킹 3위 패키지
            $arr['500152']['item_count'] = 1 * $_item_cnt;
            $arr['500039']['item_count'] = 1 * $_item_cnt;
            $arr['500102']['item_count'] = 1 * $_item_cnt;
            $arr['500148']['item_count'] = 1 * $_item_cnt;
            $arr['500149']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500146) { // 랭킹 4위 패키지
            $arr['500153']['item_count'] = 1 * $_item_cnt;
            $arr['500036']['item_count'] = 1 * $_item_cnt;
            $arr['500102']['item_count'] = 1 * $_item_cnt;
            $arr['500148']['item_count'] = 1 * $_item_cnt;
            $arr['500149']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500147) { // 랭킹 5위 패키지
            $arr['500128']['item_count'] = 1 * $_item_cnt;
            $arr['500036']['item_count'] = 1 * $_item_cnt;
            $arr['500102']['item_count'] = 1 * $_item_cnt;
            $arr['500148']['item_count'] = 1 * $_item_cnt;
            $arr['500149']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500148) { // 자원패키지(10만)
            $arr['500089']['item_count'] = 1 * $_item_cnt;
            $arr['500091']['item_count'] = 1 * $_item_cnt;
            $arr['500093']['item_count'] = 1 * $_item_cnt;
            $arr['500095']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500149) { // 자원증가패키지(1일)
            $arr['500024']['item_count'] = 1 * $_item_cnt;
            $arr['500025']['item_count'] = 1 * $_item_cnt;
            $arr['500026']['item_count'] = 1 * $_item_cnt;
            $arr['500027']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500150) { // 신규 군주용 보물 상자
            $arr['500102']['item_count'] = 2 * $_item_cnt;
            $arr['500032']['item_count'] = 1 * $_item_cnt;
            $arr['500029']['item_count'] = 1 * $_item_cnt;
            $arr['500031']['item_count'] = 1 * $_item_cnt;
            $arr['500030']['item_count'] = 1 * $_item_cnt;
            $arr['500034']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500158) { // 시즌2기념패키지
            $arr['500075']['item_count'] = 10 * $_item_cnt;
            $arr['500019']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500168) { // 우수 영웅 패키지(x20)
            $arr['500103']['item_count'] = 20 * $_item_cnt;
        } else if ($_item_pk == 500169) { // 영웅 패키지(x40)
            $arr['500075']['item_count'] = 40 * $_item_cnt;
        } else if ($_item_pk == 500170) { // 탐색강화 패키지(x20)
            $arr['500104']['item_count'] = 20 * $_item_cnt;
        } else if ($_item_pk == 500171) { // 역참 지원 패키지(x20)
            $arr['500035']['item_count'] = 20 * $_item_cnt;
        } else if ($_item_pk == 500172) { // 진열 상품 갱신 패키지(x20)
            $arr['500016']['item_count'] = 20 * $_item_cnt;
        } else if ($_item_pk == 500173) { // 즉시 회군 패키지(x20)
            $arr['500164']['item_count'] = 20 * $_item_cnt;
        } else if ($_item_pk == 500174) { // 그랜드 오픈 기념 패키지)
            $arr['500128']['item_count'] = 1 * $_item_cnt;
            $arr['500061']['item_count'] = 5 * $_item_cnt;
            $arr['500102']['item_count'] = 1 * $_item_cnt;
            $arr['500156']['item_count'] = 1 * $_item_cnt;
            $arr['500022']['item_count'] = 1 * $_item_cnt;
            $arr['500085']['item_count'] = 1 * $_item_cnt;
            $arr['500116']['item_count'] = 1 * $_item_cnt;
            $arr['500119']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500204 ) { // 한 보따리
            $arr['500061']['item_count'] = 1 * $_item_cnt;
            $arr['500165']['item_count'] = 1 * $_item_cnt;
            $arr['500103']['item_count'] = 1 * $_item_cnt;
            $arr['500164']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500219) { // 출석 패키지
            $arr['500016']['item_count'] = 1 * $_item_cnt;
            $arr['500164']['item_count'] = 1 * $_item_cnt;
            $arr['500061']['item_count'] = 1 * $_item_cnt;
            $arr['500038']['item_count'] = 1 * $_item_cnt;
            $arr['500103']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500229) { // 우수 영웅 이벤트 패키지(x22)
            $arr['500103']['item_count'] = 22 * $_item_cnt;
        } else if ($_item_pk == 500230) { // 탐색강화 패키지(x22)
            $arr['500104']['item_count'] = 22 * $_item_cnt;
        } else if ($_item_pk == 500231) { // 즉시 회군 패키지(x22)
            $arr['500164']['item_count'] = 22 * $_item_cnt;
        } else if ($_item_pk == 500232) { // 의천검 패키지(x10)
            $arr['500004']['item_count'] = 10 * $_item_cnt;
            $arr['500085']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500233) { // 손자병법 패키지(x10)
            $arr['500003']['item_count'] = 10 * $_item_cnt;
            $arr['500085']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500234) { // 맹덕신서 패키지(x10)
            $arr['500005']['item_count'] = 10 * $_item_cnt;
            $arr['500085']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500235) { // 전국책 패키지(x10)
            $arr['500001']['item_count'] = 10 * $_item_cnt;
            $arr['500085']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500236) { // 태평요술서 패키지(x10)
            $arr['500002']['item_count'] = 10 * $_item_cnt;
            $arr['500085']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500237) { // 전투 효과 패키지(24시간)
            $arr['500044']['item_count'] = 1 * $_item_cnt;
            $arr['500045']['item_count'] = 1 * $_item_cnt;
            $arr['500046']['item_count'] = 1 * $_item_cnt;
            $arr['500047']['item_count'] = 1 * $_item_cnt;
            $arr['500156']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500238) { // 전투 효과 패키지(7일)
            $arr['500048']['item_count'] = 1 * $_item_cnt;
            $arr['500049']['item_count'] = 1 * $_item_cnt;
            $arr['500050']['item_count'] = 1 * $_item_cnt;
            $arr['500157']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500239) { // 전투 효과 패키지
            $arr['500044']['item_count'] = 1 * $_item_cnt;
            $arr['500045']['item_count'] = 1 * $_item_cnt;
            $arr['500046']['item_count'] = 1 * $_item_cnt;
            $arr['500047']['item_count'] = 1 * $_item_cnt;
            $arr['500156']['item_count'] = 1 * $_item_cnt;
            $arr['500051']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500240) { // 영지 효과 패키지
            $arr['500024']['item_count'] = 1 * $_item_cnt;
            $arr['500025']['item_count'] = 1 * $_item_cnt;
            $arr['500026']['item_count'] = 1 * $_item_cnt;
            $arr['500027']['item_count'] = 1 * $_item_cnt;
            $arr['500028']['item_count'] = 1 * $_item_cnt;
            $arr['500033']['item_count'] = 1 * $_item_cnt;
            $arr['500102']['item_count'] = 1 * $_item_cnt;
        } else if ($_item_pk == 500241) { // 영자의 사과 패키지
            $arr['500103']['item_count'] = 3 * $_item_cnt;
            $arr['500104']['item_count'] = 2 * $_item_cnt;
            $arr['500061']['item_count'] = 3 * $_item_cnt;
        } else if ($_item_pk == 500246) { // 흑룡 패키지
            $arr['500103']['item_count'] = 2 * $_item_cnt;
            $arr['500075']['item_count'] = 5 * $_item_cnt;
        } else if ($_item_pk == 500249) { // 행운의 주화 패키지 10
            $arr['500061']['item_count'] = 10 * $_item_cnt;
        } else if ($_item_pk == 500250) { // 행운의 주화 패키지 30
            $arr['500061']['item_count'] = 30 * $_item_cnt;
        } else if ($_item_pk == 500251) { // 행운의 주화 패키지 50
            $arr['500061']['item_count'] = 50 * $_item_cnt;
        }

        $this->setGiveItem($arr, $_lord_pk, true, 'usePackageItem['.$_item_pk.']');

        return ['package_type' => 'item', 'm_item_pk' => $_item_pk, 'item' => $arr, 'item_cnt' => $_item_cnt];
    }

    // 아이템 지급- TODO 쿼리가 너무 많은데... 나중에 쿼리를 줄이는 쪽으로 수정이 필요할 것 같다.
    function setGiveItem($_arr, $_lord_pk, $_noti_yn = true, $_buy_type = null): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        foreach ($_arr AS $k => $v) {
            $curr_item_cnt = $this->getItemCount($_lord_pk, $k); // 로그용 이전 개수

            $r = $this->PgGame->query('WITH upsert AS (UPDATE my_item SET item_cnt = item_cnt + $1 WHERE lord_pk = $2 AND item_pk = $3 RETURNING lord_pk, item_pk, item_cnt)
                        INSERT INTO my_item (lord_pk, item_pk, item_cnt) SELECT $4, $5, $6 WHERE NOT EXISTS (SELECT lord_pk, item_pk, item_cnt FROM upsert)', [$v['item_count'], $_lord_pk, $k, $_lord_pk, $k, $v['item_count']]);
            if (! $r) {
                // debug_mesg('E', __CLASS__, __FUNCTION__, __LINE__, 'm_item_pk[' . $k .'];item_cnt['. $v['item_count'] .']');
                return false;
            }
            $after_cnt = $curr_item_cnt + $v['item_count']; // 로그용 이후 개수
            $this->getItem($_lord_pk, $k);
            if ($r) {
                // 아이템 지급이 성공 했으면 로그 남기기
                $this->classLog();
                $description = 'before_count['.$curr_item_cnt.'];after_count['.$after_cnt.'];';
                $this->Log->setItem($_lord_pk, $this->Session->getPosiPk(), 'buy', $_buy_type, $k, null, $v['item_count'], $description);
            }

            if ($_M['ITEM'][$k]['type'] == 'M') {
                $this->classQuest();
                $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'give_item', 'm_item_pk' => $k]);
            } else if ($_M['ITEM'][$k]['type'] == 'G' && $_buy_type != 'alliance_gift' && $_buy_type != 'alli_gift_quest_reward') {
                $this->classQuest();
                $this->Quest->conditionCheckQuest($_lord_pk, ['quest_type' => 'exchange_item', 'm_item_pk' => $k]);
            }

            // unread cnt 처리
            if ($_noti_yn && $_buy_type != 'now_use') {
                $this->Session->sqAppend('LORD', ['new_item_update' => $_M['ITEM'][$k]['display_type']], null, $_lord_pk);
            }

            // unread cnt 처리용
            /*if ($_noti_yn === true && $_buy_type != 'now_use') {
                $this->PgGame->query('UPDATE lord SET unread_item_last_up_dt = now() WHERE lord_pk = $1 RETURNING date_part(\'epoch\', unread_item_last_up_dt) as unread_item_last_up_dt', [$_lord_pk]);

                // LP 입력
                $this->Session->sqAppend('LORD', ['unread_item_last_up_dt' => $this->PgGame->fetchOne()], null, $_lord_pk);
            }*/
        }
        return true;
    }

    // TODO 가챠 아이템 사용 안함.
    function usePackageHeroItem($_item_pk, $_lord_pk, $_item_cnt = 1, $_forces = null): array
    {
        global $_M, $NsGlobal, $_not_m_hero_base_list;
        $NsGlobal->requireMasterData(['hero_base', 'item']);

        $r_arr = [];
        $this->classHero();

        // TODO 여기에 일괄 영웅 뽑기 코드를 옮겨 심어야함. ㅠ

        // 군주 카드를 발급하지 않기 위해 지정된 전역 변수에 군주카드의 m_hero_base_pk를 적어놓음
        if ($_item_pk == 500103)  { // 우수 영웅 즉시 영입
            $hero_pk = $this->Hero->getNewHero('ITEM_EXCEL', null, null, null, $this->Session->lord['level'], null, null, 'item_excel');
            $type = 'adv_item';
        } else if ($_item_pk == 500247) {  // 특수 영웅 즉시 영입
            $hero_pk = $this->Hero->getNewHero('ITEM_SPECIAL', null, null, null, $this->Session->lord['level'], null, null, 'item_special');
            $type = 'special';
        } else if ($_item_pk == 500384) {
            $hero_pk = $this->Hero->getNewHero('ITEM_OVERRANK', null, null, null, $this->Session->lord['level'], null, null, 'item_overrank', 'Y');
            $type = 'overrank';
        } else if ($_item_pk == 500242) { // 우수 오버랭크영웅 (수정됨)
            $hero_pk = $this->Hero->getNewHero('ITEM_OVERRANK', null, null, null, $this->Session->lord['level'], null, null, 'item_overrank', 'Y');
            $type = 'overrank';
        } else if ($_item_pk == 500706) { // 고급 오버랭크영웅
            $hero_pk = $this->Hero->getNewHero('ITEM_LUXURY_OVER', null, null, null, $this->Session->lord['level'], null, null, 'item_luxuru_over', 'Y');
            $type = 'luxury_overrank';
        } else if ($_item_pk == 500707) { // 희귀 오버랭크영웅
            $hero_pk = $this->Hero->getNewHero('ITEM_RARE_OVER', null, null, null, $this->Session->lord['level'], null, null, 'item_rare_over', 'Y');
            $type = 'rare_overrank';
        } else if ($_item_pk == 500417) {
            // 콜렉션 이벤트용 오호대장군 즉시 영입
            $coll_hero_arr = [120117, 120114, 120118, 120123, 120121]; //조운, 관우, 장비, 마초, 황충
            shuffle($coll_hero_arr); // 섞은 후
            $coll_hero_base_pk = $coll_hero_arr[0]; // 가장 먼저 온 영웅을 선택
            $hero_pk = $this->Hero->getNewHero('ITEM', null, $_M['HERO_BASE'][$coll_hero_base_pk]['rare_type'], $coll_hero_base_pk, $this->Session->lord['level'], null, null, 'item_collection');
            $type = 'collection';
        } else if ($_item_pk == 500520) {
            $hero_pk = $this->Hero->getNewHero('POWERFUL_EXCEL', null, null, null, $this->Session->lord['level'], null, null, 'powerful_excel');
            $type = 'powerful_excel';
        } else if ($_item_pk == 500521) {
            $hero_pk = $this->Hero->getNewHero('POWERFUL_NORMAL', null, null, null, $this->Session->lord['level'], null, null, 'powerful_normal');
            $type = 'powerful_normal';
        } else if ($_item_pk == 500523) { // 토벌령 포로 영웅 영입
            $hero_pk = $this->Hero->getNewHero('SUPPRESS_ITEM', null, null, null, $this->Session->lord['level'], null, null, 'suppress_item');
            $type = 'suppress_item';
        } else if ($_item_pk == 500524) { // 황건적 포로 영웅 영입
            $hero_pk = $this->Hero->getNewHero('PLUNDER_ITEM', null, null, null, $this->Session->lord['level'], null, null, 'plunder_item');
            $type = 'plunder_item';
        } else if ($_item_pk == 500525) { // 특별 영웅 즉시 영입
            $hero_pk = $this->Hero->getNewHero('SPECIAL_EXCEL', null, null, null, $this->Session->lord['level'], null, null, 'special_excel');
            $type = 'special_excel';
        } else if ($_M['ITEM'][$_item_pk]['use_type'] == 'rare_box') {
            $rare = false;
            if ($_item_pk == 500496 || $_item_pk == 500790) {
                $rare = 3;
            } elseif ($_item_pk == 500497 || $_item_pk == 500791) {
                $rare = 4;
            } elseif ($_item_pk == 500498 || $_item_pk == 500792) {
                $rare = 5;
            } elseif ($_item_pk == 500499 || $_item_pk == 500793) {
                $rare = 6;
            } elseif ($_item_pk == 500500 || $_item_pk == 500794) {
                $rare = 7;
            }
            // 에러 처리
            if (!$rare) {
                return ['err' => 'Error Occurred. [20023]']; // 아이템 사용 실패
            }
            $hero_pk = $this->Hero->getNewHero('RARE_BOX', null, $rare, null, $this->Session->lord['level'], null, null, 'item_rarebox_'.$rare);
            $type = 'rare_box_'.$rare;
        } else if ($_M['ITEM'][$_item_pk]['use_type'] == 'gachapon' || $_M['ITEM'][$_item_pk]['use_type'] == 'coll_zero') {
            $_new_gachapon = 'N';
            $_acquired_type = 'GACHAPON';
            if ($_M['ITEM'][$_item_pk]['use_type'] == 'coll_zero') {
                /*if ($_item_pk == 500528) {
					$_acquired_type = 'UNIQUE_ITEM';
				} else if ($_item_pk == 500527) {
					$_acquired_type = 'SUPPRESS_ITEM';
				} else if ($_item_pk == 500529) {
					$_acquired_type = 'COLL_ZERO';
				} else if ($_item_pk == 500530) {
					$_acquired_type = 'COLL_ZERO';
				} else if ($_item_pk == 500531) {
					$_acquired_type = 'COLL_ZERO';
				}
				$gach_hero_info = $this->Hero->getNewCollectHero($_item_pk, $_acquired_type);*/
            } else {
                if ($_item_pk == 500390) {
                    $gach_hero_info = $this->Hero->getGachaponEvent($_item_pk);
                } else if ($_item_pk == 500740) {
                    $gach_hero_info = $this->Hero->getGachaponWomanHero($_item_pk);
                } else if ($_item_pk == 500532) {
                    $_new_gachapon = 'Y';
                    $_acquired_type = 'NEW_GACHAPON';
                    $gach_hero_info = $this->Hero->getNewGachaponEvent($_item_pk);
                } else {
                    $gach_hero_info = $this->Hero->getGachaponHero($_item_pk);
                }
            }
            if (!$gach_hero_info['m_hero_base_pk'] || !is_array($gach_hero_info)) {
                // 에러 처리
                return ['err' => '현재 사용자가 많습니다.<br /><br />잠시 후 다시 시도해주시기 바랍니다'];
            }

            $hero_pk = $this->Hero->getNewHero($_acquired_type, $gach_hero_info['level'], $gach_hero_info['rare'], $gach_hero_info['m_hero_base_pk'], $this->Session->lord['level'], null, null, 'gachapon');
            $type = 'gach_'.$_item_pk;
        } else {
            $hero_pk = $this->Hero->getNewHero('ITEM', null, null, null, $this->Session->lord['level'], null, null, 'item');
            $type = 'item';
        }

        if (!$hero_pk)  {
            // 에러 처리
            return ['err' => '아이템 사용 실패<br /><br />잠시 후 다시 시도해 주시기 바랍니다.'];
        }

        $this->Hero->setMyHeroCreate($hero_pk, $_lord_pk, 'V', null, null, 'N', $type);

        //$r_arr[$hero_pk] = $m_hero_base_pk;

        $hero_info = $this->Hero->getFreeHeroInfo($hero_pk);
        $m_hero_base_pk = $_M['HERO'][$hero_info['m_hero_pk']]['m_hero_base_pk'];

        return ['package_type' => 'hero', 'm_item_pk' => $_item_pk, 'hero' => $hero_info, 'pick' => $m_hero_base_pk];
    }

    function useBuffItem($_posi_pk, $_item_pk): bool|array
    {
        global $_M, $NsGlobal, $i18n;
        $NsGlobal->requireMasterData(['item']);

        // 아이템이 존재하지 않으면 실패하도록 추가 20230809 송누리
        if (! isset($_M['ITEM'][$_item_pk])) {
            // TODO 디버깅 메세지를 남겨야 할 수도?
            return false;
        }

        $this->classResource();
        $this->classGoldPop();
        $this->classFigureReCalc();
        $this->classEffect();
        $this->classTimer();
        $this->classLog();

        $m_item_pk = null;

        // lord_pk
        if ($this->Session->lord['lord_pk']) {
            $lord_pk = $this->Session->lord['lord_pk'];
        } else {
            $this->PgGame->query('SELECT lord_pk FROM position WHERE posi_pk = $1', [$_posi_pk]);
            $lord_pk = $this->PgGame->fetchOne();
        }

        // 버프 개별 영지 적용을 위해 쿼리 수정 19.09.02
        switch ($_item_pk) { // 개별영지 대상 아이템들
            case 500024: // 쟁기
            case 500025: // 건초
            case 500026: // 도끼
            case 500027: // 용광로
            case 500028: // 상인들의 지원
            case 500029: // 개량 쟁기
            case 500030: // 개량 건초
            case 500031: // 개량 도끼
            case 500032: // 개량 용광로
            case 500033: // 난민 수용
            case 500034: // 호족들의 지원
            case 500102: // 건설허가서
            case 500535: // [强]건설허가서
                $this->PgGame->query('SELECT posi_pk FROM position WHERE posi_pk = $1 AND type = $2', [$_posi_pk, 'T']);
                break;
            default : // 기본적으로는 전체영지 대상.
                $this->PgGame->query('SELECT posi_pk FROM position WHERE lord_pk = $1 AND type = $2', [$lord_pk, 'T']);
                break;
        }
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        // 응급 선인의 치료 아이템 예외처리
        if ($_item_pk == 500243) {
            // 기존 치료 아이템 삭제
            foreach($rows AS $k => $v) {
                $this->PgGame->query('SELECT time_pk, date_part(\'epoch\', end_dt)::integer - date_part(\'epoch\', now())::integer as reduce_time FROM timer WHERE queue_pk = (SELECT terr_item_buff_pk FROM territory_item_buff WHERE posi_pk = $1 AND m_item_pk IN ($2, $3)) AND queue_type = $4 AND status = $5', [$v['posi_pk'], 500156, 500157, 'B', 'P']);
                $this->PgGame->fetch();
                if ($this->PgGame->row) {
                    $this->Timer->speedup($this->PgGame->row['time_pk'], $this->PgGame->row['reduce_time']);
                }
            }
        }

        //중복 사용중인가...
        $this->PgGame->query('SELECT m_item_pk FROM territory_item_buff WHERE posi_pk = $1 AND m_item_pk = $2', [$_posi_pk, $_item_pk]);
        if ($this->PgGame->fetchOne()) {
            $m_item_pk = $_item_pk;
        } else {
            //같은 타입의 아이템 중복 사용중인가...
            $use_type = $_M['ITEM'][$_item_pk]['use_type'];
            foreach ($_M['ITEM'] AS $k => $v) {
                if ($v['use_type'] == $use_type) {
                    $this->PgGame->query('SELECT m_item_pk FROM territory_item_buff WHERE posi_pk = $1 AND m_item_pk = $2', [$_posi_pk, $k]);
                    if ($this->PgGame->fetchOne()) {
                        $m_item_pk = $k;
                    }
                }
            }
        }

        //500102와 500535는 중복 사용 불가- 2013.01 START
        $search_item_pk = 0;
        if($_item_pk == 500102){
            $search_item_pk = 500535;
        } else if($_item_pk == 500535){
            $search_item_pk = 500102;
        }

        if($search_item_pk > 0) {
            $this->PgGame->query('SELECT m_item_pk FROM territory_item_buff WHERE posi_pk = $1 AND m_item_pk = $2', [$_posi_pk, $search_item_pk]);
            if ($this->PgGame->fetchOne()) {
                $NsGlobal->setErrorMessage($i18n->t('msg_unable_use_same_time_item', [$i18n->t('item_title_500102'), $i18n->t('item_title_500535')])); // {{1}}와 {{2}}는<br><br>동시에 사용 할 수 없습니다.
                return false;
            }

        }
        //500102와 500535는 중복 사용 불가- 2013.01 END

        $buff_time = $_M['ITEM'][$_item_pk]['buff_time'];
        if ($m_item_pk) {
            foreach($rows AS $v) {
                $query_params = [$v['posi_pk'], $m_item_pk, 'B'];
                $this->PgGame->query('SELECT time_pk FROM timer WHERE queue_pk = (SELECT terr_item_buff_pk FROM territory_item_buff WHERE posi_pk = $1 AND m_item_pk = $2) AND queue_type = $3', $query_params);
                $time_pk = $this->PgGame->fetchOne();
                if (!$time_pk) {
                    $NsGlobal->setErrorMessage('Error Occurred. [20024]'); // 해당 아이템 time_pk 정보가 없습니다.
                    return false;
                }

                // territory_item_buff update
                $this->PgGame->query("UPDATE territory_item_buff SET buff_time = buff_time + $3, end_dt = end_dt + interval '$buff_time second' WHERE posi_pk = $1 AND m_item_pk = $2", [$v['posi_pk'], $m_item_pk, $buff_time]);

                // Timer update
                $this->Timer->increaseEndTime($time_pk, $buff_time);

                $this->Log->setBuff($lord_pk, $_posi_pk, null, $m_item_pk, 'I', $buff_time);
            }

            return ['m_item_pk' => $_item_pk];
        }

        $this->classEffect();
        $this->classTimer();
        foreach($rows AS $k => $v) {
            $this->Effect->initEffects();
            $this->PgGame->query("INSERT INTO territory_item_buff (posi_pk, m_item_pk, buff_time, start_dt, end_dt) VALUES ($1, $2, $3, now(), now() + interval '$buff_time second')", [$v['posi_pk'], $_item_pk, $buff_time]);

            $terr_item_buff_pk = $this->PgGame->currSeq('territory_item_buff_terr_item_buff_pk_seq');
            $effects_for_update = [$_item_pk];

            $effect_types = $this->Effect->getEffectTypes($effects_for_update);

            if (COUNT($effect_types) > 0) {
                $this->Effect->setUpdateEffectTypes($v['posi_pk'], $effect_types);
            }

            $this->Timer->set($v['posi_pk'], 'B', $terr_item_buff_pk, 'B', ($_item_pk. ':'. $_M['ITEM'][$_item_pk]['use_type']), $buff_time);

            if ( $_item_pk == BUILD_QUEUE_INCREASE_ITEM || $_item_pk == BUILD_QUEUE2_INCREASE_ITEM ) {
                $this->useIncreaseBuildQueue($v['posi_pk'], $_item_pk);
            }

            $this->classLog();
            $this->Log->setBuff($lord_pk, $v['posi_pk'], $terr_item_buff_pk, $_item_pk, 'P', $buff_time);
        }

        return ['m_item_pk' => $_item_pk];
    }

    function useIncreaseQbigItem($_m_item_pk, $_lord_pk, $_item_count = 1): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        $this->classCash();
        $cash = match (true) {
            ($_m_item_pk == 500125) => 5,
            ($_m_item_pk == 500126) => 10,
            ($_m_item_pk == 500127) => 50,
            ($_m_item_pk == 500199) => 79,
            ($_m_item_pk == 500128) => 100,
            ($_m_item_pk == 500129), ($_m_item_pk == 500272) => 500,
            ($_m_item_pk == 500130) => 1000,
            ($_m_item_pk == 500153) => 300,
            ($_m_item_pk == 500152) => 400,
            ($_m_item_pk == 500151) => 700,
            ($_m_item_pk == 500268) => 12,
            ($_m_item_pk == 500269) => 32,
            ($_m_item_pk == 500270) => 80,
            ($_m_item_pk == 500271) => 200,
            default => $_M['ITEM'][$_m_item_pk]['supply_amount'],
        };
        $cash = $cash * $_item_count;
        $this->Cash->increaseCash($_lord_pk, $cash, 'item:'. $_m_item_pk);
        return ['m_item_pk' => $_m_item_pk, 'item_cnt' => $_item_count];
    }

    function useIncreaseGoldItem($_m_item_pk, $_posi_pk, $_item_count = 1): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);

        $this->classGoldPop();

        $gold = explode(':', $_M['ITEM'][$_m_item_pk]['supply_amount']);
        $gold = (INT)$gold[1] * $_item_count;
        $r = $this->GoldPop->increaseGold($_posi_pk, $gold, null, 'item_' . $_m_item_pk);
        return ['m_item_pk' => $_m_item_pk, 'item_cnt' => $_item_count];
    }

    function useIncreaseResourceItem($_m_item_pk, $_posi_pk, $_item_count = 1): array
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);
        $this->classResource();
        $resource = explode(':', $_M['ITEM'][$_m_item_pk]['supply_amount']);

        $r = $this->Resource->increase($_posi_pk, [$resource[0] => $resource[1] * $_item_count], null, 'item_' . $_m_item_pk);
        return ['m_item_pk' => $_m_item_pk, 'item_cnt' => $_item_count];
    }

    function useChangeLordCardItem($_lord_pk, $m_item_pk, $_card_type): array
    {
        global $i18n;
        $this->classHero();

        // 군주 카드 변경 마지막 업데이트시간 검사(86400 : 1일)
        $this->PgGame->query('SELECT date_part(\'epoch\', lord_card_up_dt)::integer FROM lord WHERE lord_pk = $1', [$_lord_pk]);
        $last_up_dt = $this->PgGame->FetchOne();
        $remain_dt = time() - $last_up_dt;
        if ($remain_dt < 86400) {
            return ['err' => $i18n->t('msg_item_use_failed_remain_time', [Useful::readableTime(86400 - $remain_dt)])];
        }

        if (!$_card_type || ($_card_type < 1 && $_card_type > 5)) {
            return ['err' => 'Error Occurred. [20025]']; // 잘못된 군주 타입
        }

        // 군주 카드 상태 검사
        $this->PgGame->query('SELECT a.hero_pk, a.posi_pk, a.status_cmd, a.m_offi_pk, b.m_hero_pk, b.level FROM my_hero a, hero b WHERE a.hero_pk = b.hero_pk AND a.yn_lord = $2 AND a.lord_pk = $1', [$_lord_pk, 'Y']);
        if (!$this->PgGame->fetch()) {
            return ['err' => 'Error Occurred. [20026]']; // 군주 정보 없음
        }
        $prev_lord_card_info = $this->PgGame->row;

        if ($prev_lord_card_info['status_cmd'] != 'I') {
            return ['err' => 'Error Occurred. [20027]']; // 군주 카드가 대기 상태가 아닙니다.
        }

        // 트랜잭션
        global $NsGlobal, $_NS_SQ_REFRESH_FLAG;
        try {
            $this->PgGame->begin();
            $_NS_SQ_REFRESH_FLAG = true;

            // 군주 카드 변경 시작 - 이전 군주 카드의 status를 강제로 G로 바꿈
            $this->PgGame->query('UPDATE my_hero SET status = $1 WHERE hero_pk = $2 AND lord_pk = $3', ['G', $prev_lord_card_info['hero_pk'], $_lord_pk]);

            // 이전 군주 카드 방출 // status가 G이고 status_cmd가 I일 경우에만 방출이 가능함
            $ret = $this->Hero->setAbandon($prev_lord_card_info['hero_pk']);
            if (!$ret) {
                throw new Exception('Error Occurred. [20028]'); // Abandon failed
            }
            $this->Session->sqAppend('HERO', [$prev_lord_card_info['hero_pk'] => ['status' => null]], null, $_lord_pk);

            $new_lord_hero_pk = $this->Hero->getNewLord($_card_type, $prev_lord_card_info['level'], 'change_lord');
            if (!$new_lord_hero_pk) {
                throw new Exception('Error Occurred. [20029]'); // create new lord failed
            }

            $ret = $this->Hero->setMyHeroCreate($new_lord_hero_pk, $_lord_pk, 'A', $prev_lord_card_info['posi_pk'], $prev_lord_card_info['m_offi_pk'], 'Y');
            if (!$ret) {
                throw new Exception('Error Occurred. [20030]'); // MyHeroRegist failed
            }

            $notice = $prev_lord_card_info['posi_pk'] == $this->Session->getPosiPk();
            $ret = $this->Hero->setCommand($prev_lord_card_info['posi_pk'], $new_lord_hero_pk, 'I', 'None', $notice);
            if (!$ret) {
                throw new Exception('Error Occurred. [20031]'); // setCommand failed
            }

            $r = $this->PgGame->query('UPDATE lord SET lord_pic = $1, lord_hero_pk = $2, lord_card_up_dt = now(), lord_enchant = 0 WHERE lord_pk = $3', [$_card_type, $new_lord_hero_pk, $_lord_pk]);
            if (!$r || $this->PgGame->getAffectedRows() == 0) {
                throw new Exception('Error Occurred. [20032]'); // lord table update failed
            }

            $this->PgGame->commit();
        } catch (Exception $e) {
            // 실패, sq 무시
            $this->PgGame->rollback();

            //dubug_mesg남기기
            // debug_mesg('T', __CLASS__, __FUNCTION__, __LINE__, $e->getMessage() . ';lord_pk['.$_lord_pk.'];m_item_pk['.$m_item_pk.'];');

            return ['err' => $e->getMessage()];
        }

        // 처리 완료후 호출해야 할 함수와 sq 처리 작업
        $_NS_SQ_REFRESH_FLAG = false;
        $NsGlobal->commitComplete();

        $this->Session->setLoginReload();
        $this->Session->sqAppend('LORD', ['lord_card_up_dt' => time()]);
        // $this->Session->sqAppend('LORD', Array('lord_hero_info' => $Hero->getMyHeroInfo($new_lord_hero_pk)));

        return ['lord_hero_pk' => $new_lord_hero_pk, 'lord_pic' => $_card_type, 'prev_lord_hero_pk' => $prev_lord_card_info['hero_pk'], 'lord_info' => $this->Hero->getMyHeroInfo($new_lord_hero_pk)];
    }

    // 특정 군주에게 아이템 일괄지급(보상 등)을 위해 추가 20240408 송누리. 아이템 구매등과 같은 경우 buyItem을 사용해야함.
    function giveItem ($_lord_pk, $_items, $_posi_pk = null, $_buy_type = null): bool
    {
        global $NsGlobal, $_M;
        $NsGlobal->requireMasterData(['item']);

        $this->classLog();
        try {
            $item_query = [];
            $item_push = [];
            foreach ($_items as $_item) {
                if (! isset($_M['ITEM'][$_item['m_item_pk']])) {
                    throw new Error('This item does not exist. ('.$_item['m_item_pk'].')');
                }

                $curr_item_cnt = $this->getItemCount($_lord_pk, $_item['m_item_pk']); // 로그용 이전 개수
                $after_cnt = $curr_item_cnt + $_item['item_count'];

                $item_query[] = "($_lord_pk, {$_item['m_item_pk']}, {$_item['item_count']})";

                $description = 'before_count['.$curr_item_cnt.'];after_count['.$after_cnt.'];';
                $this->Log->setItem($_lord_pk, $_posi_pk, 'buy', $_buy_type, $_item['m_item_pk'], null, $_item['item_count'], $description);

                $item_push[$_item['m_item_pk']] = ['item_cnt' => $after_cnt];
                $this->Session->sqAppend('LORD', ['new_item_update' => $_M['ITEM'][$_item['m_item_pk']]['display_type']], null, $_lord_pk);
            }
            $item_query_string = implode(',', $item_query);
            $this->PgGame->query("INSERT INTO my_item as i (lord_pk, item_pk, item_cnt) VALUES $item_query_string ON CONFLICT (lord_pk, item_pk) DO UPDATE SET item_cnt = i.item_cnt + EXCLUDED.item_cnt");
            $this->Session->sqAppend('ITEM', $item_push, null, $_lord_pk);
            // $this->Session->sqAppend('LORD', ['unread_item_last_up_dt' => $this->PgGame->fetchOne()], null, $_lord_pk);
        } catch (Throwable $e) {
            $NsGlobal->setErrorMessage($e->getMessage());
            return false;
        }

        return true;
    }

    function BuyItem($_lord_pk, $_m_item_pk, $_item_count, $_buy_type = null, $_noti_yn = true): bool
    {
        // buy_type
        // 퀘스트 보상 : quest_reward, 시장 : market
        // 아이템 구입 : buy_item, 아이템 구입 후 즉시 사용 : now_use
        // 매직큐브 아이템 지급 : magiccube
        // 전투 : troop, 영웅탐색 : hero_get, 치트툴 : cheat

        global $NsGlobal, $_M, $i18n;
        $NsGlobal->requireMasterData(['item']);

        if (! isset($_M['ITEM'][$_m_item_pk])) {
            $NsGlobal->setErrorMessage('This item does not exist. ('.$_m_item_pk.')');
            return false;
        }

        // 지정된 개수 이상 사면 효과 없는 아이템이 있으므로 해당 아이템 처리
        $ret = $this->buyCheckItem($_lord_pk, $_m_item_pk, $_item_count);
        if (!$ret) {
            return false;
        }

        // 강력한 지원상자, 5성 영웅 상자 1개 이상 구매 못함
        if ($_m_item_pk == 500498 || $_m_item_pk == 500744) {
            if ($_item_count > 1) {
                $NsGlobal->setErrorMessage($i18n->t('msg_item_buy_max_count', [1]));
                return false;
            }
        }


        // 이벤트 가챠폰 제한 사항 추가. TODO 가챠폰 사용 안함
        /*if ($_m_item_pk == 500390) {
            // 구매 시간 체크
            $now_hour = date('H');
            $now_minutes = date('i');
            if ($now_hour == 17 && $now_minutes <= 15) {
                throw new ErrorHandler('error', '매일 오후 5시 부터 15분 간<br />판매 영웅 초기화 작업이 진행 됩니다.<br />잠시 후 이용해주세요.');
            }

            //이벤트 가챠폰의 경우 20회 이상 구매 못 하도록 제한.
            $this->PgGame->query('SELECT gachapon_buy_cnt FROM lord WHERE lord_pk = $1', [$_lord_pk]);
            if ($this->PgGame->fetchOne() >= GACHAPON_LORD_BUY_LIMIT_CNT && !GACHAPON_INFINITY_MODE) {
                $NsGlobal->setErrorMessage('일일 구매 횟수를 초과하였습니다.');
                return false;
            }

            // 이벤트 가챠폰의 경우 7성 영웅이 판매되면 아이템 지급 금지 및 판매 종료.
            $this->PgGame->query('SELECT gach_event_buy_count FROM gachapon_event WHERE orderno = $1', [7]);
            if ($this->PgGame->fetchOne() >= 1) {
                $NsGlobal->setErrorMessage('이미 판매가 종료되었습니다.<br />다음 기회를 이용해 주세요.');
                return false;
            }
        }*/

        // 신규 이벤트 가챠폰 제한 사항 추가. TODO 가챠폰 사용 안함
        /*if ($_m_item_pk == 500532) {
            // 구매 시간 체크
            $now_hour = date('H');
            $now_minutes = date('i');

            if ($now_hour == 17 && $now_minutes <= 15) {
                $NsGlobal->setErrorMessage('매일 오후 5시 부터 15분 간<br />판매 영웅 초기화 작업이 진행 됩니다.<br />잠시 후 이용해주세요.');
                return false;
            }

            //신규 이벤트 가챠폰의 경우 20회 이상 구매 못 하도록 제한.
            $this->PgGame->query('SELECT new_gachapon_buy_cnt FROM lord WHERE lord_pk = $1', [$_lord_pk]);
            if ($this->PgGame->fetchOne() >= NEW_GACHAPON_LORD_BUY_LIMIT_CNT && NEW_GACHAPON_INFINITY_MODE != 'Y') {
                $NsGlobal->setErrorMessage('일일 구매 횟수를 초과하였습니다.');
                return false;
            }


            // 이벤트 가챠폰의 경우 7성 영웅이 판매되면 아이템 지급 금지 및 판매 종료.
            $this->PgGame->query('SELECT gach_event_buy_count FROM new_gachapon_event WHERE orderno = $1', [7]);
            if ($this->PgGame->fetchOne() >= 1) {
                $NsGlobal->setErrorMessage('이미 판매가 종료되었습니다.<br />다음 기회를 이용해 주세요.');
                return false;
            }
        }*/

        // 실제 아이템 지급
        if ($_buy_type != 'now_use') {
            $arr = [];
            $arr[$_m_item_pk]['item_count'] = $_item_count;

            $r = $this->setGiveItem($arr, $_lord_pk, $_noti_yn, $_buy_type);
            if (!$r) {
                $NsGlobal->setErrorLogging(true);
                $NsGlobal->setErrorMessage('Error Occurred. [20033]'); // 아이템 지급 중 오류
                return false;
            } else {
                // 아이템 지급에 성공했다면 한번만 구매할 수 있도록 체크하여 업데이트
                if ($_m_item_pk == 500498 || $_m_item_pk == 500744) {
                    // 현재 등급을 가져와
                    $this->PgGame->query('SELECT level FROM lord WHERE lord_pk = $1', [$_lord_pk]);
                    $limit_buy_level = $this->PgGame->fetchOne();
                    // 업데이트 시킴 (my_event 체크는 게임 접속시 없다면 무조건 생성하므로 패스함 - 문제 발생시 확인필요.)
                    $this->PgGame->query('UPDATE my_event SET last_limit_buy = $2 WHERE lord_pk = $1', [$_lord_pk, $limit_buy_level]);
                    // LP 데이터 보냄
                    $this->Session->sqAppend('LORD', ['last_limit_buy' => $limit_buy_level], null, $_lord_pk);
                }
            }

        } else { // 아이템 즉시 사용으로 아이템은 지급되지 않고 로그만 쌓여야함. item_count는 0개
            // Log
            $this->classLog();
            $this->PgGame->query('SELECT item_cnt FROM my_item WHERE lord_pk = $1 AND item_pk = $2', [$_lord_pk, $_m_item_pk]);
            $after_cnt = $this->PgGame->fetchOne() ?? 0;
            $description = 'before_count[0];after_count['.$after_cnt.'];';
            $this->Log->setItem($_lord_pk, $this->Session->getPosiPk(), 'buy', $_buy_type, $_m_item_pk, null, $_item_count, $description);
        }

        return true;
    }

    function buyCheckItem($_lord_pk, $_m_item_pk, $_item_cnt): bool
    {
        $buy_possible = true;
        $buy_cnt = 0;
        if ($_m_item_pk == 500163) { // 매관매직
            $this->PgGame->query('SELECT m_offi_pk FROM lord WHERE lord_pk = $1', [$_lord_pk]);
            $m_offi_pk = $this->PgGame->fetchOne();

            if ($m_offi_pk < 110121) {
                $m_offi_pk = 110120;
            }

            // 기존 갖고 있는 아이템 개수
            $get_count = ($this->getItemCount($_lord_pk, $_m_item_pk)) + $m_offi_pk + $_item_cnt;

            if ($get_count > 110130) {
                $buy_cnt = $_item_cnt - ($get_count - 110130);
                $buy_possible = false;
            }
        } else if ($_m_item_pk == 500019) {	//영웅 슬롯 확장
            // 기존 갖고 있는 아이템 개수
            $get_count = $this->getItemCount($_lord_pk, $_m_item_pk);
            $sum_cnt = $this->Session->lord['num_slot_guest_hero'] + (($get_count + $_item_cnt) * 10);

            if ($sum_cnt> NUM_SLOT_GUEST_HERO) {
                $buy_cnt = floor((NUM_SLOT_GUEST_HERO - ($this->Session->lord['num_slot_guest_hero'] + ($get_count * 10))) / 10);
                $buy_possible = false;
            }
        } else if ($_m_item_pk == 500508) {	// 천리마
            $this->PgGame->query('SELECT item_limi_1 FROM my_event WHERE lord_pk = $1', [$_lord_pk]);
            $get_count = $this->PgGame->fetchOne();
            if ($get_count > 10) {
                $buy_cnt = 0;
                $buy_possible = false;
            }
            if (($get_count + $_item_cnt)> 10) {
                $buy_cnt = 10 - $get_count;
                $buy_possible = false;
            }
        } else if ($_m_item_pk == 500509) {	// 혹독한 훈련
            $this->PgGame->query('SELECT item_limi_2 FROM my_event WHERE lord_pk = $1', [$_lord_pk]);
            $get_count = $this->PgGame->fetchOne();
            if ($get_count > 10) {
                $buy_cnt = 0;
                $buy_possible = false;
            }

            if (($get_count + $_item_cnt)> 10) {
                $buy_cnt = 10 - $get_count;
                $buy_possible = false;
            }
        } else if ($_m_item_pk == 500510) {	// 건설독려
            $this->PgGame->query('SELECT item_limi_3 FROM my_event WHERE lord_pk = $1', [$_lord_pk]);
            $get_count = $this->PgGame->fetchOne();
            if ($get_count > 10) {
                $buy_cnt = 0;
                $buy_possible = false;
            }
            if (($get_count + $_item_cnt)> 10) {
                $buy_cnt = 10 - $get_count;
                $buy_possible = false;
            }
        }

        if (!$buy_possible) {
            global $NsGlobal, $i18n;
            $msg = $i18n->t('msg_item_buy_use_max_count');
            if ($buy_cnt > 0) {
                $msg .= '<br /><br />'. $i18n->t('msg_item_buy_max_count', [$buy_cnt]); // msg_item_buy_max_count;
            }
            $NsGlobal->setErrorMessage($msg);
            return false;
        }
        return true;
    }

    function cancelBuffItem($_posi_pk): void
    {
        $this->PgGame->query('SELECT terr_item_buff_pk, m_item_pk FROM territory_item_buff WHERE posi_pk = $1', [$_posi_pk]);
        $this->PgGame->fetchAll();
        $rows = $this->PgGame->rows;

        $this->classTimer();
        $this->classLog();
        foreach($rows AS $v) {
            $this->PgGame->query('SELECT time_pk FROM timer WHERE queue_type = $2 AND queue_pk = $1', [$v['terr_item_buff_pk'], 'B']);
            $time_pk = $this->PgGame->fetchOne();
            $this->Timer->cancel($time_pk);
            $this->Log->setBuff(false, $_posi_pk, $v['terr_item_buff_pk'], $v['m_item_pk'], 'C', $buff_time);
            if ( ($v['m_item_pk'] == BUILD_QUEUE_INCREASE_ITEM) || ($v['m_item_pk'] == BUILD_QUEUE2_INCREASE_ITEM) ) {
                $this->PgGame->query('UPDATE build SET concurr_max = $3 WHERE posi_pk = $1 AND in_cast_pk = $2', [$_posi_pk, 1, BUILD_QUEUE_DEFAULT_COUNT]);
            }
        }

        // 버프삭제
        $this->PgGame->query('DELETE FROM territory_item_buff WHERE posi_pk = $1', [$_posi_pk]);
    }

    function useInitHeroEnchant($_hero_pk, $_lord_pk, $_posi_pk): true
    {
        $this->classHero();
        $this->Hero->initMyHeroEnchant($_hero_pk, $_lord_pk, $_posi_pk);
        return true;
    }

    function useInitHeroTradeBidCount($_lord_pk): array
    {
        $this->classHeroTrade();
        return $this->HeroTrade->initHeroBidCount($_lord_pk);
    }

    //출석체크 아이템 TODO 사용안함
    function setAttendanceItem($_lord_pk, $_attendance_cnt): void
    {
        if ($_attendance_cnt == 1) {
            $m_item_pk = 500164; // 즉시회군
            $item_cnt = 1;
        } else if ($_attendance_cnt == 2) {
            $m_item_pk = 500061; // 행운의 주화
            $item_cnt = 1;
        } else if ($_attendance_cnt == 3) {
            $m_item_pk = 500038;	// 삼급 지원령
            $item_cnt = 1;
        } else if ($_attendance_cnt == 4) {
            $m_item_pk = 500121;	// 장착기술해제
            $item_cnt = 1;
        } else if ($_attendance_cnt == 5) {
            $m_item_pk = 500464;	// 공적패 상자
            $item_cnt = 1;
        } else {
            throw new ErrorHandler('error', '잘못된 출석 정보.');
        }

        $ret = $this->BuyItem($_lord_pk, $m_item_pk, $item_cnt, 'attendance_event');
        if ($ret) {
            $this->Session->sqAppend('PUSH', ['ATTENDANCE_EVENT_COUNT' => $_attendance_cnt], null, $_lord_pk);
        }
    }

    function setGateBuff($_lord_pk, $_posi_pk, $_item_pk = 500522, $_buff_time = 86400): void
    {
        // global $_M, $NsGlobal;
        // $NsGlobal->requireMasterData(['item']);

        $this->classResource();
        $this->classGoldPop();
        $this->classFigureReCalc();
        $this->classEffect();
        $this->classProduction();

        $m_item_pk = $_item_pk;

        //중복 사용중인가...
        $insert = true;
        $this->PgGame->query('SELECT m_item_pk FROM territory_item_buff WHERE posi_pk = $1 AND m_item_pk = $2', [$_posi_pk, $_item_pk]);
        if ($this->PgGame->fetchOne()) {
            $insert = false;
        }

        $buff_time = $_buff_time; // $_M['ITEM'][$_item_pk]['buff_time'];

        // 버프가 존재하지 않을 때만 추가해줌
        if ($insert) {
            $this->classTimer();
            $this->Effect->initEffects();
            $this->PgGame->query("INSERT INTO territory_item_buff (posi_pk, m_item_pk, buff_time, start_dt, end_dt) VALUES ($1, $2, $3, now(), now() + interval '$buff_time second')", [$_posi_pk, $_item_pk, $buff_time]);

            $terr_item_buff_pk = $this->PgGame->currSeq('territory_item_buff_terr_item_buff_pk_seq');
            $effects_for_update = [$_item_pk];

            $effect_types = $this->Effect->getEffectTypes($effects_for_update);

            if (COUNT($effect_types) > 0) {
                $this->Effect->setUpdateEffectTypes($_posi_pk, $effect_types);
            }

            if ( ($_item_pk == BUILD_QUEUE_INCREASE_ITEM) || ($_item_pk == BUILD_QUEUE2_INCREASE_ITEM) ) {
                $this->useIncreaseBuildQueue($_posi_pk, $_item_pk);
            }

            $this->classLog();
            $this->Log->setBuff($_lord_pk, $_posi_pk, $terr_item_buff_pk, $_item_pk, 'P', $buff_time);
        }

        $this->Production->get($_posi_pk); // 버프 갱신을 위해 추가
    }

    function delGateBuff($_lord_pk, $_posi_pk, $_item_pk = 500522): void
    {
        $this->PgGame->query('DELETE FROM territory_item_buff WHERE m_item_pk = $1 AND posi_pk = $2', [$_item_pk, $_posi_pk]);
        $this->classLog();
        $this->Log->setBuff($_lord_pk, $_posi_pk, null, $_item_pk, 'F');

        $effects_for_update = [$_item_pk];

        $this->classEffect();
        $this->Effect->initEffects();
        $effect_types = $this->Effect->getEffectTypes($effects_for_update);
        if (COUNT($effect_types) > 0) {
            $this->Effect->setUpdateEffectTypes($_posi_pk, $effect_types);
        }

        $this->classProduction();
        $this->Production->get($_posi_pk); // 버프 갱신을 위해 추가
    }

    function setItemBuff($_lord_pk, $_posi_pk, $_item_pk, $_buff_time = 86400): void
    {
        //중복 사용중인가...
        $insert = true;
        $this->PgGame->query('SELECT m_item_pk FROM territory_item_buff WHERE posi_pk = $1 AND m_item_pk = $2', [$_posi_pk, $_item_pk]);
        if ($this->PgGame->fetchOne()) {
            $insert = false;
        }

        $buff_time = $_buff_time; // $_M['ITEM'][$_item_pk]['buff_time'];

        // 버프가 존재하지 않을 때만 추가해줌
        if ($insert) {
            $this->classEffect();
            $this->Effect->initEffects();
            $this->PgGame->query("INSERT INTO territory_item_buff (posi_pk, m_item_pk, buff_time, start_dt, end_dt) VALUES ($1, $2, $3, now(), now() + interval '$buff_time second')", [$_posi_pk, $_item_pk, $buff_time]);

            $terr_item_buff_pk = $this->PgGame->currSeq('territory_item_buff_terr_item_buff_pk_seq');
            $effects_for_update = [$_item_pk];

            $effect_types = $this->Effect->getEffectTypes($effects_for_update);

            if (COUNT($effect_types) > 0) {
                $this->Effect->setUpdateEffectTypes($_posi_pk, $effect_types);
            }

            if ( ($_item_pk == BUILD_QUEUE_INCREASE_ITEM) || ($_item_pk == BUILD_QUEUE2_INCREASE_ITEM) ) {
                $this->useIncreaseBuildQueue($_posi_pk, $_item_pk);
            }

            $this->classLog();
            $this->Log->setBuff($_lord_pk, $_posi_pk, $terr_item_buff_pk, $_item_pk, 'P', $buff_time);
        }

        $this->classProduction();
        $this->Production->get($_posi_pk); // 버프 갱신을 위해 추가
    }

    function delItemBuff($_lord_pk, $_posi_pk, $_item_pk): void
    {

        $this->PgGame->query('DELETE FROM territory_item_buff WHERE m_item_pk = $1 AND posi_pk = $2', [$_item_pk, $_posi_pk]);

        $this->Log->setBuff($_lord_pk, $_posi_pk, null, $_item_pk, 'F');

        $effects_for_update = [$_item_pk];

        $this->classEffect();
        $this->Effect->initEffects();

        $effect_types = $this->Effect->getEffectTypes($effects_for_update);
        if (COUNT($effect_types) > 0) {
            $this->Effect->setUpdateEffectTypes($_posi_pk, $effect_types);
        }

        $this->classProduction();
        $this->Production->get($_posi_pk); // 버프 갱신을 위해 추가
    }

    function checkLimitBuy ($_m_item_pk, $_item_count = 1): bool
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);
        $this->PgGame->query('SELECT item_cnt, last_buy_date FROM my_item_buy WHERE lord_pk = $1 AND item_pk = $2', [$this->Session->lord['lord_pk'], $_m_item_pk]);
        // 조회되지 않으면 구매 가능.
        if (! $this->PgGame->fetch()) {
            return true;
        }
        $buy_count = $this->PgGame->row['item_cnt'] + $_item_count;
        $m_item = $_M['ITEM'][$_m_item_pk];

        // TOO 차후 월간 구매 제한에 처리는 나중에 따로 구현 필요
        if ((INT)$m_item['limit_buy'] > 0 && $buy_count > (INT)$m_item['limit_buy']) {
            return false;
        }

        // 아닌 경우는 구매 불가
        return true;
    }

    function updateLimitBuy ($_m_item_pk, $_item_count = 1): void
    {
        global $_M, $NsGlobal;
        $NsGlobal->requireMasterData(['item']);
        $m_item = $_M['ITEM'][$_m_item_pk];
        if ($m_item['limit_buy'] > 0) {
            $this->PgGame->query('INSERT INTO my_item_buy as mib (lord_pk, item_pk, item_cnt, last_buy_date) VALUES ($1, $2, $3, now()) ON CONFLICT (lord_pk, item_pk) DO UPDATE SET item_cnt = mib.item_cnt + $3', [$this->Session->lord['lord_pk'], $_m_item_pk, $_item_count]);
        }
        $this->getBuyItem($this->Session->lord['lord_pk'], $_m_item_pk);
    }
}