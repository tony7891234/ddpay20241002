<?php

$prefix = 'dcat';
$order_id = 'dcat1234';
//$result = substr($order_id, strlen($prefix));
$result = substr($order_id, 0,4);
var_dump($result);

$str = 'E18236120202411100756s10d1100b15';
var_dump(strlen($str));
