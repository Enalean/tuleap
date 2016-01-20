#!/bin/sh
#
# Copyright (c) Xerox Corporation, Codendi 2007-2008.
# This file is licensed under the GNU General Public License version 2. See the file COPYING.
#
# Purpose:
#    Automatically re-generate online documentation
#

# Put all the languages available in the following string, separated by a single space
ALL_LANGUAGES="en_US fr_FR"

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
CMDDIR=$BASEDIR/cli/cmd
     
# Check arguments
while	true	# look for options
do	case	"$1" in
	\-v*)	VERBOSE=1;;
	\-f*)	FORCE=1;;
	\-h*)	HELP=1;;
	\-l*)	LANGUAGES=$2; shift;;
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

if [ $HELP -eq 1 ]
then
    echo "Usage: generate_doc.sh [-f] [-v] [-h]";
    echo "  -f : force to generate the documentation without checking file dates";
    echo "  -v : verbose";
    echo "  -h : help";
    echo "  -l <lang> : generate only the documentation in the <lang> language (lang= en_US, fr_FR, etc ...)";
    echo "Note: the '-d' flag has been deprecated and is no longer used";
    exit 2;
fi

make -C $BASEDIR/cli

exit 0
