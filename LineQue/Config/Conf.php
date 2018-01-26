<?php

namespace LineQue\Config;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of conf
 *
 * @author Administrator
 */
class Conf {

    public static $VERSION = '1.0.0';

    public static function getConf() {
        return array(
            'DBTYPE' => 'Redis',
            'Redis' => self::getRedis()
        );
    }

    private static function getRedis() {
        return array(
            'HOST' => '127.0.0.1',
            'PORT' => 6379,
            'PWD' => '',
            'DBNAME' => "0",
        );
    }

}
