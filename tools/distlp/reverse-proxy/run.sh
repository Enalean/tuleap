#!/usr/bin/env bash

set -e

cp /tuleap/tools/distlp/reverse-proxy/proxy-vars.conf /etc/nginx/
cp /tuleap/tools/distlp/reverse-proxy/tuleap.conf /etc/nginx/conf.d/tuleap.conf

openssl req -batch -nodes -x509 -newkey rsa:4096 -keyout /etc/pki/tls/private/localhost.key.pem -out /etc/pki/tls/certs/localhost.cert.pem -days 365 -subj "/C=XX/ST=SomeState/L=SomeCity/O=SomeOrganization/OU=SomeDepartment/CN=tuleap-web.tuleap-aio-dev.docker" 2>/dev/null

/sbin/nginx -g "daemon off;"
