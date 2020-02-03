#!/bin/sh

set -e

mkdir -p /etc/pki/tls/private/ /etc/pki/tls/certs
if [ ! -f /etc/pki/tls/certs/localhost.cert.pem ]; then
    openssl req \
        -batch \
        -nodes \
        -x509 \
        -newkey rsa:4096 \
        -keyout /etc/pki/tls/private/localhost.key.pem \
        -out /etc/pki/tls/certs/localhost.cert.pem \
        -days 365 \
        -subj "/C=XX/ST=SomeState/L=SomeCity/O=SomeOrganization/OU=SomeDepartment/CN=tuleap-web.tuleap-aio-dev.docker" \
        -addext "subjectAltName=DNS:tuleap-web.tuleap-aio-dev.docker"
fi

exec "$@"
