<?php

namespace LineQue\Config;

/**
 * 
 * @author Linko
 * @email 18716463@qq.com
 * @link https://github.com/kknv/LinkQue
 * @version 1.0.0
 */
class Conf {

    public static function getConf() {
        return array(
            'DBTYPE' => 'Redis',
            'Redis' => self::getRedis()
        );
    }

    private static function getRedis() {
        return array(
            'SERVER' => '127.0.0.1',
            'PORT' => 6379,
            'PWD' => '',
            'DBNAME' => '0',
        );
    }

}
