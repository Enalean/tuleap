#! /bin/bash

#
# BEGINNING OF CONFIGURATION INFORMATION
#
TEST_SERVER_FULLNAME="unconfigured"
TEST_SERVER_HOSTNAME="unconfigured"
PORT_NUMBER="unconfigured"
TEST_SERVER_DIR="unconfigured"
YOUR_EMAIL_ADDRESS="unconfigured"
LOCAL_LDAP_SERVER="unconfigured"
#
# END OF CONFIGURATION INFORMATION
#

#
# Prompt for a password.
#
trap "stty echo" EXIT
stty -echo
echo -n "Enter database passord: " 1>&2
read TEST_SERVER_DB_PASSWORD
stty echo
trap - EXIT
echo "(password)"

if [ "$TEST_SERVER_FULLNAME" = "unconfigured" ]
then
    echo "You must edit the configuration information in this" 1>&2
    echo "script before running the script." 1>&2
    exit 1
fi

substitute_keywords() {
    if [ -f "$2" ]
    then
	echo "Backing up $2 to $2.old"
	mv "$2" "$2.old" || exit 1
    fi

    echo "Generating $2.new from $1."

    sed -e "s@TEST_SERVER_DIR@${TEST_SERVER_DIR//@/\\@}@g" \
	-e "s@PORT_NUMBER@${PORT_NUMBER//@/\\@}@g" \
	-e "s@TEST_SERVER_FULLNAME@${TEST_SERVER_FULLNAME//@/\\@}@g" \
	-e "s@TEST_SERVER_HOSTNAME@${TEST_SERVER_HOSTNAME//@/\\@}@g" \
	-e "s@YOUR_EMAIL_ADDRESS@${YOUR_EMAIL_ADDRESS//@/\\@}@g" \
	-e "s@TEST_SERVER_DB_PASSWORD@${TEST_SERVER_DB_PASSWORD//@/\\@}@g" \
	-e "s@LOCAL_LDAP_SERVER@${LOCAL_LDAP_SERVER//@/\\@}@g" \
	"$1" > "$2.new" || exit 1

    if [ -f "$2" ]
    then
	echo "Backing up $2 to $2.old"
	rm -f "$2.old" || exit 1
	ln "$2" "$2.old" || exit 1
    fi

    echo "Moving $2.new as $2."
    mv "$2.new" "$2" || exit 1
}

if [ ! -d bin -o ! -d etc -o ! -d httpd -o ! -d var ]
then
    echo "This directory does not look like a CodeX test server directory." 1>&2
    echo "It does not have bin, etc, httpd, and var subdirectories." 1>&2
    exit 1
fi

if [ ! -f etc/local.inc.template -o ! -f etc/httpd/conf/httpd.conf.template ]
then
    echo "This directory does not look like a CodeX test server directory." 1>&2
    echo "It does not have both a etc/local.inc.template and a" 1>&2
    echo "etc/httpd/conf/httpd.conf.template file." 1>&2
    exit 1
fi

umask 007
substitute_keywords etc/local.inc.template etc/local.inc
substitute_keywords etc/httpd/conf/httpd.conf.template etc/httpd/conf/httpd.conf
