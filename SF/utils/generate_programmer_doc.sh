#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# $Id$
#
# Purpose:
#    Automatically re-generate online programmer guide
#

FORCE=0
HELP=0
VERBOSE=0
LOCAL_CVSROOT=":pserver:guerin@cvs.codex.codex.xerox.com:/cvsroot/codex";

# Check arguments
while	((1))	# look for options
do	case	"$1" in
	\-v*)	VERBOSE=1;;
	\-f*)	FORCE=1;;
	\-h*)	HELP=1;;
	\-d*)   shift;
	        LOCAL_CVSROOT=$1;;
	*)	if [ ! -z "$1" ];
	    then
	        echo "Invalid option $1";
	        echo "Use -h flag to see all the valid options";
	        exit 2;
	    fi
	    break;;
	esac
	shift # next argument
done

if [ $HELP == 1 ]
then
    echo "Usage: generate_programmer_doc.sh [-d CVSROOT] [-f] [-v] [-h]";
    echo "  -d CVSROOT : specify the CVSROOT";
    echo "  -f : force to generate the documentation witout checking CVS";
    echo "  -v : verbose";
    echo "  -h : help";
    exit 2;
fi

CURRENTDIR=`pwd`
# honor BASEDIR if defined
if [ -z "$BASEDIR" ]; then 
    BASEDIR=/home/httpd/documentation
fi
CMDDIR=$BASEDIR/programmer_guide/cmd
cd $BASEDIR/programmer_guide/xml/en_US

if [ $FORCE != 1 ]
then
    # check if some need some update
    COUNT=`cvs -q -d$LOCAL_CVSROOT update | wc -l`
    if [ $COUNT == 0 ]
    then
        # No changes in the documentation
        if [ $VERBOSE == 1 ]
        then
            echo "No changes in the documentation"
        fi
        exit 0
    fi
fi

mkdir -p ../../html/en_US

$CMDDIR/xml2html.sh CodeX_Programmer_Guide.xml ../../html/en_US/ >/tmp/log_xml2html_$$ 2>&1
if [ $? != 0 ]
then
    echo "CodeX documentation generation failed!"
	echo "See error log below:"
	echo ""
	cat /tmp/log_xml2html_$$
    exit 1
fi
if [ $VERBOSE == 1 ]
then
    cat /tmp/log_xml2html_$$
fi

# set the path
OLD_PATH=${PATH}
export PATH=${PATH}:${BASEDIR}/programmer_guide/cmd

mkdir -p $BASEDIR/programmer_guide/pdf/en_US

$CMDDIR/xml2pdf.sh CodeX_Programmer_Guide.xml $BASEDIR/programmer_guide/pdf/en_US/CodeX_Programmer_Guide_new.pdf >/tmp/log_xml2pdf_$$ 2>&1 
if [ $? != 0 ]
then
    echo "CodeX documentation generation failed!"
	echo "See error log below:"
    echo ""
    cat /tmp/log_xml2pdf_$$
    export PATH=${OLD_PATH}
    exit 1
fi
if [ $VERBOSE == 1 ]
then
    cat /tmp/log_xml2pdf_$$
fi
export PATH=${OLD_PATH}

cd $BASEDIR/programmer_guide/pdf/en_US
cp -f CodeX_Programmer_Guide.pdf CodeX_Programmer_Guide_old.pdf > /dev/null
mv CodeX_Programmer_Guide_new.pdf CodeX_Programmer_Guide.pdf
cd "$CURRENTDIR"
exit 0
