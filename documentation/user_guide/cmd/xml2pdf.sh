#!/bin/sh

if [ $# != 2 ]
then
    echo "Usage: xml2pdf <xml file> <pdf file>"
    exit 2
fi 

echo "Transforming XML file '$1' to PDF file '$2' ..."
tmpfile="/tmp/docbook-$$"
xml2fo.sh $1 $tmpfile.fo
if [ $? != 0 ]
then
        echo "Failed!"
        exit 1
fi
fo2pdf.sh $tmpfile.fo $2
if [ $? != 0 ]
then
        echo "Failed!"
        exit 1
fi
echo "Done!"
exit 0

