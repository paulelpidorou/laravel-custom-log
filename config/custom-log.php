<?php

// config for PaulElpidorou/CustomLog
use PaulElpidorou\CustomLog\Formatters\CustomFormatter;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log file name
    |--------------------------------------------------------------------------
    |
    */

    'default_name' => 'LaravelCustomLog',


    /*
    |--------------------------------------------------------------------------
    | Max files
    |--------------------------------------------------------------------------
    | The number of maximum files allowed if the log driver is daily
    */

    'max_files' => env('CUSTOM_LOG_MAX_FILES', 7),


    /*
    |--------------------------------------------------------------------------
    | Storage path
    |--------------------------------------------------------------------------
    | The path where the log files should be stored
    */

    'storage_path' => env('CUSTOM_LOG_STORAGE_PATH', storage_path('logs')),

    /*
    |--------------------------------------------------------------------------
    | Log format
    |--------------------------------------------------------------------------
    |
    */

    'log_format' => "[%datetime%] %channel%.%level_name%: %message% %extra% %context%\n",


    'allow_inline_breaks' => true,


    'ignore_empty_context_and_extra' => true,


    'formatter_class' => CustomFormatter::class,

    /*
    |--------------------------------------------------------------------------
    | Date format used for logged
    |--------------------------------------------------------------------------
    |
    */

    'date_format' => 'Y-m-d H:i:s',

    /*
    |--------------------------------------------------------------------------
    | Request tracking configurations
    |--------------------------------------------------------------------------
    |
    */

    'request_tracking' => [
        //log request origin
        'origin' => env('CUSTOM_LOG_TRACKING_ORIGIN', true),

        //log request url
        'url' => env('CUSTOM_LOG_TRACKING_URL', true),

        //log request ip address
        'ip' => env('CUSTOM_LOG_TRACKING_IP', true),

        //log request user agent
        'user_agent' => env('CUSTOM_LOG_TRACKING_USER_AGENT', true),
    ],


    /*
    |--------------------------------------------------------------------------
    | Request tracking configurations
    |--------------------------------------------------------------------------
    |
    */

    'user_tracking' => [
        //log user id
        'user_id' => env('CUSTOM_LOG_TRACKING_USER_ID', true),

        //log user info
        'user_name' => env('CUSTOM_LOG_TRACKING_USER_NAME', true),

    ]





];
