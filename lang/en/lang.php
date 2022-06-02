<?php

return [
    'log' => 'Log',
    'date' => 'Date',
    'undo' => 'Undo',
    'changes' => [
        'user' => ':user has :type :model',
        'unknown' => ':model is :type',
        'type' => [
            'updated' => 'updated',
            'created' => 'created',
            'deleted' => 'deleted',
        ],
        'column' => '<p></p>:column changed <br>from: :from <br>to: :to'
    ],
];
