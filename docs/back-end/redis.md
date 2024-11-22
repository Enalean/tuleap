# Redis in dev environment

## Start Redis

``` console
$> docker-compose up -d redis
```

## Configure Tuleap instance to target Redis

``` console
$> make bash-web
$tuleap> cat /etc/tuleap/conf/redis.inc
<?php

$redis_server = 'redis';
$redis_port = 6379;
$redis_password = '';
```

Set number of backend worker
greater than 0 (for example: `tuleap config-set sys_nb_backend_workers 1`). Then
restart tuleap deamon: `service tuleap restart`.

# Inspect content on Redis server

``` console
$> docker-compose exec redis redis-cli
127.0.0.1:6379> keys *
[…]
```
