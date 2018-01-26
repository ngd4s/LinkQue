<?php

namespace LineQue\Lib;

use Exception;
use LineQue\Config\Conf;
use LineQue\Lib\File\FileDb;
use LineQue\Lib\Mysql\MysqlDb;
use LineQue\Lib\Redis\RedisDb;
use LineQue\Worker\ProcLine;
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
    private $language;

    /**
     * 初始化数据库控制
     * @param type $dbConf
     */
    public function __construct($dbConf = null, $lang = 'CH') {
        $this->dbInstance = $this->doDbInstance($dbConf);
        $this->procLine = new ProcLine(LOGPATH);
        $this->language = $lang;
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
     * 这个方法主动去实例化用户指定的类,并主动调用其perform方法
     * @param type $job
     * @return boolean
     */
    public function run($job) {
        try {
            $instance = $this->getAppInstance($job);
            if (!$instance) {
                $this->procLine->safeEcho(Language::getLanguage($this->language)['AppFail'] . ':' . $job['class'] . PHP_EOL);
            } else {
                $this->workingOn($job); //开始执行
                $this->procLine->safeEcho(Language::getLanguage($this->language)['AppStartPerform'] . ':' . $job['id'] . PHP_EOL);
                $rs = $this->runApp($instance);
//                $this->procLine->safeEcho(Language::getLanguage($this->language)['AppEndPerform'] . ':' . $job['id'] . PHP_EOL);//执行结束
                if ($rs) {
                    $this->procLine->safeEcho(Language::getLanguage($this->language)['AppWorkingDone'] . ':' . $job['id'] . PHP_EOL);
                    $this->workingDone($job); //执行完成
                    return true;
                } else {
                    $this->procLine->safeEcho(Language::getLanguage($this->language)['AppWorkingFail'] . ':' . $job['id'] . PHP_EOL);
                    $this->workingFail($job); //执行失败
                    return false;
                }
            }
        } catch (Exception $e) {
            $this->procLine->safeEcho(Language::getLanguage($this->language)['AppException'] . ':' . $job['id'] . ':' . json_encode($e) . PHP_EOL);
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
            $this->procLine->safeEcho(Language::getLanguage($this->language)['AppCantFindClass'] . ':' . $job['class'] . PHP_EOL);
            throw new Exception('找不到' . $job['class'] . '.');
        }
        if (!method_exists($job['class'], 'run')) {
            $this->procLine->safeEcho(Language::getLanguage($this->language)['AppCantFindPerform'] . ':' . $job['class'] . PHP_EOL);
            throw new Exception($job['class'] . '没有run方法.');
        }
        $intf = class_implements($job['class']);
        if (!isset($intf['LineQue\Lib\AppInterface'])) {
            $this->procLine->safeEcho(Language::getLanguage($this->language)['AppCantFindInterface'] . ':' . $job['class'] . PHP_EOL);
            throw new Exception($job['class'] . '没有perform方法.');
        }
        return new $job['class']($job); //实例化job
    }

    /**
     * 实例化不同的数据库,目前默认为redis,后可改为mysql,文件等方式
     * @param type $dbConf
     * @return RedisDb
     */
    public function doDbInstance($dbConf = null) {
        $dbConf ?: $dbConf = Conf::getConf();
        if ($dbConf) {
//            $type = ucfirst(strtolower($dbConf['DBTYPE']));
//            $Dbtype = ucfirst(strtolower($dbConf['DBTYPE']));
//            $class = $Dbtype . "Db";
//            return new $class($dbConf[$Dbtype]);
            switch (strtolower($dbConf['DBTYPE'])) {
                case "file":
                    return new FileDb($dbConf['File']);
                case "mysql":
                    return new MysqlDb($dbConf['Mysql']);
                case "redis":
                default :
                    return new RedisDb($dbConf['Redis']);
            }
        } else {
            die('数据库配置无效');
        }
    }

}
