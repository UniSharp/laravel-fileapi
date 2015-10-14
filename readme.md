## Laravel File API

### Introduction

Laravel File API is good way to handle files with Laravel Storage.

### Installation

1. Install File API

    `composer require unisharp/laravel-fileapi`

1. Set service provider in `config/app.php`

    ```php
    Unisharp\FileApi\FileApiServiceProvider::class,
    ```

1. publish config file

    `php artisan vendor:publish --tag=fileapi_config`

### Config

in `config/fileapi.php`

1. fill in the storage path, which make routes for you.

    ```php
    'path' => ['/images/event/', '/images/article/'],
    ```

    it will generate routes like below :

    ```php
    Route::get('/images/event/{filename}', function ($filename) {
        $entry = new \Unisharp\FileApi\FileApi('/images/event/');
        return $entry->getResponse($filename);
    });

    Route::get('/images/article/{filename}', function ($filename) {
        $entry = new \Unisharp\FileApi\FileApi('/images/article/');
        return $entry->getResponse($filename);
    });
    ```

1. set default thumb sizes(by key and value)

    ```php
    'default_thumbs' => ['S' => '96x96', 'M' => '256x256', 'L' => '480x480'],
    ```

### Initialize File API

    use \Unisharp\FileApi\FileApi;
    $fa = new FileApi();
    
or
    
    $fa = new FileApi('/images/event/'); # initialize it by giving a base path
    $fa_article = new FileApi('/images/article/'); # initiate another instance
    

### Save to Storage By Giving Uploaded File

* Default Usage : get unique filename

    ```php
    $file = $fa->save(\Input::file('main_image')); // => wfj412.jpg
    ```
    
* Custimize your upload file name

    ```php
    $file = $fa->save(\Input::file('main_image'), 'custimized_filename'); // => custimized_filename.jpg
    ```

### Save thumbnails

* By default will set three thumbs(equal scaling)

    ```php
    $file = $fa->save(\Input::file('main_image'));
    ```

* Set custom thumb sizes

    ```php
    $file = $fa
        ->thumbs(['S' => '150x100', 'M' => '300x200', 'L' => '450x300'])
        ->save(\Input::file('main_image'));
    ```

* make cropped thumbs
        
    ```php
    $file = $fa->crop()->save(\Input::file('main_image'));
    ```

### Get file fullpath (abstract path from Laravel Storage)

    $fa->getPath('wfj412.jpg'); // => '/images/event/wfj412.jpg'
    
### Parse File Path to URL
if you store your file into cloud storage and you want to get url cloud site,
you can use url() method to get it

    echo $fa->getUrl('wfjsdf.jpg'); // => "https://s3-ap-northeast-1.amazonaws.com/xxx/xxx/55c1e027caa62L.png"
    
### Work with Laravel Storage

* Get file content

    ```php
    \Storage::get($fa->getPath('wfj412.jpg'));
    ```
        
* Write files

    ```php
    \Storage::put($fa->getPath('wfj412.jpg'));
    ```
        
* Get Mime Type

    ```php
    \Storage::mimeType($fa->getPath('wfj412.jpg'));
    ```
