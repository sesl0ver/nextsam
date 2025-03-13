<?php

// TODO 안쓰는 클래스
class CouponSimple
{
    public Session $Session;
    public Pg $PgGame;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    function newCoupon(): string|bool
    {
        return strtoupper(Useful::uniqId(8));
    }

    function verifyCoupon($_coupon): bool
    {
        global $NsGlobal;
        if (!$_coupon || strlen($_coupon) != 8 || $_coupon == '00000000') {
            $NsGlobal->setErrorMessage('invalid coupon fmt');
            return false;
        }

        $seed = substr($_coupon, 0, 1);
        $rr = substr($_coupon, 1, 1);
        $seed2 = substr($_coupon, 3, 1);
        $r1 = substr($_coupon, 5, 1);
        $r2 = substr($_coupon, 6, 1);
        $r3 = substr($_coupon, 7, 1);

        if ($seed != $seed2) {
            $NsGlobal->setErrorMessage('invalid coupon');
            return false;
        }

        // odd - TODO 코드가 이상한데?
        if ($seed%2) {
            if ($rr != ($r1-$r2-$r3)) {
                $NsGlobal->setErrorMessage('invalid coupon');
                return false;
            }
        } else { // even
            if ($rr != ($r1+$r2+$r3)) {
                $NsGlobal->setErrorMessage('invalid coupon');
                return false;
            }
        }

        return true;
    }

    // use_by 로 중복사용 여부 관리 할 수 있음
    //  udid 라면 디바이스당 제한
    //  lord_pk 라면 군주당 제한
    // prefix 로 쿠폰이벤트 타입을 넣으면 행사당 udid 나 lord_pk 제한이 가능
    //  사전등록 군주당 제한이라면 "PR_[lord_pk]" 형식으로 가능
    function useCoupon($_coupon, $_use_by, $_db = null): bool
    {
        global $NsGlobal;
        $ret = $this->verifyCoupon($_coupon);
        if (!$ret) {
            return false;
        }

        $this->PgGame->query('SELECT coupon, use_by FROM coupon_simple_use WHERE coupon = $1 OR use_by = $2', [$_coupon, $_use_by]);
        if ($_db->fetch()) {
            $c = $_db->row['coupon'];
            $u = $_db->row['use_by'];

            if ($u == $_use_by) {
                $NsGlobal->setErrorMessage('used only once');
            }
            else if ($c == $_coupon) {
                $NsGlobal->setErrorMessage('used coupon');
            }
            return false;
        }

        if (!$_db->query('INSERT INTO coupon_simple_use (coupon, use_by) VALUES ($1, $2)', [$_coupon, $_use_by])) {
            $NsGlobal->setErrorMessage('internal error (db ins)');
            return false;
        }

        return true;
    }
}