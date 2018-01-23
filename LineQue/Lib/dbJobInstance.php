<?php

namespace LineQue\Lib;

use LineQue\Config\Conf;
use LineQue\Lib\Redis\RedisDb;

/**
 * 这一层是数据库和worker/jober中间的连接层
 * 通过这一层,隔离了数据库操作,数据库可以由这一层抽象出来
 * 实例化不同的数据库类型
 * 这一层还实例化用户指定的类,因此可以
 *
 * @author Administrator
 */
class dbJobInstance {

    /**
     * 数据库实例
     * @var type 
     */
    private $dbInstance = null;

    /**
     * APP实例
     * @var type 
     */
    private $appInstance = null;

    /**
     * 初始化数据库控制
     * @param type $dbConf
     */
    public function __construct($dbConf = null) {
        $this->dbInstance = $this->doDbInstance($dbConf);
    }

    /**
     * 获取一个Job,只获取不出队
     * @param type $queue
     * @return type
     */
    public function getJob($queue) {
        return $this->dbInstance->getJob('lineque:' . $queue);
    }

    /**
     * 出队一个job
     * @param type $queue
     * @return type
     */
    public function popJob($queue) {
        return $this->dbInstance->popJob('lineque:' . $queue);
    }

    /**
     * 修改job的状态为running
     * @param type $job
     * @return type
     */
    public function workingOn($job) {
        $this->updateStat(Status::STATUS_WAITING, false); //开始,watting统计-1
        return $this->updateJobStatus($job, Status::STATUS_RUNNING);
    }

    /**
     * 修改job的状态为FAILED
     * @param type $job
     * @return type
     */
    public function workingFail($job) {
        $this->updateStat(Status::STATUS_RUNNING, false); //失败,running统计-1
        return $this->updateJobStatus($job, Status::STATUS_FAILED);
    }

    /**
     * 修改job的状态为COMPLETE
     * @param type $job
     * @return type
     */
    public function workingDone($job) {
        $this->updateStat(Status::STATUS_RUNNING, false); //成功,running统计-1
        return $this->updateJobStatus($job, Status::STATUS_COMPLETE);
    }

    /**
     * 修改job的状态
     * @param type $job
     * @param type $status
     * @return type
     */
    private function updateJobStatus($job, $status) {
        if ($status == Status::STATUS_COMPLETE || $status == Status::STATUS_FAILED) {//成功/失败之后,删除running的job
            $this->dbInstance->delByKey('lineque:job:' . Status::statusToString(Status::STATUS_RUNNING) . ':' . $job['id']);
        }
        $this->updateStat($status);
        return $this->dbInstance->updateJobStatus('lineque:job:' . Status::statusToString($status) . ':' . $job['id'], $status, $job);
    }

    /**
     * lineque:stat用以统计四种状态的数量
     * 这里修改每种数量的增减变化
     * @param type $status
     * @param type $step
     * @param type $inc
     * @return type
     */
    private function updateStat($status, $inc = true, $step = 1) {
        if ($inc) {
            return $this->dbInstance->incrKey('lineque:stat:' . Status::statusToString($status), $step);
        } else {
            return $this->dbInstance->decrKey('lineque:stat:' . Status::statusToString($status), $step);
        }
    }

    /**
     * 新增一个job
     * @param type $queue
     * @param type $class
     * @param type $args
     * @return type
     */
    public function addJob($queue, $class, $args = null) {
        $this->updateStat(Status::STATUS_WAITING);
        return $this->dbInstance->addJobToList('lineque:' . $queue, $class, $args);
    }

    /**
     * job执行状态,正常的job是从执行这个方法
     * 这个方法主动去实例化用户指定的类,并主动调用其perform方法
     * @param type $job
     * @return boolean
     */
    public function perform($job) {
        try {
            $instance = $this->getAppInstance($job);
            $this->workingOn($job); //开始执行
            $instance->perform();
        } catch (\Exception $e) {
            throw $e;
            return false;
        }
        return true;
    }

    /**
     * 获取用户指定的类,并初始化其参数
     * @param type $job
     * @return type
     * @throws \Exception
     * @throws Exception
     */
    public function getAppInstance($job) {
        if (!is_null($this->appInstance)) {
            return $this->appInstance;
        }
        if (!class_exists($job['class'])) {
            echo '找不到' . $job['class'] . '.' . PHP_EOL;
            throw new \Exception('找不到' . $job['class'] . '.');
        }
        if (!method_exists($job['class'], 'perform')) {
            echo $job['class'] . '没有perform方法.' . PHP_EOL;
            throw new Exception($job['class'] . '没有perform方法.');
        }
        $this->appInstance = new $job['class']();
        $this->appInstance->job = $job;
        return $this->appInstance;
    }

    /**
     * 实例化不同的数据库,目前默认为redis,后可改为mysql,文件等方式
     * @param type $dbConf
     * @return RedisDb
     */
    public function doDbInstance($dbConf = null) {
        $dbConf ?: $dbConf = Conf::getConf();
        if ($dbConf) {
            switch (strtolower($dbConf['DBTYPE'])) {
                case "redis":
                default :
                    return new RedisDb($dbConf['Redis']);
            }
        } else {
            die('数据库配置无效');
        }
    }

}
