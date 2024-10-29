<?php

$prefix = 'dcat';
$order_id = 'dcat1234';
//$result = substr($order_id, strlen($prefix));
$result = substr($order_id, 0,4);
var_dump($result);
