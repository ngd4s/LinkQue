<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LineQue\Lib\Mysql;

use LineQue\Lib\DbInterface;
use LineQue\Lib\Status;

/**
 * Description of MysqlDb
 *
 * @author Administrator
 */
class MysqlDb implements DbInterface {

    private $pdo;

    public function __construct($dbConf = null) {
        $this->pdo = $this->getDbInstance($dbConf);
        $this->checkLineQueTable(); //初始化对象的时候,检查表是否存在(如果当前进程意外终止,则新开启的进程也会检查此项)
    }

    //put your code here
    public function addJobToList($que, $class, $args = null, $id = null) {
        if (!$class || !$que) {
            return false;
        }
        $sql = "INSERT INTO `lineque` value(null,:que,:class,:args,:utime)";
        $bind = array(
            ":que" => $que,
            ":class" => $class,
            ":args" => json_encode($args),
            ":utime" => date("Y-m-d H:i:s"));

        if ($this->pdo->execute($sql, $bind)) {
            return $this->pdo->getLastInsertId();
        }
        return false;
    }

    public function closeDbInstance() {
//        $this->pdo->close();
//        $this->pdo = null;
    }

    public function delByJobid($jobid, $status) {
        $sql = "DELETE FROM `job` WHERE `id`=:id";
        if ($this->pdo->execute($sql, array('id' => $jobid))) {
            return true;
        }
        return false;
    }

    public function getDbInstance($dbConf = null) {
        if ($dbConf) {
            $pdo = new Pdo($dbConf);
            if ($pdo) {
                return $pdo;
            } else {
                die('Mysql初始化失败');
            }
        } else {
            die('Mysql配置无效');
        }
    }

    public function getJob($queue) {
        $sql = "SELECT * FROM `lineque` WHERE `que`=:que ORDER BY `id` asc LIMIT 0,1";
        $data = $this->pdo->query($sql, array('que' => $queue));
        if ($data) {
            $data[0]['args'] = json_decode($data[0]['args'], true);
            return $data[0];
        }
        return false;
    }

    public function popJob($queue) {
        $data = $this->getJob($queue);
        $sql = "DELETE FROM `lineque` WHERE `id`=:id";
        if ($data && $this->pdo->execute($sql, array('id' => $data['id']))) {
            return $data;
        }
        return false;
    }

    public function updateJobStatus($jobid, $status, $otherinfo = null) {
        $sql = "SELECT * FROM `job` WHERE `id`=:id";
        $data = $this->pdo->query($sql, array('id' => $jobid));
        if (!$data) {
            $sql = "INSERT INTO `job` value(:id,:status,:job,:utime)";
            $bind = array(
                ":id" => $jobid,
                ":status" => $status,
                ":job" => json_encode($otherinfo),
                ":utime" => date("Y-m-d H:i:s"));
            return $this->pdo->execute($sql, $bind);
        } else {
            $sql = "UPDATE `job` SET status=:status,job=:job where id=:id";
            $bind = array(
                ":id" => $jobid,
                ":status" => $status,
                ":job" => json_encode($otherinfo));
            return $this->pdo->execute($sql, $bind);
        }
        return false;
    }

    /**
     * 一个整型的key/value,增加他的值
     * @param type $status
     * @param type $step
     * @return type
     */
    public function incrStat($status, $step = 1) {
        $sql = "UPDATE `stat` SET num=num+{$step} where status=:status";
        $bind = array(":status" => strval(strtoupper(Status::statusToString($status))));
        return $this->pdo->execute($sql, $bind);
    }

    /**
     * 一个整型的key/value,增加他的值
     * @param type $status
     * @param type $step
     * @return type
     */
    public function decrStat($status, $step = 1) {
        $sql = "UPDATE `stat` SET num=num-{$step} where status=:status";
        $bind = array(":status" => strval(strtoupper(Status::statusToString($status))));
        return $this->pdo->execute($sql, $bind);
    }

    public function checkLineQueTable() {
        $MySqlConf = \LineQue\Config\Conf::initMysql();
        //判断表是否存在
        foreach ($MySqlConf as $table => $sql) {
            $result = $this->pdo->query("SHOW TABLES LIKE '" . $table . "'");
            if ('1' == count($result)) {
                echo $table . " Exists." . PHP_EOL;
            } else {
                echo $table . " does not Exist!Build it." . PHP_EOL;
                $this->pdo->execute($sql);
                echo $table . " Build success." . PHP_EOL;
            }
        }
    }

}
