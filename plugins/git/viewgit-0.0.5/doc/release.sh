#!/bin/sh
log="doc/ChangeLog"
tar="viewgit.tar"
git archive --format=tar --prefix=viewgit/ HEAD > $tar
git log --stat > $log
tar --owner=0 --group=0 --transform 's!^!viewgit/!' -rf viewgit.tar $log
rm $log
tar vtf $tar
gzip $tar
ls -l $tar.gz
