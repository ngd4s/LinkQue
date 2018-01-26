<?php

namespace App;

use LineQue\Lib\AppInterface;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Administrator
 */
class FileP implements AppInterface {

    public function __construct($job) {
        file_put_contents('/data/LineQue/xxxxx.log', json_encode($job));
    }

    public function run() {
        return true;
    }

}
