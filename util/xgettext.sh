#!/bin/bash
#
# xgettext.sh
#
# extracts strings from various sources
# into one pot file
#
# @author Christopher Han <xiphux@gmail.com>
# @copyright Copyright (c) 2010 Christopher Han
# @package GitPHP
# @package util
#

VER="`cat include/version.php | grep '^\$gitphp_version = ' | cut -d '\"' -f 2`"
DIR="locale"
COPYRIGHT="Christopher Han"
EMAIL="xiphux@gmail.com"
PKGNAME="GitPHP"
BUNDLE="gitphp"
FILE="gitphp.pot"

# Extract from templates
lib/smarty-gettext/tsmarty2c.php templates > smarty.c
xgettext -d ${BUNDLE} -o "${FILE}" -p ${DIR} -j --package-name="${PKGNAME}" --package-version="${VER}" --msgid-bugs-address="${EMAIL}" --copyright-holder="${COPYRIGHT}" --add-comments --no-location --from-code=utf-8 smarty.c
rm smarty.c

# Extract from include directory
find include -iname '*.php' -type f | xgettext -ktranslate -kngettext -d ${BUNDLE} -o "${FILE}" -p ${DIR} -L PHP -j --package-name="${PKGNAME}" --package-version="${VER}" --msgid-bugs-address="${EMAIL}" --copyright-holder="${COPYRIGHT}" --from-code=utf-8 -f -

# Extract from index
xgettext -ktranslate -kngettext -d ${BUNDLE} -o "${FILE}" -p ${DIR} -L PHP -j --package-name="${PKGNAME}" --package-version="${VER}" --msgid-bugs-address="${EMAIL}" --copyright-holder="${COPYRIGHT}" --from-code=utf-8 index.php

# File references from smarty-gettext show up as code comments,
# convert them back to file references
sed -e 's/^#\./#:/' -i "${DIR}/${FILE}"
