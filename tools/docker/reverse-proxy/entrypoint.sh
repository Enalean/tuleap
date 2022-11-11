#!/bin/sh

set -e

mkdir -p /etc/pki/tls/private/ /etc/pki/tls/certs
if [ ! -f /etc/pki/tls/certs/front-reverse-proxy.cert.pem ]; then
    openssl req \
        -batch \
        -nodes \
        -x509 \
        -newkey rsa:4096 \
        -keyout /etc/pki/tls/private/front-reverse-proxy.key.pem \
        -out /etc/pki/tls/certs/front-reverse-proxy.cert.pem \
        -days 3650 \
        -subj "/C=XX/ST=SomeState/L=SomeCity/O=SomeOrganization/OU=SomeDepartment/CN=tuleap-web.tuleap-aio-dev.docker" \
        -addext "subjectAltName=DNS:tuleap-web.tuleap-aio-dev.docker"
fi

exec "$@"
