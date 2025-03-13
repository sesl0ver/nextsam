<?php

class Auth
{
    protected Pg $Db;
    protected string|null $uuid;
    protected string|null $token;
    public string $platform;

    public bool $need_membership = false;
    public string|null $lc;
    public string $status;
    public string|null $id;
    public array $account_info;
    public i18n $i18n;

    public function __construct(Pg $_Db, $_uuid, $_lc, $_token, $_platform)
    {
        $this->Db = $_Db;
        $this->lc = (! $_lc) ? '1' : $_lc;  // Guest Default Lc = 1
        $this->token = $_token ?? null;
        $this->platform = $_platform ?? 'HOME';
        $this->id = null;
        $this->uuid = $_uuid ?? null;
        $this->status = '';
        $this->i18n = i18n::getInstance();
    }

    public function getAccount (): bool
    {
        $this->account_info = [];
        if ($this->lc && $this->token !== '') {
            if ($this->lc === '1') { // 가입한 경우. LC 값으로 플랫폼 구분을 할 수도 있음.
                $this->Db->query('SELECT member_pk, id FROM ns_member WHERE token = $1', [$this->token]);
                if (! $this->Db->fetch() && ! isset($this->Db->row['member_pk'])) {
                    $this->status = 'invalid';
                    return true;
                }

                $this->account_info['member_pk'] = $this->Db->row['member_pk'];
                $this->account_info['id'] = $this->Db->row['id'];
                $this->id = $this->Db->row['id'];

                $this->Db->query('SELECT account_pk FROM account WHERE access_by = $1 AND uid = $2', [$this->lc, $this->token]);
            } else if ($this->lc === '2') {
                $this->Db->query('SELECT account_pk FROM account WHERE access_by = $1 AND uid = $2', [$this->lc, $this->uuid]);
            }
            if ($this->Db->fetch()) {
                $this->status = 'member';
                $this->account_info['account_pk'] = $this->Db->row['account_pk'];
            } else {
                $this->need_membership = true;
            }
        } else { // 아닌 경우
            $this->Db->query('SELECT device_pk, status FROM device WHERE uuid = $1', [$this->uuid]);
            if ($this->Db->fetch()) {
                $row = $this->Db->row;
                if ($row['status'] === 'G') {
                    $this->Db->query('SELECT account_pk FROM account WHERE access_by = $1 AND uid = $2', ['G', $row['device_pk']]);
                    if ($this->Db->fetch() && $this->Db->row['account_pk']) {
                        $this->status = 'guest';
                        $this->id = $this->Db->row['account_pk'];
                        $this->account_info['account_pk'] = $this->Db->row['account_pk'];
                    } else {
                        throw new ErrorHandler('error', $this->i18n->t('msg_not_found_account'));
                    }
                } else {
                    $this->status = 'signup';
                }
            } else {
                $this->status = 'not_signup';
            }
        }
        return true;
    }

    public function createGuest (): void
    {
        if (!$this->uuid) {
            throw new ErrorHandler('error', 'uuid is null');
        }

        $this->Db->query('SELECT count(device_pk) FROM device WHERE uuid = $1', [$this->uuid]);
        if (!$this->Db->query_result) {
            throw new ErrorHandler('error', 'uuid select query error');
        }
        if ($this->Db->fetchOne() <> 0) {
            throw new ErrorHandler('error', 'uuid already signup.');
        }

        $this->Db->query('INSERT INTO device (uuid, status, platform) VALUES ($1, $2, $3)', [$this->uuid, AUTH_STATUS_GUEST, $this->platform]);
        if (!$this->Db->query_result){
            throw new ErrorHandler('error', 'uuid signup error [device]');
        }
        $device_pk = $this->Db->currSeq('device_device_pk_seq');
        if (! $device_pk) {
            throw new ErrorHandler('error', 'uuid signup error [currSeq]');
        }
        $this->Db->query('INSERT INTO account (access_by, uid, device_pk) VALUES ($1, $2, $3)', [AUTH_STATUS_GUEST, $device_pk, $device_pk]);
        if (!$this->Db->query_result) {
            throw new ErrorHandler('error', 'uuid signup error [account]');
        }
    }

    public function createMembership(): void
    {
        if (!$this->uuid || !$this->lc || !$this->token) {
            throw new ErrorHandler('error', 'some property is null');
        }
        if (!$this->need_membership) {
            throw new ErrorHandler('error', 'flag is not set');
        }
        $this->Db->query('SELECT device_pk, status FROM device WHERE uuid = $1', [$this->uuid]);
        if (!$this->Db->query_result) {
            throw new ErrorHandler('error', 'uuid select query error');
        }
        if ($this->Db->fetch()) {
            $row = $this->Db->row;
            if ($row['status'] === AUTH_STATUS_GUEST) { // Guest 라면 정식 전환
                $this->Db->query('UPDATE device SET status = $1 WHERE device_pk = $2', ['M', $row['device_pk']]);
                if (!$this->Db->query_result || $this->Db->getAffectedRows() <> 1) {
                    throw new ErrorHandler('error', 'device update error (E1)');
                }
                $this->Db->query('UPDATE account SET access_by = $1, uid = $2, member_pk = $3, update_dt = now() WHERE access_by = $4 AND uid = $5',
                                    [$this->lc, $this->token, $this->account_info['member_pk'], AUTH_STATUS_GUEST, $row['device_pk']]);
                if (!$this->Db->query_result || $this->Db->getAffectedRows() <> 1)
                    throw new ErrorHandler('error', 'account update error (E1)');
            } else { // Member 라면 동일 디바이스에 신규 계정
                $this->Db->query('UPDATE device SET mapping = mapping + 1 WHERE device_pk = $1', [$row['device_pk']]);
                if (!$this->Db->query_result || $this->Db->getAffectedRows() <> 1) {
                    throw new ErrorHandler('error', 'device update error (E2)');
                }
                $this->Db->query('INSERT INTO account (access_by, uid, member_pk, device_pk) VALUES ($1, $2, $3, $4)',
                                    [$this->lc, $this->token, $this->account_info['member_pk'], $row['device_pk']]);
                if (!$this->Db->query_result) {
                    throw new ErrorHandler('error', 'account insert error (E2)');
                }
            }
        } else { // 회원 가입 후 최초 접근 (즉시정식가입)
            $this->Db->query('INSERT INTO device (uuid, status, platform) VALUES ($1, $2, $3)', [$this->uuid, 'M', $this->platform]);
            if (!$this->Db->query_result) {
                throw new ErrorHandler('error', 'signup error [device]');
            }
            $device_pk = $this->Db->currSeq('device_device_pk_seq');
            // 로그인 유저인 경우 uid는 token을 사용
            $this->Db->query('INSERT INTO account (access_by, uid, member_pk, device_pk) VALUES ($1, $2, $3, $4)',
                                [$this->lc, $this->token, $this->account_info['member_pk'], $device_pk]);
            if (!$this->Db->query_result) {
                throw new ErrorHandler('error', 'signup error [account]');
            }
        }
        $this->status = 'redraw';
    }

    public function createPlatformUser (): void
    {
        if (!$this->uuid || !$this->lc) {
            throw new ErrorHandler('error', 'some property is null');
        }
        if (!$this->need_membership) {
            throw new ErrorHandler('error', 'flag is not set');
        }
        // 플랫폼 유저 등록
        $this->Db->query('INSERT INTO device (uuid, status, platform) VALUES ($1, $2, $3)', [$this->uuid, 'P', $this->platform]);
        if (!$this->Db->query_result) {
            throw new ErrorHandler('error', 'signup error [device]');
        }
        $device_pk = $this->Db->currSeq('device_device_pk_seq');
        $this->Db->query('INSERT INTO account (access_by, uid, device_pk) VALUES ($1, $2, $3)',
            [$this->lc, $this->uuid, $device_pk]);
        if (!$this->Db->query_result) {
            throw new ErrorHandler('error', 'signup error [account]');
        }
        $this->status = 'redraw';
    }

    public function setPlatformToken ($_account_pk, $_salt): void
    {
        $this->Db->query('UPDATE account SET salt_key = $2 WHERE account_pk = $1', [$_account_pk, $_salt]);
        if (!$this->Db->query_result) {
            throw new ErrorHandler('error', 'Token key issuance failed.');
        }
    }

    public function removePlatformToken ($_account_pk): void
    {
        $this->Db->query('UPDATE account SET salt_key = null WHERE account_pk = $1', [$_account_pk]);
        if (!$this->Db->query_result) {
            throw new ErrorHandler('error', 'Token key issuance failed.');
        }
    }

    public function getUserPlatform ($_account_pk): string|null
    {
        $this->Db->query('SELECT d.platform FROM account a LEFT JOIN device d on a.device_pk = d.device_pk WHERE account_pk = $1;', [$_account_pk]);
        if (!$this->Db->fetch()) {
            throw new ErrorHandler('error', 'Token key issuance failed.');
        }
        return $this->Db->row['platform'] ?? null;
    }
}