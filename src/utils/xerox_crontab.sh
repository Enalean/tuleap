#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2005. All Rights Reserved
# http://codex.xerox.com
#
# 
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    Run a series of scripts in the right order for the periodic cron
#    update of CodeX

# Read util directory location from local.inc
if [ -z "$CODEX_LOCAL_INC" ]; then 
    CODEX_LOCAL_INC=/etc/codex/conf/local.inc
fi
CODEX_UTILS_PREFIX=`/bin/grep '^\$codex_utils_prefix' $CODEX_LOCAL_INC | /bin/sed -e 's/\$codex_utils_prefix\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
dump_dir=`/bin/grep '^\$dump_dir' $CODEX_LOCAL_INC | /bin/sed -e 's/\$dump_dir\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
SYS_DISABLE_SUBDOMAINS=`/bin/grep '^\$sys_disable_subdomains' $CODEX_LOCAL_INC | /bin/sed -e 's/\$sys_disable_subdomains\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
if [ -z $SYS_DISABLE_SUBDOMAINS ]; then
	SYS_DISABLE_SUBDOMAINS=0
fi

# First run the dump utility for users and groups
cd $CODEX_UTILS_PREFIX/underworld-dummy
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
# because the $dump_dir dir has perm 700 which is not
# enough for newaliases to operate correctly
# and run the newaliases command to update sendmail
#
# NOTE: the newaliases command is not necessary because
# sendmail automagically detects the change of the aliases
# file. But just in case...
cp $dump_dir/aliases /etc/aliases.codex
/usr/bin/newaliases

# generate the list of CodeX virtual hosts
./apache_conf.pl


#
# NOW THE REAL UPDATE
#

cd $CODEX_UTILS_PREFIX 
# update user and groups system files
# as well as various repositories
cp -f /etc/passwd /etc/passwd.backup
cp -f /etc/shadow /etc/shadow.backup
cp -f /etc/group /etc/group.backup
cp -f /etc/smbpasswd /etc/smbpasswd.backup 2>/dev/null
./new_parse.pl

# Apache configuration must be reloaded because of new SVN repositories
cp -f /etc/httpd/conf.d/codex_svnroot.conf /etc/httpd/conf.d/codex_svnroot.conf.backup
cp -f $dump_dir/subversion_dir_dump /etc/httpd/conf.d/codex_svnroot.conf
/sbin/service httpd reload

# update authorized SSH keys in home dir
./ssh_create.pl

# create mailing lists in mailman (activated Nov. 9 by jstidd)
./mailing_lists_create.pl

# remove deleted releases and released files
cd $CODEX_UTILS_PREFIX/download
./download_filemaint.pl
