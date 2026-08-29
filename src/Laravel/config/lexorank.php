<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Maximum bulk allocation count
    |--------------------------------------------------------------------------
    |
    | Safety limit for initialRanks() / betweenMany() to prevent accidental
    | allocation of pathological list sizes in a single call.
    |
    */
    'max_count' => 100_000,

    /*
    |--------------------------------------------------------------------------
    | Maximum serialized rank length
    |--------------------------------------------------------------------------
    |
    | Generated ranks longer than this raise RankSpaceExhaustedException.
    | Prefer rebalancing before approaching this limit. Recommended DB column
    | width is 255 characters (VARCHAR(255) / CHAR varying(255)).
    |
    */
    'max_rank_length' => 255,

    /*
    |--------------------------------------------------------------------------
    | Default Eloquent rank column
    |--------------------------------------------------------------------------
    */
    'column' => 'rank',
];
