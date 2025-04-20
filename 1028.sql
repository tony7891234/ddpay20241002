CREATE TABLE `cd_order_250408` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `orderid` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `merchantid` int DEFAULT NULL COMMENT '商户ID',
  `sysorderid` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '系统订单号',
  `amount` decimal(20,4) DEFAULT NULL COMMENT '金额',
  `status` smallint DEFAULT '2' COMMENT '状态',
  `remarks` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '备注',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  `update_time` int DEFAULT NULL COMMENT '更新时间',
  `completetime` int DEFAULT NULL COMMENT '完成时间',
  `proxy_id` smallint DEFAULT NULL COMMENT '卡商',
  `merchantnumber` smallint DEFAULT NULL COMMENT '类型',
  `username` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '会员姓名',
  `date` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '订单创建时间',
  `callbackurl` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '跳转地址',
  `notifyurl` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '异步通知地址',
  `account` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '收款账号',
  `realname` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '收款人名称',
  `bank` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '银行名称',
  `bankname` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '开户行',
  `service_id` smallint DEFAULT NULL COMMENT '客服',
  `notify_status` tinyint unsigned NOT NULL DEFAULT '0',
  `notify_num` smallint unsigned NOT NULL DEFAULT '0',
  `pay_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '付款人',
  `kh_ip` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '客户IP',
  `kouling` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '0' COMMENT '口令',
  `other_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '三方订单状态:0=处理中,1=成功',
  `other_json` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `qr_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `sf_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `num` smallint unsigned DEFAULT '0',
  `inizt` int NOT NULL DEFAULT '0',
  `sdzt` int DEFAULT '0',
  `yh_bq` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `time` decimal(20,1) DEFAULT NULL,
  `sf_xr` int DEFAULT NULL,
  `bank_lx` int DEFAULT '0',
  `amount_real` decimal(20,4) DEFAULT NULL,
  `bank_open` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '收付款方式1：银行卡2AnSpace',
  `amount_real_pay` decimal(20,4) NOT NULL DEFAULT '0.0000' COMMENT '实际付款金额',
  `pay_time` int DEFAULT '0' COMMENT '付款时间(银行到账时间)',
  `df_fee` decimal(20,4) NOT NULL DEFAULT '0.0000' COMMENT '代付手续费',
  `agent_commission` decimal(20,4) NOT NULL DEFAULT '0.0000' COMMENT '代理佣金',
  PRIMARY KEY (`order_id`),
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `cd_order_merchantid_merchantnumber_create_time_index` (`merchantid`,`merchantnumber`,`create_time`,`status`) USING BTREE,
  KEY `cd_order_merchantnumber_merchantid_create_time_status_index` (`merchantnumber`,`merchantid`,`create_time`,`status`) USING BTREE,
  KEY `cd_order_proxy_id_create_time_status_index` (`proxy_id`,`create_time`,`status`) USING BTREE,
  KEY `cd_order_inizt_index` (`inizt`) USING BTREE,
  KEY `sf_id_2` (`sf_id`,`inizt`) USING BTREE,
  KEY `status` (`status`,`create_time`,`notify_status`,`notify_num`,`sdzt`) USING BTREE,
  KEY `notify_status` (`notify_status`,`notify_num`,`sdzt`) USING BTREE,
  KEY `completetime` (`completetime`) USING BTREE,
  KEY `bank_open` (`bank_open`) USING BTREE,
  KEY `bank_lx` (`bank_lx`) USING BTREE,
  KEY `createtime_completetime` (`create_time`,`completetime`) USING BTREE,
  KEY `yh_bq` (`yh_bq`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb3;
-- 2025。3.19 号 备注
--   KEY `cd_order_merchantnumber_index` (`merchantnumber`) USING BTREE,
--   KEY `cd_order_merchantids_index` (`merchantid`) USING BTREE,
--   KEY `sf_id` (`sf_id`) USING BTREE,
--   KEY `isx_create_time` (`create_time`) USING BTREE,
--   KEY `cd_order_status_index` (`status`) USING BTREE,
--   KEY `cd_order_proxy_id_index` (`proxy_id`) USING BTREE,
--   KEY `cd_order_proxy_id_create_time_index` (`proxy_id`,`create_time`) USING BTREE,
--   KEY `amount_real_pay` (`amount_real_pay`) USING BTREE,
--   KEY `agent_commission` (`agent_commission`) USING BTREE,



CREATE TABLE `cd_moneylog_250405` (
  `moneylog_id` int NOT NULL AUTO_INCREMENT,
  `service_id` int DEFAULT NULL COMMENT '客服',
  `proxy_id` int DEFAULT NULL COMMENT '码商',
  `merchant_id` int DEFAULT NULL COMMENT '商户',
  `type` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '变动类型',
  `action` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '方法',
  `begin` decimal(20,3) DEFAULT '0.000' COMMENT '期初金额',
  `after` decimal(20,3) DEFAULT '0.000' COMMENT '期末金额',
  `money` decimal(20,3) DEFAULT '0.000' COMMENT '发生额',
  `content` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '详情',
  `remark` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '备注',
  `ext` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '扩展内容',
  `adduser` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '操作人',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  `test` int NOT NULL DEFAULT '0',
  `sxf` decimal(3,3) DEFAULT '0.000',
  `bank_lx` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '收付款方式1：银行卡2AnSpace',
  PRIMARY KEY (`moneylog_id`),
  UNIQUE KEY `proxy_id` (`proxy_id`,`merchant_id`,`money`,`create_time`) USING BTREE,
  KEY `cd_moneylog_create_time_type_index` (`create_time`,`type`) USING BTREE,
  KEY `cd_moneylog_merchant_id_type_index` (`merchant_id`,`type`) USING BTREE,
  KEY `bank_lx` (`bank_lx`) USING BTREE,
  KEY `adduser` (`adduser`) USING BTREE,
  KEY `action` (`action`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb3;

alter table   cd_moneylog  add  INDEX `action` (`action`) USING BTREE;
alter table   cd_moneylog  add  INDEX `adduser` (`adduser`) USING BTREE;


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
  `error_message`  text COLLATE utf8mb4_unicode_ci COMMENT  '错误信息',
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


--  * @property  int order_id 订单id
--  * @property  int orderid 订单号
--  * @property  int create_time 添加时间
--  * @property  int notify_time 下次回掉时间
--  * @property  int notify_num  回掉次数
--  * @property  int notify_status  回掉状态
--  * @property  int type  1=充值；2=提款
--  * @property  string response  返回内容
--  * @property  string request  回掉内容
--  * @property  string notify_url  回掉地址
create database map_notify_order;
use  map_notify_order;

create database map_order;
use  map_order;

create database map_money_log;
use  map_money_log;


 CREATE TABLE `cd_notify_order_1109` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_id` int unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `orderid` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `notify_time` int unsigned NOT NULL DEFAULT '0' COMMENT '下次回掉时间',
  `notify_num` int unsigned NOT NULL DEFAULT '0' COMMENT '回掉次数',
  `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'type  1=充值；2=提款',
  `response` text COMMENT '返回内容',
  `request` text COMMENT '回掉内容',
  `notify_url` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '回掉地址',
  `notify_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '回掉状态:0=未回调;1=已经回调;2=失败',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `notify_time` (`notify_time`,`notify_num`) USING BTREE,
  KEY `notify_status` (`notify_status`) USING BTREE,
  KEY `notify_num` (`notify_num`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb3 COMMENT='异常回掉单';


CREATE TABLE `cd_map_notify_order` (
  `orderid` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '订单号',
  `table_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '表名字',
   INDEX orderid (orderid) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3  COMMENT='notify_order对应的日表';

--  删除的订单 2117680627344538  11.3 号的

--

drop table cd_map_order;
CREATE TABLE `cd_map_order` (
  `orderid` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '订单号',
  `sf_id` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '银行的id',
  `table_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '表名字',
     INDEX orderid (orderid) USING HASH,
     INDEX sf_id (sf_id) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3  COMMENT='订单对应的表名字';


 cd_request | CREATE TABLE `cd_request` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `merchant` longtext COLLATE utf8mb3_unicode_ci COMMENT '商户请求数据',
  `request` longtext COLLATE utf8mb3_unicode_ci COMMENT '系统请求数据',
  `response` longtext COLLATE utf8mb3_unicode_ci COMMENT '银行返回数据',
  `time_cost` varchar(200) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '请求的时间',
  `receipt` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '凭证链接',
  `callback` longtext COLLATE utf8mb3_unicode_ci COMMENT '银行回调数据',
  `createtime` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=15356583 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci COMMENT='日志'

