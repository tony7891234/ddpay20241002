#
#
# select orderid  from  baxi_20241003.cd_order_1104   where account='00020101021226860014br.gov.bcb.pix2564qrcode.fitbank.com.br/QR/cob/B700DCCD5365121B6A0BC16B56B9B4989CF5204000053039865802BR5925 TECNOLOGIA E SERV6009SAO PAULO61080145490162070503***6304D4DF';
#
#
#
#
#
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241003.cd_order_250701  order by order_id  desc limit 5;
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241010.cd_order   order by order_id  asc limit  5;
#
# -- 3.查询大表的总数据
# select  count(*)  from  baxi_20241003.cd_order_250701 ;
# -- 4。执行插入
# INSERT INTO baxi_20241003.cd_order_250701 ( SELECT * FROM baxi_20241010.cd_order  where  order_id>=211312834  LIMIT 500000);
#
# --  5 取出最大值 比如 1001
# select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241003.cd_order_250701  order by order_id  desc limit 5;
# -- 6。删除 小于5的数据
# delete  from   baxi_20241010.cd_order   where    order_id<=211812840  limit 2  ;
#
# -- 查询
# select  count(*) from   baxi_20241010.cd_order    where    order_id<=212312840  ;

# 2025。6。15 号备注   上面的方式废弃了   使用下面的方式

# 1.最大的 cd_order_250701.order_id   2. 这个id 之后的50w
# 第一 插入多少条数据
INSERT INTO baxi_20241003.cd_order_250701 ( SELECT * FROM baxi_20241010.cd_order  WHERE order_id >= ( SELECT MAX(order_id) FROM baxi_20241003.cd_order_250701 ) LIMIT 300000 );
# 第二 删除50w数据
delete  from   baxi_20241010.cd_order   where    order_id<=( SELECT MAX(order_id) FROM baxi_20241003.cd_order_250701 )  limit 300000  ;
# 第三 查看订单号   可以不看
select  count(*) from   baxi_20241010.cd_order   where    order_id<=( SELECT MAX(order_id) FROM baxi_20241003.cd_order_250701 )  ;

--      上面订单  下面资金

select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241003.cd_moneylog_250701  order by moneylog_id  desc limit 5;
select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241010.cd_moneylog   order by moneylog_id  asc limit  5;

# INSERT INTO baxi_20241003.cd_moneylog_250701 ( SELECT * FROM baxi_20241010.cd_moneylog  where  moneylog_id>=236339591   LIMIT 1000000);
#
#
# delete  from   baxi_20241010.cd_moneylog   where   moneylog_id<=232839590  limit  500000   ;
#
# select  count(*) from   baxi_20241010.cd_moneylog    where    moneylog_id<=237339590  ;


# 第一 插入多少条数据
INSERT INTO baxi_20241003.cd_moneylog_250701 ( SELECT * FROM baxi_20241010.cd_moneylog  WHERE moneylog_id >= ( SELECT MAX(moneylog_id) FROM baxi_20241003.cd_moneylog_250701 ) LIMIT 500000 );
# 第二 删除50w数据
delete  from   baxi_20241010.cd_moneylog   where    moneylog_id<=( SELECT MAX(moneylog_id) FROM baxi_20241003.cd_moneylog_250701 )  limit 500000  ;
# 第三 查看订单号   可以不看
select  count(*) from   baxi_20241010.cd_moneylog   where    moneylog_id<=( SELECT MAX(moneylog_id) FROM baxi_20241003.cd_moneylog_250701 )  ;


#
# --   移动表   end
#
# select   *   from   cd_request_3  order by id asc limit 1;
# select   *   from   cd_request_3  order by id  desc  limit 1;
#
#
# SELECT table_name,
#        ROUND(data_length / 1024 / 1024, 2) AS data_size_mb,
#        ROUND(index_length / 1024 / 1024, 2) AS index_size_mb,
#        ROUND(data_free / 1024 / 1024, 2) AS free_space_mb
# FROM information_schema.tables
# WHERE table_schema = 'baxi_20241010' AND table_name = 'cd_order';
#
# ANALYZE TABLE cd_order;
#
#
# OPTIMIZE TABLE cd_order;
# SELECT table_name,
#        ROUND(data_length / 1024 / 1024, 2) AS data_size_mb,
#        ROUND(index_length / 1024 / 1024, 2) AS index_size_mb,
#        ROUND(data_free / 1024 / 1024, 2) AS free_space_mb
# FROM information_schema.tables
# WHERE table_schema = 'baxi_20241010' AND table_name = 'cd_moneylog';
#
# ALTER TABLE cd_moneylog   ENGINE=InnoDB;
#
# SHOW TABLE STATUS LIKE  'cd_moneylog' \G;
#

# 2025。7。8号验证过的 清理空间的方法
# 1。检查表的空间
SELECT
    table_name,
    ROUND(data_length/1024/1024) AS data_mb,
    ROUND(index_length/1024/1024) AS index_mb,
    ROUND(data_free/1024/1024) AS free_mb
FROM information_schema.tables
WHERE table_schema = 'baxi_20241010'
  AND table_name = 'cd_moneylog';
# 2 执行  2分钟多
OPTIMIZE TABLE cd_moneylog;
# 3。 执行   几秒钟就好
ANALYZE TABLE cd_moneylog;



# 1。检查表的空间
SELECT
    table_name,
    ROUND(data_length/1024/1024) AS data_mb,
    ROUND(index_length/1024/1024) AS index_mb,
    ROUND(data_free/1024/1024) AS free_mb
FROM information_schema.tables
WHERE table_schema = 'baxi_20241010'
  AND table_name = 'cd_order';
# 2 执行
OPTIMIZE TABLE cd_order;
# 3。 执行
ANALYZE TABLE cd_order;

SELECT
    table_name,
    ROUND(data_length/1024/1024) AS data_mb,
    ROUND(index_length/1024/1024) AS index_mb,
    ROUND(data_free/1024/1024) AS free_mb
FROM information_schema.tables
WHERE table_schema = 'baxi_20241003'
  AND table_name = 'cd_order_250701';

