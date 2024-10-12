<?php

function multiCurlPostRequest($urlsWithParams) {
    // 初始化多个 cURL 句柄
    $multiHandle = curl_multi_init();
    $curlHandles = [];
    $responses = [];

    // 为每个 URL 创建一个 cURL 句柄
    foreach ($urlsWithParams as $url => $params) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true); // 设置为 POST 请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params)); // 设置 POST 数据
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en-US,en;q=0.9',
            'Connection: keep-alive'
        ]);
        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[$url] = $ch; // 保存句柄以便后续使用
    }

    // 执行所有请求并等待完成
    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
        curl_multi_select($multiHandle); // 等待活动请求完成
    } while ($running > 0);

    // 处理每个请求的响应
    foreach ($curlHandles as $url => $ch) {
        $response = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // 获取 HTTP 状态码

        // 打印 URL、HTTP 状态码和响应内容
        echo "URL: $url\n";
        echo "HTTP Code: $httpCode\n";
        echo "Response: $response\n\n";

        // 移除并关闭句柄
        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }

    // 关闭多路 cURL 句柄
    curl_multi_close($multiHandle);
}

// 使用示例
$urlsWithParams = [
    "https://api.example.com/order1" => [
        "order_id" => "123",
        "amount" => 100.00,
        "currency" => "USD"
    ],
    "https://api.example.com/order2" => [
        "order_id" => "456",
        "amount" => 200.00,
        "currency" => "EUR"
    ],
    "https://api.example.com/order3" => [
        "order_id" => "789",
        "amount" => 300.00,
        "currency" => "GBP"
    ]
];

multiCurlPostRequest($urlsWithParams);
