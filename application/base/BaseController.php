<?php
namespace Base;

use \Phalcon\Mvc\Controller;
use \Phalcon\Http\Response;
use Phalcon\Http\Response\Headers;
use Phalcon\DI;

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
}