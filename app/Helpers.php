<?php

if (!function_exists('curlPost')) {
    /**
     * @param $url
     * @param $params
     * @param array $headers
     * @param bool $is_json
     * @return bool|string
     */
    function curlPost($url, $params, $is_json = true, $headers = [])
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        // post  请求的处理
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        // json  请求处理
        if ($is_json) {
            $params = is_array($params) ? json_encode($params) : $params;
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            if ($headers) {
                $head_str = implode(',', $headers);
                curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', $head_str]);
            } else {
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            }
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        }

        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }
        return $data;
    }
}

/**
 * 全局函数
 */
if (!function_exists('logToResponse')) {
    /**
     * 写入数据到某个文件  临时导出数据使用
     * @param $message
     * @param string $fileName
     */
    function logToResponse($message, $fileName = 'response.txt')
    {
        $fileName = 'logs/' . $fileName;
        file_put_contents($fileName, $message, FILE_APPEND);
        file_put_contents($fileName, "\n", FILE_APPEND);
    }
}

/**
 * 获取设备类型
 */
if (!function_exists('get_device_type')) {
    /**
     * @return string
     */
    function get_device_type()
    {
        //全部变成小写字母
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $type = 'other';

        //分别进行判断
        if (strpos($agent, 'iphone') || strpos($agent, 'ipad')) {
            $type = 'ios';
        }

        if (strpos($agent, 'android')) {
            $type = 'android';
        }

        return $type;
    }
}

if (!function_exists('allUpdateOrAdd')) {
    /**
     * 添加或更新的方法
     * 原生SQL INSERT INTO test_data (id,message,data) VALUES ('1','1607588191','66'),('2','1607588191','31'),('3','1607588191','45'),('4','1607588191','45')
     *         ON DUPLICATE KEY UPDATE `message`=VALUES(`message`),`data`=VALUES(`data`)
     * @param $connection string 数据库连接
     * @param $table string  数据表
     * @param $fieldArr array 要更新的字段(比如[username,age])
     * @param $unique_filed string 唯一值
     * @param array $updateData 要更新数据库数据
     * @param string $updateFieldDiv 要更新字段自定义
     * @param int $chunkSize 切片数量
     * @return bool|int
     */
    function allUpdateOrAdd($connection, $table, $fieldArr, $unique_filed = 'id', $updateData = [], $updateFieldDiv = '', $chunkSize = 1000)
    {
        if (empty($updateData)) return false;
//        $fieldNames = implode(',', $fieldArr);
        $fieldNames = implode('`,`', $fieldArr);
        $fieldNames = ('`' . $fieldNames . '`');
        # 分片处理更新
        $chunks = array_chunk($updateData, $chunkSize);
        $i = 0;
        foreach ($chunks as $value) {
            $sqlMain = "INSERT INTO `{$table}` ({$fieldNames}) VALUES ";
            foreach ($value as $k => $v) {
                $res = [];
                foreach ($fieldArr as $field) {
//                    $res[] = $v[$field];
                    $res[] = is_string($v[$field]) ? addslashes($v[$field]) : $v[$field]; // 如果是 string  使用反斜线引用字符串
                }
                $updateValues = "'" . implode("','", $res) . "'";
                $sqlMain .= "({$updateValues}),";
                $i++;
                unset($res);
            }
            $str = '';
            foreach ($fieldArr as $field) {
                if ($field == $unique_filed) continue; // 唯一索引的字段不参与更新
                $str .= "`{$field}`=VALUES(`{$field}`),";
            }
            $str .= $updateFieldDiv;
            $sqlMain = rtrim($sqlMain, ',') . " ON DUPLICATE KEY UPDATE " . rtrim($str, ',');
            if ($i) {
                \DB::connection($connection)->insert($sqlMain);
            }
        }
        return $i;
    }
}

if (!function_exists('yuan')) {
    /**
     * 将分单位换算为元单位
     * @param int $money
     * @param int $format 保留几位小数
     * @return string
     */
    function yuan($money = 0, $format = 2)
    {
        return sprintf("%01." . $format . "f", $money / 100);
    }
}

if (!function_exists('createNewRandString')) {
    /**
     * 生成新的字符串
     * @param int $length
     * @return string
     */
    function createNewRandString($length = 5)
    {
        $returnStr = '';
        $pattern = 'abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < $length; $i++) {
            $returnStr .= $pattern[mt_rand(0, 25)]; //生成php随机数
        }
        return strtolower($returnStr) . rand(10000, 99999);
    }

}

if (!function_exists('formatNumber')) {
    /**
     * 格式化数字
     * @param int $money
     * @param int $format 保留几位小数
     * @return string
     */
    function formatNumber($money = 0, $format = 3)
    {
        return sprintf("%01." . $format . "f", $money);
    }
}

if (!function_exists('divideMultipleTimes')) {
    /**
     * @param $num
     * @param $times
     * @param $divisor
     * @return string
     */
    function divideMultipleTimes($num, $times, $divisor)
    {
        for ($i = 1; $i <= $times; $i++) {
            $num /= $divisor;
        }
        return $num;
    }
}

if (!function_exists('fen')) {
    /**
     * 将元转换为分单位
     * @param int $money
     * @return int
     */
    function fen($money = 0)
    {
        return intval(strval($money * 100));
    }
}

if (!function_exists('logToMe')) {
    /**
     * 需要开发人员处理的 bug 记录 log 日志
     * @param $message string
     * @param $arr array
     * @param $errCode int
     */
    function logToMe($message, $arr = [], $errCode = 0)
    {
        \Log::alert($message, $arr);
    }
}

if (!function_exists('isDevelopment')) {
    /**
     * 是否开发环境
     * @return bool
     */
    function isDevelopment()
    {
        return config('app.env') == 'local' ? true : false;
    }
}

if (!function_exists('getTimeString')) {
    /**
     * 获取当前的时间 string  格式
     * @return int
     */
    function getTimeString()
    {
        return now()->format('Y-m-d H:i:s');
    }
}

if (!function_exists('formatTimeToString')) {
    /**
     * @param $timestamp
     * @return string
     */
    function formatTimeToString($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
}

if (!function_exists('addFloatNum')) {
    /**
     * 浮点数加
     * @param $float_1
     * @param $float_2
     * @param int $format
     * @return string
     */
    function addFloatNum($float_1, $float_2, $format = 3)
    {
        return function_exists('bcadd') ? bcadd($float_1, $float_2, $format) : formatNumber($float_1 + $float_2, $format);
    }
}

if (!function_exists('decFloatNum')) {
    /**
     * 浮点数相减
     * @param $float_1
     * @param $float_2
     * @param int $format
     * @return string
     */
    function decFloatNum($float_1, $float_2, $format = 3)
    {

        return function_exists('bcsub') ? bcsub($float_1, $float_2, $format) : formatNumber($float_1 - $float_2, $format);
    }
}

if (!function_exists('mulFloatNum')) {
    /**
     * 浮点数乘
     * @param $float_1
     * @param $float_2
     * @param int $format
     * @return string
     */
    function mulFloatNum($float_1, $float_2, $format = 3)
    {
        if ($float_1 == 0 || $float_2 == 0) {
            return 0;
        }
        return function_exists('bcmul') ? bcmul($float_1, $float_2, $format) : formatNumber($float_1 * $float_2, $format);
    }
}

if (!function_exists('divFloatNum')) {
    /**
     * 浮点数相除
     * @param $float_1
     * @param $float_2
     * @param int $format
     * @return string
     */
    function divFloatNum($float_1, $float_2, $format = 3)
    {
        if ($float_1 == 0 || $float_2 == 0) {
            return 0;
        }
        return function_exists('bcdiv') ? bcdiv($float_1, $float_2, $format) : formatNumber($float_1 / $float_2, $format);
    }
}
