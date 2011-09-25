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

CSSDIR="css"
CSSEXT=".css"
MINCSSEXT=".min.css"

rm -f ${JSDIR}/*${MINEXT}

for i in ${JSDIR}/*${JSEXT}; do
	echo "Minifying ${i}..."
	JSMODULE="`basename ${i%$JSEXT}`"
	#java -jar "${COMPRESSORDIR}/${COMPRESSORJAR}" --charset utf-8 -o "${i%$JSEXT}${MINEXT}" "${i}"
	java -classpath lib/rhino/js.jar:lib/closure/compiler.jar org.mozilla.javascript.tools.shell.Main lib/requirejs/r.js -o name=${JSMODULE} out=${JSDIR}/${JSMODULE}${MINEXT} baseUrl=${JSDIR} paths.jquery="empty:" exclude="jquery" optimize="closure"
done

rm -f ${CSSDIR}/*${MINCSSEXT}

for i in ${CSSDIR}/*${CSSEXT}; do
	echo "Minifying ${i}..."
	#java -jar "${COMPRESSORDIR}/${COMPRESSORJAR}" --charset utf-8 -o "${i%$CSSEXT}${MINCSSEXT}" "${i}"
	java -classpath lib/rhino/js.jar org.mozilla.javascript.tools.shell.Main lib/requirejs/r.js -o cssIn=${i} out=${i%$CSSEXT}${MINCSSEXT} optimizeCss="standard"
done
