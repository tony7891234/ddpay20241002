<?php

$prefix = 'dcat';
$order_id = 'dcat1234';
$result = substr($order_id, strlen($prefix));
var_dump($result);
