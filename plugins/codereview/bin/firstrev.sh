#!/bin/sh

#looking for the first revision number using the path
firstRev=$(svn log --stop-on-copy --xml $1 | grep revision | tail -1 | cut -d'"' -f2 | cut -d'"' -f1)
echo $firstRev
