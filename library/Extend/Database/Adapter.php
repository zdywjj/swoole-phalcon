<?php
namespace Lib\Extend\Database;

use Phalcon\DI;
use Phalcon\Db;
use Lib\Extend\Cache\Redis;

class Adapter extends \Phalcon\Mvc\Model
{
    //当前来连接的数据源
    protected $_targetDb = '';
    //当前数据库连接对象
    protected $_connect  = null;
    //数据库轮询redis key前缀
    const WRITE_HASH_KEY = 'sjxshop_write_database_connection_';
    const READ_HASH_KEY  = 'sjxshop_read_database_connection_';
    const HASH_KEY       = 'sjxshop_database_connection_';
    /**
     * 数据库连接
     * @throws \Exception
     */
    protected function _connect()
    {
        try{
            $targetDb = $this->_targetDb;
            if(empty($targetDb)){
                throw new \Exception('请指定正确的数据源！', 1);
            }
            $config = DI::getDefault()->get('config')->database->$targetDb;
            if(!isset($config)){
                throw new \Exception("{$targetDb}数据库配置读取失败！", 1);
            }
            $config = $config->toArray();
            $mysqlProxy = $config['mysql_proxy'];
            $slaveBalance = $config['slave_balance'];

            if($mysqlProxy === true){
                $writeDatabase = $config['write_db'];
                $readDatabase  = $config['read_db'];
                if(empty($writeDatabase) || empty($readDatabase)){
                    throw new \Exception('请检查Mysql的配置是否正确!', 1);
                }
                if($slaveBalance === true){
                    $readKey = $this->_getDatabaseConnection($readDatabase,self::READ_HASH_KEY.$targetDb);
                    $writeKey = $this->_getDatabaseConnection($writeDatabase,self::WRITE_HASH_KEY.$targetDb);
                    $hashReadKey  = $readKey.$targetDb;
                    $hashWriteKey = $writeKey.$targetDb;
                }else{
                    reset($writeDatabase);
                    reset($readDatabase);
                    $readKey = key($readDatabase);
                    $writeKey = key($writeDatabase);
                    $hashReadKey  = $readKey.$targetDb;
                    $hashWriteKey = $writeKey.$targetDb;
                }
                $this->_setShared($hashReadKey,$readDatabase[$readKey]);
                $this->_setShared($hashWriteKey,$writeDatabase[$writeKey]);
                //设置要使用的数据库
                $this->setReadConnectionService($hashReadKey);
                $this->setWriteConnectionService($hashWriteKey);
            }else if($mysqlProxy === false){
                $database = $config['database'];
                if(empty($database)){
                    throw new \Exception('请检查Mysql的配置是否正确!', 1);
                }
                reset($database);
                $databaseKey = key($database);
                $hashDatabaseKey = $databaseKey.$targetDb;
                $this->_setShared($hashDatabaseKey,$database[$databaseKey]);
                $this->setConnectionService($hashDatabaseKey);
            }else{
                throw new \Exception('请检查Mysql的配置是否正确!', 1);
            }
        }catch(\Throwable $e){
            throw new \Exception($e->getMessage(), 1);
        }
    }

    /**
     * 注册当前使用的数据库服务
     * @param $key
     * @param $db
     */
    private function _setShared(string $key,array $db)
    {
        try{
            $serviceCheck = DI::getDefault()->has($key);
            if(!$serviceCheck){
                DI::getDefault()->setShared($key, function () use ($db){
                    return new \Lib\Extend\Database\Pdo\Mysql($db);
                });
            }
        }catch(\Throwable $e){
            throw new \Exception($e->getMessage(), 1);
        }

    }

    /**
     * 获取当前分配的数据库key
     * @param $database
     * @param $cacheHashKey
     * @return string
     * @throws \Exception
     */
    private function _getDatabaseConnection(array $database, string $cacheHashKey):string
    {
        if(!empty($database)){
            $redisObj = Redis::init();
            $database = array_keys($database);
            try{
                $hashKey = $redisObj->get($cacheHashKey);
                if(in_array($hashKey,$database)){
                    $key = array_search($hashKey, $database);
                    $key += 1;
                    $hashKey = isset($database[$key]) ? $database[$key] : current($database);
                }else{
                    $hashKey = current($database);
                }
                $redisObj->set($cacheHashKey,$hashKey);
            }catch(\Throwable $e){
                $error = $e->getMessage();
                throw new \Exception($error, 1);
            }
        }

        return isset($hashKey) ? $hashKey : '';
    }

    /**
     * 设置表名
     * @param $table
     */
    public function setTargetTable(string $table)
    {
        $this->setSource($table);
    }

    /**
     * @return mixed
     */
    public function getReadConnection()
    {
        $connect=parent::getReadConnection();
        $connect->checkConnected();
        return $connect;
    }
    /**
     * @return mixed
     */
    public function getWriteConnection()
    {
        $connect=parent::getWriteConnection();
        $connect->checkConnected();
        return $connect;
    }

    /**
     * 执行查询sql，使用读库连接对象
     * @param $sql
     * @param array|null $bindParams
     * @return array
     * @throws \Exception
     */
    public function select(string $sql, array $bindParams = null):array
    {
        if(empty($sql)){
            throw new \Exception('sql异常:sql不能为空!');
        }
        try{
            $this->_connect = $this->getReadConnection();
            $query = $this->_connect->query($sql,$bindParams);
            $query->setFetchMode(Db::FETCH_ASSOC);
            $data = $query->fetchAll();
        }catch (\PDOException $e){
            $err = $e->getMessage();
            if($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013 || stripos($err,'MySQL server has gone away') !== false) {
                try{
                    $dbService = $this->getReadConnectionService();
                    DI::getDefault()->remove($dbService);
                    $this->_connect();
                    $this->_connect = $this->getReadConnection();
                    $query = $this->_connect->query($sql,$bindParams);
                    $query->setFetchMode(Db::FETCH_ASSOC);
                    $data = $query->fetchAll();
                }catch (\PDOException $e){
                    throw new \Exception("数据库再次连接异常：{$err}#{$sql}", 1);
                }

            }else{
                throw new \Exception("sql异常[$sql]:{$err}", 1);
            }
        }
        return $data;
    }
    /**
     * 执行查询sql，使用主库连接对象【用于数据实时性要求高的特殊场景】
     * @param string $sql
     * @param array|null $bindParams
     * @return array
     * @throws \Exception
     */
    public function masterSelect(string $sql, array $bindParams = null):array
    {
        if(empty($sql)){
            throw new \Exception('sql异常:sql不能为空!');
        }
        try{
            $this->_connect = $this->getWriteConnection();
            $query = $this->_connect->query($sql,$bindParams);
            $query->setFetchMode(Db::FETCH_ASSOC);
            $data = $query->fetchAll();
        }catch (\Throwable $e){
            $error = $e->getMessage();
            throw new \Exception("sql异常[$sql]:{$error}");
        }
        return $data;
    }

    /**
     * 执行增删改sql语句，使用写库连接对象
     * @param $sql
     * @param $bindParams
     * @return string|bool
     * @throws \Exception
     */
    public function exec(string $sql, array $bindParams=NUll)
    {
        if(empty($sql)){
            throw new \Exception('sql异常:参数不能为空!');
        }
        try{
            $this->_connect = $this->getWriteConnection();
            $result = $this->_connect->execute($sql, $bindParams);
        }catch (\Throwable $e){
            $error = $e->getMessage();
            throw new \Exception("sql异常[$sql]:{$error}");
        }
        return $result;
    }
}