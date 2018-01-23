<?php

namespace LineQue\Lib;

class Autoload {

    public static function start() {
        spl_autoload_register('LineQue\Lib\Autoload::autoload');
    }

    /**
     * 类库自动加载
     * @param string $class 对象类名
     * @return void
     */
    public static function autoload($class) {
        // 检查是否存在映射
        if (false !== strpos($class, '\\')) {
            $filename = str_replace('\\', '/', $class) . '.php';
            $FirstNamespace = substr($filename, 0, strpos($filename, '/'));
            switch ($FirstNamespace) {
                case 'LineQue':
                    $filename = dirname(LineQue) . '/' . $filename;
                    break;
                default :
                    $filename = dirname(APP) . '/' . $filename;
            }
//            print_r(is_file($filename) . $filename . '<br/>');
            if (is_file($filename)) {
                include $filename;
            }
        }
    }

}
