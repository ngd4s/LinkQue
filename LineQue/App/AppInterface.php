<?php

namespace App;

/**
 * App接口,用户可以继承本接口
 *
 * @author Linko
 * @email 18716463@qq.com
 * @link https://github.com/kknv/LinkQue git上的项目地址
 * @version 1.0.0
 */
interface AppInterface {

    /**
     * job执行方法
     */
    public function run();
}
