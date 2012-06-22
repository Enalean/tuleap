#!/bin/sh

#looking for the first revision number using the path
a=$(svn log --stop-on-copy --xml $1 | grep revision | tail -1 | cut -d'"' -f2 | cut -d'"' -f1)
echo $a
#REVISION=$(svn info $1 |grep '^Revision:' | sed -e 's/^Revision: //')
#echo $REVISION
#creating diff between first revision and the HEAD
svn diff -r $a:HEAD $1 >$2