drop table  cd_batch_withdraw;
CREATE TABLE `cd_batch_withdraw` (
  `bach_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `merchant_id` int(10) unsigned NOT NULL DEFAULT '0',
  `batch_no` bigint(20) unsigned NOT NULL DEFAULT '0'  COMMENT '批量单号',
  `message` text COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `response_success` text COLLATE utf8mb4_unicode_ci COMMENT '成功的',
  `response_fail` text COLLATE utf8mb4_unicode_ci COMMENT '失败的',
  `file` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '文件路径',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(接收处理)',
  PRIMARY KEY (`bach_id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `batch_no` (`batch_no`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='批量出款';


drop table cd_withdraw_orders;
CREATE TABLE `cd_withdraw_orders` (
  `order_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `merchant_id` int(10) unsigned NOT NULL DEFAULT '0',
  `batch_no` bigint(20) unsigned NOT NULL DEFAULT '0'  COMMENT '批量单号',
  `bank_order_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '银行单号',
  `pix_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'pix类型',
  `status` smallint(3) unsigned NOT NULL DEFAULT '1'  COMMENT '支付状态',
  `pix_account` varchar(50)  NOT NULL DEFAULT ''  COMMENT 'pix账号',
  `withdraw_amount` DECIMAL(20,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '出款金额',
  `user_message` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '附言(给客户的)',
  `remark` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注(运营)',
  `error_message` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '错误信息',
  `request_bank` text COLLATE utf8mb4_unicode_ci COMMENT '请求银行内容',
  `response_bank` text COLLATE utf8mb4_unicode_ci COMMENT '银行返回',
  `notify_info` text COLLATE utf8mb4_unicode_ci COMMENT '银行回掉信息',
  `pix_info` text COLLATE utf8mb4_unicode_ci COMMENT 'pix_info',
  `pix_out` text COLLATE utf8mb4_unicode_ci COMMENT 'pix_out',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`order_id`),
  KEY `batch_no` (`batch_no`),
  KEY `pix_type` (`pix_type`),
  KEY `bank_order_id` (`bank_order_id`),
  KEY `pix_account` (`pix_account`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提款订单';

