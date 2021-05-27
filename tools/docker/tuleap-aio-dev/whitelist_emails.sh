#!/bin/bash


cat /data/etc/email_whitelist | sed 's/$/ :/' > /etc/postfix/transport
echo "* error: Recipient not whitelisted." >> /etc/postfix/transport
postmap /etc/postfix/transport
