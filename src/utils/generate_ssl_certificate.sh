#!/bin/bash
#
# Codendi
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
#
# 
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    Generate a self-signed certificate to enable SSL support. This script
#    removes any existing key and certificate.

RM='/bin/rm'
CHMOD='/bin/chmod'
SERVICE='/sbin/service'
OPENSSL='/usr/bin/openssl'
TAR='/bin/tar'

if [ -e /etc/debian_version ]; then
    KEYDIR="/etc/ssl/private"
    CERTDIR="/etc/ssl/certs"
else
    KEYDIR="/etc/pki/tls/private"
    CERTDIR="/etc/pki/tls/certs"
fi

mkdir -p $KEYDIR $CERTDIR

echo "Generating a self-signed certificate to enable SSL support."
echo "When asked to enter the 'Common Name', please use your server domain name (sys_default_domain)."
echo "This will remove any existing SSL key and certificate."
read -p "Continue? [yn]: " yn
if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

export SSL_KEY="$KEYDIR/localhost.key"
SSL_CERT="$CERTDIR/localhost.crt"
SSL_CSR="$CERTDIR/localhost.csr"

# Remove existing key and certificate
if [ -f $SSL_CSR ]; then 
  $TAR cf /var/tmp/oldcert.tar $SSL_KEY $SSL_CERT $SSL_CSR
fi
$RM -f $SSL_KEY
$RM -f $SSL_CERT
$RM -f $SSL_CSR

# Generate a new key
$OPENSSL genrsa 1024 > $SSL_KEY
$CHMOD go-rwx $SSL_KEY
# pseudo-random serial number
serialno="0x$((date; echo "$$"; cat $SSL_KEY) | md5sum | cut -b1-7)"

# Create new certificate, valid for 10 years
umask 77
# All in one (no CSR) 
#$OPENSSL req -new -key $SSL_KEY -x509 -days 3650 -out $SSL_CERT -set_serial "$serialno" 

# Generate Certificate Signing Request (CSR)
$OPENSSL req -new -key $SSL_KEY -out $SSL_CSR 

# Generate a self-signed certificate
$OPENSSL req -key $SSL_KEY -in $SSL_CSR -x509 -days 3650 -out $SSL_CERT -set_serial "$serialno"

# Restart httpd server
#$SERVICE httpd restart
echo "You will need to restart your HTTP server to take into account the new certificate"
