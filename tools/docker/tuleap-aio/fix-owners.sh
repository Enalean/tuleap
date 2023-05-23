#!/usr/bin/env bash

echo -n "Update data ownership to current image..."

chown -R gitolite:gitolite \
    /data/lib/tuleap/gitolite/repositories \
    /data/lib/gitolite

chown -R codendiadm:codendiadm \
    /data/etc/tuleap \
    /data/etc/httpd/conf.d/codendi_svnroot.conf \
    /data/etc/httpd/conf.d/codendi_svnroot.conf.old \
    /data/home/codendiadm \
    /data/lib/tuleap/boomerang \
    /data/lib/tuleap/docman \
    /data/lib/tuleap/forumml \
    /data/lib/tuleap/images \
    /data/lib/tuleap/mediawiki \
    /data/lib/tuleap/tracker \
    /data/lib/tuleap/trackerv3 \
    /data/lib/tuleap/user \
    /data/lib/tuleap/wiki \
    /data/lib/tuleap/gitolite/admin \
    /var/lib/tuleap/svn_plugin \
    /data/lib/tuleap/git-lfs

chown codendiadm:codendiadm \
    /data/home/groups \
    /data/home/users \
    /data/lib/tuleap \
    /data/lib/tuleap/backup \
    /data/lib/tuleap/cvsroot \
    /data/lib/tuleap/gitolite \
    /data/lib/tuleap/gitroot \
    /data/lib/tuleap/svnroot

chown codendiadm \
    /data/lib/tuleap/ftp/tuleap/*

chown -R codendiadm \
    /data/lib/tuleap/svnroot/*

chown dummy \
    /data/home/groups/* \
    /data/lib/tuleap/ftp/pub/*

chown dummy:dummy \
    /data/lib/tuleap/dumps

chown root:ftp \
     /data/lib/tuleap/ftp

chown ftpadmin:ftpadmin \
    /data/lib/tuleap/ftp/incoming \
    /data/lib/tuleap/ftp/pub

find /data/lib/tuleap/ftp/tuleap \
    -type f \
    -exec chown codendiadm.ftpadmin {} \;

echo "DONE !"
