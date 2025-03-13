<?php

// Decimal - http://php.net/manual/en/book.bc.php

class Decimal
{
    private string|int|self $number = '0';
    private string $first_number = '0';
    private int $scale = 20;

    public function __construct()
    {
    }

    public static function set ($number, $scale = 0): self
    {
        $decimal = new self();
        if ($scale > 0) {
            $decimal->setScale($scale);
        } else {
            bcscale($decimal->scale);
        }
        $number = $decimal->validNumber($number);
        $decimal->first_number = $number;
        $decimal->number = $number;
        return $decimal;
    }

    public function getValue (): string
    {
        return $this->number;
    }

    public function getInt (): int
    {
        return (int)$this->number;
    }

    public function getFirst (): string
    {
        return $this->first_number;
    }

    public function setScale ($scale = 3): void // 소수점 자리수
    {
        bcscale($scale);
    }

    public function plus ($value): self // 더하기
    {
        $value = $this->validNumber($value);
        $this->number = bcadd($this->number, $value);
        return $this;
    }

    public function minus ($value): self // 빼기
    {
        $value = $this->validNumber($value);
        $this->number = bcsub($this->number, $value);
        return $this;
    }

    public function mul ($value): self // 곱
    {
        $value = $this->validNumber($value);
        $this->number = bcmul($this->number, $value);
        return $this;
    }

    public function div ($value): self // 나눔
    {
        $value = $this->validNumber($value);
        $value = bcdiv($this->number, $value);
        $this->number = (! $value) ? 'Error' : $value;
        return $this;
    }

    public function round ($num_digit = 2): self // 반올림
    {
        $this->number = round($this->number, $num_digit);
        return $this;
    }

    public function ceil ($num_digit = 0): self // 올림
    {
        $digit = ($num_digit > 0) ? bcpow(10, $num_digit) : 0;
        if ($digit > 0) {
            $this->number = bcmul($this->number, $digit);
        }
        $this->number = ceil($this->number);
        if ($digit > 0) {
            $value = bcdiv($this->number, $digit, $num_digit);
            $this->number = (! $value) ? 'Error' : $value;
        }
        return $this;
    }

    public function floor ($num_digit = 0): self // 버림
    {
        $digit = ($num_digit > 0) ? bcpow(10, $num_digit) : 0;
        if ($digit > 0) {
            $this->number = bcmul($this->number, $digit);
        }
        $this->number = floor($this->number);
        if ($digit > 0) {
            $value = bcdiv($this->number, $digit, $num_digit);
            $this->number = (! $value) ? 'Error' : $value;
        }
        return $this;
    }

    public function pow ($count): self // 거듭제곱
    {
        $this->number = bcpow($this->number, $count);
        return $this;
    }

    public function mod ($value): self // 나머지
    {
        $this->number = bcmod($this->number, $value);
        return $this;
    }

    public function sqrt (): self // 제곱근
    {
        $this->number = sqrt($this->number);
        return $this;
    }

    public function eq ($value): bool // 비교연산 =
    {
        $value = $this->validNumber($value);
        return (bccomp($this->number, $value) === 0);
    }

    public function gt ($value): bool // 비교연산 >
    {
        $value = $this->validNumber($value);
        return (bccomp($this->number, $value) === 1);
    }

    public function lt ($value): bool // 비교연산 <
    {
        $value = $this->validNumber($value);
        return (bccomp($this->number, $value) === -1);
    }

    public function gte ($value): bool // 비교연산 >=
    {
        $value = $this->validNumber($value);
        return (bccomp($this->number, $value) !== -1);
    }

    public function lte ($value): bool // 비교연산 <=
    {
        $value = $this->validNumber($value);
        return (bccomp($this->number, $value) !== 1);
    }

    // TODO mod, powmod 는 필요하다면 나중에 생성하는 것으로 함.

    public function isInt (): bool
    {
        return (bool)preg_match('/^(\-)?(0|[1-9]+[0-9]*)$/', $this->number);
    }

    public function trim (int $retain = 0): self
    {
        if ($this->isInt()) {
            return $this;
        }

        $trimmed = rtrim(rtrim($this->number, "0"), ".");
        if ($retain) {
            $trimmed = explode(".", $trimmed);
            $decimals = $trimmed[1] ?? "";
            $required = $retain - strlen($decimals);
            if ($required > 0) {
                $trimmed = $trimmed[0] . "." . $decimals . str_repeat("0", $required);
            }
        }

        $this->number = $trimmed;
        return $this;
    }

    public function validNumber (string|int|float|Decimal $value): string
    {
        if ($value instanceof Decimal) {
            return $value->getValue();
        }

        if (is_int($value)) {
            return strval($value);
        }

        if (is_float($value)) {
            $floatAsString = strval($value);
            // Look if scientific E-notation
            if (preg_match('/e\-/i', $floatAsString)) {
                // Auto-detect decimals
                $decimals = preg_split('/e\-/i', $floatAsString);
                $decimals = intval(strlen($decimals[0])) + intval($decimals[1]);
                return number_format($value, $decimals, ".", "");
            } elseif (preg_match('/e\+/i', $floatAsString)) {
                return number_format($value, 0, "", "");
            }
            return $floatAsString;
        }

        if (is_string($value)) {
            if (preg_match('/^\-?(0|[1-9]+[0-9]*)(\.[0-9]+)?$/', $value)) {
                return $value;
            }
        }
        return $value;
    }
}