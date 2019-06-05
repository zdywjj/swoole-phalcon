<?php
$env = getenv('ENV_PHP_VAR');
if(empty($env)){
    throw new \Exception('ENV_PHP_VAR 环境变量不能为空！');
}
$envpath = "include_env/{$env}/";
$stapath = "include_sta/";
return [
    'database'	    => require_once $envpath.'database.php',
    'logger'	    => require_once $envpath.'logger.php',
    'redis'		    => require_once $envpath.'redis.php',
    'rabbitmq'	    => require_once $envpath.'rabbitmq.php',
    'module'	    => require_once $stapath.'module.php',
    'interfaceurl'  => require_once $envpath.'interfaceurl.php',
];


