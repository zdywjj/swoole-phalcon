<?php

return [
    'host'      	=> '127.0.0.1', // http服务地址
    'port'      	=> 9505,        // http服务端口
    'worker_num'  	=> 5,           // worker进程总数
    'dispatch_mode' => 3,           // 数据包分发策略
    'max_request'  	=> 10000,       // worker进程的最大任务数
    'listener_host' => '127.0.0.1', // 监听服务地址
    'listener_port' => 9506,         // 监听服务端口
];
