<?php
/**
 * Redis服务驱动
 */
namespace Lib\Extend\Cache;

use Phalcon\DI;

class Redis{
    public static $redis = null;
    public static $config = null;

    public static function connect()
    {
        try{
            self::$config = DI::getDefault()->get('config')->redis->toArray();
            $cluster = self::$config['cluster'];
            if($cluster === true){
                $redisNodes = self::$config['nodes'];
                $redisNodes = array_column($redisNodes, 'host');
                if(empty($redisNodes)){
                    throw new \Exception('请检查Redis的配置是否正确!', 1);
                }
                self::$redis = new \RedisCluster(NULL, $redisNodes,3);
            }else{
                $host = self::$config['node']['host'];
                $port = self::$config['node']['port'];
                $password = self::$config['node']['password'];
                self::$redis = new \Redis();
                self::$redis->connect($host, $port,3);
                if(!empty($password)){
                    self::$redis->auth($password);
                }
            }
        }catch(\Throwable $e){
            $error = 'Redis服务器连接失败:'.$e->getMessage();
            throw new \Exception($error, 1);
        }
    }
    /**
     * 初始化Redis实例
     * @return null|\Redis
     */
    public static function init()
    {
        if(self::$redis === null){
            self::connect();
            if(!empty(self::$config['opt_prefix'])){
                self::$redis->setOption(\Redis::OPT_PREFIX, self::$config['opt_prefix']);
            }
            return self::$redis;
        }
        self::checkoutConnect();
        return self::$redis;
    }

    public static function checkoutConnect()
    {
        try{
            self::$redis->set('redis_connection_checkout',1);
        }catch (\Exception $e){
            trigger_error('redis reconnect!',E_USER_ERROR);
            self::connect();
            if(!empty(self::$config['opt_prefix'])){
                self::$redis->setOption(\Redis::OPT_PREFIX, self::$config['opt_prefix']);
            }
        }
    }
}