<?php

namespace Unisharp\FileApi\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Unisharp\FileApi\FileApi;

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


        if (function_exists('carrier') && !empty(carrier()->get('response_msg'))) {
            $response_msg = carrier()->get('response_msg');
        } else {
            $response_msg = [
                'status' => [
                    'code' => 200,
                    'message' =>'Success to upload image' ,
                ],
                'data' => [
                    'filename' => $filename,
                    'path' => with(new FileApi("images/${target}/"))->get($filename, 'origin')
                ]
            ];
        }

        return $response_msg;
    }

    public function videos($target, $param = null)
    {
        $fa = new \Unisharp\FileApi\FileApi('videos/' . $target);
        $filename = $fa->save(request()->file('video'));
        event("video.${target}.created", [
            'param' => $param,
            'filename' => $filename,
            'path' => "videos/${target}/{$filename}"
        ]);

        if (function_exists('carrier') && !empty(carrier()->get('response_msg'))) {
            $response_msg = carrier()->get('response_msg');
        } else {
            $response_msg = [
                'status' => [
                    'code' => 200,
                    'message' => 'Success to upload video',
                ],
                'data' => [
                    'filename' => $filename,
                    'path' => with(new FileApi("videos/${target}/"))->get($filename, 'origin')
                ]
            ];
        }

        return $response_msg;
    }
}
