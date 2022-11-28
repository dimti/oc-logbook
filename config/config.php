<?php

return [
    /**
     * @desc set to true if you have pruning old log records
     */
    'prune' => false,

    'retention' => [
        /**
         * @desc set value greater than 0 if you have remove old log records with stayed the latest concrete quantity of records
         */
        'max_records' => false,

        /**
         * @desc Record`s older that quantity of days is removed
         */
        'days' => 30,
    ],
];
