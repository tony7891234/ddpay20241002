<?php

namespace App\Traits;

/**
 * 输出
 * Traits ResponseAble
 * @package App\Traits
 */
trait ResponseAble
{
    /**
     * 成功返回JSON
     * @param string|array|object $response 返回值
     * @param string $message 信息
     * @return array
     */
    protected function success($response = '', $message = 'success')
    {
        $response = [
            'msg' => $message,
            'code' => 200,
            'data' => $response ? $response : new \stdClass(),
        ];

        if (isDevelopment()) {
            try {
                $response['query'] = \Debugbar::getData()['queries'];
            } catch (\Exception $exception) {

            }
        }

        return response()->json($response, 200)->setEncodingOptions(JSON_UNESCAPED_UNICODE)->send();
    }

    /**
     * 失败返回JSON
     * @param string $message 错误信息
     * @param int $code 错误代码
     * @param int $httpCode http状态码
     * @return array
     */
    protected function error($message = 'error', $code = -1, $httpCode = 200)
    {
        // notice  如果有 500 以上的报错，需要联系开发人员，这里写一个日志
        // 400 以上的都是请求方的问题，也记录下
        if (abs($code) > 400 && abs($code) < 1000) {
            \Log::emergency($message, \Request::toArray());
        }

        // $code=200 用作了成功的状态，正常来说，失败是不会传递200的，但是还是做下判断处理
        $code = $code == 200 ? -200 : $code;
        $response = [
            'msg' => $message,
            'code' => $code,
            'data' => new \stdClass(),
        ];

        if (isDevelopment()) {
            try {
                $response['query'] = \Debugbar::getData()['queries'];
            } catch (\Exception $exception) {

            }
        }

        return response()->json($response, $httpCode)->setEncodingOptions(JSON_UNESCAPED_UNICODE)->send();
    }
}
