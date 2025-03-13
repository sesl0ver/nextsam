<?php
// TODO 요충지는 차후 번역
class npcPoint
{
    protected Session $Session;
    protected Pg $PgGame;
    protected Letter $Letter;

    public function __construct(Session $_Session, Pg $_PgGame)
    {
        $this->Session = $_Session;
        $this->PgGame = $_PgGame;
    }

    protected function classLetter (): void
    {
        if (! isset($this->Letter)) {
            $this->Letter = new Letter($this->Session, $this->PgGame);
        }
    }

    public function batchLetter (): void
    {
        $this->PgGame->query('SELECT yn_point_letter FROM lord WHERE lord_pk = $1', [$this->Session->lord['lord_pk']]);
        if ($this->PgGame->fetchOne() == 'N') {

            global $_M;
            $letter = [];
            $letter['type'] = 'S';
            $letter['title'] = '난세의 효웅들로부터 요충지를 확보하십시오.';

            $letter_content = <<< EOF
천하는 혼란에 잠기고 난세를 틈탄 효웅들이 각처에 난립하여 
요충지를 점령하고 병력을 모으고 있습니다.

요충지는 점령 시 군사적으로도 많은 혜택을 줄 뿐 아니라 
요충지 점령 및 방어 등 공략에 가장 큰 공을 세운 군주는 
원래 주둔 중인 영웅과 병력 중 일부를 흡수하게 됩니다.

요충지는 대륙에 총 81개 밖에 존재하지 않고 모든 군주들에게 
동일하게 노출될 뿐 아니라 요충지별로 가장 큰 공을 세운 군주만 
뛰어난 영웅과 병력을 가질 수 있으므로 
동일한 요충지를 노리고 있는 주변 군주들의 동태에도 주의를 기울여야 합니다.

<요충지좌표><table class="point_table">
<tbody>
EOF;
            $i = 0;
            foreach ($_M['POSITION_NPC_POINT_LIST'] as $posi_pk) {
                if ($i % 9 === 0) {
                    $letter_content .= '<tr>';
                }
                $letter_content .= "<td>$posi_pk</td>";
                if ($i % 9 === 9) {
                    $letter_content .= '<tr>';
                }
                $i++;
            }

            $letter_content .= <<< EOF
</tbody>
</table>
*요충지 Lv1 : 검은색(81개)

<요충지 전투 가능 시간>
* 요충지 생성 : 매주 목요일 10:00
* 요충지 전투 종료 : 다음 주 월요일 24:00
* 전투 가능 시간 : 매일 10:00 ~ 24:00 까지만 전투 가능 
  (24시 이후 도착한 부대는 자동회군됨)
* 요충지 점령 포인트 보상 : 요충지 전투 종료 1시간 후 (화요일 01:00)

초기 주둔 중인 병력을 제거하고 먼저 점령하는 것도 중요하지만 
오랫동안 점령하고 있는 것도 큰 공적으로 인정받게 되므로
다양한 전략 사용이 가능하고 대규모 병력 이동 시 
비어 있는 영지를 노리는 군주까지 주의하셔야 합니다.

요충지를 전략적으로 공략하여 성장의 발판으로 삼아 주시면 고맙겠습니다.

군주님의 건승을 기원합니다.	
EOF;
            $letter['content'] = $letter_content;

            $this->classLetter();
            $this->Letter->sendLetter(ADMIN_LORD_PK, [$this->Session->lord['lord_pk']], $letter, true, 'Y');

            $this->PgGame->query('UPDATE lord SET yn_point_letter = $2 WHERE lord_pk = $1', [$this->Session->lord['lord_pk'], 'Y']);
        }
    }
}