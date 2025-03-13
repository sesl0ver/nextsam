<?php

class CrossCoupon
{
    public Pg|null $PgCommon;

    public function __construct(Pg $PgCommon = null)
    {
        $this->PgCommon = $PgCommon;
        if (! $this->PgCommon) {
            $this->PgCommon = new Pg('COMMON');
        }
    }

    public function getCoupon($_type, $_lord_pk, $_use_by): false|array
    {
        global $NsGlobal;
        $this->PgCommon->query('SELECT cros_coup_pk, desc_title, desc_body FROM cross_coupon WHERE type = $1 AND end_dt > NOW() ORDER BY cros_coup_pk' [$_type]);
        if ($this->PgCommon->getNumRows() < 1) {
            $NsGlobal->setErrorMessage('invalid type');
            return false;
        }
        $this->PgCommon->fetchAll();
        $rows = $this->PgCommon->rows;
        for ($i = 0, $i_l = COUNT($rows); $i < $i_l; $i++) {
            $coupon = $this->getCouponByPk($rows[$i]['cros_coup_pk'], $_lord_pk, $_use_by);
            if (!$coupon) {
                $NsGlobal->setErrorMessage('coupon error.'); // TODO 글러벌 에러 메세지 필요.
                return false;
            }
            $rows[$i]['coupon'] = $coupon;
        }
        return $rows;
    }

    private function getCouponByPk($_pk, $_lord_pk, $_use_by): mixed
    {
        global $NsGlobal;
        $this->PgCommon->query('DELETE FROM cross_coupon_pub WHERE cros_coup_pk = $1 AND coupon = (SELECT coupon FROM cross_coupon_pub WHERE cros_coup_pk = $2 LIMIT 1) RETURNING coupon', [$_pk, $_pk]);
        $coupon = $this->PgCommon->fetchOne();
        if (!$coupon) {
            $NsGlobal->setErrorMessage('failed to select');
            return false;
        }

        $this->PgCommon->query('INSERT INTO cross_coupon_use VALUES ($1, $2, $3, $4, NOW())', [$_pk, $coupon, $_lord_pk, $_use_by]);
        if ($this->PgCommon->getAffectedRows() != 1) {
            $NsGlobal->setErrorMessage('failed to select2');
            return false;
        }

        $this->PgCommon->query('UPDATE cross_coupon SET no_use = no_use + 1 WHERE cros_coup_pk = $1', [$_pk]);

        return $coupon;
    }
}