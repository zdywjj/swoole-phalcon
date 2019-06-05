<?php
/**
* 系统日志模块
*/
namespace Lib\Logger\Adapter;
use Phalcon\DI;

class Logger extends  \Phalcon\Logger\Adapter\File
{
	const INFO 		    = 6;
	const DEBUG 		= 7;
	const NOTICE 		= 5;
	const WARNING 	    = 4;
	const ERROR 		= 3;
	const TMP_PATH	    = '/tmp/php_error.log';

    /**
     * 用于业务中日志的记录
     * @param string $message
     * @param string $file
     * @param bool $async
     * @return bool
     */
	public static function write(string $message, string $file, bool $async = true):bool
	{
		try{
		    $date = date('Y-m-d H:i:s');
            $message = "[{$date}]{$message}".PHP_EOL;
			$filePath = self::init($file,self::INFO);
            $logger = new self($filePath);
            $logger->log($message,self::INFO);
		}catch(\Throwable $e){
			$error['type']	   = 'log';
			$error['upinfo']   = $message;
			$error['message']  = '日志写入失败:'.$e->getMessage();
            $error = json_encode($error);
            self::errors($error);
		}
		return true;
	}

    /**
     * 记录系因统运行产生的语法或服务错误信息
     * @param string $message
     * @param bool $async
     * @return bool
     */
	public static function errors(string $message, bool $async = true):bool
	{
		try{
            $date = date('Y-m-d H:i:s');
            $message = "[{$date}]{$message}".PHP_EOL;
		    $file = 'error.log';
			$filePath = self::init($file,self::ERROR);
            $logger = new self($filePath);
            $logger->log($message,self::ERROR);
		}catch(\Throwable $e){
			$error['type']	    = 'log';
			$error['upinfo']    = $message;
			$error['message']   = '系统错误日志写入失败:'.$e->getMessage();
			$error = json_encode($error);
            $logger = new self(self::TMP_PATH);
            $logger->log($error,self::ERROR);
		}
		return true;
	}

	private static function init(string $file, int $type):string
	{
		$loggerConfig = DI::getDefault()->get('config')->logger->toArray();
		$logRootPath = $loggerConfig['log_root_path'];
        $errorRootPath = $loggerConfig['error_root_path'];
		$date = date('Y-m-d');
		
		if($type === self::INFO){
			$filePath = $logRootPath.$date.DIRECTORY_SEPARATOR;
		}else if($type === self::ERROR){
			$filePath = $errorRootPath.$date.DIRECTORY_SEPARATOR;
		}
		if(!is_dir($filePath)){
			mkdir($filePath,0777,true);
		}
		$filePath .= $file;
		return $filePath;
	}
}