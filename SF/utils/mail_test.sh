#!/bin/sh
# Test script to see if mailing lists work correctly
UTILSHOME="/home/httpd/SF/utils"

# First run the dump utility for users and groups
cd $UTILSHOME/underworld-dummy
# ./dump_database.pl - disable for mailing list test

# Second dump the mailing list
./mailing_lists_dump.pl

# Dump the authorized ssh keys
# ./ssh_dump.pl - disable for mailing list test

# dump the mail aliases (not needed here
# because we do not want to create e-mail aliases
# for the codex members (member@codex.xerox.com)
# ./new_aliases.pl


# generate the e-mail aliases file (this 
# script generates a ready to use file like
# the one in /etc/aliases
./mail_aliases.pl

# we also need to copy the CodeX aliases file in /etc
# because the /home/dummy dir has perm 700 which is not
# enough for newaliases to operate correctly
# and run the newaliases command to update sendmail
cp /home/dummy/dumps/aliases /etc/aliases.codex
/usr/bin/newaliases

# and restart sendmail to be sure the new aliases DB
# is taken into account (should not be necessary but I had
# problems without restarting)
# LJ killall -HUP sendmail


# generate the DNS zone file
#./dns_conf.pl  - disable for mailing list test


#
# NOW THE REAL UPDATE
#

cd $UTILSHOME 
# update user and groups system files
# as well as various repositories
# ./new_parse.pl  - disable for mailing list test


# update authorized SSH keys in home dir
# ./ssh_create.pl  - disable for mailing list test

# create mailing lists (activated Nov. 9 by jstidd)
./mailing_lists_create.pl
