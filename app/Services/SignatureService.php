<?php

namespace App\Services;

class SignatureService
{
    public function generateSign($data, $signKey)
    {
        // 1. 对数组按key排序
        ksort($data);
        
        // 2. 将数组转换为key=value&key=value格式
        $string = '';
        foreach ($data as $key => $value) {
            if ($key != 'sign' && !is_null($value) && $value !== '') {
                $string .= $key . '=' . $value . '&';
            }
        }
        
        // 3. 加上签名密钥
        $string .= 'key=' . $signKey;
        
        // 4. MD5加密并转大写
        return strtoupper(md5($string));
    }

    public function verifySign($data, $signKey, $sign)
    {
        return $this->generateSign($data, $signKey) === $sign;
    }
} 