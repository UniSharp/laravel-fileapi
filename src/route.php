<?php

$paths = config('fileapi.path');

if (!empty($paths)) {
    foreach ($paths as $path) {
        Route::get($path.'{filename}', function ($filename) use ($path) {
            try {
                $file = \Storage::get($path . $filename);
                return response($file, 200)->header('Content-Type', \Storage::mimeType($file));
            } catch (\League\Flysystem\FileNotFoundException $e) {
                abort(404);
            }
        });
    }
}


