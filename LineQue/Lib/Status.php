<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LineQue\Lib;

/**
 * Description of Status
 *
 * @author Administrator
 */
class Status {

    const STATUS_WAITING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_FAILED = 3;
    const STATUS_COMPLETE = 4;

    public static function statusToString($status) {
        switch ($status) {
            case self::STATUS_WAITING:
                return 'WATTING';
            case self::STATUS_RUNNING:
                return 'RUNNING';
            case self::STATUS_FAILED:
                return 'FAILED';
            case self::STATUS_COMPLETE:
                return 'COMPLETE';
        }
        return '';
    }

}
