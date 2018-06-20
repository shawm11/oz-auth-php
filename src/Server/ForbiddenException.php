<?php

namespace Shawm11\Oz\Server;

class ForbiddenException extends ServerException
{
    public function __construct($message = '', $code = 403, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
