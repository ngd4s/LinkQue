<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LineQue\Worker;

use LineQue\Lib\Language;

/**
 * Description of Line
 *
 * @author Administrator
 */
class ProcLine {

    private $logFile;
    private $initDisplay = array();

    public function __construct($logFile = null) {
        $this->logFile = $logFile;
    }

    /**
     * Display staring UI.
     *
     * @return void
     */
    public function displayUI($lang = 'CH') {
        self::safeEcho("┌───────────────────────────── LineQue ─────────────────────────────┐" . PHP_EOL);
        self::safeEcho("├───────────────────────────────────────────── LineQueVersion:" . Master::VERSION . " ┤" . PHP_EOL);
        self::safeEcho("│" . Language::getLanguage($lang)['Thanks1'] . "                                       │" . PHP_EOL);
        self::safeEcho("│" . Language::getLanguage($lang)['Thanks2'] . "                  │" . PHP_EOL);
        self::safeEcho("│" . Language::getLanguage($lang)['Thanks3'] . "                  │" . PHP_EOL);
        self::safeEcho("│" . Language::getLanguage($lang)['Thanks4'] . "                │" . PHP_EOL);
        $this->showInitDisplay();
        self::safeEcho("├──────────────────────────────────────────────── PHPVersion:" . PHP_VERSION . " ┤" . PHP_EOL);
        self::safeEcho("└───────────────────────────────────────────────────────────────────┘" . PHP_EOL);
//        self::safeEcho("\033[47;30muser\033[0m" . str_pad('', self::$_maxUserNameLength + 2 - strlen('user')) . "\033[47;30mworker\033[0m" . str_pad('', self::$_maxWorkerNameLength + 2 - strlen('worker')) . "\033[47;30mlisten\033[0m" . str_pad('', self::$_maxSocketNameLength + 2 - strlen('listen')) . "\033[47;30mprocesses\033[0m \033[47;30m" . "status\033[0m\n");
//
//        foreach (self::$_workers as $worker) {
//            self::safeEcho(str_pad($worker->user, self::$_maxUserNameLength + 2) . str_pad($worker->name, self::$_maxWorkerNameLength + 2) . str_pad($worker->getSocketName(), self::$_maxSocketNameLength + 2) . str_pad(' ' . $worker->count, 9) . " \033[32;40m [OK] \033[0m\n");
//        }
//        self::safeEcho("----------------------------------------------------------------\n");
//        if (self::$daemonize) {
//            global $argv;
//            $start_file = $argv[0];
//            self::safeEcho("Input \"php $start_file stop\" to quit. Start success.\n\n");
//        } else {
//            self::safeEcho("Press Ctrl-C to quit. Start success.\n");
//        }
        $this->initDisplay = null;
    }

    private function showInitDisplay() {
        foreach ($this->initDisplay as $string) {
            $lenth = strlen($string);
            for ($i = 0; $i < 67 - $lenth; $i++) {//结尾字符串补充这么多空格
                $string .= ' ';
            }
            self::safeEcho("│" . $string . "│" . PHP_EOL);
        }
    }

    public function initDisplay($string) {
        $this->initDisplay[] = $string;
    }

    /**
     * Safe Echo.
     *
     * @param $msg
     */
    public function safeEcho($msg) {
        echo $msg;
        $this->log($msg);
    }

    /**
     * Log.
     *
     * @param string $msg
     * @return void
     */
    public function log($msg) {
//        $msg = str_replace('\033[40;35m', '', $msg);
//        $msg = str_replace('\033[0m', '', $msg);
        if ($this->logFile) {
            file_put_contents((string) $this->logFile, date('Y-m-d H:i:s') . ' ' . $msg, FILE_APPEND | LOCK_EX);
        }
    }

}
