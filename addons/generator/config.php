<?php

return [
    [
        'name'    => 'rewrite',
        'title'   => '伪静态',
        'type'    => 'array',
        'content' => [],
        'value'   => [
            'gen/edit'        => '/tool/gen#put',
            'gen/list'        => '/tool/gen/list#get',
            'gen/dblist'      => '/tool/gen/db/list#get',
            'gen/remove'      => '/tool/gen/<ids>#delete',
            'gen/getInfo'     => '/tool/gen/<id>#get',
            'gen/column'      => '/tool/gen/column/:tableId',
            'gen/preview'     => '/tool/gen/preview/:tableId',
            'gen/createTable' => '/tool/gen/createTable',
            'gen/importTable' => '/tool/gen/importTable#post',
            'gen/genCode'     => '/tool/gen/genCode/:tableName',
            'gen/synchDb'     => '/tool/gen/synchDb/:tableName',
            'gen/batchGenCode'=> '/tool/gen/batchGenCode',
        ],
        'rule'    => 'required',
        'msg'     => '',
        'tip'     => '',
        'ok'      => '',
        'extend'  => '',
    ],
];
