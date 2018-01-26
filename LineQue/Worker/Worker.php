<?php

namespace LineQue\Worker;

use Exception;
use LineQue\Lib\dbJobInstance;
use LineQue\Lib\Language;
use const LOGPATH;

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
    private $language;

    public function __construct($Que, $interval, $lang = 'CH') {
        $this->Que = $Que;
        $this->interval = $interval;
        $this->language = $lang;
        $this->DbInstance = new dbJobInstance();
        $this->procLine = new ProcLine(LOGPATH);
    }

    public function startWork() {
//        $this->registerSigHandlers();
        $this->work(); //此处已经是子进程的子进程了,可以在此处进行下一步逻辑了
    }

    public function work() {
        $this->procLine->safeEcho(Language::getLanguage($this->language)['SlaverStartWorkLoop'] . 'PID=' . posix_getpid() . PHP_EOL);
        while (1) {
//            $this->procLine->safeEcho(Language::getLanguage($this->language)['SlaverPing'] . PHP_EOL);//进程存活输出
            pcntl_signal_dispatch(); //查看信号队列
            $job = $this->DbInstance->getJob($this->Que); //此时可以执行一个新job
            $this->doAJob($job); //执行一个job
            usleep($this->interval * 1000000); //休眠多少秒
        }
    }

    private function doAJob($job) {
        if ($job) {
            $canDo = isset($job['args']['lineDoTime']) && $job['args']['lineDoTime'] > 0 ? ($job['args']['lineDoTime'] <= time() ? true : false) : true; //如果设置了执行时间,则在执行时间之后才出队
            if ($canDo) {
                $job = $this->DbInstance->popJob($this->Que); //将这个job出队
                $this->procLine->safeEcho(Language::getLanguage($this->language)['SlaverStartAJob'] . 'PID=' . posix_getpid() . 'JobInfo:' . json_encode($job) . PHP_EOL);
                try {
                    $this->DbInstance->run($job); //执行
                } catch (Exception $ex) {
                    $this->procLine->safeEcho(Language::getLanguage($this->language)['SlaverException'] . 'PID=' . posix_getpid() . ':' . json_encode($ex) . PHP_EOL);
                }
                $this->procLine->safeEcho(Language::getLanguage($this->language)['SlaverEndAJob'] . 'PID=' . posix_getpid() . ',JobId=' . $job['id'] . PHP_EOL);
            }
        }
        return true;
    }
//
//    /**
//     * 注册信号
//     */
//    private function registerSigHandlers() {
////        if (!function_exists('pcntl_signal')) {
////            return;
////        }
//        // 停止
//        pcntl_signal(SIGINT, array($this, 'signalHandler'), false);
//        // 用户信号,可用于重载
//        pcntl_signal(SIGUSR1, array($this, 'signalHandler'), false);
//        // 用户信号
//        pcntl_signal(SIGUSR2, array($this, 'signalHandler'), false);
//        // connection status
//        pcntl_signal(SIGIO, array($this, 'signalHandler'), false);
//        // 忽略
//        pcntl_signal(SIGPIPE, SIG_IGN, false);
//    }
//
//    /**
//     * 信号处理函数
//     * @param type $signo
//     * @return boolean
//     */
//    public function signalHandler($signo) {
//        $this->procLine->log(Language::getLanguage($this->language)['SlaverSigno'] . ':' . $signo . PHP_EOL);
//        switch ($signo) {
//            case SIGIO: //
//                $this->procLine->log('Slaver Signo:SIGIO' . PHP_EOL);
//                echo "Slaver SIGIO" . PHP_EOL;
//                break;
//            case SIGINT: //
//                $this->procLine->log('Slaver Signo:SIGINT' . PHP_EOL);
//                echo "Slaver SIGINT" . PHP_EOL;
//                break;
//            case SIGUSR1: //用户自定义信号
//                $this->procLine->log('Slaver Signo:SIGUSR1' . PHP_EOL);
//                echo "Slaver SIGUSR1" . PHP_EOL;
//                break;
//            case SIGUSR2: //用户自定义信号
//                $this->procLine->log('Slaver Signo:SIGUSR2' . PHP_EOL);
//                echo "Slaver SIGUSR2" . PHP_EOL;
//                break;
//            default:
//                return false;
//        }
//    }
}
