INSERT INTO baxi_20241003.cd_order_1031 ( SELECT * FROM baxi_20241010.cd_order   where  order_id> 84130450 LIMIT 1000000);
-- 1.获取新表最大的 id
select  order_id,   DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from cd_order_1031  order by order_id  desc limit 7;
-- 2。插入旧表，这个id 之后的100w数据
INSERT INTO baxi_20241003.cd_order_1031 ( SELECT * FROM baxi_20241010.cd_order  where order_id>123683951   order by order_id asc LIMIT 4000000);
-- 3.检查旧表这个id之前的数据，是否等于新表的总数据
select  count(*)  from cd_order_1031  where  order_id<=128110898;


-- 19  1729267200
-- 20  1729353600

-- 原表，最早的时间
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from   baxi_20241010.cd_order  order by order_id  asc limit 7;


select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from   baxi_20241010.cd_order  where create_time >=1729267200  order by order_id  asc limit 7;


--  1.插入到达标
INSERT INTO baxi_20241003.cd_order_1031 ( SELECT * FROM baxi_20241010.cd_order  where create_time   <1729353600    order by create_time asc LIMIT 2000000);


-- 2.1 原表订单
select   count(*)  from   baxi_20241010.cd_order  where create_time>=1729267200   and   create_time   <1729353600  ;

-- 2.2 大表订单
select   count(*)  from   baxi_20241003.cd_order_1031  where create_time>=1729267200   and   create_time   <1729353600  ;
-- 3. 如果数据一致，删除原表订单。最好从id 删除



-- 333333
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241003.cd_order_1031  order by order_id  desc limit 7;
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241010.cd_order   order by order_id  asc limit  3;


select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241010.cd_order   order by order_id  asc limit  3;
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241010.cd_order   order by order_id  asc limit  3;


delete from   baxi_20241010.cd_order    where  order_id<=142112476  limit 2000000;


select  count(*) from   baxi_20241010.cd_order    where  order_id<=142112476  ;



--   移动表   start
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241003.cd_order_1031  order by order_id  desc limit 5;
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241010.cd_order   order by order_id  asc limit  5;

-- 3.查询大表的总数据
select  count(*)  from  baxi_20241003.cd_order_1031 ;
-- 4。执行插入
INSERT INTO baxi_20241003.cd_order_1031 ( SELECT * FROM baxi_20241010.cd_order  where  order_id>=148112532  LIMIT 1000000);

--  5 取出最大值 比如 1001
select  order_id, orderid,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241003.cd_order_1031  order by order_id  desc limit 5;
-- 6。删除 小于5的数据
delete  from   baxi_20241010.cd_order   where   order_id<=148112531  ;


-- 查询
select  count(*) from   baxi_20241010.cd_order    where    order_id<=148112531  ;
select  count(*) from   baxi_20241003.cd_order_1031     ;



--      上面订单  下面资金

select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241003.cd_moneylog_1031  order by moneylog_id  desc limit 5;
select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241010.cd_moneylog   order by moneylog_id  asc limit  5;

INSERT INTO baxi_20241003.cd_moneylog_1031 ( SELECT * FROM baxi_20241010.cd_moneylog  where  moneylog_id>=164339581   LIMIT 2000000);
select  moneylog_id,  DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d %H:%i:%s') AS formatted_time  from baxi_20241003.cd_moneylog_1031  order by moneylog_id  desc limit 5;

select  count(*) from   baxi_20241003.cd_moneylog_1031  ;
select  count(*) from   baxi_20241010.cd_moneylog  where  moneylog_id<=  166339580;
select  count(*) from   baxi_20241010.cd_moneylog ;


delete  from   baxi_20241010.cd_moneylog   where   moneylog_id<=166339580  ;
--   移动表   end




INSERT INTO baxi_20241010.cd_order  ( SELECT * FROM  baxi_20241003.cd_order   where  orderid= '013117410270773492' );
INSERT INTO baxi_20241010.cd_order  ( SELECT * FROM  baxi_20241003.cd_order   where  orderid= '2117410246038159' );



select orderid  from  baxi_20241003.cd_order_1031   where account='00020101021226860014br.gov.bcb.pix2564qrcode.fitbank.com.br/QR/cob/B700DCCD5365121B6A0BC16B56B9B4989CF5204000053039865802BR5925 TECNOLOGIA E SERV6009SAO PAULO61080145490162070503***6304D4DF';
select orderid  from  baxi_20241003.cd_order   where account='00020101021226860014br.gov.bcb.pix2564qrcode.fitbank.com.br/QR/cob/B700DCCD5365121B6A0BC16B56B9B4989CF5204000053039865802BR5925 TECNOLOGIA E SERV6009SAO PAULO61080145490162070503***6304D4DF';
select orderid  from  baxi_20241010.cd_order   where account='00020101021226860014br.gov.bcb.pix2564qrcode.fitbank.com.br/QR/cob/B700DCCD5365121B6A0BC16B56B9B4989CF5204000053039865802BR5925 TECNOLOGIA E SERV6009SAO PAULO61080145490162070503***6304D4DF';



