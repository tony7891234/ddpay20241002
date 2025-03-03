https://jingyan.baidu.com/article/47a29f24610740c0142399ea.html


## 过程
````   
php artisan admin:publish
php artisan admin:install

mkdir  storage/admin

php artisan vendor:publish --tag=iframe-tab
php artisan vendor:publish --tag=iframe-tab.config
php artisan vendor:publish --tag=iframe-tab.view


cp vendor/workerman/workerman/Protocols/Ws.php
vendor/workerman/workerman/Protocols/Wss.php

* * * * *  php  /www/wwwroot/Laravel/T1/ddpay20241002/artisan schedule:run >> /dev/null 2>&1
* * * * *  php   /www/wwwroot/ddpay20241002/artisan  t2 >> /dev/null 2>&1


mkdir  logs
mkdir public/logs_me  (自己添加 Y-m 文件)
mkdir  storage/app/voluti
mkdir  storage/app/pem

php artisan storage:link
php artisan  workman  restart
php  artisan queue:work --queue=dcat_admin,default
````

##  资料
````   
扩展包
https://github.com/IronnMan/dcat-admin-packages
````

##  指令相关
````   
/www/server/php/83/bin/php artisan 
````
curl --request GET \
--url https://api.pagnovo.com/transactions/:11 \
--header 'Accept: application/json' \
--header 'Authorization: Basic {43a5e34a-0a05-4111-ab9d-ea1eb008199b}'


##  数据库
````   
DATABASE = 4aoypo5gyfoj1kddb1zt
USERNAME = KeY8BWU0K4670FITk2XP
PASSWORD = INHXT0PjV3xIjqIhQahq

导出数据库
mysql -u KeY8BWU0K4670FITk2XP -p 4aoypo5gyfoj1kddb1zt <  /www/wwwroot/baxi.sql
mysqldump -u KeY8BWU0K4670FITk2XP -p 4aoypo5gyfoj1kddb1zt >  /www/wwwroot/baxi.sql

导入数据库
mysql -h baxi.cbc0my2esp7q.us-east-1.rds.amazonaws.com -u baxi_aws  -p baxi_dev_20241010 < /www/wwwroot/baxi.sql

iseewrlJxRJiIdasdsssrre

mysql -h baxi.cbc0my2esp7q.us-east-1.rds.amazonaws.com -u baxi_aws -p

````

ps -ef |grep 'php -c /etc/php-cli.ini artisan notify'

## 查询
````   
grep   TX17292757173569581417   /www/wwwroot/guoji/public/logs_abc/df_notify20241019.txt



grep  '19-Oct-2024'   /www/server/php/74/var/log/php-fpm.log

````
## 域名
````   
后台 https://test107.hulinb.com/admin333
日志 https://test107.hulinb.com/admin555/log-viewer/logs
````

##  数据库
````  
--  从旧的表拿数据到新的表
INSERT INTO baxi_20241010.cd_order ( SELECT * FROM   baxi_20241003.cd_order  where  orderid='013117420204337004');

````

## 域名相关
````   
批量出款后台  巴西
后台  https://test107.hulinb.com/admin999  
日志  https://test107.hulinb.com/admin/log-viewer/logs

批量出款后台   印度
后台  https://test107.viwyw.com/admin777  
日志  https://test107.viwyw.com/admin/log-viewer/logs
````


dig +short baxi.cbc0my2esp7q.us-east-1.rds.amazonaws.com

CPF 09804233410 1 test

##  supervisor  执行的订单有
````   
1: 回掉 create_time 5小时之前的待回掉订单 NotifyCommand.php 
php artisan  notify  notify

2:再次回掉,上面1回掉没被接受，并且小于2次回掉的 NotifyOrderCommand.php
php artisan  notify_order

3: 上面2也失败的，直接去站点回掉  NotifyToSiteCommand.php
php  artisan notify_site

4: workman
php artisan  workman  restart

5: job 执行
php artisan queue:work --queue=dcat_admin,default


另外   Kernel 中还有一个补发遗漏订单 10 分钟执行一次
NotifyCommand 文件的 forLeftOrder 方法，在次之前24小时到再次之前4小时内的订单
4小时,这个数字是必须大于 1 中的时间的。也就是1 可能执行不到了，所以需要这个执行
这个目的是因为，有些单子回掉太晚。比如 12小时之前的单子，1就执行不到
````

##  日志检测
````    

所有post
tail -n 100000 /www/wwwlogs/www.hulinb.com.log | grep "POST"  | awk '{print $4}' | cut -d: -f2,3 | sort | uniq -c
所有 get
tail -n 100000 /www/wwwlogs/www.hulinb.com.log | grep "GET"  | awk '{print $4}' | cut -d: -f2,3 | sort | uniq -c

1.入款请求
tail -n 100000 /www/wwwlogs/www.hulinb.com.log | grep "POST /api/bxds/submitOrder" | awk '{print $4}' | cut -d: -f2,3 | sort | uniq -c
1.2  出款请求
tail -n 100000 /www/wwwlogs/www.hulinb.com.log |  grep "POST /api/bx/submitOrder"  | awk '{print $4}' | cut -d: -f2,3 | sort | uniq -c
2. 入款查询
tail -n 100000 /www/wwwlogs/www.hulinb.com.log |  grep "POST /api/bx/QueryOrder"  | awk '{print $4}' | cut -d: -f2,3 | sort | uniq -c
3.银行回掉
tail -n 100000 /www/wwwlogs/www.hulinb.com.log |  grep "POST /api/callback/deposit"  | awk '{print $4}' | cut -d: -f2,3 | sort | uniq -c


最近100w 笔中  每小时的访问量
tail -n 1000000 /www/wwwlogs/www.hulinb.com.log | awk '{print $4}' | cut -d: -f1,2 | tr -d "[" | sort | uniq -c
100w 中每分钟的访问量
tail -n 1000000 /www/wwwlogs/www.hulinb.com.log | awk '{print $4}' | cut -d: -f2,3 | tr -d "[" | sort | uniq -c




awk '{print $4}' /www/wwwlogs/www.hulinb.com.log  | cut -d: -f1,2 | tr -d "[" | sort | uniq -c
每分钟的访问量
awk '{print $4}' /www/wwwlogs/www.hulinb.com.log  | cut -d: -f2,3 | tr -d "[" | sort | uniq -c

tail -n 100000  /www/wwwlogs/www.hulinb.com.log | awk '{print $4}' | cut -d: -f1,2 | tr -d "[" | sort | uniq -c

````
