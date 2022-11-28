<?php

return [
    'log' => 'Лог',
    'date' => 'Дата',
    'undo' => 'Восстановить',
    'changes' => [
        'user' => ':user :type <b>:model</b>',
        'unknown' => '<b>:model</b> :type',
        'type' => [
            'updated' => 'обновил',
            'created' => 'добавил',
            'deleted' => 'удалил',
        ],
        'unknownType' => [
            'updated' => 'обновлена',
            'created' => 'добавлена',
            'deleted' => 'удалена',
        ],
        'column' => '<b>:column</b>: :from ➔ <b>:to</b><br>',
    ],
];
