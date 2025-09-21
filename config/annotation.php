<?php

return [
    'inject' => [
        'enable'     => true,
        'namespaces' => [
        ],
    ],
    'route'  => [
        'enable'      => true,
        'controllers' => [
            // Register controller namespaces to scan for #[Route] and #[Group]
            'app\\controller',
        ],
    ],
    'model'  => [
        'enable' => true,
    ],
    'ignore' => [],
];
