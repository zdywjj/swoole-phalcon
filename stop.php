<?php
$env = getenv('ENV_PHP_VAR');
if(empty($env)){
    throw new \Exception('ENV_PHP_VAR 环境变量不能为空！');
}
//定义系统根目录路径&应用目录路径
define('ROOT_PATH', dirname(__FILE__));
$serverConfig = require_once ROOT_PATH."/config/include_env/{$env}/server.php";

$client = new swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
if (!$client->connect($serverConfig['listener_host'], $serverConfig['listener_port'])){
    exit("connect failed. Error: {$client->errCode}\n");
}
$client->send("stop");
$client->close();
