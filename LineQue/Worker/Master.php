<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LineQue\Worker;

/**
 * Description of Master
 *
 * @author Administrator
 */
class Master {

    const VERSION = '1.0.0';

    private $Que;
    private $interval;
    private $daemonize;
    private $masterPid;
    private $slavePid = 0;

//    private $procCount; //子进程数量
//    private $procPid = array(); //生成多个子线程,后续版本更新,因为多个子线程会导致资源锁的问题,而我还没有好的方法避免这个问题,所以目前,只能等于2,一个主线程一个子线程

    public function __construct($Que, $interval, $daemonize = false) {
        $this->Que = $Que;
        $this->interval = $interval;
        $this->daemonize = $daemonize;
        $this->procLine = new ProcLine();
    }

    public function startWork() {
        if ($this->daemonize) {
            $dan = $this->doDan(); //脱离控制台
        }
        $this->masterPid = posix_getpid();
//        $this->registerSigHandlers();
        $this->startMaster(); //此处已经是子进程的子进程了,可以在此处进行下一步逻辑了
    }

    public function startMaster() {
        while (1) {
//            pcntl_signal_dispatch(); //不适用ticks形式的信号注册方式,那种方式效率太低,借助work的循环进行信号处理工作
            if (!$this->slavePid) {//子进程是否已经存在
                $pid = pcntl_fork();
                if ($pid == 0) {
                    usleep(10000);
                    $SlaveWorker = new Worker($this->Que, $this->interval);
                    $SlaveWorker->startWork();
                    return true;
                } elseif ($pid > 0) {
                    $this->slavePid = $pid;
                    $this->displayUI();
                    $status = "等待子进程";
                    pcntl_wait($status);
                    $exitStatus = pcntl_wexitstatus($status);
                    $this->slavePid = 0;
                } else {
                    exit("fork进程出错,请检查PHP配置");
                }
            }
            sleep(1);
        }
    }

    /**
     * 使进程脱离控制台控制
     */
    public function doDan() {
        umask(0);
        $pid = pcntl_fork(); //从此处分成两个进程执行
        if ($pid > 0) {//这里是父进程要执行的,但上一行代码获取的pid是子进程的pid,由父进程获取
            exit(0); //父进程退出,子进程变成孤儿进程被1号进程收养,进程已经脱离终端控制
        } elseif ($pid == 0) {//子进程拿到的值是0,想要获取自己的进程号有其他方法,posix_getpid()
            posix_setsid(); // 最重要的一步，让该进程脱离之前的会话，终端，进程组的控制
            chdir(APP ? APP : '/'); // 修改当前进程的工作目录，由于子进程会继承父进程的工作目录，修改工作目录以释放对父进程工作目录的占用。
            /*
             * 通过上一步，我们创建了一个新的会话组长，进程组长，且脱离了终端，但是会话组长可以申请重新打开一个终端，为了避免
             * 这种情况，我们再次创建一个子进程，并退出当前进程，这样运行的进程就不再是会话组长。
             */
            $pid = pcntl_fork();
            if ($pid > 0) {
                exit(0);
            } elseif ($pid == 0) {
                fclose(STDIN); // 由于守护进程用不到标准输入输出，关闭标准输入，输出，错误输出描述符
                fclose(STDOUT);
                fclose(STDERR);
                return true;
            }
        } else {
            die("开启子进程失败,请确定PHP环境是否正常");
        }
    }

    public function displayUI() {
        if ($this->daemonize) {
            $this->procLine->initDisplay('Daemonize:True');
        } else {
            $this->procLine->initDisplay('Daemonize:False');
        }
        if ($this->masterPid) {
            $this->procLine->initDisplay('MasterPid:' . $this->masterPid);
        }
        if ($this->slavePid) {
            $this->procLine->initDisplay("SlaverPid:" . $this->slavePid);
        }
        $this->procLine->displayUI();
    }

    /**
     * 注册信号
     */
    private function registerSigHandlers() {
//        if (!function_exists('pcntl_signal')) {
//            return;
//        }
        // 停止
        pcntl_signal(SIGINT, array($this, 'signalHandler'), false);
        // 用户信号,可用于重载
        pcntl_signal(SIGUSR1, array($this, 'signalHandler'), false);
        // 用户信号
        pcntl_signal(SIGUSR2, array($this, 'signalHandler'), false);
        // connection status
        pcntl_signal(SIGIO, array($this, 'signalHandler'), false);
        // 忽略
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }

    /**
     * 信号处理函数
     * @param type $signo
     * @return boolean
     */
    public function signalHandler($signo) {
        switch ($signo) {
            case SIGIO: //
                echo "SIGIO" . PHP_EOL;
                break;
            case SIGINT: //
                echo "SIGINT" . PHP_EOL;
                break;
            case SIGUSR1: //用户自定义信号
                echo "SIGUSR1" . PHP_EOL;
                break;
            case SIGUSR2: //用户自定义信号
                echo "SIGUSR2" . PHP_EOL;
                break;
            default:
                return false;
        }
    }

/////////////////////////////////多进程,暂不可用
//    /**
//     * 多进程模式,开启多个进程
//     * @return boolean
//     */
//    public function forkWorkers() {
//        for ($i = 0; $i < $this->procCount; $i++) {
//            $pid = pcntl_fork();
//            if ($pid == -1) {
//                exit("fork进程出错,请检查PHP配置");
//            }
//            if ($pid == 0) {
//                usleep(10000);
//                return true;
//            } elseif ($pid > 0) {
//                $this->$procPid[] = $pid;
//            }
//        }
//        $this->displayUI();
//    }
}
