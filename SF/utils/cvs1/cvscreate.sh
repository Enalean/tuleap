#!/bin/sh
echo ""
echo "CVS Repository Tool"
echo "(c)1999 SourceForge Development Team"
echo "Released under the GPL, 1999"
echo ""

# if no arguments, print out help screen
if test $# -lt 2; then 
	echo "usage:"
	echo "  cvscreate.sh [repositoryname] [groupid]"
	echo ""
	exit 1 
fi

# make sure this repository doesn't already exist
if [ -d /cvsroot/$1 ] ; then
	echo "$1 already exists."
	echo ""
	exit 1
fi

# first create the repository
mkdir /cvsroot/$1
cvs -d/cvsroot/$1 init

# make it group writable
chmod 775 /cvsroot/$1

# import default directory, with default cvs.txt
#mkdir $1
#cp cvs.txt $1
#cd $1
#cvs -d/cvsroot/$1 import -m "SourceForge CVStool creation" $1 SourceForge start	
#rm cvs.txt
#cd ..
#rmdir $1

# turn off pserver writers, on anonymous readers
echo "" > /cvsroot/$1/CVSROOT/writers
echo "anonymous" > /cvsroot/$1/CVSROOT/readers
echo "anonymous:\$1\$0H\$2/LSjjwDfsSA0gaDYY5Df/:anoncvs_$1" > /cvsroot/$1/CVSROOT/passwd 

# setup loginfo to make group ownership every commit
echo "ALL chgrp -R $1 /cvsroot/$1" > /cvsroot/$1/CVSROOT/loginfo
echo "" > /cvsroot/$1/CVSROOT/val-tags
chmod 664 /cvsroot/$1/CVSROOT/val-tags

# set group ownership, anonymous group user 
chown -R nobody:$2 /cvsroot/$1
cat /etc/passwd | grep -v anoncvs_$1 > newpasswd 
cp newpasswd /etc/passwd
rm -f newpasswd
/usr/sbin/adduser -M -g $2 -d/cvsroot/$1 -s /bin/false -n anoncvs_$1

