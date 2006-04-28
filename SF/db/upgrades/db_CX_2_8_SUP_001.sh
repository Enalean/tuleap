#!/bin/sh
# Fix for SR #334
# This script modifies existing /svnroot/projname/hooks/post-commit files 
# by redirecting the output of commit-email.pl to /dev/null.
# This script works properly even if some hook scripts have already been converted.
set -e

BASEDIR=/svnroot

/bin/ls ${BASEDIR} | while read rep; do 
    if [ -f "${BASEDIR}/${rep}/hooks/post-commit" ]; then
        echo "Updating ${rep} svn repository"
	cat ${BASEDIR}/${rep}/hooks/post-commit | sed -e 's#\(/usr/local/bin/commit-email.pl "$REPOS" "$REV"\)$#\1 2\>\&1 >/dev/null#' > ${BASEDIR}/${rep}/hooks/post-commit
    fi
done
