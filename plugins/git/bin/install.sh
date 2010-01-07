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


SCRIPT_DIR=`dirname $0`
RPMS_DIR="$SCRIPT_DIR/../RPMS"
cd $RPMS_DIR
dieOnError 'RPM directory not found' 

ARCH='x86_64'
if [ "`uname -i`" != 'x86_64' ];then
    ARCH='i386'
fi

echo "  -> Removing installed Git if any ..."
rpm -e --allmatches git perl-Git perl-Error 
echo "  -> Installing Git plugin RPM ..."
newest_rpm=`ls -1  -I old -I TRANS.TBL | tail -1`
rpm -Uvh ${newest_rpm}/${ARCH}/*.rpm
dieOnError 'Unable to install git rpm'

# Git Plugin
GIT=`which git`
dieOnError 'git command not found'
GIT_SHELL=`which git-shell`
grep $GIT_SHELL /etc/shells &> /dev/null
if [ $? -eq 0 ];then
    echo  '[WARNING] git shell is already in /etc/shells'
else
    echo $GIT_SHELL >> /etc/shells	
fi
exit 0
