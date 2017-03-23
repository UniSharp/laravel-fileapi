# Laravel File API

## Features

 * Handle files with Laravel Storage.
 * Load files through Laravel routing instead of public path.
 * Save images with thumbs, compressed image, and sizes are customisable.

## Installation

1. Install File API

    ```php
    composer require unisharp/laravel-fileapi
    ```

1. Set service provider in `config/app.php`

    ```php
    Unisharp\FileApi\FileApiServiceProvider::class,
    ```

1. publish config file

    ```php
    php artisan vendor:publish --tag=fileapi_config
    ```

## Config

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

1. set default image compress quality

    ```php
    'compress_quality' => 90,
    ```

1. choose whether you want to enable upload directly by url(api)

    ```php
    'enable_api_upload' => false,
    ```

    and upload to url by below

    ```
    POST /upload/images/event/the-file-name
    ```

1. and you might also want to set some middlewares to protect the upload route

    ```php
    'middlewares' => [],
    ```
    
## Usage

### Initialize File API

```php
use \Unisharp\FileApi\FileApi;
    
$fa = new FileApi(); # use default path (as '/images/')
$fa_event = new FileApi('/images/event/'); # initialize it by giving a base path
$fa_article = new FileApi('/images/article/'); # initiate another instance
```

### Save By Giving Uploaded File

* Default Usage : get unique filename

    ```php
    $file = $fa->save(\Input::file('main_image')); // => wfj412.jpg
    ```
    
* Custimize your upload file name

    ```php
    $file = $fa->save(\Input::file('main_image'), 'custom-file-name'); // => custom-file-name.jpg
    ```
    
* By default will set three thumbs(equal scaling)

### Thumbnail functions

* Set custom thumb sizes

    ```php
    $file = $fa
        ->thumbs([
            'S' => '150x100',
            'M' => '300x200',
            'L' => '450x300'
            ])
        ->save(\Input::file('main_image'));
    ```

* make cropped thumbs
        
    ```php
    $file = $fa->crop()->save(\Input::file('main_image'));
    ```

### Get image url

```php
// large size
$fa->get('wfj412.jpg');
$fa->get('wfj412.jpg', 'L');
$fa->get('wfj412.jpg', 'FileApi::SIZE_LARGE');

// medium size
$fa->get('wfj412.jpg', 'M');
$fa->get('wfj412.jpg', FileApi::SIZE_MEDIUM);

// full size
$fa->get('wfj412.jpg', 'full');
$fa->get('wfj412.jpg', FileApi::SIZE_ORIGINAL);

// comporssed
$fa->get('wfj412.jpg', 'CP'); // => get image url of compressed one
```
    
### Delete image and thumbs

```php
$fa->drop('wfj412.jpg');
```

### Get file fullpath (abstract path from Laravel Storage)

```php
$fa->getPath('wfj412.jpg'); // => '/images/event/wfj412.jpg'
```  
    
### Parse File Path to URL
if you store your file into cloud storage and you want to get url cloud site, you can use url() method to get it

```php
echo $fa->getUrl('wfjsdf.jpg'); // => "https://s3-ap-northeast-1.amazonaws.com/xxx/xxx/55c1e027caa62L.png"
```
    
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
## Auto Upload

if `enable_api_upload=true` in `config/fileapi.php`, you can upload file to these two path

1. Image

    * head
 
             POST /api/v1/images/{target}/{param?}
    * body

             image={file multipart body}
  
2. Video

    * head

            /api/v1/videos/{target}/{param?}

    * body

            video={file multipar body}
  

### After uploaded

you add event listener to finish up after file uploaded, file api will fire `image.{target}.created` and
`video.{target}.created`


#### Step

1. Write listener under `App\Listeners`

        <?php

        namespace App\Listeners;

        class ArticleImageListener
        {

             public function handle($param, $filename, $path)
            {
                ... do something ...
            }
        }
        
2. Write event mapping in `Providers\EvnetService\Providers`

            protected $listen = [
                'image.article.created' => [
                    'App\Listeners\ArticleImageListener'
                ],
            ];

### Configurations

    'enable_api_upload' => false, // auto upload api
    'api_prefix' => '/api/v1',    // upload api url prefix
    'middlewares' => [],          // middlewares that wrap the api upload route
