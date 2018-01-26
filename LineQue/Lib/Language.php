<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LineQue\Lib;

/**
 * Description of Language
 *
 * @author Administrator
 */
class Language {

    public static $Language = array(
        'StartNewSlaver' => array('CH' => '开始新的队列进程', 'EN' => 'Start New Queue Slaver Process'),
        'StartForkSlaver' => array('CH' => '开始创建子进程', 'EN' => 'Start Fork Slaver Process'),
        'ForkSlaverSuccess' => array('CH' => '创建子进程成功', 'EN' => 'Fork Slaver Process Success'),
        'SlaverExit' => array('CH' => '子进程意外退出', 'EN' => 'Slaver Process Exit Unexpectedly'),
        'ExitSingno' => array('CH' => '退出信号', 'EN' => 'Exit Singno'),
        'SlaverStartWork' => array('CH' => '子进程开始执行', 'EN' => 'Slaver Process Start Work'),
        'ForkSlaverError' => array('CH' => '创建子进程出错,请检查PHP配置', 'EN' => 'Slaver Process Error,Check PHP Config(Need pcntl_fork)'),
        'ProcessInfo' => array('CH' => '进程信息─────', 'EN' => ' ProcessInfo '),
        'Runtime' => array('CH' => '运行参数─', 'EN' => ' Runtime '),
        'DbConFig' => array('CH' => '数据库配置', 'EN' => ' DbConFig '),
        'Daemonize' => array('CH' => 'Daemonize', 'EN' => 'Daemonize'),
        'MasterPid' => array('CH' => 'MasterPid', 'EN' => 'MasterPid'),
        'SlaverPid' => array('CH' => 'SlaverPid', 'EN' => 'SlaverPid'),
        'Queue' => array('CH' => 'Queue', 'EN' => 'Queue'),
        'Interval' => array('CH' => 'Interval', 'EN' => 'Interval'),
        'LogPath' => array('CH' => 'LogPath', 'EN' => 'LogPath'),
        'DBTYPE' => array('CH' => 'DBTYPE', 'EN' => 'DBTYPE'),
        'MasterSigno' => array('CH' => '主进程收到信号', 'EN' => 'Master Get A Signo'),
        'SlaverStartWorkLoop' => array('CH' => '子进程开始循环', 'EN' => 'Slaver Process Start Work Loop'),
        'SlaverStartAJob' => array('CH' => '子进程即将开始一个新Job', 'EN' => 'Slaver Process Start A Job'),
        'SlaverException' => array('CH' => '新Job执行发生异常', 'EN' => 'Slaver Process Exception'),
        'SlaverEndAJob' => array('CH' => '新Job执行结束', 'EN' => 'Slaver Process End A Job'),
        'AppFail' => array('CH' => '用户App初始化失败', 'EN' => 'App init Fail'),
        'AppWorkingOn' => array('CH' => '用户APP即将执行', 'EN' => 'App Working On'),
        'AppStartPerform' => array('CH' => '用户APP开始执行', 'EN' => 'App Start Run'),
        'AppEndPerform' => array('CH' => '用户APP执行结束', 'EN' => 'App End Run'),
        'AppWorkingDone' => array('CH' => '用户APP执行成功', 'EN' => 'App Working Done'),
        'AppWorkingFail' => array('CH' => '用户APP执行失败', 'EN' => 'App Working Fail'),
        'AppException' => array('CH' => '用户APP执行异常', 'EN' => 'App Exception'),
        'AppCantFindClass' => array('CH' => '找不到用户APP', 'EN' => 'App Cant Find Class'),
        'AppCantFindPerform' => array('CH' => '用户APP找不到Perform方法', 'EN' => 'App Cant Find Run Funciton'),
        'AppCantFindInterface' => array('CH' => '用户APP未实现AppInterface接口', 'EN' => 'App Cant Find Interface(Your App must implements AppInterface)'),
        'SlaverSigno' => array('CH' => '子进程收到信号', 'EN' => 'Slaver Get A Signo'),
        'MasterPing' => array('CH' => '主进程存活...', 'EN' => 'Master Ping...'),
        'SlaverPing' => array('CH' => '子进程存活...', 'EN' => 'Slaver Ping...'),
        'Thanks1' => array('CH' => '感谢您选择LineQue___________', 'EN' => 'Thanks For Choosing LineQue.'),
        'Thanks2' => array('CH' => 'LineQue是一款基于PHP的简单队列程序_______________', 'EN' => 'It\'s a simple Queue Program,that is based on PHP.'),
        'Thanks3' => array('CH' => '本程序参考了很多PHP_RESQUE思想___________________', 'EN' => 'I have taken a lot of references from PHP_RESQUE.'),
        'Thanks4' => array('CH' => '需要更多帮助,请访问________________________________', 'EN' => 'Need Help?You can find more at http://www.baidu.com'),
    );

    /**
     * CH-中文
     * EN-英文
     * @param type $lan
     */
    public static function getLanguage($lan) {
        $lang = array();
        foreach (self::$Language as $key => $val) {
            $lang[$key] = isset($val[$lan]) ? $val[$lan] : $val["CH"];
        }
        return $lang;
    }

}
