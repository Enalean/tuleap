#!/bin/sh

if [ $# != 3 ]
then
    echo "Usage: xml2pdf <xml file> <pdf file> <language>"
    exit 2
fi 

# Determine the script location
# and the CMD_HOME directory (absolute path)
progname=$0
CMD_HOME=`dirname $progname`
cd ${CMD_HOME} ; CMD_HOME=`pwd`; cd -

xmldir=`dirname $1`
xmlfilename=`basename $1`

pdfdir=`dirname $2`
pdffilename=`basename $2`
cd ${pdfdir}; PDFDIR=`pwd`; cd -

echo "Transforming XML file '$1' to PDF file '$2' ..."
tmpfile="/tmp/docbook-cug-$$"

prevdir=`pwd`;
cd $xmldir
$CMD_HOME/xml2fo.sh $xmlfilename $tmpfile.fo $3
if [ $? != 0 ]
then
        echo "Failed!"
        exit 1
fi

$CMD_HOME/fo2pdf.sh $tmpfile.fo ${PDFDIR}/${pdffilename}
if [ $? != 0 ]
then
        echo "Failed!"
        exit 1
fi
cd $prevdir
echo "Done!"
exit 0

