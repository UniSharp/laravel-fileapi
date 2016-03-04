<?php

namespace Unisharp\FileApi\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;


class FileController extends Controller
{
    public function images($target, $param = null)
    {
        $fa = new \Unisharp\FileApi\FileApi('images/' . $target);
        $filename = $fa->save(request()->file('image'));
        event("image.${target}.created", [
            'param' => $param,
            'filename' => $filename,
            'path' => "images/${target}/{$filename}",
        ]);

        return [
            'status' => [
                'code' => 200,
                'message' =>'Success to upload image' ,
            ],
            'data' => [
                'filename' => $filename,
                'path' => "images/${target}/{$filename}"
            ]
        ];
    }

    public function videos($target, $param = null)
    {
        $fa = new \Unisharp\FileApi\FileApi('videos/' . $target);
        $filename = $fa->save(request()->file('video'));
        event("video.${target}.created", [
            'param' => $param,
            'filename' => $filename,
            'path' => "videos/${target}/{$filename}",
        ]);

        return [
            'status' => [
                'code' => 200,
                'message' =>'Success to upload image' ,
            ],
            'data' => [
                'filename' => $filename,
                'path' => "images/${target}/{$filename}"
            ]
        ];
    }
}
