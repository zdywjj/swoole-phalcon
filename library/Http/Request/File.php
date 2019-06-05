<?php
namespace Lib\Http\Request;

class File
{
    protected $_name = '';
    protected $_tmp = '';
    protected $_size = 0;
    protected $_type = '';
    protected $_realType = '';
    protected $_error = 0;
    protected $_key = '';
    protected $_extension = '';

    public function __construct(array $file,$key = null)
    {
        if(isset($file['name'])){
            $this->_name = $file['name'];

            if(!empty($file['name'])){
                $this->_extension = strrchr($file['name'],'.');
            }
        }
        if(isset($file['tmp_name'])){
            $this->_tmp = $file['tmp_name'];
        }
        if(isset($file['size'])){
            $this->_size = $file['size'];
        }
        if(isset($file['type'])){
            $this->_type = $file['type'];
        }
        if(isset($file['error'])){
            $this->_error = $file['error'];
        }
        $this->_key = $key;
    }

    public function getSize():int
    {
        return $this->_size;
    }

    public function getName():string
	{
		return $this->_name;
	}

    public function getTempName():string
	{
		return $this->_tmp;
	}

    public function getType():string
	{
		return $this->_type;
	}

    public function isUploadedFile():bool
	{
		$tmp = $this->getTempName();
		return is_uploaded_file($tmp);
	}

    public function moveTo(string $destination):bool
	{
		return move_uploaded_file($this->_tmp, $destination);
	}
}