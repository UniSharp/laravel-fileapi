## Laravel File API

### Introduction

Laravel File API is good way to handle files with Laravel Storage.

### Install File API

composer.json:

    "require" : {
        "unisharp/laravel-fileapi" : "dev-master"
    }, 
    "repositories": {
        "type": "git",
        "url": "https://github.com/UniSharp/laravel-fileapi.git
    }

save it and then 

    composer update    

### Initialize File API

    use \Unisharp\FileApi\FileApi;
    
    $fa = new FileApi();
    
or
    
    $fa = new FileApi('/images'); # initialize it by giving a base path
    

### Save to Storage By Giving Lravel Upload File

* Normal Usage

    get unique filename

        $file = $fa->save(\Input::file('image')); // => wfj412.jpg
    
    or input files is an array
    
        $files = [];
        foreach (\Input::file('images') as $file) {
            $files []= $fa->save('images');
        }
    
* Custimize your upload file name

        $file = $fa->save('image', 'custimized_filename'); // => custimized_filename.jpg
          

### Get file fullpath

    $fa->getFullPath('wfj412.jpg'); // => '/images/wfj412.jpg'
    
### Routing your files

All uploaded files cannot found in public folder, because FileApi use Laravel Storage to store them.  
You can write a routing rules to get it. it will response file content and use http cache by default.

    Route::get('/upload/{filename}', function ($filename) {
        $fa = new FileApi();
        return $fa->getResponse($filename);
    });
    
and it can add headers array by optional

    return $fa->getResponse($filename, ['Content-Disposition', 'attachement']);
    
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
    
