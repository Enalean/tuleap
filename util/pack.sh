#!/bin/bash
#
# pack.sh
#
# pack tarballs for release
#
# @author Christopher Han <xiphux@gmail.com>
# @copyright Copyright (c) 2010 Christopher Han
# @package GitPHP
# @package util
#

STAGEDIR="staging"
PKGDIR="gitphp"

# Prepare the staging directory
rm -Rf "${STAGEDIR}"
mkdir -p "${STAGEDIR}"

# Get a working snapshot of the HEAD
git archive --format=tar --prefix=${PKGDIR}/ HEAD | tar -C "${STAGEDIR}" -xvf -

# Get the version
cd "${STAGEDIR}"
VERSION="`cat ${PKGDIR}/include/version.php | grep '^\$gitphp_version = ' | cut -d '\"' -f 2`"

if [ -z "${VERSION}" ]; then
	echo "Could not determine version"
	exit 1
fi

# Make the snapshot versioned
PKGVERDIR="${PKGDIR}-${VERSION}"
mv -v "${PKGDIR}" "${PKGVERDIR}"
cd "${PKGVERDIR}"

# Remove the gitignore files
find . -iname '.gitignore' -exec rm {} ';'

# Remove the debug locale, it's not useful in the released version
rm -rf ./locale/zz_Debug

# Build the translations
./util/msgfmt.sh

# Minify javascript
./util/minify.sh

# Remove requirejs compressor after we've used it, no need to redistribute it
rm -Rf lib/requirejs
rm -Rf lib/closure
rm -Rf lib/rhino

# Remove the utility scripts
rm -rf ./util

cd ..

# Roll the tarballs
rm -f ${PKGVERDIR}.zip
rm -f ${PKGVERDIR}.tar.bz2
rm -f ${PKGVERDIR}.tar.gz

zip -r9 ${PKGVERDIR}.zip ${PKGVERDIR}
tar -cf ${PKGVERDIR}.tar ${PKGVERDIR}/
bzip2 -kv9 ${PKGVERDIR}.tar
gzip -v9 ${PKGVERDIR}.tar

# Remove the working copy
rm -rf ${PKGVERDIR}
