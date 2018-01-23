#!/usr/bin/env php
<?php
define('LineQue', __DIR__);
define('APP', __DIR__ . '/App');

require_once __DIR__ . '/Lib/Autoload.php';

if (!class_exists('LineQue\Lib\Autoload', false)) {
    die('自动加载类错误' . PHP_EOL);
} else {
    LineQue\Lib\Autoload::start();
}
if (!function_exists('pcntl_fork')) {
    die('不支持pcntl_fork函数' . PHP_EOL);
}

$QUE = getenv('QUE');
$queue = $QUE ? $QUE : 'default';
$INTERVAL = getenv('INTERVAL'); //worker循环间隔
$worker = new LineQue\Worker\Master($queue, $INTERVAL > 0 ? $INTERVAL : 5);
//file_put_contents($queue . '_LineQue.pid', getmypid()) or die('目录无写权限');
$worker->startWork();
