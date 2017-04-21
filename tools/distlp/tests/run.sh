#!/bin/sh

# This script will execute all the tests once the platform is ready to accept them
# Once it's done, the junit xml is moved on the output directory with the right
# credentials.

set -ex

BASEDIR=$(dirname $0)
cd $BASEDIR

if [ ! -f 'phpunit' ]; then
    curl -ssLO https://phar.phpunit.de/phpunit-6.1.phar
    mv phpunit-6.1.phar phpunit
fi


code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://reverse-proxy/svnplugin/svn-project-01/sample || true)
while [ $code -ne 401 ]; do
    sleep 1
    code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://reverse-proxy/svnplugin/svn-project-01/sample || true)
done

php7 phpunit --log-junit /tmp/distlp.xml SVNTest.php || true

if [ -d /output ]; then
    uid=$(stat -c %u /output)
    gid=$(stat -c %g /output)
    install -m 0644 -o $uid -g $gid /tmp/distlp.xml /output/
fi
