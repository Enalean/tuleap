#! /bin/bash

### Basic script to create debian packages...

clear

mkdir -p /tmp/vufind_build/vufind/usr/local/vufind
cd /tmp/vufind_build/vufind/usr/local

svn --force export https://vufind.svn.sourceforge.net/svnroot/vufind/trunk vufind

mv /tmp/vufind_build/vufind/usr/local/vufind/packages/DEBIAN /tmp/vufind_build/vufind

chmod 0775 /tmp/vufind_build/vufind/DEBIAN/postinst

cd /tmp/vufind_build/
dpkg-deb --build vufind

mv vufind.deb vufind_1.0.1.deb

