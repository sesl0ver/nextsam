<?php

class i18n
{
    private static i18n|null $self_cast = null;
    protected string $locale = 'ko';
    public array $data = [];

    public function __construct()
    {
        $language = I18N_LOCALE_LIST;
        foreach ($language as $lang) {
            $this->data[$lang] = json_decode(file_get_contents(__DIR__ . "/../i18n/locales/$lang.json"), true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$self_cast != null) {
            return self::$self_cast;
        }
        self::$self_cast = new self();
        return self::$self_cast;
    }

    public function getBundle(): array|string
    {
        return json_encode($this->data,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }

    public function setLang($_locale = 'ko'): string
    {
        $this->locale = $_locale;
        $_SESSION['lang'] = $_locale;
        return $this->locale;
    }

    public function getLang(): string
    {
        return (isset($_SESSION['lang'])) ? $_SESSION['lang'] : $this->locale;
    }

    public function t($_code, $_replaces = []): string
    {
        $text = $this->data[$this->getLang()][$_code] ?? "__({$this->getLang()})$_code";
        if (count($_replaces) > 0) {
            $i = 1;
            foreach ($_replaces as $replace) {
                $text = str_replace('{{'.$i.'}}', $replace, $text);
                $i++;
            }
        }
        return $text;
    }
}