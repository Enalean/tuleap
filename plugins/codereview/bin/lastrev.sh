#!/bin/sh

#looking for the last revision number using the path
REVISION=$(svn info $1 |grep '^Revision:' | sed -e 's/^Revision: //')
echo $REVISION