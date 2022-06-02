<?php

return [
    'log' => 'Лог',
    'date' => 'Дата',
    'undo' => 'Восстановить',
    'changes' => [
        'user' => ':user :type :model',
        'unknown' => ':model :type',
        'type' => [
            'updated' => 'обновил',
            'created' => 'добавил',
            'deleted' => 'удалил',
        ],
        'column' => '<p></p>:column изменено <br>с: :from <br>на: :to'
    ],
];
