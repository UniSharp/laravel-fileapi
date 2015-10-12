## Laravel File API

### Introduction

Laravel File API is good way to handle files with Laravel Storage.

### Installation

    1. Install File API

        composer require unisharp/laravel-fileapi

    1. Set service provider in `config/app.php`

        Unisharp\FileApi\FileApiServiceProvider::class,

    1. publish config file

        php artisan vendor:publish --tag=fileapi_config

    1. fill the path in config/fileapi.php, it will generate routes for your files.

### Initialize File API

    use \Unisharp\FileApi\FileApi;
    
    $fa = new FileApi();
    
or
    
    $fa = new FileApi('/images'); # initialize it by giving a base path
    

### Save to Storage By Giving Uploaded File

* Default Usage : get unique filename

        $file = $fa->save(\Input::file('image')); // => wfj412.jpg
    
* Custimize your upload file name

        $file = $fa->save(\Input::file('image'), 'custimized_filename'); // => custimized_filename.jpg
          

### Get file fullpath (abstract path from Laravel Storage)

    $fa->getPath('wfj412.jpg'); // => '/images/wfj412.jpg'
    
### Parse File Path to URL
if you store your file into cloud storage and you want to get url cloud site,
you can use url() method to get it

    echo $fa->getUrl('wfjsdf.jpg'); // => "https://s3-ap-northeast-1.amazonaws.com/xxx/xxx/55c1e027caa62L.png"
    
### Work with Laravel Storage

* Get file content

        \Storage::get($fa->getPath('wfj412.jpg'));
        
* Write files

        \Storage::put($fa->getPath('wfj412.jpg'));
        
* Get Mime Type

        \Storage::mimeType($fa->getPath('wfj412.jpg'));
    
