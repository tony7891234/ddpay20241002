


CREATE TABLE `cd_order_250914` LIKE `cd_order_250914`;
CREATE TABLE `cd_moneylog_250914` LIKE `cd_moneylog_250914`;


-- 2025。10。11 号备注
-- CREATE TABLE cd_order_copy LIKE cd_order;
-- truncate table cd_order_250914;
-- truncate table cd_moneylog_250914;

INSERT INTO   cd_moneylog_250914 ( SELECT * FROM cd_moneylog  order by moneylog_id asc  LIMIT 3);
INSERT INTO   cd_order_250914 ( SELECT * FROM cd_order  order by order_id asc  LIMIT 3);
select  count(*)    from  cd_order_250914;
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from cd_order_250914  order by order_id  desc limit 5;
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from  cd_order   order by order_id  asc limit  5;


INSERT INTO cd_order_250914 ( SELECT * FROM  cd_order  WHERE order_id >= ( SELECT MAX(order_id) FROM cd_order_250914 )  and  create_time<UNIX_TIMESTAMP('2025-10-14 16:00:00') LIMIT 300000 );
# 第二 删除50w数据
delete  from    cd_order   where    order_id<=( SELECT MAX(order_id) FROM cd_order_250914 )  limit 300000  ;
# 第三 查看订单号   可以不看
select  count(*) from    cd_order   where    order_id<=( SELECT MAX(order_id) FROM cd_order_250914 )  ;


select  count(*) from    cd_order   where   create_time<UNIX_TIMESTAMP('2025-10-14 16:00:00');


--      上面订单  下面资金
select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from   cd_moneylog_250914    order by moneylog_id  desc limit 5;
select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from  cd_moneylog   order by moneylog_id  asc limit  5;



# 第一 插入多少条数据
INSERT INTO   cd_moneylog_250914   ( SELECT * FROM  cd_moneylog  WHERE moneylog_id >= ( SELECT MAX(moneylog_id) FROM   cd_moneylog_250914   ) LIMIT 500000 );
INSERT INTO   cd_moneylog_250914   ( SELECT * FROM  cd_moneylog  WHERE moneylog_id >= ( SELECT MAX(moneylog_id) FROM   cd_moneylog_250914   ) and  create_time<UNIX_TIMESTAMP('2025-10-14 16:00:00') LIMIT 500000 );
# 第二 删除50w数据
delete  from    cd_moneylog   where    moneylog_id<=( SELECT MAX(moneylog_id) FROM   cd_moneylog_250914   )  limit 500000  ;
# 第三 查看订单号   可以不看
select  count(*) from    cd_moneylog   where    moneylog_id<=( SELECT MAX(moneylog_id) FROM   cd_moneylog_250914   )  ;
select  count(*) from    cd_moneylog   where     create_time<UNIX_TIMESTAMP('2025-10-14 16:00:00');
-- select  count(*) from    cd_moneylog_250914;


select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from   cd_moneylog_250914    order by moneylog_id  desc limit 10;
INSERT INTO   cd_moneylog_250914   ( SELECT * FROM  cd_moneylog  WHERE moneylog_id > 367839609 LIMIT 2 );
# 2 页可以这样
INSERT INTO   cd_moneylog_250914   ( SELECT * FROM  cd_moneylog  WHERE moneylog_id > ( SELECT MAX(moneylog_id) FROM   cd_moneylog_250914   ) LIMIT 2 );
# 3. 如果需要限制在某个
INSERT INTO   cd_moneylog_250914   ( SELECT * FROM  cd_moneylog  WHERE moneylog_id > ( SELECT MAX(moneylog_id) FROM   cd_moneylog_250914   ) LIMIT 500000 );



-- cd_request_log
select  id,  DATE_FORMAT(FROM_UNIXTIME(createtime), '%Y-%m-%d %H:%i:%s') AS formatted_time  from   cd_request_log_1024    order by id  desc limit 5;
select  id,  DATE_FORMAT(FROM_UNIXTIME(createtime), '%Y-%m-%d %H:%i:%s') AS formatted_time  from  cd_request_log   order by id  asc limit  5;



CREATE TABLE `cd_request_log_1024` LIKE `cd_request_log`;
INSERT INTO `cd_request_log_1024` SELECT * FROM `cd_request_log`;

INSERT INTO   cd_request_log_1024   ( SELECT * FROM  cd_request_log  WHERE id > ( SELECT MAX(id) FROM   cd_request_log_1024   ) LIMIT 1000000 );

delete  from    cd_request_log   where    id<=( SELECT MAX(id) FROM   cd_request_log_1024   )  limit 1000000  ;

select  count(*)   from  cd_request_log;
