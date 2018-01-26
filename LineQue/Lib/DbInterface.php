<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LineQue\Lib;

/**
 *
 * @author Administrator
 */
interface DbInterface {

    /**
     * 根据队列名获取头部的一个job,但是不出队
     * @param type $queue
     */
    public function getJob($queue);

    /**
     * 出队一个job
     * @param type $queue
     */
    public function popJob($queue);

    /**
     * 队列表中插入一个job
     * @param type $key
     * @param type $class
     * @param type $args
     * @param type $id
     */
    public function addJobToList($que, $class, $args = null, $id = null);

    /**
     * 更新job状态,此时更新的是状态记录表
     * @param type $jobid
     * @param type $status
     * @param type $otherinfo
     */
    public function updateJobStatus($jobid, $status, $otherinfo = null);

    /**
     * 根据jobid删除状态记录表中的记录
     * @param type $jobid
     */
    public function delByJobid($jobid, $status);

    public function incrStat($status, $step = 1);

    public function decrStat($status, $step = 1);

    /**
     * 关闭数据库实例
     */
    public function closeDbInstance();
}
