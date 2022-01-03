<?php

$paths = config('fileapi.path');

if (!empty($paths)) {
    foreach ($paths as $path) {
        Route::get($path.'{filename}/watermark', function ($filename) use ($path) {
            $entry = new \Unisharp\FileApi\FileApi($path);
            return redirect()->to($entry->get($filename, 'W'));
        });
    }

    foreach ($paths as $path) {
        Route::get($path.'{filename}', function ($filename) use ($path) {
            $entry = new \Unisharp\FileApi\FileApi($path);
            return $entry->getResponse($filename);
        });
    }
}

$enable_api_upload = config('fileapi.enable_api_upload', true);

if ($enable_api_upload) {
    Route::group(['middleware' => config('fileapi.middlewares', [])], function () {
        Route::post(
            config('fileapi.api_prefix', '/api/v1') . '/images/{target}/{param?}',
            'Unisharp\FileApi\Http\Controllers\Api\FileController@images'
        );

        Route::post(
            config('fileapi.api_prefix', '/api/v1') . '/videos/{target}/{param?}',
            'Unisharp\FileApi\Http\Controllers\Api\FileController@videos'
        );
    });
}
