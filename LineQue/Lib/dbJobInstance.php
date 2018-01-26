<?php

namespace LineQue\Lib;

use Exception;
use LineQue\Config\Conf;
use LineQue\Lib\Redis\RedisDb;
use const LOGPATH;

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
    private $procLine = null;

    /**
     * 初始化数据库控制
     */
    public function __construct() {
        $this->dbInstance = $this->doDbInstance();
        $this->procLine = new ProcLine(defined(LOGPATH) ? LOGPATH : null);
    }

    /**
     * 获取一个Job,只获取不出队
     * @param type $queue
     * @return type
     */
    public function getJob($queue) {
        return $this->dbInstance->getJob($queue);
    }

    /**
     * 出队一个job
     * @param type $queue
     * @return type
     */
    public function popJob($queue) {
        return $this->dbInstance->popJob($queue);
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
            $this->dbInstance->delByJobid($job['id'], Status::STATUS_RUNNING);
        }
        $this->updateStat($status);
        return $this->dbInstance->updateJobStatus($job['id'], $status, $job);
    }

    /**
     * lineque:stat用以统计四种状态的数量
     * 这里修改每种数量的增减变化
     * @param type $status
     * @param type $inc
     * @param type $step
     * @return type
     */
    private function updateStat($status, $inc = true, $step = 1) {
        if ($inc) {
            return $this->dbInstance->incrStat($status, $step);
        } else {
            return $this->dbInstance->decrStat($status, $step);
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
        return $this->dbInstance->addJobToList($queue, $class, $args);
    }

    /**
     * job执行状态,正常的job是从执行这个方法
     * 这个方法主动去实例化用户指定的类,并主动调用其run方法
     * @param type $job
     * @return boolean
     */
    public function run($job) {
        try {
            $instance = $this->getAppInstance($job);
            if (!$instance) {
                $this->procLine->EchoAndLog('用户App初始化失败:' . $job['class'] . PHP_EOL);
            } else {
                $this->workingOn($job); //开始执行
                $this->procLine->EchoAndLog('用户APP开始执行:' . $job['id'] . PHP_EOL);
                $rs = $this->runApp($instance);
                if ($rs) {
                    $this->procLine->EchoAndLog('用户APP执行成功:' . $job['id'] . PHP_EOL);
                    $this->workingDone($job); //执行完成
                    return true;
                } else {
                    $this->procLine->EchoAndLog('用户APP执行失败:' . $job['id'] . PHP_EOL);
                    $this->workingFail($job); //执行失败
                    return false;
                }
            }
        } catch (Exception $e) {
            $this->procLine->EchoAndLog('用户APP执行异常:' . $job['id'] . ':' . json_encode($e) . PHP_EOL);
            throw $e;
        }
        return false;
    }

    /**
     * 将app的run方法放进独立的进程执行,两个好处
     * 1,保护instance进程
     * 2,run方法出错退出意味着job执行失败
     * @param type $instance
     * @return boolean
     */
    public function runApp($instance) {
        $dbConf = Conf::getConf();
        if ($dbConf['DBTYPE'] == 'Redis') {
            $pid = pcntl_fork();
            if ($pid > 0) {
                $status = 0;
                $exitPid = pcntl_wait($status);
                if ($exitPid && $status == 0) {
                    return true;
                }
            } elseif ($pid == 0) {
                $instance->run(); //执行用户的perform方法
                exit(0);
            }
            return false;
        } else {//非Redis方式,本人写的不好,开始写的时候没想到使用子进程管理数据库连接导致报错的问题
            try {
                $instance->run(); //执行用户的perform方法
                return true;
            } catch (Exception $ex) {
                return false;
            }
        }
    }

    /**
     * 获取用户指定的类,并初始化其参数
     * @param type $job
     * @return type
     * @throws Exception
     * @throws Exception
     */
    public function getAppInstance($job) {
        if (!class_exists($job['class'])) {
            $this->procLine->EchoAndLog('找不到用户APP:' . $job['class'] . PHP_EOL);
            throw new Exception('找不到' . $job['class'] . '.');
        }
        if (!method_exists($job['class'], 'run')) {
            $this->procLine->EchoAndLog('用户APP找不到run方法:' . $job['class'] . PHP_EOL);
            throw new Exception($job['class'] . '没有run方法.');
        }
//        $intf = class_implements($job['class']);
//        if (!isset($intf['LineQue\Lib\AppInterface'])) {
//            $this->procLine->EchoAndLog('用户APP未实现AppInterface接口:' . $job['class'] . PHP_EOL);
//            throw new Exception($job['class'] . '没有perform方法.');
//        }
        return new $job['class']($job); //实例化job
    }

    /**
     * 实例化不同的数据库,目前默认为redis,后可改为mysql,文件等方式
     * @param type $dbConf
     * @return RedisDb
     */
    public function doDbInstance() {
        $dbConf = Conf::getConf();
        if ($dbConf) {
//            $Dbtype = ucfirst(strtolower($dbConf['DBTYPE']));
//            $class = $Dbtype . "Db";
//            return new $class($dbConf[$Dbtype]);
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
