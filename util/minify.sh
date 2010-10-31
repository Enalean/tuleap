#!/bin/bash
#
# minify.sh
#
# Minfies javascript files
#
# @author Christopher Han <xiphux@gmail.com>
# @copyright Copyright (c) 2010 Christopher Han
# @package GitPHP
# @subpackage util
#

JSDIR="js"
COMPRESSORDIR="lib/yuicompressor/build"
COMPRESSORJAR="yuicompressor-2.4.2.jar"

JSEXT=".js"
MINEXT=".min.js"

rm -f ${JSDIR}/*${MINEXT}

for i in ${JSDIR}/*${JSEXT}; do
	echo "Minifying ${i}..."
	java -jar "${COMPRESSORDIR}/${COMPRESSORJAR}" --charset utf-8 -o "${i%$JSEXT}${MINEXT}" "${i}"
done
