<?php

namespace Index\Controllers;

use Base\BaseController;

class IndexController extends BaseController
{
    public function indexAction()
    {
        //echo 111;
        $this->returnJson('hello');
    }
}
