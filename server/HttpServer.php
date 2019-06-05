<?php
class HttpServer
{
    public $di              = null;
    public $http            = null;
    public $application     = null;
    public static $rootPath  = null;
    public static $appPath   = null;

    public function __construct(array $serverConfig)
    {
        $this->http = new swoole_http_server($serverConfig['host'], $serverConfig['port']);
        $this->http->on('WorkerStart', array($this,'onWorkerStart'));
        $this->http->set(['worker_num'=>$serverConfig['worker_num'],'dispatch_mode'=>$serverConfig['dispatch_mode'],'max_request' =>$serverConfig['max_request'],'package_max_length'=>5000000]);
        $this->http->on('request',array($this,'onRequest'));
        $tcpServer = $this->http->addListener($serverConfig['listener_host'], $serverConfig['listener_port'], SWOOLE_SOCK_TCP);
        $tcpServer->set(array());
        $tcpServer->on('receive',array($this,'onReload'));
        $this->http->start();
    }

    public function onRequest($request, $response)
    {
        try{
            $_GET = !empty($request->get) ? $request->get : [];
            $_POST = !empty($request->post) ? $request->post : [];
            $_COOKIE = !empty($request->cookie) ? $request->cookie : [];
            $_SERVER = !empty($request->server) ? $request->server : [];
            $_FILES = !empty($request->files) ? $request->files : [];
            $GLOBALS['HTTP_RAW_POST_DATA'] = !empty($request->rawContent()) ? $request->rawContent() : null;
            $GLOBALS['SWOOLE_HTTP_RESPONSE'] = $response;
            $GLOBALS['SWOOLE_HTTP_REQUEST'] = $request;

            foreach ($request->header as $key=>$value){
                $_SERVER[$key] = $value;
            }
            $errorRootPath = $this->di->get('config')->logger->error_root_path;
            set_error_handler(array($this,'_setErrorHandler'),E_ALL | E_STRICT);
            $result = $this->application->handle()->getContent();
            $response->end($result);
        }catch (\Throwable $e){
            $message = ['code'=>$e->getCode(),'msg'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()];
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            $date = date('Y-m-d');
            $filePath = $errorRootPath.$date.DIRECTORY_SEPARATOR;
            $fileName = 'error.log';
            if(!is_dir($filePath)){
                mkdir($filePath,0777,true);
            }
            $time = date('Y-m-d H:i:s');
            $filePath .= $fileName;
            $message = "[{$time}]{$message}".PHP_EOL;
            $logger = new \Phalcon\Logger\Adapter\File($filePath);
            $logger->log($message,\Phalcon\Logger::ERROR);
            $result = ['data'=>null,'msg'=>'操作失败，请稍后重试！','type'=>1];
            $result = json_encode($result,JSON_UNESCAPED_UNICODE);
            $response->status(500);
            $response->end($result);
        }
        return;
    }

    public function onWorkerStart()
    {
        //引入配置文件
        $config = require_once self::$rootPath.'/config/config.php';
        $router = require_once self::$rootPath.'/routes/router.php';
        //注册composer类库(如有用到去掉注释即可)
        //require_once self::$rootPath.'/vendor/autoload.php';

        //实例化DI&Handle the request对象
        $this->di = new \Phalcon\DI\FactoryDefault();
        $this->application = new \Phalcon\Mvc\Application($this->di);

        //指定项目日志配置项
        $config['logger'] = $config['logger']['home_log'];

        //解析模块配置
        $modules = $config['module'];
        $modules = $modules['modules'];
        foreach($modules as $key=>$module){
            $modules[$key]['path'] = self::$appPath.$module['path'];
        }
        //注册系统模块
        $this->application->registerModules($modules);
        //设置路由规则
        $this->di->set('router', function () use ($router){
            return $router;
        },true);

        //设置系统根路径
        $rootPath = self::$rootPath;
        $this->di->setShared('root_path',function() use ($rootPath){
            return $rootPath;
        });

        //设置项目路径
        $appPath = self::$appPath;
        $this->di->setShared('app_path',function() use ($appPath){
            return $appPath;
        });

        //加载配置
        $this->di->setShared('config',function() use ($config){
            $config = new \Phalcon\Config($config);
            return $config;
        });
    }

    public function onReload(swoole_server $serv, $fd, $from_id, $data)
    {
        if($data === 'reload'){
            $this->http->reload();
            $serv->send($fd,'success');
        }else if ($data === 'stop'){
            $this->http->shutdown();
        }
    }

    public function _setErrorHandler($errno, $errstr ,$errfile, $errline)
    {
        $env = getenv('ENV_PHP_VAR');
        if($env !== 'prod'){
            if(stripos($errstr,'PDO::getAttribute()') === false && stripos($errstr,'Http request is finished.') === false){
                $errorRootPath = $this->di->get('config')->logger->error_root_path;
                $message = ['code'=>$errno,'msg'=>$errstr,'file'=>$errfile,'line'=>$errline];
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
                $date = date('Y-m-d');
                $filePath = $errorRootPath.$date.DIRECTORY_SEPARATOR;
                $fileName = 'warning.log';
                if(!is_dir($filePath)){
                    mkdir($filePath,0777,true);
                }
                $filePath .= $fileName;
                $logger = new \Phalcon\Logger\Adapter\File($filePath);
                $logger->log($message,\Phalcon\Logger::WARNING);
            }
        }
    }
}
