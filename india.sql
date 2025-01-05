CREATE TABLE `cd_order_0101` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(255) NOT NULL,
  `merchantid` int(11) DEFAULT NULL COMMENT '商户ID',
  `sysorderid` varchar(250) DEFAULT NULL COMMENT '系统订单号',
  `amount` decimal(20,4) DEFAULT NULL COMMENT '金额',
  `status` smallint(6) DEFAULT '2' COMMENT '状态',
  `remarks` varchar(250) DEFAULT NULL COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `completetime` int(11) DEFAULT NULL COMMENT '完成时间',
  `proxy_id` smallint(6) DEFAULT NULL COMMENT '卡商',
  `merchantnumber` smallint(6) DEFAULT NULL COMMENT '类型',
  `username` varchar(250) DEFAULT NULL COMMENT '会员姓名',
  `date` varchar(250) DEFAULT NULL COMMENT '订单创建时间',
  `callbackurl` varchar(250) DEFAULT NULL COMMENT '跳转地址',
  `notifyurl` varchar(250) DEFAULT NULL COMMENT '异步通知地址',
  `account` varchar(250) DEFAULT NULL COMMENT '收款账号',
  `realname` varchar(250) DEFAULT NULL COMMENT '收款人名称',
  `bank` varchar(250) DEFAULT NULL COMMENT '银行名称',
  `bankname` varchar(250) DEFAULT NULL COMMENT '开户行',
  `service_id` smallint(6) DEFAULT NULL COMMENT '客服',
  `notify_status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `notify_num` smallint(5) unsigned NOT NULL DEFAULT '0',
  `pay_name` varchar(255) DEFAULT NULL COMMENT '付款人',
  `kh_ip` varchar(255) DEFAULT NULL COMMENT '客户IP',
  `kouling` varchar(255) DEFAULT '0' COMMENT '口令',
  `other_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '三方订单状态:0=处理中,1=成功',
  `other_json` text,
  `qr_code` varchar(255) NOT NULL DEFAULT '',
  `sf_id` varchar(255) NOT NULL DEFAULT '',
  `num` smallint(5) unsigned DEFAULT '0',
  `inizt` int(3) NOT NULL DEFAULT '0',
  `sdzt` int(3) DEFAULT '0',
  `yh_bq` text,
  `time` decimal(20,1) DEFAULT NULL,
  `sf_xr` int(3) DEFAULT NULL,
  `bank_lx` int(3) DEFAULT '0',
  `amount_real` decimal(20,4) DEFAULT NULL,
  `bank_open` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '收付款方式1：银行卡2AnSpace',
  `amount_real_pay` decimal(20,4) NOT NULL DEFAULT '0.0000' COMMENT '实际付款金额',
  `pay_time` int(11) DEFAULT '0' COMMENT '付款时间(银行到账时间)',
  `df_fee` decimal(20,4) NOT NULL DEFAULT '0.0000' COMMENT '代付手续费',
  `agent_commission` decimal(20,4) NOT NULL DEFAULT '0.0000' COMMENT '代理佣金',
  `sh_ry` varchar(250) DEFAULT NULL COMMENT '审核人员',
    PRIMARY KEY (`order_id`),
  KEY `isx_create_time` (`create_time`),
  KEY `orderid` (`orderid`),
  KEY `sf_id` (`sf_id`),
  KEY `cd_order_merchantids_index` (`merchantid`),
  KEY `cd_order_merchantnumber_index` (`merchantnumber`),
  KEY `cd_order_merchantid_merchantnumber_create_time_index` (`merchantid`,`merchantnumber`,`create_time`,`status`) USING BTREE,
  KEY `cd_order_merchantnumber_merchantid_create_time_status_index` (`merchantnumber`,`merchantid`,`create_time`,`status`),
  KEY `cd_order_proxy_id_create_time_status_index` (`proxy_id`,`create_time`,`status`),
  KEY `cd_order_proxy_id_create_time_index` (`proxy_id`,`create_time`),
  KEY `cd_order_proxy_id_index` (`proxy_id`),
  KEY `cd_order_status_index` (`status`),
  KEY `cd_order_inizt_index` (`inizt`),
  KEY `sf_id_2` (`sf_id`,`inizt`),
  KEY `status` (`status`,`create_time`,`notify_status`,`notify_num`,`sdzt`),
  KEY `notify_status` (`notify_status`,`notify_num`,`sdzt`),
  KEY `completetime` (`completetime`),
  KEY `bank_open` (`bank_open`),
  KEY `bank_lx` (`bank_lx`),
  KEY `createtime_completetime` (`create_time`,`completetime`),
  KEY `amount_real_pay` (`amount_real_pay`) USING BTREE,
  KEY `agent_commission` (`agent_commission`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `cd_moneylog_0101` (
  `moneylog_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) DEFAULT NULL COMMENT '客服',
  `proxy_id` int(11) DEFAULT NULL COMMENT '码商',
  `merchant_id` int(11) DEFAULT NULL COMMENT '商户',
  `type` varchar(250) DEFAULT NULL COMMENT '变动类型',
  `action` varchar(250) DEFAULT NULL COMMENT '方法',
  `begin` decimal(20,3) DEFAULT '0.000' COMMENT '期初金额',
  `after` decimal(20,3) DEFAULT '0.000' COMMENT '期末金额',
  `money` decimal(20,3) DEFAULT '0.000' COMMENT '发生额',
  `content` varchar(250) DEFAULT NULL COMMENT '详情',
  `remark` varchar(250) DEFAULT NULL COMMENT '备注',
  `ext` text COMMENT '扩展内容',
  `adduser` varchar(250) DEFAULT NULL COMMENT '操作人',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `test` int(10) NOT NULL DEFAULT '0',
  `sxf` decimal(3,3) DEFAULT '0.000',
  `bank_lx` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '收付款方式1：银行卡2AnSpace',
  PRIMARY KEY (`moneylog_id`),
  UNIQUE KEY `proxy_id` (`proxy_id`,`merchant_id`,`money`,`create_time`),
  KEY `cd_moneylog_create_time_type_index` (`create_time`,`type`),
  KEY `cd_moneylog_merchant_id_type_index` (`merchant_id`,`type`),
  KEY `bank_lx` (`bank_lx`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--   移动表   start
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from  cd_order_0101  order by order_id  desc limit 5;
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from   cd_order   order by order_id  asc limit  5;

-- 3.查询大表的总数据
select  count(*)  from   cd_order_0101 ;
-- 4。执行插入
INSERT INTO  cd_order_0101 ( SELECT * FROM   cd_order  where  order_id>=83639969  LIMIT 1000000);

--  5 取出最大值 比如 1001
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from  cd_order_0101  order by order_id  desc limit 5;
-- 6。删除 小于5的数据
delete  from     cd_order   where   order_id<=83639968  limit 200000;


-- 查询
select  count(*) from     cd_order    where    order_id<=72567322  ;
select  count(*) from     cd_order_0101    where    order_id<=72567322  ;
select  count(*) from    cd_order_0101  ;
select  count(*) from    cd_order ;


--      上面订单  下面资金

select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from cd_moneylog_0101  order by moneylog_id  desc limit 5;
select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from cd_moneylog   order by moneylog_id  asc limit  5;

INSERT INTO cd_moneylog_0101 ( SELECT * FROM cd_moneylog  where  moneylog_id>=90937991   LIMIT 3000000);
select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from cd_moneylog_0101  order by moneylog_id  desc limit 5;

select  count(*) from   cd_moneylog_0101  ;
select  count(*) from   cd_moneylog  where  moneylog_id<=  170339580;
select  count(*) from   cd_moneylog ;


delete  from   cd_moneylog   where   moneylog_id<=90937990  limit 500000 ;
--   移动表   end
