<?php

namespace LineQue\Worker;

use LineQue\Lib\dbJobInstance;

/**
 * Description of Worker
 *
 * @author Administrator
 */
class Worker {

    private $Que;
    private $interval;
    private $DbInstance = null;
    private $procLine = null;

    public function __construct($Que, $interval) {
        $this->Que = $Que;
        $this->interval = $interval;
        $this->DbInstance = new dbJobInstance();
        $this->procLine = new ProcLine();
    }

    public function startWork() {
        $this->work(); //此处已经是子进程的子进程了,可以在此处进行下一步逻辑了
    }

    public function work() {
        while (1) {
            $job = $this->DbInstance->getJob($this->Que); //此时可以执行一个新job
            if ($job) {
                $this->procLine->safeEcho(posix_getpid() . ':' . json_encode($job) . PHP_EOL);
                $canDo = isset($job['args']['lineDoTime']) && $job['args']['lineDoTime'] > 0 ? ($job['args']['lineDoTime'] <= time() ? true : false) : true; //如果设置了执行时间,则在执行时间之后才出队
                if ($canDo) {
                    $job = $this->DbInstance->popJob($this->Que); //将这个job出队
                    $this->doForkJob($job);
                }
            }
            usleep($this->interval * 1000000);
        }
    }

    private function doForkJob($job) {
        $pid = pcntl_fork();
        if ($pid > 0) {//父进程等待子进程结束
            $this->waitingChildProcess($pid, $job);
        } elseif ($pid == 0) {//子进程处理job
            if ($this->DbInstance->perform($job)) {
                exit(0); //子进程退出,父进程将收到这个0
            }
        } else {
            
        }
    }

    private function waitingChildProcess($pid, $job) {
        // Parent process, sit and wait
        $status = "等待子进程";
        pcntl_wait($status);
        $exitStatus = pcntl_wexitstatus($status);
        if ($exitStatus !== 0) {
            $this->DbInstance->workingFail($job); //执行失败
        } else {
            $this->DbInstance->workingDone($job); //执行完成
        }
    }

}
