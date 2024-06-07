<?php

return [

    'connections' => [

        'connection_first' => [
            'driver' => env('DB_CONNECTION_FIRST', 'mysql'),
            'host' => env('DB_HOST_FIRST', '127.0.0.1'),
            'port' => env('DB_PORT_FIRST', 3306),
            'database' => env('DB_DATABASE_FIRST', 'tps2024'),
            'username' => env('DB_USERNAME_FIRST', 'root'),
            'password' => env('DB_PASSWORD_FIRST', ''),
            'unix_socket' => env('DB_SOCKET_FIRST', ''),
            'charset' => env('DB_CHARSET_FIRST', 'utf8mb4'),
            'collation' => env('DB_COLLATION_FIRST', 'utf8mb4_unicode_ci'),
            'prefix' => env('DB_PREFIX_FIRST', ''),
            'strict' => env('DB_STRICT_MODE_FIRST', true),
            'engine' => env('DB_ENGINE'),
            'timezone' => env('DB_TIMEZONE_FIRST', '+00:00'),
        ],

        'connection_second' => [
            'driver' => env('DB_CONNECTION_SECOND', 'mysql'),
            'host' => env('DB_HOST_SECOND', '127.0.0.1'),
            'port' => env('DB_PORT_SECOND', 3306),
            'database' => env('DB_DATABASE_SECOND', 'accapp'),
            'username' => env('DB_USERNAME_SECOND', 'root'),
            'password' => env('DB_PASSWORD_SECOND', ''),
            'unix_socket' => env('DB_SOCKET_SECOND', ''),
            'charset' => env('DB_CHARSET_SECOND', 'utf8mb4'),
            'collation' => env('DB_COLLATION_SECOND', 'utf8mb4_unicode_ci'),
            'prefix' => env('DB_PREFIX_SECOND', ''),
            'strict' => env('DB_STRICT_MODE_SECOND', true),
            'engine' => env('DB_ENGINE'),
            'timezone' => env('DB_TIMEZONE_SECOND', '+00:00'),
        ],

        'connection_third' => [
            'driver' => env('DB_CONNECTION_THIRD', 'mysql'),
            'host' => env('DB_HOST_THIRD', '127.0.0.1'),
            'port' => env('DB_PORT_THIRD', 3306),
            'database' => env('DB_DATABASE_THIRD', 'accapp'),
            'username' => env('DB_USERNAME_THIRD', 'root'),
            'password' => env('DB_PASSWORD_THIRD', ''),
            'unix_socket' => env('DB_SOCKET_THIRD', ''),
            'charset' => env('DB_CHARSET_THIRD', 'utf8mb4'),
            'collation' => env('DB_COLLATION_THIRD', 'utf8mb4_unicode_ci'),
            'prefix' => env('DB_PREFIX_THIRD', ''),
            'strict' => env('DB_STRICT_MODE_THIRD', true),
            'engine' => env('DB_ENGINE'),
            'timezone' => env('DB_TIMEZONE_THIRD', '+00:00'),
        ],
    ],

];
