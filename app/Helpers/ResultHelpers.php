<?php

namespace App\Helpers;

class ResultHelpers
{
    public static function success($data, $status_code, $success)
    {
        $result['status_code'] = $status_code;
        $result['success'] = $success;
        $result['data'] = $data;
        return response($result, 200);
    }

    public static function errors($data, $status_code, $success)
    {
        $result['status_code'] = $status_code;
        $result['success'] = $success;
        $result['errors'] = $data;
        return response($result, 400);
    }
}
