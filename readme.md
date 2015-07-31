## FileEntry API

### Introduction

FileEntry API is good way to handle files with Laravel Storage.

### Initialize FileEntry

    $entry = new FileEntry();
    
or
    
    $entry = new FileEntry('/images'); # initialize it by giving a base path
    

### Get Upload Files By Giving input name

* Normal Usage

    get unique filename

        $file = $entry->upload('image'); // => wfj412.jpg
    
    or input_name is an array
    
        $files = $entry->upload('images'); // => ['asqwefv.png', '1erfv.png']
    
* Costimize your upload file name

        $file = $entry->upload('image', 'costimized_filename'); // => costimized_filename.jpg
        
    of course you can giving an array of file names for file entry
        
        $files = $entry->upload(
            'images', 
            [
                'costimized_filename1',
                'costimized_filename2'
            ]
        );
        // => ['costimized_filename1.jpg', 'costimized_filename2.jpg']
        

### Get file fullpath

    $entry->fullpath('wfj412.jpg'); // => '/images/wfj412.jpg'
    
### Routing your files

All uploaded files cannot found in public folder, because FileEntry use Laravel Storage to store them.  
You can write a routing rules to get it. it will response file content and use http cache by default.

    Route::get('/upload/{filename}', function($filename) {
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
    