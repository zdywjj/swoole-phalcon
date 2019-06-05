<?php
namespace Base;

use \Phalcon\Mvc\Controller;
use \Phalcon\Http\Response;
use Phalcon\Http\Response\Headers;
use Phalcon\DI;
use Lib\Http\Request\File;

class BaseController extends Controller
{
    public function returnJson(array $content, int $code = 200)
    {
        $content = json_encode($content);
        $response = new Response();
        $swooleResponse = $GLOBALS['SWOOLE_HTTP_RESPONSE'];
        $swooleResponse->status($code);
        $response->setContent($content);
        $response->send();
    }

    /**
     * 获取POST请求参数
     * @param null $name
     * @param null $defaultValue
     * @return mixed|null
     */
    public function getPost($name = null, $defaultValue = null)
    {
        $contentType = $_SERVER['content-type'];
        $contentType = strtolower($contentType);
        switch($contentType){
            case 'application/json; charset=utf-8' :
            case 'application/json' :
                $params = $GLOBALS['HTTP_RAW_POST_DATA'];
                $params = json_decode($params,true);

                if($name === null){
                    return $params;
                }
                if(!array_key_exists($name,$params)){
                    return $defaultValue;
                }
                $value = $params[$name];
                return $value;
            default :
                if($name === null){
                    $value = $this->request->getPost();
                    return $value;
                }
                $value = $this->request->getPost($name,null,$defaultValue);
                return $value;
        }
    }

    /**
     * 获取上传的文件
     * @return array
     */
    public function getUploadedFiles()
    {
        $superFiles = $_FILES;
        $files = [];
        if(count($superFiles)>0){
            foreach($superFiles as $key=>$input){
                if(count($input) == count($input, 1)){
                    if($input['name'] !== ""){
                        $inputKey = $key;
                        $files[] = new File($input, $inputKey);
                    }
                }else{
                    foreach($input as $k=>$file){
                        if($file['name'] !== ""){
                            $inputKey = "{$key}.{$k}";
                            $files[] = new File($file, $inputKey);
                        }
                    }
                }
            }
        }
        return $files;
    }

    /**
     * 判断是否有文件上传
     * @return int
     */
    public function hasFiles():int
    {
        $numberFiles = 0;
        $files = $_FILES;
        foreach($files as $file){
            if(count($file) == count($file, 1)){
                if($file['name'] !== ""){
                    $numberFiles++;
                }
            }else{
                foreach($file as $f){
                    if($f['name'] !== ""){
                        $numberFiles++;
                    }
                }
            }
        }
        return $numberFiles;
    }
}