#!/bin/sh
# honor JAVA_HOME if defined
if [ -z "$JAVA_HOME" ]; then 
    JAVA_HOME=/usr/java/jre1.3.1_04/bin
fi

# honor SAXON_HOME if defined
if [ -z "$SAXON_HOME" ]; then 
    SAXON_HOME=/usr/local/saxon
fi

# Determine the script location
progname=$0
scriptdir=`dirname $progname`
DOC_HOME="$scriptdir/../.."

if [ $# != 2 ]
then
    echo "Usage: xml2html <xml file> <html folder>"
    exit 2
fi 

echo "Transforming XML file '$1' to HTML in '$2' ..."
CP=${SAXON_HOME}/saxon.jar
echo Using CLASSPATH: ${CP}
PREV_DIR=`pwd`
cd $2
${JAVA_HOME}/java -cp ${CP} com.icl.saxon.StyleSheet ${PREV_DIR}/$1 ${DOC_HOME}/user_guide/xsl/htmlhelp/htmlhelp.xsl > /dev/null
cd ${PREV_DIR}
echo "Done!"
