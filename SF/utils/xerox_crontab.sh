#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# $Id$
#
# Purpose:
#    Run a series of scripts in the right order for the periodic cron
#    update of CodeX

UTILSHOME="/home/httpd/SF/utils"

# First run the dump utility for users and groups
cd $UTILSHOME/underworld-dummy
./dump_database.pl

# Then dump the mailing list
./mailing_lists_dump.pl

# Dump the authorized ssh keys
./ssh_dump.pl

# dump the mail aliases (not needed here
# because we do not want to create e-mail aliases
# for the codex members (member@codex.xerox.com)
# ./new_aliases.pl


# generate the e-mail aliases file (this 
# script generates a ready to use file like
# the one in /etc/aliases
./mail_aliases.pl

# we also need to copy the CodeX aliases file in /etc
# because the ~dummy dir has perm 700 which is not
# enough for newaliases to operate correctly
# and run the newaliases command to update sendmail
#
# NOTE: the newaliases command is not necessary because
# sendmail automagically detects the change of the aliases
# file. But just in case...
cp ~dummy/dumps/aliases /etc/aliases.codex
/usr/bin/newaliases

# and restart sendmail to be sure the new aliases DB
# is taken into account (should not be necessary but I had
# 2nd note: in the end it's not necessary to restart it
# problems without restarting)
# LJ killall -HUP sendmail


# generate the DNS zone file and restart the DNS daemon
#
./dns_conf.pl
DNS_DIR=/var/named
cp -f $DNS_DIR/codex_full.zone $DNS_DIR/codex_full.zone.backup
cp -f ~dummy/dumps/dns_dump $DNS_DIR/codex_full.zone
killall -HUP named

# generate the list of CodeX virtual hosts
./apache_conf.pl


#
# NOW THE REAL UPDATE
#

cd $UTILSHOME 
# update user and groups system files
# as well as various repositories
cp -f /etc/passwd /etc/passwd.backup
cp -f /etc/shadow /etc/shadow.backup
cp -f /etc/group /etc/group.backup
cp -f /etc/smbpasswd /etc/smbpasswd.backup 2>/dev/null
./new_parse.pl

# Apache must be restarted after user/group update because
# Unix Groups are used in Apache Virtual Hosts declaration
cp -f /etc/httpd/conf/codex_vhosts.conf /etc/httpd/conf/codex_vhosts.conf.backup
cp -f ~dummy/dumps/apache_dump /etc/httpd/conf/codex_vhosts.conf
cp -f /etc/httpd/conf/codex_svnhosts.conf /etc/httpd/conf/codex_svnhosts.conf.backup
cp -f ~dummy/dumps/subversion_dump /etc/httpd/conf/codex_svnhosts.conf
/usr/sbin/apachectl graceful

# update authorized SSH keys in home dir
./ssh_create.pl

# create mailing lists in mailman (activated Nov. 9 by jstidd)
./mailing_lists_create.pl

# remove deleted releases and released files
cd $UTILSHOME/download
./download_filemaint.pl
