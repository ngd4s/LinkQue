<?php

define('LineQue', __DIR__);
define('APP', __DIR__ . '/App');

require_once __DIR__ . '/Lib/Autoload.php';
if (!class_exists('LineQue\Lib\Autoload', false)) {
    die('自动加载类错误' . PHP_EOL);
} else {
    LineQue\Lib\Autoload::start();
}

$jober = new LineQue\Jober\Jober();
for ($i = 0; $i < 3; $i++) {
    $jobid[] = $jober->addJob('default', 'App\FileP', array('x' => $i));
}
print_r($jobid);
