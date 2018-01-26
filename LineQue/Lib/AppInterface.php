<?php

namespace LineQue\Lib;

/**
 * App接口,用户必须继承本接口
 *
 * @author Administrator
 */
interface AppInterface {

    /**
     * job必然执行本方法
     */
    public function run();
}
