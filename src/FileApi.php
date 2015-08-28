<?php

namespace Unisharp\FileApi;

use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileApi
{
    protected $basepath;

    public function __construct($basepath = '/')
    {
        if (mb_substr($basepath, -1, 1, 'utf8') != '/') {
            $basepath .= '/';
        }

        if (mb_substr($basepath, 0, 1, 'utf8') == '/') {
            $basepath = mb_substr($basepath, 1, null, 'utf8');
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

        switch (\File::mimeType($upload_file)) {
            case 'image/jpeg':
            case 'image/jpg':
                $img = imagecreatefromjpeg($upload_file->getRealPath());
                $exif = read_exif_data($upload_file->getRealPath());
                if (isset($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 8:
                            $img = imagerotate($img, 90, 0);
                            break;
                        case 3:
                            $img = imagerotate($img, 180, 0);
                            break;
                        case 6:
                            $img = imagerotate($img, -90, 0);
                            break;
                    }
                }

                imagejpeg($img, $upload_file->getRealPath());
        }

        \Storage::put(
            $this->basepath . $filename,
            file_get_contents($upload_file->getRealPath())
        );

        \File::delete($upload_file->getRealPath());
        return $filename;
    }

    public function getPath($filename)
    {
        if (mb_substr($this->basepath, -1, 1, 'utf8') != '/') {
            $this->basepath .= '/';
        }

        if (preg_match('/^\//', $filename)) {
            return $this->basepath . mb_substr($filename, 1, null, 'utf8');
        }

        return $this->basepath . $filename;
    }

    public function getUrl($filename)
    {
        if (\Config::get('filesystems.default') == 's3') {
            return \Storage::getDriver()->getAdapter()->getClient()->getObjectUrl(
                \Storage::getDriver()->getAdapter()->getBucket(),
                $this->basepath . $filename
            );
        } else {
            return $this->basepath . $filename;
        }
    }

    public function getResponse($filename, $headers = [])
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
                return response($file, 200, $headers)->header('Content-Type', \Storage::mimeType($path))
                    ->setEtag($etag)
                    ->setLastModified(new \DateTime($time))
                    ->setExpires(new \DateTime($expires))
                    ->setPublic();
            }

            return response(null, 304, $headers)
                ->setEtag($etag)
                ->setLastModified(new \DateTime($time))
                ->setExpires(new \DateTime($expires))
                ->setPublic();
        } catch (FileNotFoundException $e) {
            abort(404);
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            abort(404);
        }
    }
}
