<?php

$paths = config('fileapi.path');

if (!empty($paths)) {
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
        Route::post('upload/{folder}/{sub_folder}/{filename}', function ($folder, $sub_folder, $filename) {
            $fa = new \Unisharp\FileApi\FileApi($folder . DIRECTORY_SEPARATOR . $sub_folder);
            $fa->save(request()->file('file'), $filename);
        });
    });
}
