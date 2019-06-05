<?php
/**
 * $curl = new Curl();
 * $sendData = ['token'=>''];
 * $data = $curl->setUrl('http://127.0.0.1')->setMethod('GET')->setTimeOut(10)->sendRequest($sendData);
 */
namespace Lib\Http\Request;

class Curl
{
    //curl会话句柄
    protected $_curl        = null;
    //请求的url地址
    protected $_url         = null;
    //请求超时时间
    protected $_timeOut     = 10;
    //是否保持持续连接
    protected $_keepAlive   = true;
    //HTTP报文首部信息
    protected $_headers		 = null;
    //请求类型 GET POST
    protected $_method		 = null;
    //POST请求参数编码类型 application/x-www-form-urlencoded或multipart/form-data或application/json
    protected $_postType    = 0;

    /**
     * 设置请求地址
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->_url = $url;
        return $this;
    }
    /**
     * 设置是否使用keep alive模式
     * @param bool $keepAlive
     * @return $this
     */
    public function setKeepAlive(bool $keepAlive = true)
    {
        $this->_keepAlive = $keepAlive;
        return $this;
    }
    /**
     * 设置请求超时时间
     * @param int $timeOut
     * @return $this
     */
    public function setTimeOut(int $timeOut)
    {
        $this->_timeOut = $timeOut;
        return $this;
    }
    /**
     * 设置HTTP报文首部信息
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->_headers = $headers;
        return $this;
    }
    /**
     * 设置请求类型
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->_method = $method;
        return $this;
    }
    /**
     * 设置POST请求参数编码类型
     * @param int $type [0:application/x-www-form-urlencoded; 1:multipart/form-data; 2:application/json]
     * @return $this
     */
    public function setPostType(int $type)
    {
        $this->_postType = $type;
        return $this;
    }
    /**
     * 发送请求
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function sendRequest(array $data = [])
    {
        $this->_setopt($data);
        $return = curl_exec($this->_curl);
        if (!$return) {
            return ['error_code'=>1, 'data'=>null, 'error_msg'=>curl_error($this->_curl)];
        }
        $info = curl_getinfo($this->_curl);
        curl_close($this->_curl);

        if ($info['http_code'] != '200') {
            return ['error_code'=>1, 'data'=>null, 'error_msg'=>$info['http_code']];
        }
        return ['error_code'=>0, 'data'=>$return, 'error_msg' => '处理成功'];
    }

    /**
     * 设置请求属性
     * @param array $data
     * @throws \Exception
     */
    private function _setopt(array $data)
    {
        if($this->_curl === null){
            $this->_curl = curl_init();
            if($this->_keepAlive === true){
                $this->_addHeader('Connection: keep-alive');
            }
            $this->_methodOpt($data);
            curl_setopt($this->_curl, CURLOPT_URL, $this->_url);
            curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->_curl, CURLOPT_TIMEOUT, $this->_timeOut);
            curl_setopt($this->_curl,CURLOPT_HTTPHEADER,$this->_headers);
        }
    }
    /**
     * 根据请求类型设置相关属性
     * @param array $data
     * @throws \Exception
     */
    private function _methodOpt(array $data)
    {
        switch ($this->_method){
            case 'POST':
                if(empty($data)){
                    throw new \Exception('请求参数不能为空!', 1);
                }
                curl_setopt($this->_curl, CURLOPT_POST, 1);
                if($this->_postType === 0){
                    $data = $this->_urlencode($data);
                }else if($this->_postType === 2){
                    $data = json_encode($data);
                    $this->_addHeader('Content-Type: application/json');
                }
                curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $data);
                break;
            case 'GET':
                curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $this->_method);
                break;
            default:
                throw new \Exception('暂不支持该请求类型!', 1);
        }
    }
    /**
     * 对请求参数做格式转化处理
     * @param array $data
     * @return array|string
     */
    private function _urlencode(array $data)
    {
        foreach($data as $key=>$value){
            $param[] = urlencode($key).'='.urlencode($value);
        }
        $param = implode('&', $param);
        return $param;
    }
    /**
     * 新增headers信息
     * @param string $header
     */
    private function _addHeader(string $header)
    {
        $this->_headers[] = $header;
    }


}