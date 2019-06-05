<?php
$router = new \Phalcon\Mvc\Router();

//默认路由
$router->add("/", array(
    "module"     => "index",
    "controller" => "index",
    "action"     => "index",
));

return $router;