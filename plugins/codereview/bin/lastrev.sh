#looking for the last revision number using the path
revision=$(svn info $1 |grep '^Revision:' | sed -e 's/^Revision: //')
echo $revision