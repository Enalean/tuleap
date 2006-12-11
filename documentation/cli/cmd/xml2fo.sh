#!/bin/sh
# honor JAVA_HOME if defined
if [ -z "$JAVA_HOME" ]; then 
  JAVA_HOME=/usr/java/jre/bin
fi

# honor SAXON_HOME if defined
if [ -z "$SAXON_HOME" ]; then 
  SAXON_HOME=/usr/local/saxon
fi

# Determine the script location
progname=$0
scriptdir=`dirname $progname`
DOC_HOME="$scriptdir/../.."
cd ${DOC_HOME} ; DOC_HOME=`pwd`; cd -

if [ $# != 3 ]
then
    echo "Usage: xml2fo <xml file> <fo file> <language>"
    exit 2
fi 

echo "Transforming XML file '$1' to FO file '$2' ..."
CP=${SAXON_HOME}/saxon.jar
echo Using CLASSPATH: ${CP}
${JAVA_HOME}/java -cp ${CP} com.icl.saxon.StyleSheet $1 ${DOC_HOME}/cli/xsl/fo/docbook_$3.xsl > $2
if [ $? != 0 ]
then
	echo "Failed!"
	exit 1
fi
echo "Done!"
exit 0
