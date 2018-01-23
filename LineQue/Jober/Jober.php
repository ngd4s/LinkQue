<?php

namespace LineQue\Jober;

use LineQue\Lib\dbJobInstance;

/**
 *
 * @author Administrator
 */
class Jober {

    private $DbInstance = null;

    public function __construct() {
        $this->DbInstance = new dbJobInstance();
    }

    public function addJob($Que, $Class, $args) {
        return $this->DbInstance->addJob($Que, $Class, $args);
    }

}
