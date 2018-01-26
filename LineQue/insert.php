<?php

define('LineQue', __DIR__);
define('APP', __DIR__ . '/App');
define('LOGPATH', LineQue . '/LineQue.log');

require_once __DIR__ . '/Lib/Autoload.php';
if (!class_exists('LineQue\Lib\Autoload', false)) {
    die('自动加载类错误' . PHP_EOL);
}
LineQue\Lib\Autoload::start();
$DbInstance = new LineQue\Lib\dbJobInstance();
$jobid = $DbInstance->addJob('default', '\App\FileP', array());
print_r($jobid);
