#!/bin/sh

if [ $# != 2 ]
then
    echo "Usage: xml2pdf <xml file> <pdf file>"
    exit 2
fi 

echo "Transforming XML file '$1' to PDF file '$2' ..."
tmpfile="/tmp/docbook-$$"
xml2fo.sh $1 $tmpfile.fo
fo2pdf.sh $tmpfile.fo $2
echo "Done!"

