#!/bin/sh
# honor JAVA_HOME if defined
if [ -z "$JAVA_HOME" ]; then 
    JAVA_HOME=/usr/java/jre1.3.1_04/bin
fi

# honor FOP_HOME if defined
if [ -z "$FOP_HOME" ]; then 
    FOP_HOME=/usr/local/fop
fi

# honor JIMI_HOME if defined
if [ -z "$JIMI_HOME" ]; then 
    JIMI_HOME=/usr/local/jimi
fi

# honor SAXON_HOME if defined
if [ -z "$SAXON_HOME" ]; then 
    SAXON_HOME=/usr/local/saxon
fi

if [ $# != 2 ]
then
    echo "Usage: fo2pdf <fo file> <pdf file>"
    exit 2
fi 

echo "Transforming FO file '$1' to PDF file '$2' ..."
CP=${FOP_HOME}/build/fop.jar:${FOP_HOME}/lib/batik.jar:${SAXON_HOME}/saxon.jar:${FOP_HOME}/lib/xml-apis.jar:${FOP_HOME}/lib/avalon-framework-cvs-20020315.jar:${FOP_HOME}/lib/logkit-1.0.jar:${JIMI_HOME}/JimiProClasses.zip
echo Using CLASSPATH: ${CP}
${JAVA_HOME}/java -cp ${CP} org.apache.fop.apps.Fop -fo $1 -pdf $2
echo "Done!"
