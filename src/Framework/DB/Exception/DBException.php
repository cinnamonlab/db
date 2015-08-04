<?php

namespace Framework\DB\Exception;
use Framework\Exception\FrameworkException;

class DBException extends FrameworkException {

    function __construct( $message, $code = 500 ) {
        $this->code = $code;
    }

}
