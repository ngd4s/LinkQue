<?php

namespace App;

use LineQue\Lib\AppInterface;

/**
 * 用户APP
 * @author Administrator
 */
class UserApp implements AppInterface {

    public function __construct($job) {
        file_put_contents(__DIR__ . '/userApp.log', json_encode($job));
    }

    public function run() {
        return true;
    }

}
