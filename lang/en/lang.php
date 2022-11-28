<?php

return [
    'log' => 'Log',
    'date' => 'Date',
    'undo' => 'Undo',
    'changes' => [
        'user' => ':user has :type <b>:model</b>',
        'unknown' => '<b>:model</b> is :type',
        'type' => [
            'updated' => 'updated',
            'created' => 'created',
            'deleted' => 'deleted',
        ],
        'unknownType' => [
            'updated' => 'updated',
            'created' => 'created',
            'deleted' => 'deleted',
        ],
        'column' => '<b>:column</b>: :from ➔ <b>:to</b><br>',
    ],
];
