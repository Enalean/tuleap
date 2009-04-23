#!/bin/sh
#
# Copyright (c) Xerox Corporation, Codendi 2007-2008.
# This file is licensed under the GNU General Public License version 2. See the file COPYING.
#
# Purpose:
#    Automatically re-generate online programmer guide
#

FORCE=0
HELP=0
VERBOSE=0

CURRENTDIR=`pwd`
# honor BASEDIR if defined
if [ -z "$BASEDIR" ]; then 
    if [ -z "$CODENDI_LOCAL_INC" ]; then 
        CODENDI_LOCAL_INC=/etc/codendi/conf/local.inc
    fi
    CODENDI_DOCUMENTATION_PREFIX=`/bin/grep '^\$codendi_documentation_prefix' $CODENDI_LOCAL_INC | /bin/sed -e 's/\$codendi_documentation_prefix\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`

    BASEDIR=$CODENDI_DOCUMENTATION_PREFIX
fi
CMDDIR=$BASEDIR/programmer_guide/cmd

# Check arguments
while	((1))	# look for options
do	case	"$1" in
	\-v*)	VERBOSE=1;;
	\-f*)	FORCE=1;;
	\-h*)	HELP=1;;
	\-d*)   shift;; # legacy option
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
    echo "Usage: generate_programmer_doc.sh [-f] [-v] [-h]";
    echo "  -f : force to generate the documentation without checking file dates";
    echo "  -v : verbose";
    echo "  -h : help";
    echo "Note: the '-d' flag has been deprecated and is no longer used";
    exit 2;
fi

cd $BASEDIR/programmer_guide/xml/en_US

if [ ! -e $BASEDIR/programmer_guide/pdf/en_US/Codendi_Programmer_Guide.pdf ]; then
    FORCE=1;
fi

if [ $FORCE != 1 ]
then
    # check if some need some update
    COUNT=`find $BASEDIR/programmer_guide/xml -newer $BASEDIR/programmer_guide/pdf/en_US/Codendi_Programmer_Guide.pdf | wc -l`
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

$CMDDIR/xml2html.sh Codendi_Programmer_Guide.xml ../../html/en_US/ >/tmp/log_xml2html_$$ 2>&1
if [ $? != 0 ]
then
    echo "Codendi documentation generation failed!"
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

$CMDDIR/xml2pdf.sh Codendi_Programmer_Guide.xml $BASEDIR/programmer_guide/pdf/en_US/Codendi_Programmer_Guide_new.pdf >/tmp/log_xml2pdf_$$ 2>&1 
if [ $? != 0 ]
then
    echo "Codendi documentation generation failed!"
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
if [ -f "Codendi_Programmer_Guide.pdf" ]; then
    cp -f Codendi_Programmer_Guide.pdf Codendi_Programmer_Guide_old.pdf > /dev/null
fi
mv Codendi_Programmer_Guide_new.pdf Codendi_Programmer_Guide.pdf
cd "$CURRENTDIR"
exit 0
