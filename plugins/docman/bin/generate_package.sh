#!/bin/bash

set -e

# Get script dir
dirname=$(dirname $0)
scriptdir=$(cd "$dirname"; pwd)

# Package name from svn revision
revision=$(svn info "$scriptdir" | grep '^Revision:' | sed -e 's/^Revision: //')
pkgdirname="DocmanImport-r$revision"

# Copy source dir and clean up svn artifacts
cp -ar "$scriptdir/DocmanImport" "$pkgdirname"
rm -rf "$pkgdirname/.svn"

# Create zip package
zip -r "$pkgdirname" "$pkgdirname"

# Clean up temporary directory
rm -rf "$pkgdirname"