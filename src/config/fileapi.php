<?php

return [

    /*
    |--------------------------------------------------------------------------
    | File paths
    |--------------------------------------------------------------------------
    |
    | 'path' => ['/images/event/']
    |
    | Gets file from '/storage/image/event/' directory.
    |
    */

    'path' => ['/images/event/'],
    'watermark' => 'public/img/watermark.png',

    'default_thumbs' => ['S' => '96x96', 'M' => '256x256', 'L' => '480x480'],

    'compress_quality' => 90,

    /*
    |--------------------------------------------------------------------------
    | Enable Api upload
    |--------------------------------------------------------------------------
    |
    | Set to true to upload files via this route :
    |
    | POST /upload/images/event/the-file-name
    |
    */

    'enable_api_upload' => false,
    'api_prefix' => '/api/v1',

    'middlewares' => [], // middlewares that wrap the api upload route

];