#!/bin/sh

if [ $# != 2 ]
then
    echo "Usage: xml2pdf <xml file> <pdf file>"
    exit 2
fi 

# Determine the script location
progname=$0
scriptdir=`dirname $progname`

xmldir=`dirname $1`
xmlfilename=`basename $1`

echo "Transforming XML file '$1' to PDF file '$2' ..."
tmpfile="/tmp/docbook-$$"

prevdir=`pwd`;
cd $xmldir
$prevdir/$scriptdir/xml2fo.sh $xmlfilename $tmpfile.fo
if [ $? != 0 ]
then
        echo "Failed!"
        exit 1
fi
$prevdir/$scriptdir/fo2pdf.sh $tmpfile.fo $prevdir/$2
if [ $? != 0 ]
then
        echo "Failed!"
        exit 1
fi
echo "Done!"
exit 0

