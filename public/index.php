<?php
$env = getenv('ENV_PHP_VAR');
if(empty($env)){
    throw new \Exception('ENV_PHP_VAR 环境变量不能为空！');
}
date_default_timezone_set('Asia/Shanghai');
//定义系统根目录路径&应用目录路径
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',ROOT_PATH.'/application');
$serverConfig = require_once ROOT_PATH."/config/include_env/{$env}/server.php";
if(empty($serverConfig)){
    throw new \Exception('服务器配置项不能为空！');
}

require_once ROOT_PATH.'/server/HttpServer.php';
HttpServer::$rootPath = ROOT_PATH;
HttpServer::$appPath = APP_PATH;
new HttpServer($serverConfig);


