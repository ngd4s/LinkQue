<?php

namespace LineQue\Lib\Redis;

use Exception;
use LineQue\Lib\DbInterface;
use Redis;

/**
 * Base Resque class.
 *
 * @package		Resque
 * @author		Chris Boulton <chris@bigcommerce.com>
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class RedisDb implements DbInterface {

    const VERSION = '1.0';

    /**
     * @var redis实例
     */
    public $redis = null;

    /**
     * redis中默认有16个数据库,默认采用0号
     * @var type 
     */
    private $dbId = 0;

    public function __construct($redisConf, $dbId = 0) {
        $this->redis = $this->getDbInstance($redisConf);
        $this->dbId = $dbId;
    }

    public function getDbInstance($redisConf) {
        if ($this->redis !== null) {
            return $this->redis;
        }
        if ($redisConf && class_exists('Redis')) {
            $redis = new Redis();
            $redis->connect($redisConf['SERVER'], $redisConf['PORT']);
            !$redisConf['PWD'] ?: $redis->auth($redisConf['PWD']);
            !$redisConf['DBID'] ?: $redis->select($redisConf['DBID']);
            if ($redis) {
                return $redis;
            } else {
                die('Redis初始化失败');
            }
        } else {
            die('Redis配置无效');
        }
    }

    public function closeDbInstance() {
        $this->redis->close();
        $this->redis = null;
        return true;
    }

    /**
     * 获取一个job
     * @param type $queue
     * @return type
     */
    public function getJob($queue) {
        $jobDec = null;
        while (1) {
            $job = $this->redis->lindex($queue, 0); //返回最后位置元素
            if (!$job) {
                break;
            }
            $jobDec = json_decode($job, true);
            if (!$jobDec) {
                $this->redis->lPop($queue); //这个不是标准的json,出队扔掉
            } else {
                break;
            }
        }
        return $jobDec;
    }

    /**
     * 出队一个job
     * @param type $queue
     * @return type
     */
    public function popJob($queue) {
        $job = $this->redis->lPop($queue);
        if (!$job) {
            return;
        }
        return json_decode($job, true);
    }

    public function updateJobStatus($jobid, $status, $job = null) {
        return $this->redis->set($jobid, json_encode(array('status' => $status, 'utime' => time(), 'job' => $job)));
    }

    public function delByKey($jobid) {
        return $this->redis->delete($jobid);
    }

//    public function updateStat($status, $step = 1, $inc = true) {
//        return $this->
//    }

    public function addJobToList($key, $class, $args = null, $id = null) {
        if (is_null($id)) {
            $id = $this->generateJobId();
        }

        if ($args !== null && !is_array($args)) {
            throw new Exception('参数必须传递数组');
        }
        $this->pushToList($key, array('class' => $class, 'args' => array($args), 'id' => $id, 'queue_time' => microtime(true),));
        return $id;
    }

    public function pushToList($key, $item) {
        $encodedItem = json_encode($item);
        if ($encodedItem === false) {
            return false;
        }
//        $this->redis->sadd('queues', $key);
        $length = $this->redis->rPush($key, $encodedItem);
        if ($length < 1) {
            return false;
        }
        return true;
    }

    public static function generateJobId() {
        return md5(uniqid('', true));
    }

///////////////////////////////////////////以下为基本操作///////////////////////////////////////////
    public function getByKey($key) {
        return $this->redis->get($key);
    }

    /**
     * 一个整型的key/value,增加他的值
     * @param type $key
     * @param type $step
     * @return type
     */
    public function incrKey($key, $step = 1) {
        return (bool) $this->redis->incrby($key, $step);
    }

    /**
     * 一个整型的key/value,增加他的值
     * @param type $key
     * @param type $step
     * @return type
     */
    public function decrKey($key, $step = 1) {
        return (bool) $this->redis->decrby($key, $step);
    }

///////////////////////////////////////////以下未整理///////////////////////////////////////////
    /**
     * Remove items of the specified queue
     *
     * @param string $queue The name of the queue to fetch an item from.
     * @param array $items
     * @return integer number of deleted items
     */
    public static function dequeue($queue, $items = Array()) {
        if (count($items) > 0) {
            return self::removeItems($queue, $items);
        } else {
            return self::removeList($queue);
        }
    }

    /**
     * Remove specified queue
     *
     * @param string $queue The name of the queue to remove.
     * @return integer Number of deleted items
     */
    public static function removeQueue($queue) {
        $num = self::removeList($queue);
        self::redis()->srem('queues', $queue);
        return $num;
    }

    /**
     * Pop an item off the end of the specified queues, using blocking list pop,
     * decode it and return it.
     *
     * @param array         $queues
     * @param int           $timeout
     * @return null|array   Decoded item from the queue.
     */
    public static function blpop(array $queues, $timeout) {
        $list = array();
        foreach ($queues AS $queue) {
            $list[] = 'queue:' . $queue;
        }

        $item = self::redis()->blpop($list, (int) $timeout);

        if (!$item) {
            return;
        }

        /**
         * Normally the Resque_Redis class returns queue names without the prefix
         * But the blpop is a bit different. It returns the name as prefix:queue:name
         * So we need to strip off the prefix:queue: part
         */
        $queue = substr($item[0], strlen(self::redis()->getPrefix() . 'queue:'));

        return array(
            'queue' => $queue,
            'payload' => json_decode($item[1], true)
        );
    }

    /**
     * Return the size (number of pending jobs) of the specified queue.
     *
     * @param string $queue name of the queue to be checked for pending jobs
     *
     * @return int The size of the queue.
     */
    public static function size($queue) {
        return self::redis()->llen('queue:' . $queue);
    }

    /**
     * Reserve and return the next available job in the specified queue.
     *
     * @param string $queue Queue to fetch next available job from.
     * @return Resque_Job Instance of Resque_Job to be processed, false if none or error.
     */
    public static function reserve($queue) {
        return Resque_Job::reserve($queue);
    }

    /**
     * Get an array of all known queues.
     *
     * @return array Array of queues.
     */
    public static function queues() {
        $queues = self::redis()->smembers('queues');
        if (!is_array($queues)) {
            $queues = array();
        }
        return $queues;
    }

    /**
     * Remove Items from the queue
     * Safely moving each item to a temporary queue before processing it
     * If the Job matches, counts otherwise puts it in a requeue_queue
     * which at the end eventually be copied back into the original queue
     *
     * @private
     *
     * @param string $queue The name of the queue
     * @param array $items
     * @return integer number of deleted items
     */
    private static function removeItems($queue, $items = Array()) {
        $counter = 0;
        $originalQueue = 'queue:' . $queue;
        $tempQueue = $originalQueue . ':temp:' . time();
        $requeueQueue = $tempQueue . ':requeue';

        // move each item from original queue to temp queue and process it
        $finished = false;
        while (!$finished) {
            $string = self::redis()->rpoplpush($originalQueue, self::redis()->getPrefix() . $tempQueue);

            if (!empty($string)) {
                if (self::matchItem($string, $items)) {
                    self::redis()->rpop($tempQueue);
                    $counter++;
                } else {
                    self::redis()->rpoplpush($tempQueue, self::redis()->getPrefix() . $requeueQueue);
                }
            } else {
                $finished = true;
            }
        }

        // move back from temp queue to original queue
        $finished = false;
        while (!$finished) {
            $string = self::redis()->rpoplpush($requeueQueue, self::redis()->getPrefix() . $originalQueue);
            if (empty($string)) {
                $finished = true;
            }
        }

        // remove temp queue and requeue queue
        self::redis()->del($requeueQueue);
        self::redis()->del($tempQueue);

        return $counter;
    }

    /**
     * matching item
     * item can be ['class'] or ['class' => 'id'] or ['class' => {:foo => 1, :bar => 2}]
     * @private
     *
     * @params string $string redis result in json
     * @params $items
     *
     * @return (bool)
     */
    private static function matchItem($string, $items) {
        $decoded = json_decode($string, true);

        foreach ($items as $key => $val) {
            # class name only  ex: item[0] = ['class']
            if (is_numeric($key)) {
                if ($decoded['class'] == $val) {
                    return true;
                }
                # class name with args , example: item[0] = ['class' => {'foo' => 1, 'bar' => 2}]
            } elseif (is_array($val)) {
                $decodedArgs = (array) $decoded['args'][0];
                if ($decoded['class'] == $key &&
                        count($decodedArgs) > 0 && count(array_diff($decodedArgs, $val)) == 0) {
                    return true;
                }
                # class name with ID, example: item[0] = ['class' => 'id']
            } else {
                if ($decoded['class'] == $key && $decoded['id'] == $val) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Remove List
     *
     * @private
     *
     * @params string $queue the name of the queue
     * @return integer number of deleted items belongs to this list
     */
    private static function removeList($queue) {
        $counter = self::size($queue);
        $result = self::redis()->del('queue:' . $queue);
        return ($result == 1) ? $counter : 0;
    }

    /*
     * Generate an identifier to attach to a job for status tracking.
     *
     * @return string
     */
}
