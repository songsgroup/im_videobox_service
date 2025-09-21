<?php

return [
    [
        'name'    => 'rewrite',
        'title'   => '伪静态',
        'type'    => 'array',
        'content' => [],
        'value'   => [
            'admin/job/add'          => '/monitor/job$#post',
            'admin/job/edit'         => '/monitor/job$#put',
            'admin/job/list'         => '/monitor/job/list$#get',
            'admin/job/remove'       => '/monitor/job/<ids>$#delete',
            'admin/job/getInfo'      => '/monitor/job/<id>$#get',
            'admin/job/changeStatus' => '/monitor/job/changeStatus$#put',
            'admin/job/run'       => '/monitor/job/run$#put',
            'admin/jobLog/add'    => '/monitor/jobLog#post',
            'admin/jobLog/edit'   => '/monitor/jobLog#put',
            'admin/jobLog/list'   => '/monitor/jobLog/list#get',
            'admin/jobLog/clean'  => '/monitor/jobLog/clean#delete',
            'admin/jobLog/remove' => '/monitor/jobLog/<ids>#delete',
            'admin/jobLog/getInfo'=> '/monitor/jobLog/<id>#get',
        ],
        'rule'    => 'required',
        'msg'     => '',
        'tip'     => '',
        'ok'      => '',
        'extend'  => '',
    ],
];
