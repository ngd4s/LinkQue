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

    public static function getConf() {
        return array(
            'DBTYPE' => 'Redis',
            'Redis' => self::getRedis(),
            'Mysql' => self::getMysql(),
            'File' => self::getFile(),
        );
    }

    private static function getRedis() {
        return array(
            'HOST' => '127.0.0.1',
            'PORT' => 6379,
            'PWD' => '',
            'DBNAME' => '0',
        );
    }

    private static function getMysql() {
        return array(
            'HOST' => 'localhost',
            'PORT' => 3306,
            'USER' => 'lineque',
            'PWD' => 'lineque',
            'DBNAME' => 'LineQue',
            'CHARSET' => 'utf8',
        );
    }

    public static function initMysql() {
        return array(
            'lineque' => "CREATE TABLE `lineque` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `que` varchar(255) NOT NULL COMMENT '队列名',
  `class` varchar(255) NOT NULL,
  `args` text,
  `utime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '插入时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
            'job' => "CREATE TABLE `job` (
  `id` int(11) NOT NULL,
  `status` varchar(16) NOT NULL,
  `job` text,
  `utime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
            'stat' => "CREATE TABLE `stat` (
  `status` varchar(16) NOT NULL,
  `num` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `stat` VALUES ('COMPLETE', '0');
INSERT INTO `stat` VALUES ('FAILED', '0');
INSERT INTO `stat` VALUES ('RUNNING', '0');
INSERT INTO `stat` VALUES ('WATTIMG', '0');"
        );
    }

    private static function getFile() {
        return array(
            'PATH' => '/data/LineQue/App'
        );
    }

}
