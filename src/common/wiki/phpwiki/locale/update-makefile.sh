#!/bin/sh
#
# $Id: update-makefile.sh,v 1.9 2005/02/12 10:35:08 rurban Exp $
#
# This shell script is used to update the list of .po files and the
# dependencies for phpwiki.pot in the Makefile.
#
# Do not invoke this script directly, rather run:
#
#    make depend
#
# to update the Makefile.
#

# Generate the head (manually-edited part) of the new Makefile
#
makefile_head () {
    sed '/^# DO NOT DELETE THIS LINE$/,$ d' Makefile && cat <<'EOF'
# DO NOT DELETE THIS LINE
#
# The remainder of this file is auto-generated
#
# (Run 'make depend' regenerate this section.)
#
EOF
}

# Find all .po files in po/.
#
po_files () {
    find po -name "*.po" |
	sort |
	sed 's/^/po: /p;
             s|^.*/\(.*\)\.po$|mo: \1/LC_MESSAGES/phpwiki.mo \1/LC_MESSAGES/phpwiki.php|;'
}

# Find all php and html source code which should be scanned
# by xgettext() for localizeable strings.
# find ../lib fails on cygwin
# TODO: autogenerate .exclude list from CVS/Entries
pot_file_deps () {
    test -f .exclude || ( echo lib/pear/ > .exclude; echo lib/WikiDB/adodb/ > .exclude; echo lib/nusoap/ > .exclude )
    (cd ..; find lib themes \( -type d -regex '\(^lib/pear\)\|\(^lib/WikiDB/adodb\)\|\(^lib/nusoap\)' \) -prune -o \( -type f -a -name \*.php -o -name \*.tmpl \)) |
        egrep -v '(^lib/pear)|(^lib/WikiDB/adodb)|(^lib/nusoap)' |
        grep -v -f .exclude |
	sed 's|^|${POT_FILE}: ../|;' |
	sort
}

# Generate the new Makefile
{ makefile_head &&
    po_files &&
    echo "#" &&
    pot_file_deps; } > Makefile.new || exit 1

if diff -q Makefile Makefile.new > /dev/null
then
    # Don't touch the Makefile if unchanged.
    # (This avoids updating the timestamp)
    rm Makefile.new
    echo "Makefile unchanged" 1>&2
    exit 0
fi

mv Makefile.new Makefile && echo "Makefile updated" 1>&2
