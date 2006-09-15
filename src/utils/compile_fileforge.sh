#!/bin/bash
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# Purpose:
#    How to compile the fileforge file. This script must be executed from root account !!
#

PERL='/usr/bin/perl'

if [ -z "$CODEX_LOCAL_INC" ]; then 
    CODEX_LOCAL_INC=/etc/codex/conf/local.inc
fi
ftp_incoming_dir=`/bin/grep '^\$ftp_incoming_dir' $CODEX_LOCAL_INC | /bin/sed -e 's/\$ftp_incoming_dir\s*=\s*\(.*\);\(.*\)/\1/'`
ftp_frs_dir_prefix=`/bin/grep '^\$ftp_frs_dir_prefix' $CODEX_LOCAL_INC | /bin/sed -e 's/\$ftp_frs_dir_prefix\s*=\s*\(.*\);\(.*\)/\1/'`
codex_bin_prefix=`/bin/grep '^\$codex_bin_prefix' $CODEX_LOCAL_INC | /bin/sed -e 's/\$codex_bin_prefix\s*=\s*\(.*\);\(.*\)/\1/'`


substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  $PERL -pi -e "s|$2|$3|g" $1
}

cp fileforge.c fileforge_custom.c
substitute fileforge_custom.c '"/var/lib/codex/ftp/incoming/"' "$ftp_incoming_dir" 
substitute fileforge_custom.c '"/var/lib/codex/ftp/codex/"' "$ftp_frs_dir_prefix" 

gcc fileforge_custom.c -o fileforge
chown root.root fileforge
chmod u+s fileforge
mv fileforge $codex_bin_prefix
rm fileforge_custom.c
