<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LineQue\Lib;

/**
 *
 * @author Administrator
 */
interface DbInterface {

    public function getDbInstance($redisConf);

    public function getJob($queue);

    public function popJob($queue);
}
