<?php

namespace LineQue\Lib\File;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FileD
 *
 * @author Administrator
 */
class FileD {

    private $fileRs;
    private $filePath;

    public function __construct($filePath) {
        $this->filePath = str_replace('//', '/', $filePath . '/');
    }

    public function getFirstLine($filename) {
        $this->openFile($filename, 'r');
        $line = fgets($this->fileRs);
        $this->closeFile();
        return $line;
    }

    public function popFirstLine($filename) {
        $fullName = $this->filePath . $filename;
        $this->openFile($filename, 'r');
        $line = fgets($this->fileRs);
        ob_start();
        fpassthru($this->fileRs);
        $this->closeFile();
        file_put_contents($fullName, ob_get_clean());
        return $line;
    }

    public function delALine($filename, $key) {
        $fullName = $this->filePath . $filename;
        $tmpname = $this->filePath . $filename . '.temp'; //创建一个临时文件
        $this->openFile($filename, 'r');
        while (!feof($this->fileRs)) {//读取文件内容,一行一行的读,然后一行一行写入到临时文件中
            $line = fgets($this->fileRs);
            $lineDEC = json_decode($line, true);
            if (isset($lineDEC['id']) && $lineDEC['id'] == $key) {//如果这一行存在于这个文件中
                continue;
            }
        }
        $this->closeFile();
        unlink($fullName); //删除原来的文件
        if (file_exists($tmpname)) {
            rename($tmpname, $fullName); //把临时文件改名为指定文件,实现替换文件的过程
        }
        return true;
    }

    public function writeFile($filename, $msg) {
        return $this->writeF($filename, $msg);
    }

    public function appendFile($filename, $msg) {
        return $this->writeF($filename, $msg, 'a+');
    }

    private function openFile($filename, $mode) {
        $fullName = $this->filePath . $filename;
        $this->fileRs ? @fclose($this->fileRs) : null;
        if (!file_exists($fullName)) {
            $this->fileRs = fopen($fullName, 'w+'); //此函数会自己创建文件,
        } else {
            $this->fileRs = fopen($fullName, $mode); //此函数会自己创建文件,
        }
//        flock($this->fileRs, LOCK_SH);
    }

    private function writeF($filename, $msg, $mode = 'w') {
        if (!$msg) {
            return false;
        }
        $this->openFile($filename, $mode);
        if (!fwrite($this->fileRs, $msg . PHP_EOL)) {
            $this->closeFile();
            return true;
        }
        $this->closeFile();
        return false;
    }

    public function closeFile() {
        $this->fileRs ? @fclose($this->fileRs) : null;
        return true;
    }

//    public function buildFile($filename) {
//        $fullName = $this->filePath . $filename;
//        if (!file_exists($fullName)) {
//            
//        }
//    }
}
