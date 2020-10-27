#!/bin/sh

set -e

mkdir -p /etc/gitlab/ssl/
chmod 755 /etc/gitlab/ssl

if [ ! -f /etc/gitlab/ssl/gitlab.local.crt ]; then
    openssl req \
        -batch \
        -nodes \
        -x509 \
        -newkey rsa:4096 \
        -keyout /etc/gitlab/ssl/gitlab.local.key \
        -out /etc/gitlab/ssl/gitlab.local.crt \
        -days 365 \
        -subj "/C=XX/ST=SomeState/L=SomeCity/O=SomeOrganization/OU=SomeDepartment/CN=gitlab.local" \
        -addext "subjectAltName=DNS:gitlab.local"
fi

exec /assets/wrapper
