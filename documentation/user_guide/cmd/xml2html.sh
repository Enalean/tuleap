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
# and the DOC_HOME directory (absolute path)
progname=$0
scriptdir=`dirname $progname`
DOC_HOME="$scriptdir/../.."
cd ${DOC_HOME} ; DOC_HOME=`pwd`; cd -

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
if [ $? != 0 ]
then 
	cd ${PREV_DIR}
        echo "Failed!"
        exit 1
fi
cd ${PREV_DIR}
echo "Done!"
exit 0
