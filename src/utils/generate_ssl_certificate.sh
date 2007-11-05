#!/bin/bash
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2006. All Rights Reserved
# http://codex.xrce.xerox.com
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

echo "Generating a self-signed certificate to enable SSL support."
echo "When asked to enter the 'Common Name', please use your server domain name (sys_default_domain)."
echo "This will remove any existing SSL key and certificate."
read -p "Continue? [yn]: " yn
if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

export SSL_KEY='/etc/httpd/conf/ssl.key/server.key'
SSL_CERT='/etc/httpd/conf/ssl.crt/server.crt'
SSL_CSR='/etc/httpd/conf/ssl.csr/server.csr'
# Remove existing key and certificate
$TAR cf /var/tmp/oldcert.tar $SSL_KEY $SSL_CERT $SSL_CSR
$RM $SSL_KEY
$RM $SSL_CERT
$RM $SSL_CSR

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
