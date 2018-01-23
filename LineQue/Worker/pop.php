<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of pop
 *
 * @author Administrator
 */
class pop {

    private $redis;

    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('0.0.0.0', 6379);
    }

    public function blpop() {
        while (1) {
            try {
                $s = $this->redis->blPop('list1', 3);
                var_export($s);
            } catch (Exception $ex) {
                
            }
        }
    }

    public function lindex() {
        while (1) {
            $s = $this->redis->lindex('list1', 0);
            var_export($s);
            if ($s) {
                $q = $this->redis->lPop('list1');
                var_export($q);
            } else {
                break;
            }
        }
    }

}

$pos = new pop();

$pos->lindex();
