#!/usr/bin/env php
<?php
define('LineQue', __DIR__);
define('APP', __DIR__ . '/App');

// 只允许在cli下面运行  
if (php_sapi_name() != "cli") {
    die("only run in command line mode\n");
}
$LANG = getenv('LANG');
$QUE = getenv('QUE');
$INTERVAL = getenv('INTV'); //worker循环间隔
$queue = $QUE ? $QUE : 'default';
define('LOGPATH', LineQue . '/LineQue.log');

require_once __DIR__ . '/Lib/Autoload.php';

if (!class_exists('LineQue\Lib\Autoload', false)) {
    die('自动加载类错误' . PHP_EOL);
}
if (!function_exists('pcntl_fork')) {
    die('不支持pcntl_fork函数' . PHP_EOL);
}
LineQue\Lib\Autoload::start();

$worker = new \LineQue\Worker\Master($queue, $INTERVAL > 0 ? $INTERVAL : 5, 0, $LANG);
//file_put_contents($queue . '_LineQue.pid', getmypid()) or die('目录无写权限');
$worker->startWork();
