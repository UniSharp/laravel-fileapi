<?php

namespace Unisharp\FileApi;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileApi
{
    protected $basepath;
    protected $default_sizes = ['S' => '96x96', 'M' => '256x256', 'L' => '480x480'];
    protected $thumb_sizes = null;
    protected $shouldCropThumb = false;

    public function __construct($basepath = DIRECTORY_SEPARATOR)
    {
        if (mb_substr($basepath, -1, 1, 'utf8') != DIRECTORY_SEPARATOR) {
            $basepath .= DIRECTORY_SEPARATOR;
        }

        if (mb_substr($basepath, 0, 1, 'utf8') == DIRECTORY_SEPARATOR) {
            $basepath = mb_substr($basepath, 1, null, 'utf8');
        }

        $this->basepath = $basepath;
    }

    public function get($filename, $size = null)
    {
        if (empty($filename)) {
            return '';
        }
        // Cut original file name
        $file = explode('.', $filename);
        $file_path = '';

        if (empty($filename)) {
            return '';
        }
        
        if (empty($size) && \Storage::exists($this->basepath . $file[0] . '_L.' . $file[1])) {
            $file_path = $this->basepath . $file[0] . '_L.' . $file[1];
        } elseif (\Storage::exists($this->basepath . $file[0] . '_' . $size . '.' . $file[1])) {
            $file_path = $this->basepath . $file[0] . '_' . $size . '.' . $file[1];
        } else {
            $file_path = $this->basepath . $filename;
        }

        if (\Config::get('filesystems.default') == 's3') {
            return \Storage::getDriver()->getAdapter()->getClient()->getObjectUrl(
                \Storage::getDriver()->getAdapter()->getBucket(),
                $this->basepath . $filename
            );
        } else {
            return url($file_path);
        }
    }

    public function getWatermark($filename, $watermark)
    {
        $origin_basepath = $this->basepath;
        try {
            $path = $this->basepath . $filename;
            $image_types = array('image/png', 'image/gif', 'image/jpeg', 'image/jpg');
            $watermark_image = $this->setTmpImage(base_path($watermark));
            $image = $this->setTmpImage(storage_path('app/' . $path));
            imagesavealpha($watermark_image, true);
            imagesetbrush($image, $watermark_image);
            $watermark_pos_x = imagesx($image) - imagesy($watermark_image);
            $watermark_pos_y = imagesy($image) - imagesy($watermark_image);
            imageline(
                $image,
                $watermark_pos_x,
                $watermark_pos_y,
                $watermark_pos_x,
                $watermark_pos_y,
                IMG_COLOR_BRUSHED
            );
            imagesavealpha($image, true);
            if (!Storage::exists('images/wtermark')) {
                Storage::makeDirectory('images/watermark');
            }
            imagepng($image, storage_path('app/images/watermark/' . $filename));

            $this->basepath = 'images/watermark/';
            return $this->getResponse($filename);
        } catch (\Exception $e) {
            $this->basepath = $origin_basepath;
            return $this->getResponse($filename);
        }
    }

    public function thumbs($thumb_sizes = array())
    {
        if (!empty($thumb_sizes)) {
            $this->thumb_sizes = $thumb_sizes;
        }

        return $this;
    }

    public function crop()
    {
        $this->shouldCropThumb = true;

        return $this;
    }

    public function save(UploadedFile $upload_file, $cus_name = null)
    {
        $file = $this->moveFile($upload_file, $cus_name);
        return $file;
    }

    public function getPath($filename)
    {
        if (mb_substr($this->basepath, -1, 1, 'utf8') != DIRECTORY_SEPARATOR) {
            $this->basepath .= DIRECTORY_SEPARATOR;
        }

        if (preg_match('/^\//', $filename)) {
            $filename = mb_substr($filename, 1, null, 'utf8');
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

            $response = response(null, 304, $headers);

            $file_content_changed = trim(\Request::header('If-None-Match'), '\'\"') != $etag;
            $file_modified = new \DateTime(\Request::header('If-Modified-Since')) != new \DateTime($time);

            if ($file_content_changed || $file_modified) {
                $response = response($file, 200, $headers)->header('Content-Type', \Storage::mimeType($path));
            }

            return $response
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

    public function drop($filename)
    {
        // Cut original file name
        $dot = strpos($filename, '.');
        $origin_name = substr($filename, 0, $dot);

        // Find all images in basepath
        $allFiles = \Storage::files($this->basepath);
        $files = array_filter($allFiles, function ($file) use ($origin_name) {
            return preg_match('/^(.*)'.$origin_name.'(.*)$/', $file);
        });

        // Delete original image and thumbnails
        foreach ($files as $file) {
            \Storage::delete($file);
        }
    }

    /********************************************
     ***********  Private Functions *************
     ********************************************/

    private function moveFile($upload_file, $cus_name)
    {
        $suffix = $upload_file->getClientOriginalExtension();

        if (empty($cus_name)) {
            $original_name = uniqid();
        } else {
            $original_name = $cus_name;
        }
        $filename = $original_name . '.' .$suffix;

        $img = $this->setTmpImage($upload_file);

        \Storage::put(
            $this->basepath . $filename,
            file_get_contents($upload_file->getRealPath())
        );

        \File::delete($upload_file->getRealPath());

        if (!is_null($img) && !empty($this->getThumbSizes())) {
            $this->saveThumb($img, $original_name, $suffix);
        }

        return $filename;
    }

    private function setTmpImage($upload_file)
    {
        $image_types = array('image/png', 'image/gif', 'image/jpeg', 'image/jpg');
        $image_path = $upload_file instanceof UploadedFile ? $upload_file->getRealPath() : $upload_file;

        if (in_array(\File::mimeType($upload_file), $image_types)) {
            switch (\File::mimeType($upload_file)) {
                case 'image/png':
                    $img = imagecreatefrompng($image_path);
                    break;
                case 'image/gif':
                    $img = imagecreatefromgif($image_path);
                    imagegif($img, $image_path);
                    break;
                case 'image/jpeg':
                case 'image/jpg':
                default:
                    $img = imagecreatefromjpeg($image_path);
                    try {
                        $exif = exif_read_data($image_path);
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
                        imagejpeg($img, $image_path);
                    } catch (\Exception $e) {
                        //ignore cannot read exif
                    }
            }

            return $img;
        } else {
            return null;
        }
    }

    private function saveThumb($img, $original_name, $suffix)
    {
        foreach ($this->getThumbSizes() as $size_code => $size) {
            if (is_int($size_code) && $size_code < count($this->getThumbSizes())) {
                $size_name = 'L';
            } else {
                $size_name = $size_code;
            }

            $thumb_name   = $this->basepath . $original_name . '_' . $size_name . '.' . $suffix;
            $main_image   = $original_name . '.' . $suffix;
            $tmp_filename = 'tmp' . DIRECTORY_SEPARATOR . $main_image;

            $tmp_thumb = $this->resizeOrCropThumb($img, $size);

            // save tmp image
            \Storage::disk('local')->put($tmp_filename, \Storage::get($this->basepath . $main_image));
            $tmp_path = \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

            // save thumbnail image
            imagepng($tmp_thumb, $tmp_path . $tmp_filename);
            $tmp_file = \Storage::disk('local')->get($tmp_filename);
            \Storage::put($thumb_name, $tmp_file);

            // remove tmp image
            \Storage::disk('local')->delete($tmp_filename);
        }
    }

    private function resizeOrCropThumb($img, $size)
    {
        $width        = imagesx($img);
        $height       = imagesy($img);
        $arr_size     = explode('x', $size);
        $thumb_width  = (int)$arr_size[0];
        $thumb_height = (int)$arr_size[1];

        if ($this->thumbShouldCrop()) {
            $img = $this->cropThumb($img, $width, $height, $thumb_width, $thumb_height);
        } else {
            $this->resizeThumb($width, $height, $thumb_width, $thumb_height);
        }

        // create a new temporary thumbnail image
        $tmp_thumb = imagecreatetruecolor($thumb_width, $thumb_height);
        $whitecolor = imagecolorallocatealpha($tmp_thumb, 255, 255, 255, 0);
        imagefill($tmp_thumb, 0, 0, $whitecolor);

        // copy and resize original image into thumbnail image
        imagecopyresampled($tmp_thumb, $img, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
        return $tmp_thumb;
    }

    private function thumbShouldCrop()
    {
        return $this->shouldCropThumb;
    }

    private function cropThumb($img, &$width, &$height, $thumb_width, $thumb_height)
    {
        $image_ratio = $height / $width;
        $thumb_ratio = $thumb_height / $thumb_width;

        if ($image_ratio !== $thumb_ratio) {
            if ($image_ratio < $thumb_ratio) {
                $new_width = $thumb_width * $height / $thumb_height;

                $square = [
                    'x' => ($width - $new_width) / 2,
                    'y' => 0,
                    'width' => $new_width,
                    'height' => $height
                ];

                $width = $new_width;
            } elseif ($image_ratio > $thumb_ratio) {
                $new_height = $thumb_height * $width / $thumb_width;

                $square = [
                    'x' => 0,
                    'y' => ($height - $new_height) / 2,
                    'width' => $width,
                    'height' => $new_height
                ];

                $height = $new_height;
            }

            $img = imagecrop($img, $square);
        }

        return $img;
    }

    private function resizeThumb($width, $height, &$thumb_width, &$thumb_height)
    {
        $image_ratio = $height / $width;
        $thumb_ratio = $thumb_height / $thumb_width;

        if ($image_ratio !== $thumb_ratio) {
            if ($image_ratio < $thumb_ratio) {
                $thumb_height = $thumb_width * $height / $width;
            } elseif ($image_ratio > $thumb_ratio) {
                $thumb_width = $thumb_height * $width / $height;
            }
        }
    }

    private function getThumbSizes()
    {
        $config = config('fileapi.default_thumbs');

        if (!is_null($this->thumb_sizes)) {
            return $this->thumb_sizes;
        } elseif (!is_null($config)) {
            return $config;
        } else {
            return $this->default_sizes;
        }
    }
}
