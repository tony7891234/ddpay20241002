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
批量出款后台
后台  https://test107.hulinb.com/admin999  joyce  joycejoyce
日志  https://test107.hulinb.com/admin/log-viewer/logs

````
