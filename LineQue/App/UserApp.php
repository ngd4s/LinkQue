<?php

namespace App;

/**
 * 用户APP
 * 
 * @author Linko
 * @email 18716463@qq.com 
 * @link https://github.com/kknv/LinkQue
 * @version 1.0.0
 */
class UserApp implements AppInterface {

    public function __construct($job) {
        file_put_contents(__DIR__ . '/userApp.log', json_encode($job));
    }

    public function run() {
        return true;
    }

}
