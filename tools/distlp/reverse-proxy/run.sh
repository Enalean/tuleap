#!/usr/bin/env bash

set -ex

/opt/rh/rh-php56/root/bin/php /tuleap/tools/distlp/reverse-proxy/run.php

exec /sbin/nginx -g "daemon off;"
