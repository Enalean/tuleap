#!/bin/bash
#
# Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
# This file is licensed under the GNU General Public License version 2. See the file COPYING.
#
# Purpose:
#    How to compile the fileforge file. This script must be executed from root account !!
#

PERL='/usr/bin/perl'

if [ -z "$CODENDI_LOCAL_INC" ]; then 
    CODENDI_LOCAL_INC=/etc/codendi/conf/local.inc
fi
ftp_incoming_dir=`/bin/grep '^\$ftp_incoming_dir' $CODENDI_LOCAL_INC | /bin/sed -e 's/\$ftp_incoming_dir\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'" `
ftp_frs_dir_prefix=`/bin/grep '^\$ftp_frs_dir_prefix' $CODENDI_LOCAL_INC | /bin/sed -e 's/\$ftp_frs_dir_prefix\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'" `
codendi_bin_prefix=`/bin/grep '^\$codendi_bin_prefix' $CODENDI_LOCAL_INC | /bin/sed -e 's/\$codendi_bin_prefix\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'" `


substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  $PERL -pi -e "s|$2|$3|g" $1
}

cp fileforge.c fileforge_custom.c
substitute fileforge_custom.c '/var/lib/codendi/ftp/incoming' "$ftp_incoming_dir" 
substitute fileforge_custom.c '/var/lib/codendi/ftp/codex' "$ftp_frs_dir_prefix" 

gcc fileforge_custom.c -o fileforge
chown root.root fileforge
chmod u+s fileforge
mv fileforge $codendi_bin_prefix
rm fileforge_custom.c
