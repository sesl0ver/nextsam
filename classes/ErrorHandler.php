<?php

class ErrorHandler extends Error
{
    public Exception $previous;
    public string $error_type = '';
    protected string|array $data = [];

    public function __construct(string $_type, string $_message, bool $_logging = false)
    {
        $this->error_type = $_type;
        Debug::debugMessage($_type, $_message, $_logging);
        parent::__construct($_message, $this->getCode());
    }
}