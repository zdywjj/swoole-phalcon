<?php
return [
    //redis是否是集群模式
    'cluster' => false,
    //redis key前缀
    'opt_prefix' => '',
    //是否启用长连接
    'persistent' => true,
    //单节点配置
    'node' => [
        'host'     => '',
        'port'     => '',
        'password' => ''
    ],
    //集群配置
    'nodes' =>[
        'node1' => [
            'host' => '',
        ],
        'node2' => [
            'host' => '',
        ],
        'node3' => [
            'host' => '',
        ],
        'node4' => [
            'host' => '',
        ],
        'node5' => [
            'host' => '',
        ],
        'node6' => [
            'host' => '',
        ],
    ],
];