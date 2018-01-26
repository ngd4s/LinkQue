<?php

namespace LineQue\Lib\Mysql;

use PDOException;

/**
 * PDO数据库驱动 
 */
class Pdo {

    protected $PDOStatement = null;
    public $linkPDO = null;
    // 是否使用永久连接
    public $pconnect = false;
    protected $config;
    protected $queryStr;
    protected $transTimes = 0;
    protected $error;

    /**
     * 架构函数 读取数据库配置信息
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config = '') {
        $config ? $this->config = $config : array();
        isset($this->config['params']) ?: $this->config['params'] = array();
        $this->reConnect();
    }

    /**
     * 初始化数据库连接
     * @access protected
     * @param boolean $master 主服务器
     * @return void
     */
    public function reConnect() {
        // 默认单数据库
        if (!$this->linkPDO) {
            $this->connect();
        }
    }

    /**
     * 连接数据库方法
     * @access public
     */
    public function connect($config = '') {
        if (!isset($this->linkPDO)) {
            if (empty($config)) {
                $config = $this->config;
            }
            if ($this->pconnect) {
                $config['params'][\PDO::ATTR_PERSISTENT] = true;
            }
            if (version_compare(PHP_VERSION, '5.3.6', '<=')) { //禁用模拟预处理语句
                $config['params'][\PDO::ATTR_EMULATE_PREPARES] = false;
            }
            try {
                $this->linkPDO = new \PDO("mysql:host=" . $config['HOST'] . ";port=" . $config['PORT'] . ";dbname=" . $config['DBNAME'] . "", $config['USER'], $config['PWD'], $config['params']);
            } catch (PDOException $e) {
                throw $e;
            }
            $this->linkPDO->exec('SET NAMES ' . $config['CHARSET']);
        }
        return $this->linkPDO;
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param string $str  sql指令
     * @param array $bind 参数绑定
     * @return mixed
     */
    public function query($str, $bind = array()) {
        $this->reConnect();
        $this->doPrepare($str, $bind);
        return $this->getAll();
    }

    /**
     * 执行语句
     * @access public
     * @param string $str  sql指令
     * @param array $bind 参数绑定
     * @return integer
     */
    public function execute($str, $bind = array()) {
        $this->reConnect();
        $this->doPrepare($str, $bind);
        $this->numRows = $this->PDOStatement->rowCount();
        return $this->numRows;
    }

    private function doPrepare($str, $bind = array()) {
        $this->PDOStatement ? $this->PDOStatement->closeCursor() : null; //释放前次的查询结果
        $this->PDOStatement = $this->linkPDO->prepare($str);
        if (false === $this->PDOStatement) {
            throw new Exception($this->error());
        }
//        print_r($this->PDOStatement);
        // 参数绑定
        $this->bindPdoParam($bind);
        $result = $this->PDOStatement->execute();
        if (false === $result) {
            throw new \Exception($this->error());
        }
        return $result;
    }

    /**
     * 参数绑定
     * @access protected
     * @return void
     */
    protected function bindPdoParam($bind) {
        // 参数绑定
        foreach ($bind as $key => $val) {
            if (is_array($val)) {
                array_unshift($val, $key);
            } else {
                $val = array($key, $val);
            }
            call_user_func_array(array($this->PDOStatement, 'bindValue'), $val);
        }
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans() {
        $this->reConnect(true);
        if (!$this->linkPDO) {
            throw new Exception('数据库连接失败');
        }
        //数据rollback 支持
        if ($this->transTimes == 0) {
            $this->linkPDO->beginTransaction();
        }
        $this->transTimes++;
        return;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolen
     */
    public function commit() {
        if ($this->transTimes > 0) {
            $result = $this->linkPDO->commit();
            $this->transTimes = 0;
            if (!$result) {
                throw new Exception($this->error());
            }
        }
        return true;
    }

    /**
     * 事务回滚
     * @access public
     * @return boolen
     */
    public function rollback() {
        if ($this->transTimes > 0) {
            $result = $this->linkPDO->rollback();
            $this->transTimes = 0;
            if (!$result) {
                throw new Exception($this->error());
            }
        }
        return true;
    }

    /**
     * 获得所有的查询数据
     * @access private
     * @return array
     */
    private function getAll() {
        //返回数据集
        $result = $this->PDOStatement->fetchAll(\PDO::FETCH_ASSOC);
        $this->numRows = count($result);
        return $result;
    }

    /**
     * 关闭数据库
     * @access public
     */
    public function close() {
        $this->linkPDO = null;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @access public
     * @return string
     */
    public function error() {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $this->error = $error[1] . ':' . $error[2];
        } else {
            $this->error = '';
        }
        if ('' != $this->queryStr) {
            $this->error .= "\n [ SQL语句 ] : " . $this->queryStr;
        }
        return $this->error;
    }

//
//    /**
//     * SQL指令安全过滤
//     * @access public
//     * @param string $str  SQL指令
//     * @return string
//     */
//    public function escapeString($str) {
//        switch ($this->dbType) {
//            case 'MSSQL':
//            case 'SQLSRV':
//            case 'MYSQL':
//                return addslashes($str);
//            case 'PGSQL':
//            case 'IBASE':
//            case 'SQLITE':
//            case 'ORACLE':
//            case 'OCI':
//                return str_ireplace("'", "''", $str);
//        }
//    }

    /**
     * value分析
     * @access protected
     * @param mixed $value
     * @return string
     */
    protected function parseValue($value) {
        if (is_string($value)) {
            $value = strpos($value, ':') === 0 ? $this->escapeString($value) : '\'' . $this->escapeString($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
            $value = $this->escapeString($value[1]);
        } elseif (is_array($value)) {
            $value = array_map(array($this, 'parseValue'), $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }

    /**
     * 获取最后插入id
     * @access public
     * @return integer
     */
    public function getLastInsertId($dbType = 'MYSQL') {
        switch ($dbType) {
            case 'PGSQL':
            case 'SQLITE':
            case 'MSSQL':
            case 'SQLSRV':
            case 'IBASE':
            case 'MYSQL':
                return $this->linkPDO->lastInsertId();
            case 'ORACLE':
            case 'OCI':
                $sequenceName = $this->table;
                $vo = $this->query("SELECT {$sequenceName}.currval currval FROM dual");
                return $vo ? $vo[0]["currval"] : 0;
        }
    }

}
