<?php

return [
    // 默认使用的数据库连接配置
    'default'         => env('DB_DRIVER', 'mysql'),

    // 自定义时间查询规则
    'time_query_rule' => [],

    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => true,

    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',

    // 时间字段配置 配置格式：create_time,update_time
    'datetime_field'  => 'create_time,update_time',

    // 数据库连接配置信息
    'connections'     => [
        'mysql' => [
            // 数据库类型
            'type'            => env('DB_TYPE', 'mysql'),
            // 服务器地址
            'hostname'        => env('DB_HOST', '8.210.221.104'),
            // 数据库名
            'database'        => env('DB_NAME', 'im_ext'),
            // 用户名
            'username'        => env('DB_USER', 'im_ext'),
            // 密码
            'password'        => env('DB_PASS', '26PwWexxFzTri5pp'),
            // 端口
            'hostport'        => env('DB_PORT', '3306'),
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8
            'charset'         => env('DB_CHARSET', 'utf8'),
            // 数据库表前缀
            'prefix'          => env('DB_PREFIX', ''),

            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 是否需要断线重连
            'break_reconnect' => false,
            // 监听SQL
            'trigger_sql'     => env('APP_DEBUG', true),
            // 开启字段缓存
            'fields_cache'    => false,
        ],

        'sqlite' => [
            'type'         => 'sqlite',
            'dsn'          => 'sqlite:'.env('SQLITE_FILE',root_path().'db'.DS.'ruoyi-tp.db'),
            'database'     => env('SQLITE_NAME', 'main'),
            'charset'      => env('SQLITE_CHARSET', 'utf8'),
            'prefix'       => env('SQLITE_PREFIX', ''),
            'fields_cache' => env('SQLITE_FIELDS_CACHE',false),
        ],

        // 更多的数据库配置信息
    ],
];
