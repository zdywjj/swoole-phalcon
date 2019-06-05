<?php
/**
 * 每个数据源的key和数据库名(dbname)保持一致
 */
return [
    //配置数据源
    'test' => [
        'mysql_proxy'   => true,  //此数据源是否开启读写分离
        'slave_balance' => false, //从库是否开启负载均衡
        'write_db' => [
            'w_database1' => [
                'adapter'      	=> 'Mysql',
                'host'          => '',
                'username'  	=> '',
                'password'   	=> '',
                'dbname'     	=> '',
                'port'          => '',
                'charset'       => ''
            ],
        ],
        'read_db' => [
            'r_database1' => [
                'adapter'      	=> 'Mysql',
                'host'          => '',
                'username'  	=> '',
                'password'   	=> '',
                'dbname'     	=> '',
                'port'          => '',
                'charset'       => ''
            ],
        ],
    ],
];