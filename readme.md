## FileEntry API

### Introduction

FileEntry API is good way to handle files with Laravel Storage.

### Initialize FileEntry

    use \Unisharp\FileEntry\FileEntry;
    
    $entry = new FileEntry();
    
or
    
    $entry = new FileEntry('/images'); # initialize it by giving a base path
    

### Get Upload Files By Giving input name

* Normal Usage

    get unique filename

        $file = $entry->save(\Input::file('image')); // => wfj412.jpg
    
    or input files is an array
    
        $files = [];
        foreach (\Input::file('images') as $file) {
            $files += $entry->save('images');
        }
    
* Costimize your upload file name

        $file = $entry->save('image', 'costimized_filename'); // => costimized_filename.jpg
          

### Get file fullpath

    $entry->fullpath('wfj412.jpg'); // => '/images/wfj412.jpg'
    
### Routing your files

All uploaded files cannot found in public folder, because FileEntry use Laravel Storage to store them.  
You can write a routing rules to get it. it will response file content and use http cache by default.

    Route::get('/upload/{filename}', function ($filename) {
        $entry = new FileEntry();
        return $entry->response($filename);
    });
    
### Work with Laravel Storage

* Get file content

        \Storage::get($entry->fullpath('wfj412.jpg'));
        
* Write files

        \Storage::put($entry->fullpath('wfj412.jpg'));
        
* Get Mime Type

        \Storage::mimeType($entry->fullpath('wfj412.jpg'));
    