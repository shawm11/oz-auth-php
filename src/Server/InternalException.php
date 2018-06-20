<?php

namespace Shawm11\Oz\Server;

class InternalException extends ServerException
{
    public function __construct($message = '', $code = 500, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
