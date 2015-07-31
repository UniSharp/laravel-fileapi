<?php

namespace Unisharp\FileEntry;

use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileEntry
{
    protected $basepath;

    public function __construct($basepath = '/')
    {
        if (mb_substr($basepath, -1, 1, 'utf8') != '/') {
            $basepath .= '/';
        }

        $this->basepath = $basepath;
    }

    public function save(UploadedFile $upload_file, $cus_name = null)
    {
        $file = $this->moveFile($upload_file, $cus_name);
        return $file;
    }

    private function moveFile($upload_file, $cus_name)
    {
        $suffix = $upload_file->getClientOriginalExtension();
        $filename = uniqid() . '.' . $suffix;
        if (!empty($cus_name)) {
            $filename = $cus_name . '.' .$suffix;
        }
        \Storage::put(
            $this->basepath . $filename,
            file_get_contents($upload_file->getRealPath())
        );

        \File::delete($upload_file->getRealPath());
        return $filename;
    }

    public function response($filename)
    {
        try {
            $path = $this->basepath . $filename;
            $file = \Storage::get($path);
            $filetime = \Storage::lastModified($path);
            $etag = md5($filetime);
            $time = date('r', $filetime);
            $expires = date('r', $filetime + 3600);
            if (trim(\Request::header('If-None-Match'), '\'\"') != $etag ||
                new \DateTime(\Request::header('If-Modified-Since')) != new \DateTime($time)
            ) {
                return response($file, 200)->header('Content-Type', \Storage::mimeType($path))
                    ->setEtag($etag)
                    ->setLastModified(new \DateTime($time))
                    ->setExpires(new \DateTime($expires))
                    ->setPublic();
            }

            return response(null, 304)->setPublic();
        } catch (FileNotFoundException $e) {
            abort(404);
        }
    }
}
