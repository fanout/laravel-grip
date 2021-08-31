<?php

namespace Fanout\LaravelGrip\Errors;

use Error;

class GripInstructAlreadyStartedError extends Error {
    public function __construct() {
        parent::__construct('GripInstruct Already Started');
    }
}
