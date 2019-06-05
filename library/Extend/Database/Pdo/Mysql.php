<?php
namespace Lib\Extend\Database\Pdo;

class Mysql extends \Phalcon\Db\Adapter\Pdo\Mysql
{
    public function checkConnected()
    {
        try{
            $this->_pdo->getAttribute(\PDO::ATTR_SERVER_INFO);
        }catch(\PDOException $e){
            $err = $e->getMessage();
            if($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013 || stripos($err,'MySQL server has gone away') !== false) {
                return $this->connect();
            }else{
                throw new \Exception("数据库连接异常：{$err}", 1);
            }
        }
    }
}
