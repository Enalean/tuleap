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
#    Automatically re-generate online documentation
#

progname=$0
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
    cd $scriptdir;
    # we need the complete path to the script directory, in order to call the doc generation
    TOP_SCRIPT_DIR=`pwd`;
    cd - > /dev/null; # redirect to /dev/null to remove display of folder (RHEL4 only)
fi

FORCE=0
HELP=0
VERBOSE=0
EXIST_CHANGE=0

CURRENTDIR=`pwd`
# honor BASEDOCDIR if defined
if [ -z "$BASEDOCDIR" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    CODEX_DOCUMENTATION_PREFIX=`/bin/grep '^\$codex_documentation_prefix' $CODEX_LOCAL_INC | /bin/sed -e 's/\$codex_documentation_prefix\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
    BASEDOCDIR=$CODEX_DOCUMENTATION_PREFIX
fi
CMDDOCDIR=$BASEDOCDIR/cli/cmd

# honor BASESRCDIR if defined
if [ -z "$BASESRCDIR" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    CODEX_DIR=`/bin/grep '^\$codex_dir' $CODEX_LOCAL_INC | /bin/sed -e 's/\$codex_dir\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
    BASESRCDIR=$CODEX_DIR/cli
fi

# honor TMPDIR if defined
if [ -z "$TMPDIR" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    TMP_DIR=`/bin/grep '^\$tmp_dir' $CODEX_LOCAL_INC | /bin/sed -e 's/\$tmp_dir\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
    TMPDIR=$TMP_DIR
fi


# Check arguments
while	((1))	# look for options
do	case	"$1" in
	\-v*)	VERBOSE=1;;
	\-f*)	FORCE=1;;
	\-h*)	HELP=1;;
    \-d*)	DESTDIR=$2; shift;;
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

# honor DESTDIR if defined
if [ -z "$DESTDIR" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    DEST_DIR=`/bin/grep '^\$codex_downloads_dir' $CODEX_LOCAL_INC | /bin/sed -e 's/\$codex_downloads_dir\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
    DESTDIR=$DEST_DIR
fi


if [ $HELP == 1 ]
then
    echo "Usage: generate_cli_package.sh [-f] [-v] [-h]";
    echo "  -f : force to generate the package without checking file dates";
    echo "  -v : verbose";
    echo "  -d : target directory where the archive will be stored (optional, default is $DESTDIR)";
    echo "  -h : help";
    exit 2;
fi

# Check if the package exists. If not, we force the generation
mkdir -p $DESTDIR
cd $DESTDIR
if [ ! -e $DESTDIR/CodeX_CLI.zip ]; then
    FORCE=1;
fi

if [ $FORCE != 1 ]
then
    # check if need some update with CLI source code (and nusoap symbolic link too)
    COUNT=`find $BASESRCDIR -newer $DESTDIR/CodeX_CLI.zip | wc -l`
    if [ $COUNT == 0 ]
    then
        # No changes in the CLI source code
        if [ $VERBOSE == 1 ]
        then
            echo "No changes in the CLI source code";
        fi
    else 
        EXIST_CHANGE=1;
        if [ $VERBOSE == 1 ]
        then
            echo "Changes found in the CLI source code";
        fi
    fi
fi

if [ $FORCE != 1 ]
then
    # check if need some update with CLI documentation
    COUNT=`find $BASEDOCDIR/cli/xml -newer $DESTDIR/CodeX_CLI.zip | wc -l`
    if [ $COUNT == 0 ]
    then
        # No changes in the CLI documentation
        if [ $VERBOSE == 1 ]
        then
            echo "No changes in the CLI documentation";
        fi
    else 
        if [ $VERBOSE == 1 ]
        then
            echo "Changes found in the documentation";
            echo "Generating documentation";
            $TOP_SCRIPT_DIR/generate_cli_doc.sh -v -f
        else
            $TOP_SCRIPT_DIR/generate_cli_doc.sh -f
        fi
        EXIST_CHANGE=1;
    fi
else
    # force the documentation generation
    if [ $VERBOSE == 1 ]
    then
        echo "Generating documentation";
        $TOP_SCRIPT_DIR/generate_cli_doc.sh -v -f
    else
        $TOP_SCRIPT_DIR/generate_cli_doc.sh -f
    fi
fi

# Check here there is no change and if we don't force, then we exit
if [ $EXIST_CHANGE != 1 ]
then
    if [ $FORCE != 1 ]
    then
        # No changes in the archive
        if [ $VERBOSE == 1 ]
        then
            echo "No changes found in the files that compose the archive. Zip generation not necessary. Use -f to enforce the generation."
        fi
        exit 0
    fi
fi

# Use the tar command to make a complex copy :
# we copy the file contained in cli, documentation/cli/pdf, documentation/cli/html into $TMPDIR,
# excluding the files .svn (subversion admin files) and *_old (old pdf documentation)
(cd $CODEX_DIR; tar --exclude '.svn' --exclude "*_old.*" -h -cf - cli/ documentation/cli/pdf documentation/cli/html) | (cd $TMPDIR; tar xf -)
cd $TMPDIR
# We reorganize the files to fit the archive organization we want
mv documentation/cli cli/documentation
# We remove documentation (empty now)
rmdir documentation
# Rename the dir cli before creating the archive
mv cli CodeX_CLI

# zip the CLI package
if [ $VERBOSE == 1 ]
then
    /usr/bin/zip -r CodeX_CLI_new.zip CodeX_CLI
else
    /usr/bin/zip -q -r CodeX_CLI_new.zip CodeX_CLI
fi

# Then permute the new archive with the former one
if [ -f "$DESTDIR/CodeX_CLI.zip" ]; then
    cp -f $DESTDIR/CodeX_CLI.zip $DESTDIR/CodeX_CLI_old.zip > /dev/null
fi
mv CodeX_CLI_new.zip $DESTDIR/CodeX_CLI.zip


if [ $? != 0 ]
then
    cd "$CURRENTDIR"
    echo "CodeX CLI package generation failed!";
    exit 1
fi

# Fix SELinux context (it is set to 'user_u:object_r:tmp_t'
SELINUX_ENABLED=1
if [ ! -e $CHCON ] || [ ! -e "/etc/selinux/config" ] || `grep -i -q '^SELINUX=disabled' /etc/selinux/config`; then
   # SELinux not installed
   SELINUX_ENABLED=0
fi
if [ $SELINUX_ENABLED ]; then
  chcon -h  root:object_r:httpd_sys_content_t $DESTDIR/CodeX_CLI.zip
fi

# Then delete the copied files needed to create the archive
rm -r CodeX_CLI/*
rmdir CodeX_CLI

cd "$CURRENTDIR"
exit 0
