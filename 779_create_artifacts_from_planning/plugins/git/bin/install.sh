#!/usr/bin/env bash

echo "############################################"
echo "#                                          #"
echo "#             Git Plugin install           #"
echo "#                                          #"
echo "############################################"


function dieOnError() {
	local rcode=$?
	local msg=$1
	if [ $rcode -ne 0 ];then
		echo '[ERROR] '$msg' code='$rcode
		exit 1
	fi
}

function getNewestPackageDir() {
	local name=$1
	local newest=`ls -1 -tr | grep -E "^${name}-[0-9]" | tail -1`
	echo $newest
}

SCRIPT_DIR=`dirname $0`
RPMS_DIR="$SCRIPT_DIR/../RPMS"
cd $RPMS_DIR
dieOnError 'RPM directory not found' 

ARCH='x86_64'
if [ "`uname -i`" != 'x86_64' ];then
    ARCH='i386'
fi


#Plugin dependencies rpms
echo " -> Removing installed dependencies if any ..."
rpm -e --allmatches geshi php-Smarty 2>&1 | grep -v "error"
echo " -> Installing plugin dependencies RPM ..."
rpm -Uvh `getNewestPackageDir geshi`/noarch/geshi*.rpm 
rpm -Uvh `getNewestPackageDir php-Smarty`/noarch/php-Smarty*.rpm 

#Git rpms
echo "  -> Removing installed Git if any ..."
rpm -e --allmatches git perl-Git perl-Error 2>&1 | grep -v "error"
echo "  -> Installing Git plugin RPM ..."
rpm -Uvh `getNewestPackageDir git`/noarch/*.rpm
dieOnError 'Unable to install some rpms'
rpm -Uvh `getNewestPackageDir git`/${ARCH}/*.rpm
dieOnError 'Unable to install git rpm'

#git cmd and git shell
GIT=`which git`
dieOnError 'git command not found'
GIT_SHELL=`which git-shell`
grep $GIT_SHELL /etc/shells &> /dev/null
if [ $? -eq 0 ];then
    echo  '[WARNING] git shell is already in /etc/shells'
else
    echo $GIT_SHELL >> /etc/shells	
fi

#root dir
GITROOT_DIR=/var/lib/codendi/gitroot
if [ ! -d "$GITROOT_DIR"  ];then
    mkdir -p $GITROOT_DIR
fi
chown codendiadm:codendiadm $GITROOT_DIR
chmod 775 $GITROOT_DIR

if [ ! -L '/gitroot'  ];then
   ln -s $GITROOT_DIR /gitroot
fi

mkdir -p /var/tmp/codendi_cache/smarty/{templates_c,cache}
chmod -R 755 /var/tmp/codendi_cache/smarty/
chown -R codendiadm:codendiadm /var/tmp/codendi_cache/smarty/

exit 0
