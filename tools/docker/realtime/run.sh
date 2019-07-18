#!/bin/bash

replacement=`echo $REALTIME_KEY | sed "s|/|\\\\\/|g"`
sed -s -i "s/private_key_to_change/$replacement/" /etc/tuleap-realtime/config.json

if [ ! -f /etc/pki/tls/tuleap-realtime-cert.pem ]; then
    cd /etc/pki/tls/
    openssl genrsa -out tuleap-realtime-key.pem 2048
    openssl req -new -key tuleap-realtime-key.pem -out tuleap-realtime-csr.pem -subj "/C=XX/ST=SomeState/L=SomeCity/O=SomeOrganization/OU=SomeDepartment/CN=realtime"
    openssl x509 -req -days 800 \
        -in tuleap-realtime-csr.pem \
        -signkey tuleap-realtime-key.pem \
        -out tuleap-realtime-cert.pem
    cp tuleap-realtime-cert.pem /published-certificate/
else
    echo "Certificate is already generated. No need to recreate it."
fi

cd /usr/lib/node_modules/tuleap-realtime/
exec node server.js --config=/etc/tuleap-realtime/config.json