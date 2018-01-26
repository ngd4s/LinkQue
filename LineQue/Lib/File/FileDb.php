<?php

namespace LineQue\Lib\File;

use LineQue\Lib\DbInterface;
use LineQue\Lib\Status;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FileDb
 *
 * @author Administrator
 */
class FileDb implements DbInterface {

    private $file;

    public function __construct($dbConf = null) {
        $this->file = new FileD($dbConf['PATH']);
    }

    public function addJobToList($que, $class, $args = null, $id = null) {
        $id = $id ? $id : md5(uniqid('', true));
        $msg = json_encode(array('class' => $class, 'args' => $args, 'id' => $id));
        if ($this->file->appendFile($que . 'List.lin', $msg)) {
            
        }
        return $id;
    }

    public function updateJobStatus($jobid, $status, $otherinfo = null) {
        return $this->file->appendFile('Job' . Status::statusToString($status) . '.lin', json_encode($otherinfo));
    }

    public function delByJobid($jobid, $status) {
        return $this->file->delALine('Job' . Status::statusToString($status) . '.lin', $jobid);
    }

    public function closeDbInstance() {
        return $this->file->closeFile();
    }

    public function incrStat($status, $step = 1) {
        $state = $this->file->getFirstLine('LineState.lin');
        $statData = json_decode($state, true);
        $statData[Status::statusToString($status)] = isset($statData[Status::statusToString($status)]) ? intval($statData[Status::statusToString($status)] + $step) : 1;
        return $this->file->writeFile('LineState.lin', json_encode($statData));
    }

    public function decrStat($status, $step = 1) {
        $state = $this->file->getFirstLine('LineState.lin');
        $statData = json_decode($state, true);
        $statData[Status::statusToString($status)] = isset($statData[Status::statusToString($status)]) && intval($statData[Status::statusToString($status)] - $step) > 0 ? intval($statData[Status::statusToString($status)] - $step) : 0;
        return $this->file->writeFile('LineState.lin', json_encode($statData));
    }

    public function getJob($queue) {
        $job = $this->file->getFirstLine($queue . 'List.lin');
        return json_decode($job, true);
    }

    public function popJob($queue) {
        $job = $this->file->popFirstLine($queue . 'List.lin');
        return json_decode($job, true);
    }

}
